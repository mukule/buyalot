<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use App\Models\Cart;
use App\Models\CartItem;
use App\Models\ProductVariant;
use App\Services\CartReservationService;
use App\Services\FrontendProductService;
use App\Services\DiscountService;
use Illuminate\Support\Str;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;
use Inertia\Inertia;
use App\Services\ShippingService;
use Illuminate\Support\Facades\Log;


class CartController extends Controller
{
   

public function checkout(
    Request $request,
    CartReservationService $cartService,
    FrontendProductService $productService,
    ShippingService $shippingService
) {
    $cart = $cartService->getCart($request);
    $cart->load('items.productVariant.product.primaryImage');

    $variantIds = $cart->items->pluck('product_variant_id')->filter()->all();
    $priceData = $productService->getPriceForVariants(
        ProductVariant::whereIn('id', $variantIds)->get()
    );

    $presentedItems = $cart->items->map(function ($it) use ($priceData) {
        $variant = $it->productVariant;
        $product = $variant?->product;

        $finalPrice = $priceData[$variant->id]['final_price']
                    ?? ($variant?->selling_price ?? 0);

        // 🔥 Get owner data using your existing method
        $ownerInfo = $variant?->getOwnerInfo() ?? null;

        return [
            'id'        => $it->id,
            'quantity'  => $it->quantity,
            'unit_price'=> $finalPrice,

            'product' => [
                'id'                => $product?->id,
                'name'              => $product?->name,
                'primary_image_url' => $product?->primary_image_url ?? null,
            ],

            'variant' => [
                'id'          => $variant?->id,
                'final_price' => $finalPrice,
            ],

            // 🔥 Owner (same style as productDetails)
            'owner' => $ownerInfo ? [
                'type' => $ownerInfo['type'],
                'name' => $ownerInfo['name'],
            ] : null,
        ];
    });

    $couponCode = $request->get('coupon_code');
    $appliedDiscount = null;
    $couponError = null;

    if ($couponCode) {
        $discountObj = \App\Models\Payment\Discount::query()
            ->active()
            ->byCode($couponCode)
            ->first();

        if ($discountObj) {
            $appliedDiscount = [
                'type' => $discountObj->type,
                'code' => $discountObj->code,
                'name' => $discountObj->name,
            ];
        } else {
            $couponError = 'Coupon code not found or has expired.';
        }
    }

    $customer = auth()->user()?->customer;
    $addresses = collect();
    $defaultAddressId = null;

    if ($customer) {
        $customerAddresses = $customer->addresses()
            ->with('pickupPoint.region')
            ->get();

        $addresses = $customerAddresses->map(function ($addr) use ($shippingService) {
            $regionId = $addr->pickupPoint?->region?->id;

            $shippingOptions = $regionId
                ? $shippingService->getOptionsByRegion($regionId)
                : null;

            return [
                'id'           => $addr->id,
                'first_name'   => $addr->first_name,
                'last_name'    => $addr->last_name,
                'phone'        => $addr->phone,
                'address'      => $addr->address_line_1,
                'region'       => $addr->pickupPoint?->region?->name,
                'pickup_point' => $addr->pickupPoint?->name,
                'is_default'   => $addr->is_default,
                'shipping'     => $shippingOptions,
            ];
        });

        $defaultAddressId = optional($addresses->firstWhere('is_default', true))->id
            ?? optional($addresses->first())->id;
    }

    $totals = $cart->calculateTotals();

    return Inertia::render('Frontend/Checkout/Summary', [
        'cart' => [
            'items'          => $presentedItems,
            'counts'         => [
                'unique_items' => $totals['unique_items'],
                'total_qty'    => $totals['total_quantity'],
            ],
            'totals'         => [
                'subtotal' => $totals['grand_total'],
            ],
            'applied_coupon' => $appliedDiscount,
            'coupon_code'    => $couponCode,
            'coupon_error'   => $couponError,
        ],

        'customer_addresses'  => $addresses,
        'shipping_address_id' => $defaultAddressId,
        'billing_address_id'  => $defaultAddressId,
    ]);
}


   
    public function index(Request $request, CartReservationService $cartService)
{
    $cart = $cartService->getCart($request);

    
    $cart->load('items.productVariant.product.primaryImage');

    
    $totalAmount = 0;     
    $totalDiscount = 0;  
    $totalPayable = 0;    

    foreach ($cart->items as $item) {
        $totalAmount += $item->marked_price * $item->quantity;
        $totalDiscount += $item->discount_amount * $item->quantity;
        $totalPayable += $item->unit_price * $item->quantity;
    }

    $summary = [
        'total_amount'   => round($totalAmount, 2),
        'total_discount' => round($totalDiscount, 2),
        'total_payable'  => round($totalPayable, 2),
    ];

    return Inertia::render('Frontend/Cart', [
        'cart'    => $cart,
        'summary' => $summary,
    ]);
}



public function store(Request $request, CartReservationService $cartService)
{
    $cart = $cartService->getCart($request);

    $request->validate([
        'product_variant_id' => 'required|integer|exists:product_variants,id',
        'quantity'           => 'required|integer|min:0',
    ]);

    $variantId = (int) $request->input('product_variant_id');
    $quantity  = (int) $request->input('quantity');

    $variant = ProductVariant::with('product')->findOrFail($variantId);
    $cartItem = $cart->items()->where('product_variant_id', $variantId)->first();

    $reservationService = app(\App\Services\CartReservationService::class);

    // Handle removing from cart (quantity = 0)
    if ($quantity === 0) {
        if ($cartItem) {
            $cartItem->delete();
        }
        $reservationService->release($cart->id, $variantId);
        return redirect()->back()->with('success', "{$variant->product->name} removed from cart!");
    }

    // Check availability considering other reservations
    $available = $reservationService->availableForCart($variantId, $cart->id);
    $ownReservedQty = (int) \App\Models\CartReservation::query()
        ->active()
        ->where('cart_id', $cart->id)
        ->where('product_variant_id', $variantId)
        ->value('quantity');

    $effectiveAvailable = $available + $ownReservedQty;

    if ($quantity > $effectiveAvailable) {
        return redirect()->back()->with('error', "Only {$effectiveAvailable} unit(s) are currently available due to other customers holding this item. Please try again later.");
    }

    // Get price info from DiscountService
    $frontendProductService = app(\App\Services\FrontendProductService::class);
    $priceData = $frontendProductService->getPriceForVariants(collect([$variant]));
    $priceInfo = $priceData[$variant->id] ?? null;

    // Log price info for debugging
    Log::info('Price info for variant', [
        'variant_id' => $variantId,
        'priceInfo'  => $priceInfo,
    ]);

    $markedPrice = $priceInfo['marked_price'] ?? 0;
    $unitPrice = $priceInfo['final_price'] ?? $markedPrice;
    $discountAmount = $priceInfo['total_discount'] ?? 0;

    
    $discountPercentage = $priceInfo['discount_percentage'] ?? 0;

    $totalPrice = $unitPrice * $quantity;

    $cartItemData = [
        'quantity'            => $quantity,
        'marked_price'        => $markedPrice,
        'unit_price'          => $unitPrice,
        'discount_amount'     => $discountAmount,
        'discount_percentage' => $discountPercentage, 
        'total_price'         => $totalPrice,
        'product_id'          => $variant->product_id,
        'seller_id'           => $variant->product->seller_id ?? null,
        'product_variant_id'  => $variantId,
    ];

    if ($cartItem) {
        $cartItem->update($cartItemData);
    } else {
        $cart->items()->create($cartItemData);
    }

    
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
