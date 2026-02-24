<?php

namespace App\Http\Controllers;

use App\Models\Cart\CartItem;
use App\Models\Products\ProductVariant;
use App\Services\CartReservationService;
use App\Services\FrontendProductService;
use App\Services\ShippingService;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Storage;
use Inertia\Inertia;


class CartController extends Controller
{


    public function index(
    Request $request,
    CartReservationService $cartService,
    FrontendProductService $productService
) {
    $cart = $cartService->getCart($request);

    // Load primary image for products
    $cart->load('items.productVariant.product.primaryImage');

    // Totals
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

    // Related products: pick top item by quantity
     $topVariant = $cart->items->sortByDesc('quantity')->first()?->productVariant;
    $relatedProducts = $topVariant ? $productService->getRelatedProducts($topVariant) : collect();

    return Inertia::render('Frontend/Cart', [
        'cart'            => $cart,
        'summary'         => $summary,
        'relatedProducts' => $relatedProducts,
    ]);
}





    public function checkout(
    Request $request,
    CartReservationService $cartService,
    FrontendProductService $productService,
    ShippingService $shippingService
) {
    // Load cart with product info
    $cart = $cartService->getCart($request);
    $cart->load('items.productVariant.product.primaryImage', 'items.productVariant.product.images');

    foreach ($cart->items as $item) {
        $available = $cartService->availableForCart($item->product_variant_id, $cart->id);
        if ($item->quantity > $available) {
            return redirect()->route('cart.index')->with('error', "Sorry, {$item->productVariant->product->name} is no longer available in the requested quantity.");
        }
    }

    $variantIds = $cart->items->pluck('product_variant_id')->filter()->all();
    $priceData = $productService->getPriceForVariants(
        ProductVariant::whereIn('id', $variantIds)->get()
    );

    // Map cart items
    $presentedItems = $cart->items->map(function ($it) use ($priceData) {
        $variant = $it->productVariant;
        $product = $variant?->product;

        $finalPrice = $priceData[$variant->id]['final_price'] ?? ($variant?->marked_price ?? 0);
        $ownerInfo = $variant?->getOwnerInfo();

        return [
            'id'          => $it->id,
            'quantity'    => $it->quantity,
            'unit_price'  => $finalPrice,
            'total_price' => $finalPrice * $it->quantity,
            'product'     => [
                'id'                => $product?->id,
                'name'              => $product?->name ?? '',
                'slug'              => $product?->slug ?? '',
                'primary_image_url' => $product?->primary_image_url
                    ?? ($product?->images->first()?->image_path
                        ? Storage::disk('s3')->url($product->images->first()->image_path)
                        : '/fallback-image.png'),
            ],
            'variant' => [
                'id'          => $variant?->id,
                'final_price' => $finalPrice,
            ],
            'owner' => $ownerInfo ? [
                'type' => $ownerInfo['type'],
                'name' => $ownerInfo['name'],
            ] : null,
        ];
    });

    // Cart totals (without shipping)
    $totals = $cart->calculateTotals();
    $cartTotal = $totals['grand_total'] ?? 0;

    // Coupon handling
    $couponCode = $request->get('coupon_code');
    $couponAmount = 0;
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

            $couponAmount = (int) round($discountObj->type === 'percent'
                ? $cartTotal * ($discountObj->value / 100)
                : min($discountObj->value, $cartTotal));
        } else {
            $couponError = 'Coupon code not found or has expired.';
        }
    }

    // Delivery-first flow: customer selects type (pickup/home) on Summary page
    // Pass regions with pickup points for selection; selected_shipping comes from frontend (query params when proceeding)
    $customer = auth()->user()?->customer;
    $shippingCost = 0;
    $selectedShipping = null;
    $defaultAddress = null;

    // Regions with pickup points (for delivery type = pickup)
    $regionsWithPickup = $shippingService->getRegionsWithPickup()->map(function ($region) use ($shippingService) {
        $warehouses = \App\Models\Warehouse\Warehouse::withoutGlobalScopes()
            ->where(function ($q) use ($region) {
                $q->where('region_id', $region->id)
                    ->orWhereHas('regions', fn ($r) => $r->where('regions.id', $region->id));
            })
            ->whereIn('type', ['pickup_point', 'dispatch_center', 'general'])
            ->where('active', true)
            ->get(['id', 'name', 'address', 'location', 'latitude', 'longitude']);

        return [
            'id' => $region->id,
            'name' => $region->name,
            'pickup_points' => $warehouses->map(fn ($w) => [
                'id' => $w->id,
                'name' => $w->name,
                'address' => $w->address,
                'location' => $w->location,
                'latitude' => $w->latitude ? (float) $w->latitude : null,
                'longitude' => $w->longitude ? (float) $w->longitude : null,
            ])->values()->toArray(),
            'shipping_options' => $shippingService->getOptionsByRegion($region->id),
        ];
    });

    if ($customer) {
        $defaultAddress = $customer->addresses()
            ->with(['pickupPoint.region', 'region', 'pickupWarehouse' => fn ($q) => $q->withoutGlobalScopes()->with('region')])
            ->orderByDesc('is_default')
            ->first();
    }

    $defaultAddressId = $defaultAddress?->id ?? null;

    // Final totals (whole numbers; shipping starts at 0; frontend updates via delivery selection)
    $grandTotal = (int) round($cartTotal + $shippingCost - $couponAmount);

    // Related products
    $topVariant = $cart->items->sortByDesc('quantity')->first()?->productVariant;
    $relatedProducts = $topVariant ? $productService->getRelatedProducts($topVariant) : collect();

    return Inertia::render('Frontend/Checkout/Summary', [
        'cart' => [
            'items' => $presentedItems,
            'counts' => [
                'unique_items' => $totals['unique_items'] ?? 0,
                'total_qty'    => $totals['total_quantity'] ?? 0,
            ],
            'totals' => [
                'subtotal'        => $cartTotal,
                'shipping'        => $shippingCost,
                'coupon_discount' => $couponAmount,
                'grand_total'     => $grandTotal,
            ],
            'applied_coupon' => $appliedDiscount,
            'coupon_code'    => $couponCode,
            'coupon_error'   => $couponError,
        ],

        'regions_with_pickup' => $regionsWithPickup,
        'home_delivery_config' => $shippingService->getHomeDeliveryConfig(),
        'selected_shipping'   => $selectedShipping,
        'customer_addresses'  => $defaultAddress ? collect([$defaultAddress]) : collect(),
        'shipping_address_id' => $defaultAddressId,
        'billing_address_id'  => $defaultAddressId,
        'relatedProducts'     => $relatedProducts,
        'googleMapsApiKey'    => config('services.google.maps_api_key', ''),
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
        return redirect()->back()->with('success', "{$variant->product->name} removed from cart!");
    }

    // Check availability considering other reservations
    $available = $reservationService->availableForCart($variantId, $cart->id);

    // With immediate stock deduction on reservation, $available is already the net stock.
    // However, during "add to cart" phase, we aren't reserving yet, so $available is correct.
    // In current implementation, reservations ONLY happen at payment initialization.

    if ($quantity > $available) {
        return redirect()->back()->with('error', "Only {$available} unit(s) are currently available. Please try again later.");
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

    info("Marked price: {$markedPrice}, unit price: {$unitPrice}, discount amount: {$discountAmount}");


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
        } else {
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
            $item->delete();
        } else {
            // Check availability considering other reservations
            $available = $reservationService->availableForCart($item->product_variant_id, $cart->id);

            if ($quantity > $available) {
                return redirect()->back()->with('error', "Only {$available} unit(s) are currently available. Please try again later.");
            }

            $item->update([
                'quantity'    => $quantity,
                'total_price' => ($item->unit_price - $item->discount_amount) * $quantity,
            ]);
        }

        return redirect()->back()->with('success', 'Cart updated successfully!');
    }

    public function estimateShipping(Request $request)
    {
        return response()->json(['message' => 'Use calculate-home-delivery for home delivery or select pickup for shipping cost.']);
    }

    public function calculateHomeDelivery(Request $request, ShippingService $shippingService)
    {
        $request->validate([
            'lat' => 'required|numeric|between:-90,90',
            'lng' => 'required|numeric|between:-180,180',
        ]);

        $lat = (float) $request->input('lat');
        $lng = (float) $request->input('lng');

        $region = $shippingService->reverseGeocodeAndMatchRegion($lat, $lng);
        if (! $region) {
            return response()->json([
                'success' => false,
                'message' => "We don't do home delivery for the selected region.",
            ], 422);
        }

        $center = $shippingService->getMainWarehouseCenter();
        if (! $center) {
            $center = ['lat' => -1.286389, 'lng' => 36.817223];
        }

        $distanceKm = $shippingService->distanceKm($lat, $lng, $center['lat'], $center['lng']);
        $shippingCost = $shippingService->calculateHomeDeliveryCost($distanceKm);
        $options = $shippingService->getOptionsByRegion($region->id);
        $days = $options['door']['days'] ?? 2;

        return response()->json([
            'success' => true,
            'region' => [
                'id' => $region->id,
                'name' => $region->name,
            ],
            'distance_km' => $distanceKm,
            'shipping_cost' => $shippingCost,
            'days' => $days,
            'home_delivery_config' => $shippingService->getHomeDeliveryConfig(),
        ]);
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



    public function payment(
    Request $request,
    CartReservationService $cartService,
    FrontendProductService $productService,
    ShippingService $shippingService
) {
    // Load cart with product info
    $cart = $cartService->getCart($request);
    $cart->load('items.productVariant.product.primaryImage', 'items.productVariant.product.images');

    foreach ($cart->items as $item) {
        $available = $cartService->availableForCart($item->product_variant_id, $cart->id);
        if ($item->quantity > $available) {
            return redirect()->route('cart.index')->with('error', "Sorry, some items in your cart became unavailable.");
        }
    }

    // Map cart items
    $presentedItems = $cart->items->map(function ($it) {
        $variant = $it->productVariant;
        $product = $variant?->product;

        $unitPrice = $variant?->marked_price ?? 0;
        $ownerInfo = $variant?->getOwnerInfo();

        return [
            'id'          => $it->id,
            'quantity'    => $it->quantity,
            'unit_price'  => $unitPrice,
            'total_price' => $unitPrice * $it->quantity,
            'product'     => [
                'id'                => $product?->id,
                'name'              => $product?->name ?? '',
                'slug'              => $product?->slug ?? '',
                'primary_image_url' => $product?->primary_image_url
                    ?? ($product?->images->first()?->image_path
                        ? Storage::disk('s3')->url($product->images->first()->image_path)
                        : '/fallback-image.png'),
            ],
            'variant' => [
                'id'          => $variant?->id,
                'final_price' => $unitPrice,
            ],
            'owner' => $ownerInfo ? [
                'type' => $ownerInfo['type'],
                'name' => $ownerInfo['name'],
            ] : null,
        ];
    });

    // Cart totals (without shipping)
    $totals = $cart->calculateTotals();
    $cartTotal = $totals['grand_total'] ?? 0;

    // Coupon handling
    $couponCode = $request->get('coupon_code');
    $couponAmount = 0;
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

            $couponAmount = (int) round($discountObj->type === 'percent'
                ? $cartTotal * ($discountObj->value / 100)
                : min($discountObj->value, $cartTotal));
        } else {
            $couponError = 'Coupon code not found or has expired.';
        }
    }

    // Customer & address: prefer query params from Summary (delivery selection), else default address
    $customer = auth()->user()?->customer;
    $defaultAddress = null;
    $selectedShipping = null;
    $shippingCost = 0;

    $addressIdFromSummary = $request->query('address_id');
    $shippingCostFromSummary = $request->query('shipping_cost');
    $deliveryMethodFromSummary = $request->query('delivery_method');
    $regionNameFromSummary = $request->query('region_name');
    $pickupPointFromSummary = $request->query('pickup_point');
    $daysFromSummary = $request->query('shipping_days');

    if ($customer && $addressIdFromSummary && $shippingCostFromSummary !== null && $deliveryMethodFromSummary) {
        $address = $customer->addresses()
            ->with(['pickupPoint.region', 'region', 'pickupWarehouse' => fn ($q) => $q->withoutGlobalScopes()->with('region')])
            ->find($addressIdFromSummary);
        if ($address) {
            $defaultAddress = $address;
            $shippingCost = max(250, (int) round((float) $shippingCostFromSummary));
            $selectedShipping = [
                'method'       => $deliveryMethodFromSummary === 'door' ? 'door' : 'pickup',
                'cost'         => $shippingCost,
                'days'         => (int) ($daysFromSummary ?? 2),
                'region'       => $regionNameFromSummary ?? $address->pickupWarehouse?->region?->name ?? $address->region?->name ?? '',
                'pickup_point' => $pickupPointFromSummary ?? $address->pickupWarehouse?->name ?? $address->pickupPoint?->name ?? null,
            ];
        }
    }

    if (! $selectedShipping && $customer) {
        $defaultAddress = $customer->addresses()
            ->with(['pickupPoint.region', 'region', 'pickupWarehouse' => fn ($q) => $q->withoutGlobalScopes()->with('region')])
            ->orderByDesc('is_default')
            ->first();

        if ($defaultAddress) {
            $regionId = $defaultAddress->pickup_warehouse_id
                ? ($defaultAddress->pickupWarehouse?->region_id ?? $defaultAddress->pickupWarehouse?->region?->id ?? $defaultAddress->pickupPoint?->region?->id)
                : $defaultAddress->region_id;
            $regionName = $defaultAddress->pickupWarehouse?->region?->name ?? $defaultAddress->pickupPoint?->region?->name ?? $defaultAddress->region?->name ?? '';
            $pickupPointName = $defaultAddress->pickupWarehouse?->name ?? $defaultAddress->pickupPoint?->name ?? '';

            $shippingOptions = $regionId ? $shippingService->getOptionsByRegion($regionId) : [];

            if ($defaultAddress->pickup_warehouse_id && isset($shippingOptions['pickup'])) {
                $selectedShipping = [
                    'method'       => 'pickup',
                    'cost'         => $shippingOptions['pickup']['cost'],
                    'days'         => $shippingOptions['pickup']['days'],
                    'region'       => $regionName,
                    'pickup_point' => $pickupPointName,
                ];
                $shippingCost = $shippingOptions['pickup']['cost'];
            } elseif ($regionId && isset($shippingOptions['door'])) {
                $doorCost = $shippingOptions['door']['cost'];
                $selectedShipping = [
                    'method'       => 'door',
                    'cost'         => $doorCost,
                    'days'         => $shippingOptions['door']['days'],
                    'region'       => $regionName,
                    'pickup_point' => null,
                ];
                $shippingCost = $doorCost;
            }
        }
    }

    // Default phone: use address first, fallback to user
    $defaultPhone = $defaultAddress?->phone
        ?? optional(auth()->user())->phone
        ?? '';

    $defaultAddressId = $defaultAddress?->id ?? null;

    // Final totals (whole numbers; shipping minimum 250)
    $grandTotal = (int) round($cartTotal + $shippingCost - $couponAmount);

    // Related products
    $topVariant = $cart->items->sortByDesc('quantity')->first()?->productVariant;
    $relatedProducts = $topVariant ? $productService->getRelatedProducts($topVariant) : collect();

    return Inertia::render('Frontend/Checkout/Payment', [
        'cart' => [
            'cart_id' => $cart->id,
            'items' => $presentedItems,
            'counts' => [
                'unique_items' => $presentedItems->count(),
                'total_qty'    => $presentedItems->sum('quantity'),
            ],
            'totals' => [
                'subtotal'        => $cartTotal,
                'shipping'        => $shippingCost,
                'coupon_discount' => $couponAmount,
                'grand_total'     => $grandTotal,
            ],
            'applied_coupon' => $appliedDiscount,
            'coupon_code'    => $couponCode,
            'coupon_error'   => $couponError,
        ],

        'selected_shipping'  => $selectedShipping,
        'home_delivery_config' => $shippingService->getHomeDeliveryConfig(),
        'customer_addresses' => $defaultAddress ? collect([$defaultAddress]) : collect(),
        'shipping_address_id' => $defaultAddressId,
        'billing_address_id'  => $defaultAddressId,
        'default_phone'       => $defaultPhone,
        'relatedProducts'     => $relatedProducts,
        'googleMapsApiKey'    => config('services.google.maps_api_key', ''),
    ]);
}

    public function estimateShipping()
    {

    }


}
