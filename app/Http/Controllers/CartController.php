<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use App\Models\Cart;
use App\Models\CartItem;
use App\Models\ProductVariant;
use App\Services\CartReservationService;
use Illuminate\Support\Str;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;
use Inertia\Inertia;
use App\Services\ShippingService;


class CartController extends Controller
{
    public function estimateShipping(Request $request)
    {
        $data = $request->validate([
            'items' => 'required|array|min:1',
            'items.*.product_variant_id' => 'required|integer|exists:product_variants,id',
            'items.*.quantity' => 'required|integer|min:1',
            'distance_km' => 'required|numeric|min:0',
        ]);

        /** @var ShippingService $shippingService */
        $shippingService = app(ShippingService::class);
        $estimate = $shippingService->estimate($data['items'], (float)$data['distance_km']);

        return response()->json([
            'success' => true,
            'shipping' => $estimate,
        ]);
    }

    public function checkout(Request $request, CartReservationService $cartService)
    {
       // $cart = $this->getCart($request);
        $cart = $cartService->getCart($request);
        $cart->load('items.productVariant.product.primaryImage');

        // Compute totals similar to order calculation (per-item discounts only + optional coupon)
        $items = $cart->items;
        $subtotal = 0.0;
        $perItemDiscountTotal = 0.0;
        $presentedItems = [];

        foreach ($items as $it) {
            $variant = $it->productVariant;
            $product = $variant?->product;
            $qty = (int) $it->quantity;
            $unitPrice = (float) ($variant?->selling_price ?? $it->unit_price ?? 0);
            $perUnitDiscount = max(0, (float)($variant?->regular_price ?? 0) - (float)($variant?->selling_price ?? 0));

            $lineSubtotal = $unitPrice * $qty;
            $lineDiscount = $perUnitDiscount * $qty;

            $subtotal += $lineSubtotal;
            $perItemDiscountTotal += $lineDiscount;

            $presentedItems[] = [
                'id' => $it->id,
                'quantity' => $qty,
                'unit_price' => $unitPrice,
                'line_subtotal' => $lineSubtotal,
                'line_discount' => $lineDiscount,
                'product' => [
                    'id' => $product?->id,
                    'name' => $product?->name,
                    'primary_image_url' => $product?->primary_image_url ?? null,
                ],
                'variant' => [
                    'id' => $variant?->id,
                    'sku' => $variant?->sku,
                    'regular_price' => $variant?->regular_price,
                    'selling_price' => $variant?->selling_price,
                ],
            ];
        }

        // Coupon (optional - via query or form state)
        $couponCode = $request->get('coupon_code');
        $couponDiscount = 0.0;
        $appliedDiscount = null;
        $couponError = null;
        /** @var \App\Models\Payment\Discount|null $discountObj */
        $discountObj = null;
        if ($couponCode) {
            $discountObj = \App\Models\Payment\Discount::query()->active()->byCode($couponCode)->first();
            if ($discountObj) {
                // Build simple items payload
                $itemsForDiscount = array_map(function ($ci) {
                    return [
                        'product_id' => $ci['product']['id'] ?? null,
                        'product_variant_id' => $ci['variant']['id'] ?? null,
                        'quantity' => $ci['quantity'],
                        'unit_price' => $ci['unit_price'],
                    ];
                }, $presentedItems);
                $couponDiscount = (float) $discountObj->calculateDiscount($subtotal, $itemsForDiscount, [
                    'order_subtotal' => $subtotal,
                ]);
                if ($couponDiscount > 0) {
                    $appliedDiscount = [
                        'type' => $discountObj->type,
                        'code' => $discountObj->code,
                        'name' => $discountObj->name,
                        'amount' => round($couponDiscount, 2),
                    ];
                }
            } else {
                // Not found among active discounts -> report as not found or expired
                $couponError = 'Coupon code not found or has expired.';
            }
        }

        // Optional distance-based shipping estimate
        $shippingAmount = 0.0;
        $shippingEstimate = null;
        if ($request->filled('distance_km')) {
            /** @var ShippingService $shippingService */
            $shippingService = app(ShippingService::class);
            $itemsForShipping = array_map(function ($ci) {
                return [
                    'product_variant_id' => $ci['variant']['id'] ?? null,
                    'quantity' => $ci['quantity'],
                    'unit_price' => $ci['unit_price'],
                ];
            }, $presentedItems);
            $shippingEstimate = $shippingService->estimate($itemsForShipping, (float)$request->get('distance_km'));
            $shippingAmount = (float)$shippingEstimate['amount'];
        }

        // If coupon targets shipping (free shipping), apply it now that we know shipping cost
        if ($discountObj) {
            $conditions = $discountObj->conditions ?? [];
            $appliesTo = $conditions['applies_to'] ?? null;
            if ($discountObj->type === 'free_shipping' || $appliesTo === 'shipping') {
                $shipSave = $shippingAmount;
                if ($shipSave > 0) {
                    $shippingAmount = 0.0;
                    $couponDiscount += $shipSave;
                    if ($appliedDiscount) {
                        $appliedDiscount['amount'] = round(($appliedDiscount['amount'] ?? 0) + $shipSave, 2);
                    } else {
                        $appliedDiscount = [
                            'type' => $discountObj->type,
                            'code' => $discountObj->code,
                            'name' => $discountObj->name,
                            'amount' => round($shipSave, 2),
                        ];
                    }
                }
            }
        }

        $discountTotal = round($perItemDiscountTotal + $couponDiscount, 2);
        $taxAmount = 0.0; // if needed, compute here
        $grandTotal = round($subtotal + $taxAmount + $shippingAmount - $discountTotal, 2);

        return Inertia::render('Frontend/Checkout/Summary', [
            'cart' => [
                'items' => $presentedItems,
                'counts' => [
                    'unique_items' => count($presentedItems),
                    'total_qty' => array_sum(array_map(fn($i)=>$i['quantity'], $presentedItems)),
                ],
                'totals' => [
                    'subtotal' => $subtotal,
                    'per_item_discount' => $perItemDiscountTotal,
                    'coupon_discount' => $couponDiscount,
                    'discount_total' => $discountTotal,
                    'shipping' => $shippingAmount,
                    'tax' => $taxAmount,
                    'grand_total' => $grandTotal,
                ],
                'applied_coupon' => $appliedDiscount,
                'coupon_code' => $couponCode,
                'coupon_error' => $couponError,
            ],
            'shipping_estimate' => $shippingEstimate,
            'distance_km' => $request->get('distance_km')
        ]);
    }


    public function index(Request $request, CartReservationService $cartService)
{
   // $cart = $this->getCart($request);
    $cart = $cartService->getCart($request);


    // Eager load productVariant and product with primaryImage
    $cart->load('items.productVariant.product.primaryImage');

    // Each cart item will now automatically have a full URL available
    // via $item->productVariant->product->primaryImageUrl
    // no need to manually map asset paths here

    return Inertia::render('Frontend/Cart', [
        'cart' => $cart,
    ]);
}


    public function store(Request $request, CartReservationService $cartService)
    {
        $cart = $cartService->getCart($request);

        $request->validate([
            'product_variant_id' => 'required|integer|exists:product_variants,id',
            'quantity'           => 'required|integer|min:0', // allow 0 to remove
        ]);

        $variantId = $request->input('product_variant_id');
        $quantity  = (int) $request->input('quantity');

        $variant   = ProductVariant::with('product')->findOrFail($variantId);
        //$cart      = $this->getCart($request);

        $cartItem = $cart->items()->where('product_variant_id', $variantId)->first();

        /** @var \App\Services\CartReservationService $reservationService */
        $reservationService = app(\App\Services\CartReservationService::class);

        if ($quantity === 0) {
            if ($cartItem) {
                $cartItem->delete();
            }
            // release any existing reservation for this variant
            $reservationService->release($cart->id, $variantId);
            return redirect()->back()->with('success', "{$variant->product->name} removed from cart!");
        }

        // Enforce availability considering reservations by other carts (holds)
        $available = $reservationService->availableForCart($variantId, $cart->id);
        // If the cart already has a reservation, include it in the allowable amount
        $ownReservedQty = (int) \App\Models\CartReservation::query()
            ->active()
            ->where('cart_id', $cart->id)
            ->where('product_variant_id', $variantId)
            ->value('quantity');
        $effectiveAvailable = $available + $ownReservedQty; // allow increasing within own hold

        if ($quantity > $effectiveAvailable) {
            return redirect()->back()->with('error', "Only {$effectiveAvailable} unit(s) are currently available due to other customers holding this item. Please try again later.");
        }

        $unitPrice      = $variant->selling_price; // authoritative price
        $discountAmount = $variant->regular_price - $variant->selling_price;
        $totalPrice     = ($unitPrice - $discountAmount) * $quantity;

        if ($cartItem) {
            $cartItem->update([
                'quantity'       => $quantity,
                'unit_price'     => $unitPrice,
                'discount_amount'=> $discountAmount,
                'total_price'    => $totalPrice,
            ]);
        } else {
            $cart->items()->create([
                'product_id'         => $variant->product_id,
                'seller_id'          => $variant->product->seller_id ?? null,
                'product_variant_id' => $variantId,
                'quantity'           => $quantity,
                'unit_price'         => $unitPrice,
                'discount_amount'    => $discountAmount,
                'total_price'        => $totalPrice,
            ]);
        }

        // Reserve the requested quantity for this cart for ~20 minutes
        $reservationService->reserve($cart->id, $variantId, $quantity, 20);

        return redirect()->back()->with('success', "{$variant->product->name} added to cart");
    }

    public function decrease(Request $request, CartItem $item, CartReservationService $cartService)
    {
        $cart = $cartService->getCart($request);

        if ($item->cart_id !== $cart->id) {
            abort(403, 'Unauthorized action.');
        }

        /** @var \App\Services\CartReservationService $reservationService */
        $reservationService = app(\App\Services\CartReservationService::class);

        if ($item->quantity > 1) {
            $item->decrement('quantity');
            $item->update([
                'total_price' => ($item->unit_price - $item->discount_amount) * $item->quantity,
            ]);
            // update reservation to new quantity
            $reservationService->reserve($cart->id, $item->product_variant_id, $item->quantity, 20);
        } else {
            // removing item -> release reservation
            $reservationService->release($cart->id, $item->product_variant_id);
            $item->delete();
        }

        return redirect()->back()->with('success', 'Cart updated successfully!');
    }

    public function update(Request $request, CartItem $item, CartReservationService $cartService)
    {
        $request->validate([
            'quantity' => 'required|integer|min:0'
        ]);

        $cart = $cartService->getCart($request);

        if ($item->cart_id !== $cart->id) {
            abort(403, 'Unauthorized action.');
        }

        /** @var \App\Services\CartReservationService $reservationService */
        $reservationService = app(\App\Services\CartReservationService::class);

        $quantity = (int) $request->input('quantity');

        if ($quantity === 0) {
            $reservationService->release($cart->id, $item->product_variant_id);
            $item->delete();
        } else {
            // Check availability considering other reservations
            $available = $reservationService->availableForCart($item->product_variant_id, $cart->id);
            $ownReservedQty = (int) \App\Models\CartReservation::query()
                ->active()
                ->where('cart_id', $cart->id)
                ->where('product_variant_id', $item->product_variant_id)
                ->value('quantity');
            $effectiveAvailable = $available + $ownReservedQty;

            if ($quantity > $effectiveAvailable) {
                return redirect()->back()->with('error', "Only {$effectiveAvailable} unit(s) are currently available due to other customers holding this item. Please try again later.");
            }

            $item->update([
                'quantity'    => $quantity,
                'total_price' => ($item->unit_price - $item->discount_amount) * $quantity,
            ]);
            $reservationService->reserve($cart->id, $item->product_variant_id, $quantity, 20);
        }

        return redirect()->back()->with('success', 'Cart updated successfully!');
    }

    public function destroy(Request $request, CartItem $item, CartReservationService $cartService)
    {
        $cart = $cartService->getCart($request);

        if ($item->cart_id !== $cart->id) {
            abort(403, 'Unauthorized action.');
        }

        $item->delete();

        return redirect()->back()->with('success', 'Item removed from cart.');
    }

    public function clear(Request $request, CartReservationService $cartService)
    {
        $cart = $this->getCart($request);
        $cart->items()->delete();

        return redirect()->back()->with('success', 'Cart cleared.');
    }

    // protected function getCart(Request $request): Cart
    // {
    //     if (Auth::check()) {
    //         $cart = Cart::firstOrCreate(['user_id' => Auth::id()]);

    //         if ($guestToken = $request->cookie('cart_token')) {
    //             $guestCart = Cart::where('cart_token', $guestToken)->first();

    //             if ($guestCart && $guestCart->id !== $cart->id) {
    //                 DB::transaction(function () use ($cart, $guestCart) {
    //                     foreach ($guestCart->items as $item) {
    //                         $cart->items()->updateOrCreate(
    //                             ['product_variant_id' => $item->product_variant_id],
    //                             [
    //                                 'quantity'        => DB::raw('quantity + ' . $item->quantity),
    //                                 'unit_price'      => $item->unit_price,
    //                                 'discount_amount' => $item->discount_amount,
    //                                 'total_price'     => ($item->unit_price - $item->discount_amount) * $item->quantity,
    //                                 'product_id'      => $item->product_id,
    //                                 'seller_id'       => $item->seller_id,
    //                             ]
    //                         );
    //                     }

    //                     $guestCart->items()->delete();
    //                     $guestCart->delete();
    //                 });
    //             }
    //         }
    //     } else {
    //         $token = $request->cookie('cart_token') ?? Str::uuid()->toString();
    //         $cart  = Cart::firstOrCreate(['cart_token' => $token]);

    //         if (!$request->cookie('cart_token')) {
    //             cookie()->queue('cart_token', $token, 60 * 24 * 30); 
    //         }
    //     }

    //     return $cart;
    // }

    public function payment(Request $request, CartReservationService $cartService)
    {
        $cart = $cartService->getCart($request);
        $cart->load('items.productVariant.product.primaryImage');

        $items = $cart->items;
        $subtotal = 0.0;
        $perItemDiscountTotal = 0.0;
        $presentedItems = [];

        foreach ($items as $it) {
            $variant = $it->productVariant;
            $product = $variant?->product;
            $qty = (int) $it->quantity;
            $unitPrice = (float) ($variant?->selling_price ?? $it->unit_price ?? 0);
            $perUnitDiscount = max(0, (float)($variant?->regular_price ?? 0) - (float)($variant?->selling_price ?? 0));

            $lineSubtotal = $unitPrice * $qty;
            $lineDiscount = $perUnitDiscount * $qty;

            $subtotal += $lineSubtotal;
            $perItemDiscountTotal += $lineDiscount;

            $presentedItems[] = [
                'id' => $it->id,
                'quantity' => $qty,
                'unit_price' => $unitPrice,
                'line_subtotal' => $lineSubtotal,
                'line_discount' => $lineDiscount,
                'product' => [
                    'id' => $product?->id,
                    'name' => $product?->name,
                    'primary_image_url' => $product?->primary_image_url ?? null,
                ],
                'variant' => [
                    'id' => $variant?->id,
                    'sku' => $variant?->sku,
                    'regular_price' => $variant?->regular_price,
                    'selling_price' => $variant?->selling_price,
                ],
            ];
        }

        // Coupon (optional - via query or state)
        $couponCode = $request->get('coupon_code');
        $couponDiscount = 0.0;
        $appliedDiscount = null;
        $discountObj = null;
        if ($couponCode) {
            $discountObj = \App\Models\Payment\Discount::query()->active()->byCode($couponCode)->first();
            if ($discountObj) {
                $itemsForDiscount = array_map(function ($ci) {
                    return [
                        'product_id' => $ci['product']['id'] ?? null,
                        'product_variant_id' => $ci['variant']['id'] ?? null,
                        'quantity' => $ci['quantity'],
                        'unit_price' => $ci['unit_price'],
                    ];
                }, $presentedItems);
                $couponDiscount = (float) $discountObj->calculateDiscount($subtotal, $itemsForDiscount, [
                    'order_subtotal' => $subtotal,
                ]);
                if ($couponDiscount > 0) {
                    $appliedDiscount = [
                        'type' => $discountObj->type,
                        'code' => $discountObj->code,
                        'name' => $discountObj->name,
                        'amount' => round($couponDiscount, 2),
                    ];
                }
            }
        }

        // Shipping estimate (optional)
        $shippingAmount = 0.0;
        $shippingEstimate = null;
        if ($request->filled('distance_km')) {
            /** @var \App\Services\ShippingService $shippingService */
            $shippingService = app(\App\Services\ShippingService::class);
            $itemsForShipping = array_map(function ($ci) {
                return [
                    'product_variant_id' => $ci['variant']['id'] ?? null,
                    'quantity' => $ci['quantity'],
                    'unit_price' => $ci['unit_price'],
                ];
            }, $presentedItems);
            $shippingEstimate = $shippingService->estimate($itemsForShipping, (float)$request->get('distance_km'));
            $shippingAmount = (float)$shippingEstimate['amount'];
        }

        if ($discountObj) {
            $conditions = $discountObj->conditions ?? [];
            $appliesTo = $conditions['applies_to'] ?? null;
            if ($discountObj->type === 'free_shipping' || $appliesTo === 'shipping') {
                $shipSave = $shippingAmount;
                if ($shipSave > 0) {
                    $shippingAmount = 0.0;
                    $couponDiscount += $shipSave;
                    if ($appliedDiscount) {
                        $appliedDiscount['amount'] = round(($appliedDiscount['amount'] ?? 0) + $shipSave, 2);
                    } else {
                        $appliedDiscount = [
                            'type' => $discountObj->type,
                            'code' => $discountObj->code,
                            'name' => $discountObj->name,
                            'amount' => round($shipSave, 2),
                        ];
                    }
                }
            }
        }

        $discountTotal = round($perItemDiscountTotal + $couponDiscount, 2);
        $taxAmount = 0.0;
        $grandTotal = round($subtotal + $taxAmount + $shippingAmount - $discountTotal, 2);

        $defaultPhone = optional(auth()->user())->phone
            ?? optional(auth()->user()?->customer)->phone
            ?? '';

        $customer = auth()->user()?->customer;
        $addresses = [];
        $defaultShippingId = null;
        if ($customer) {
            // Fetch all columns; model exposes state, country, and coordinates via accessors/appends
            $addresses = $customer->addresses()->orderByDesc('is_default')->get();
            $defaultShippingId = optional($customer->defaultAddress)->id;
        }

        return Inertia::render('Frontend/Checkout/Payment', [
            'cart' => [
                'items' => $presentedItems,
                'counts' => [
                    'unique_items' => count($presentedItems),
                    'total_qty' => array_sum(array_map(fn($i)=>$i['quantity'], $presentedItems)),
                ],
                'totals' => [
                    'subtotal' => $subtotal,
                    'per_item_discount' => $perItemDiscountTotal,
                    'coupon_discount' => $couponDiscount,
                    'discount_total' => $discountTotal,
                    'shipping' => $shippingAmount,
                    'tax' => $taxAmount,
                    'grand_total' => $grandTotal,
                ],
                'applied_coupon' => $appliedDiscount,
                'coupon_code' => $couponCode,
            ],
            'shipping_estimate' => $shippingEstimate,
            'distance_km' => $request->get('distance_km'),
            'default_phone' => $defaultPhone,
                        'customer_addresses' => $addresses,
                        'shipping_address_id' => $defaultShippingId,
                        'billing_address_id' => $defaultShippingId,
        ]);
    }

}
