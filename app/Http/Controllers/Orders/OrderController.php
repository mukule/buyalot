<?php

namespace App\Http\Controllers\Orders;

use App\Http\Controllers\Controller;
use App\Models\Customer\Customer;
use App\Models\Orders\Order;
use App\Models\Orders\OrderItem;
use App\Models\Product;
use App\Models\ProductVariant;
use App\Models\ProductVariantValue;
use App\Models\User;
use App\Models\Payment\Discount;
use App\Services\PaymentService;
use App\Http\DTOs\PaymentRequest;
use App\Services\ShippingService;
use DB;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Http\Response;
use Illuminate\Support\Facades\Validator;
use Illuminate\Support\Str;
use Inertia\Inertia;

class OrderController extends Controller
{
    /**
     * Display a listing of the resource.
     */
    public function index(Request $request)
    {
        $validator = Validator::make($request->all(), [
            'status' => 'sometimes|string|in:pending,confirmed,processing,shipped,delivered,cancelled,refunded,partially_refunded',
            'payment_status' => 'sometimes|string|in:pending,paid,partially_paid,failed,refunded,partially_refunded',
            'fulfillment_status' => 'sometimes|string|in:unfulfilled,processing,partially_fulfilled,fulfilled,cancelled',
            'customer_id' => 'sometimes|integer|exists:customers,id',
            'order_code' => 'sometimes|string',
            'date_from' => 'sometimes|date',
            'date_to' => 'sometimes|date',
            'per_page' => 'sometimes|integer|min:1|max:100',
            'page' => 'sometimes|integer|min:1',
            'sort_by' => 'sometimes|string|in:created_at,total_amount,order_code,status',
            'sort_order' => 'sometimes|string|in:asc,desc'
        ]);
        if ($validator->fails()) {
            return back()->withErrors($validator)->withInput();
        }

        $query = Order::with(['customer:id,first_name,last_name', 'orderItems.productVariant.product:id,name', 'orderItems.seller:id,name', 'shippingAddress', 'billingAddress'])
            ->when($request->status, fn($q) => $q->where('status', $request->status))
            ->when($request->payment_status, fn($q) => $q->where('payment_status', $request->payment_status))
            ->when($request->fulfillment_status, fn($q) => $q->where('fulfillment_status', $request->fulfillment_status))
            ->when($request->customer_id, fn($q) => $q->where('customer_id', $request->customer_id))
            ->when($request->order_code, fn($q) => $q->where('order_code', 'like', "%{$request->order_code}%"))
            ->when($request->date_from, fn($q) => $q->whereDate('created_at', '>=', $request->date_from))
            ->when($request->date_to, fn($q) => $q->whereDate('created_at', '<=', $request->date_to));

        $sortBy = $request->get('sort_by', 'created_at');
        $sortOrder = $request->get('sort_order', 'desc');
        $query->orderBy($sortBy, $sortOrder);

        $orders = $query->paginate($request->get('per_page', 15))->withQueryString();

        return Inertia::render('Orders/Index', [
            'orders' => $orders,
            'filters' => $request->only(['status', 'payment_status', 'fulfillment_status', 'customer_id', 'order_code', 'date_from', 'date_to']),
            'statusOptions' => [
                'pending', 'confirmed', 'processing', 'shipped',
                'delivered', 'cancelled', 'refunded', 'partially_refunded'
            ],
            'paymentStatusOptions' => [
                'pending', 'paid', 'partially_paid', 'failed', 'refunded', 'partially_refunded'
            ],
            'fulfillmentStatusOptions' => [
                'unfulfilled', 'processing', 'partially_fulfilled', 'fulfilled', 'cancelled'
            ]
        ]);
    }

    public function show(Order $order)
    {
        logger("view order details");
        $order->load(['customer', 'orderItems.productVariant.product', 'shippingAddress', 'billingAddress', 'assignedRider:id,name,email']);
        logger($order);
        // Transform order to a plain array structure that the frontend expects
        $payload = [
            'id' => $order->id,
            'ulid' => $order->ulid,
            'order_code' => $order->order_code,
            'subtotal' => (float) $order->subtotal,
            'tax_amount' => (float) $order->tax_amount,
            'shipping_amount' => (float) $order->shipping_amount,
            'discount_amount' => (float) $order->discount_amount,
            'total_amount' => (float) $order->total_amount,
            'status' => $order->status,
            'payment_status' => $order->payment_status,
            'fulfillment_status' => $order->fulfillment_status,
            'currency' => $order->currency,
            'created_at' => optional($order->created_at)?->toDateTimeString(),
            'notes' => $order->notes,
            'order_items' => $order->orderItems->map(function ($item) {
                $variant = $item->productVariant;
                $product = $variant?->product;
                return [
                    'id' => $item->id,
                    'quantity' => (int) $item->quantity,
                    'unit_price' => (float) $item->unit_price,
                    'total_price' => (float) $item->total_price,
                    // Keep both a direct product field and the nested product_variant.product for compatibility
                    'product' => $product ? ['id' => $product->id, 'name' => $product->name] : null,
                    'product_variant' => $variant ? [
                        'id' => $variant->id,
                        'sku' => $variant->sku,
                        'product' => $product ? ['id' => $product->id, 'name' => $product->name] : null,
                    ] : null,
                ];
            })->toArray(),
            'shipping_address' => $order->shippingAddress ? [
                'first_name' => $order->shippingAddress->first_name,
                'last_name' => $order->shippingAddress->last_name,
                'address_line_1' => $order->shippingAddress->address_line_1,
                'address_line_2' => $order->shippingAddress->address_line_2,
                'city' => $order->shippingAddress->city,
                'state' => $order->shippingAddress->state ?? $order->shippingAddress->state_province,
                'state_province' => $order->shippingAddress->state_province,
                'postal_code' => $order->shippingAddress->postal_code,
                'country' => $order->shippingAddress->country ?? $order->shippingAddress->country_code,
                'country_code' => $order->shippingAddress->country_code,
                'country_name' => $order->shippingAddress->country_name,
                'phone' => $order->shippingAddress->phone,
            ] : null,
            'billing_address' => $order->billingAddress ? [
                'first_name' => $order->billingAddress->first_name,
                'last_name' => $order->billingAddress->last_name,
                'address_line_1' => $order->billingAddress->address_line_1,
                'address_line_2' => $order->billingAddress->address_line_2,
                'city' => $order->billingAddress->city,
                'state' => $order->billingAddress->state ?? $order->billingAddress->state_province,
                'state_province' => $order->billingAddress->state_province,
                'postal_code' => $order->billingAddress->postal_code,
                'country' => $order->billingAddress->country ?? $order->billingAddress->country_code,
                'country_code' => $order->billingAddress->country_code,
                'country_name' => $order->billingAddress->country_name,
                'phone' => $order->billingAddress->phone,
            ] : null,
        ];

        return Inertia::render('Customer/OrderDetails', [
            'order' => $payload,
        ]);
    }

    public function assignRider(Request $request, Order $order)
    {
        $request->validate([
            'rider_id' => 'required|exists:users,id'
        ]);

        $rider = User::whereHas('roles', fn($q) => $q->where('name', 'delivery'))
            ->where('id', $request->rider_id)
            ->firstOrFail();

        $order->update(['rider_id' => $rider->id]);

        return back()->with('success', 'Order assigned to rider successfully.');
    }

/**
     * Show the form for creating a new resource.
     */
    public function create()
    {
        //
    }

    /**
     * Store a newly created resource in storage.
     */
    public function store(Request $request)
    {
        $validator = Validator::make($request->all(), [
            'customer_id' => 'sometimes|integer|exists:customers,id',
            'items' => 'required|array|min:1',
            'items.*.product_variant_id' => 'required|integer|exists:product_variants,id',
            'items.*.quantity' => 'required|integer|min:1',
            'items.*.unit_price' => 'sometimes|numeric|min:0',
            'billing_address_id'=>'sometimes:exists:customer_address,id',
            'shipping_address_id'=>'sometimes:exists:customer_address,id',
            'currency' => 'sometimes|string|size:3|in:KES,USD,EUR',
            'tax_amount' => 'sometimes|numeric|min:0',
            'shipping_amount' => 'sometimes|numeric|min:0',
            'distance_km' => 'sometimes|numeric|min:0',
            'discount_amount' => 'sometimes|numeric|min:0',
            'coupon_code' => 'sometimes|string|max:50',
            'notes' => 'sometimes|string|max:1000',
            'source' => 'sometimes|string|in:web,mobile,api,admin',
            'channel' => 'sometimes|string|max:50',
            'metadata' => 'sometimes|array',
                        'payment_provider' => 'sometimes|string|in:mpesa',
                        'phone' => 'required_if:payment_provider,mpesa|nullable|string'
        ]);
        logger($validator->errors()->all());
        if ($validator->fails()) {
            if ($request->expectsJson()) {
                return response()->json([
                    'message' => 'Validation failed',
                    'errors' => $validator->errors()
                ], 400);
            }
            return back()->withErrors($validator)->withInput();
        }

        DB::beginTransaction();

        try {
            // Resolve customer id from request or authenticated user
            $customerId = (int)($request->get('customer_id') ?? optional(auth()->user()?->customer)->id);
            if (!$customerId) {
                DB::rollBack();
                return response()->json([
                    'success' => false,
                    'message' => 'Customer not identified',
                ], 422);
            }

            // Server-side compute prices, discounts, and validate inventory
            $itemsInput = $request->items;
            $computedItems = [];
            $subtotal = 0.0;
            $perItemDiscountTotal = 0.0;

            $insufficient = [];
            foreach ($itemsInput as $item) {
                $variant = ProductVariant::query()->lockForUpdate()->find($item['product_variant_id']);
                if (!$variant) {
                    DB::rollBack();
                    return response()->json([
                        'success' => false,
                        'status' => 'error',
                        'message' => 'Product variant not found',
                    ], 404);
                }

                $product = Product::find($variant->product_id);
                if (!$product) {
                    DB::rollBack();
                    return response()->json([
                        'success' => false,
                        'status' => 'error',
                        'message' => 'Product not found',
                    ], 404);
                }

                $qty = (int) $item['quantity'];
                if ($qty <= 0) {
                    DB::rollBack();
                    return response()->json([
                        'success' => false,
                        'message' => 'Quantity must be at least 1',
                    ], 422);
                }

                if ($qty > $variant->stock) {
                    $insufficient[] = [
                        'product_variant_id' => $variant->id,
                        'requested' => $qty,
                        'available' => (int) $variant->stock,
                        'product_name' => $product->name,
                    ];
                    // do not break; collect all
                    continue;
                }

                $unitPrice = (float) $variant->selling_price; // authoritative price
                $perUnitDiscount = max(0, (float)$variant->regular_price - (float)$variant->selling_price);
                $lineSubtotal = $unitPrice * $qty; // before any coupon
                $lineDiscount = $perUnitDiscount * $qty;

                $subtotal += $lineSubtotal;
                $perItemDiscountTotal += $lineDiscount;

                $computedItems[] = [
                    'variant' => $variant,
                    'product' => $product,
                    'quantity' => $qty,
                    'unit_price' => $unitPrice,
                    'line_subtotal' => $lineSubtotal,
                    'line_discount' => $lineDiscount,
                ];
            }

            if (!empty($insufficient)) {
                DB::rollBack();
                return response()->json([
                    'success' => false,
                    'message' => 'Some items are out of stock or have insufficient quantity. You can remove them and retry.',
                    'code' => 'insufficient_stock',
                    'items' => $insufficient,
                ], 409);
            }

            $taxAmount = (float) $request->get('tax_amount', 0);
            $shippingAmount = 0.0;
            $shippingBreakdown = null;
            if ($request->filled('distance_km')) {
                /** @var ShippingService $shippingService */
                $shippingService = app(ShippingService::class);
                $itemsForShipping = array_map(function ($ci) {
                    return [
                        'product_variant_id' => $ci['variant']->id,
                        'quantity' => $ci['quantity'],
                        'unit_price' => $ci['unit_price'],
                    ];
                }, $computedItems);
                $estimate = $shippingService->estimate($itemsForShipping, (float)$request->get('distance_km'));
                $shippingAmount = (float) $estimate['amount'];
                $shippingBreakdown = $estimate;
            } else {
                $shippingAmount = (float) $request->get('shipping_amount', 0);
            }

            // Apply coupon/discount code if provided and active
            $couponCode = $request->get('coupon_code');
            $couponDiscount = 0.0;
            $shippingDiscountAmount = 0.0;
            $discountId = null;
            $appliedDiscounts = [];
            if ($couponCode) {
                $discount = Discount::query()->active()->byCode($couponCode)->first();
                if ($discount && $discount->canBeUsedByCustomer($customerId)) {
                    // Build items payload for discount calculation if needed
                    $itemsForDiscount = array_map(function ($ci) {
                        /** @var \App\Models\Product $product */
                        $product = $ci['product'];
                        $categoryIds = [];
                        if ($product->category) {
                            // collect hierarchy ids from root to leaf
                            $hier = $product->category->getHierarchy();
                            $categoryIds = array_map(fn($c) => $c['id'], $hier);
                        }

                        $sellerId = null;
                        if ($ci['product']->owner_type === 'App\\Models\\Seller') {
                            $sellerId = $ci['product']->owner_id;
                        } elseif ($ci['product']->owner_type === 'App\\Models\\User') {
                            $sellerId = null;
                        }

                        return [
                            'product_id' => $product->id,
                            'product_variant_id' => $ci['variant']->id,
                            'seller_id' => $sellerId,
                            'brand_id' => $product->brand_id ?? null,
                            'category_ids' => $categoryIds,
                            'quantity' => $ci['quantity'],
                            'unit_price' => $ci['unit_price'],
                        ];
                    }, $computedItems);

                    // First, compute monetary discount on items/order
                    $couponDiscount = (float) $discount->calculateDiscount(
                        $subtotal,
                        $itemsForDiscount,
                        [
                            'customer_id' => $customerId,
                            'order_subtotal' => $subtotal,
                            // 'region_id' could be derived from shipping address if available
                        ]
                    );

                    // Then, handle free shipping or shipping-applied discounts
                    $conditions = $discount->conditions ?? [];
                    $appliesTo = $conditions['applies_to'] ?? null;
                    if ($discount->type === 'free_shipping' || $appliesTo === 'shipping') {
                        // Apply shipping discount up to current shippingAmount
                        if ($shippingAmount > 0) {
                            $shippingDiscountAmount = $shippingAmount;
                            $shippingAmount = 0.0;
                        }
                    }

                    // Record applied discount(s)
                    if ($discount->type === 'free_shipping' || $shippingDiscountAmount > 0) {
                        $discountId = $discount->id;
                        $appliedDiscounts[] = [
                            'type' => $discount->type,
                            'code' => $discount->code,
                            'name' => $discount->name,
                            'amount' => round($shippingDiscountAmount, 2),
                        ];
                    }
                    if ($couponDiscount > 0) {
                        $discountId = $discount->id;
                        $appliedDiscounts[] = [
                            'type' => $discount->type,
                            'code' => $discount->code,
                            'name' => $discount->name,
                            'amount' => round($couponDiscount, 2),
                        ];
                    }
                }
            }

            $discountAmount = round($perItemDiscountTotal + $couponDiscount + $shippingDiscountAmount, 2);
            $totalAmount = round($subtotal + $taxAmount + $shippingAmount - $discountAmount, 2);

            // Build metadata (include shipping breakdown if available)
            $orderMetadata = $request->get('metadata', []);
            if ($shippingBreakdown) {
                $orderMetadata['shipping_estimate'] = $shippingBreakdown;
            }

            // Idempotency: if a pending order exists for the same customer with the same items and totals, reuse it
            try {
                // Build a normalized fingerprint of requested items (variant_id => qty)
                $requestedItemsMap = [];
                foreach ($computedItems as $ci) {
                    $vid = $ci['variant']->id;
                    $requestedItemsMap[$vid] = ($requestedItemsMap[$vid] ?? 0) + (int)$ci['quantity'];
                }
                ksort($requestedItemsMap);

                $recentPendingOrders = Order::query()
                    ->where('customer_id', $customerId)
                    ->where('payment_status', 'pending')
                    ->whereBetween('created_at', [now()->subHours(2), now()])
                    ->with('orderItems')
                    ->get();

                foreach ($recentPendingOrders as $existing) {
                    // Quick sanity checks on totals and meta
                    $totalsMatch = ((float)$existing->total_amount) == $totalAmount
                        && ((float)$existing->shipping_amount) == $shippingAmount
                        && (string)$existing->currency === (string)$request->get('currency', 'KES')
                        && (string)($existing->coupon_code ?? '') === (string)($couponCode ?? '');

                    if (!$totalsMatch) {
                        continue;
                    }

                    // Build existing items map
                    $existingItemsMap = [];
                    foreach ($existing->orderItems as $oi) {
                        $existingItemsMap[(int)$oi->product_variant_id] = ($existingItemsMap[(int)$oi->product_variant_id] ?? 0) + (int)$oi->quantity;
                    }
                    ksort($existingItemsMap);

                    if ($existingItemsMap === $requestedItemsMap) {
                        // Reuse this order; do not recreate or adjust stock
                        DB::commit();

                        // Clear current cart so it no longer shows these items
                        $this->clearCurrentCart($request);

                        $existing->load(['orderItems.productVariant.product:id,name', 'orderItems.seller:id,name']);

                        if ($request->expectsJson()) {
                            $payment = null;
                            $paymentInit = null;
                            if ($request->get('payment_provider') === 'mpesa' && $request->filled('phone')) {
                                /** @var PaymentService $paymentService */
                                $paymentService = app(PaymentService::class);
                                $paymentRequest = new PaymentRequest(
                                    provider: 'mpesa',
                                    method: 'stk_push',
                                    amount: (float)$existing->total_amount,
                                    currency: $existing->currency,
                                    phone: $request->get('phone'),
                                    email: null,
                                    metadata: ['order_ulid' => $existing->ulid],
                                    callbackUrl: null,
                                    returnUrl: null,
                                );
                                $payment = $paymentService->createPayment($existing, $paymentRequest);
                                $paymentInit = $paymentService->initializePayment($payment, $paymentRequest);
                            }

                            return response()->json([
                                'message' => 'Existing pending order found. Reusing for payment.',
                                'data' => $existing,
                                'payment' => $payment,
                                'payment_init' => isset($paymentInit) ? $paymentInit->toArray() : null,
                            ], 200);
                        }

                        return redirect()->route('orders.show', $existing)
                            ->with('info', 'Existing pending order found. Reusing the same order.');
                    }
                }
            } catch (\Throwable $e) {
                // Non-fatal: proceed to create a new order if idempotency check fails
                \Log::warning('Order idempotency check failed', ['error' => $e->getMessage()]);
            }

            // Create order
            $order = Order::create([
                'order_code' => $this->generateOrderCode(),
                'customer_id' => $customerId,
                'subtotal' => $subtotal,
                'tax_amount' => $taxAmount,
                'shipping_amount' => $shippingAmount,
                'discount_amount' => $discountAmount,
                'total_amount' => $totalAmount,
                'currency' => $request->get('currency', 'KES'),
                'billing_address_id' => $request->billing_address_id,
                'shipping_address_id' => $request->billing_address_id,
                'notes' => $request->get('notes'),
                'source' => $request->get('source', 'web'),
                'channel' => $request->get('channel'),
                'coupon_code' => $couponCode,
                'discount_id' => $discountId,
                'applied_discounts' => $appliedDiscounts,
                'payment_status' => 'pending',
                'metadata' => $orderMetadata
            ]);

            // Create order items and decrement stock
            foreach ($computedItems as $ci) {
                // Determine the correct seller_id based on owner_type
                $sellerId = null;
                if ($ci['product']->owner_type === 'App\\Models\\Seller') {
                    $sellerId = $ci['product']->owner_id;
                } elseif ($ci['product']->owner_type === 'App\\Models\\User') {
                    $sellerId = null;
                }
                OrderItem::create([
                    'ulid' => Str::ulid(),
                    'order_id' => $order->id,
                    'product_variant_id' => $ci['variant']->id,
                    'seller_id' => $sellerId,
                    'quantity' => $ci['quantity'],
                    'unit_price' => $ci['unit_price'],
                    'total_price' => $ci['line_subtotal'],
                    'discount_amount' => $ci['line_discount'],
                    'tax_amount' => 0,
                    'product_snapshot' => [
                        'id' => $ci['product']->id,
                        'name' => $ci['product']->name,
                        'sku' => $ci['variant']->sku,
                        'current_stock' => $ci['variant']->stock,
                    ]
                ]);

                // reduce stock quantity
                $ci['variant']->decrement('stock', $ci['quantity']);
            }

            // Increment coupon usage if applied
            if ($discountId) {
                try {
                    \App\Models\Payment\Discount::find($discountId)?->incrementUsage();
                } catch (\Throwable $e) {
                    // Do not fail order creation because of usage counter
                    logger('Failed to increment discount usage: ' . $e->getMessage());
                }
            }

            DB::commit();

            // Clear current cart so it no longer shows these items
            $this->clearCurrentCart($request);

            $order->load(['orderItems.productVariant.product:id,name', 'orderItems.seller:id,name']);

            // API Response
            if ($request->expectsJson()) {
                $payment = null;
                $paymentInit = null;
                if ($request->get('payment_provider') === 'mpesa') {
                    /** @var PaymentService $paymentService */
                    $paymentService = app(PaymentService::class);
                    $paymentRequest = new PaymentRequest(
                        provider: 'mpesa',
                        method: 'stk_push',
                        amount: (float)$order->total_amount,
                        currency: $order->currency,
                        phone: $request->get('phone'),
                        email: null,
                        metadata: ['order_ulid' => $order->ulid],
                        callbackUrl: null,
                        returnUrl: null,
                    );
                    $payment = $paymentService->createPayment($order, $paymentRequest);
                    $paymentInit = $paymentService->initializePayment($payment, $paymentRequest);
                }

                return response()->json([
                    'message' => 'Order created successfully',
                    'data' => $order,
                    'payment' => $payment,
                    'payment_init' => isset($paymentInit) ? $paymentInit->toArray() : null,
                ], 201);
            }

            // Inertia Response
            return redirect()->route('orders.show', $order)
                ->with('success', 'Order created successfully');

        } catch (\Exception $e) {
            DB::rollBack();
            info($e->getMessage());
            if ($request->expectsJson()) {
                return response()->json([
                    'message' => 'Failed to create order',
                    'error' => config('app.debug') ? $e->getMessage() : null
                ], 500);
            }

            return back()->with('error', 'Failed to create order')->withInput();
        }
    }

    /**
     * Display the specified resource.
     */
    public function show1(Request $request, Order $order)
    {
        try {
            $order->load([
                'customer',
                'orderItems.productVariantValue.product',
                'orderItems.productVariantValue.variant',
                'orderItems.seller',
            ]);

            // API Response
            if ($request->expectsJson()) {
                return response()->json([
                    'message' => 'Order retrieved successfully',
                    'data' => $order
                ]);
            }

            // Inertia Response
            return Inertia::render('Orders/Show', [
                'order' => $order,
                'statusOptions' => [
                    'pending', 'confirmed', 'processing', 'shipped',
                    'delivered', 'cancelled', 'refunded', 'partially_refunded'
                ],
                'paymentStatusOptions' => [
                    'pending', 'paid', 'partially_paid', 'failed', 'refunded', 'partially_refunded'
                ],
                'fulfillmentStatusOptions' => [
                    'unfulfilled', 'processing', 'partially_fulfilled', 'fulfilled', 'cancelled'
                ]
            ]);

        } catch (\Exception $e) {
            if ($request->expectsJson()) {
                return response()->json([
                    'message' => 'Failed to retrieve order',
                    'error' => config('app.debug') ? $e->getMessage() : null
                ], 500);
            }

            return back()->with('error', 'Failed to retrieve order');
        }
    }

    /**
     * Show the form for editing the specified resource.
     */
    public function edit(Request $request, Order $order): \Inertia\Response
    {
        $order->load(['customer', 'orderItems.productVariantValue.product', 'orderItems.seller']);

        return Inertia::render('Orders/Edit', [
            'order' => $order,
            'statusOptions' => [
                'pending', 'confirmed', 'processing', 'shipped',
                'delivered', 'cancelled', 'refunded', 'partially_refunded'
            ],
            'paymentStatusOptions' => [
                'pending', 'paid', 'partially_paid', 'failed', 'refunded', 'partially_refunded'
            ],
            'fulfillmentStatusOptions' => [
                'unfulfilled', 'processing', 'partially_fulfilled', 'fulfilled', 'cancelled'
            ]
        ]);
    }

    /**
     * Update the specified resource in storage.
     */
    public function update(Request $request, Order $order)
    {
        $validator = Validator::make($request->all(), [
            'status' => 'sometimes|string|in:pending,confirmed,processing,shipped,delivered,cancelled,refunded,partially_refunded',
            'payment_status' => 'sometimes|string|in:pending,paid,partially_paid,failed,refunded,partially_refunded',
            'fulfillment_status' => 'sometimes|string|in:unfulfilled,processing,partially_fulfilled,fulfilled,cancelled',
            'billing_address_id'=>'sometimes:exists:customer_address,id',
            'shipping_address_id'=>'sometimes:exists:customer_address,id',
            'tax_amount' => 'sometimes|numeric|min:0',
            'shipping_amount' => 'sometimes|numeric|min:0',
            'discount_amount' => 'sometimes|numeric|min:0',
            'notes' => 'sometimes|string|max:1000',
            'metadata' => 'sometimes|array',
            'tracking_number' => 'sometimes|string|max:100',
            'items' => 'sometimes|array|min:1',
            'items.*.id' => 'sometimes|integer|exists:order_items,id',
            'items.*.quantity' => 'sometimes|integer|min:1',
            'items.*.unit_price' => 'sometimes|numeric|min:0'
        ]);

        if ($validator->fails()) {
            if ($request->expectsJson()) {
                return response()->json([
                    'message' => 'Validation failed',
                    'errors' => $validator->errors()
                ], 400);
            }
            return back()->withErrors($validator)->withInput();
        }
        DB::beginTransaction();

        try {
            $updateData = $request->only([
                'status', 'payment_status', 'fulfillment_status',
                'billing_address_id', 'shipping_address_id', 'tax_amount',
                'shipping_amount', 'discount_amount', 'notes', 'metadata'
            ]);

            // Handle status timestamps
            if ($request->has('status')) {
                switch ($request->status) {
                    case 'confirmed':
                        $updateData['confirmed_at'] = now();
                        break;
                    case 'shipped':
                        $updateData['shipped_at'] = now();
                        break;
                    case 'delivered':
                        $updateData['delivered_at'] = now();
                        break;
                    case 'cancelled':
                        $updateData['cancelled_at'] = now();
                        break;
                }
            }

            // Update order items if provided
            if ($request->has('items')) {
                $subtotal = 0;
                foreach ($request->items as $itemData) {
                    if (isset($itemData['id'])) {
                        $item = OrderItem::where('order_id', $order->id)
                            ->where('id', $itemData['id'])
                            ->first();
                        $productvariant=ProductVariant::find($itemData['product_id_variant']);
                        if ($itemData['quantity']>$productvariant->stock) {
                            $productname=$productvariant->product->name;
                            DB::rollBack();
                            return response()->json([
                               "message" => "Item $productname out of stock. Remaining stock: $productvariant->stock",
                               "success"=> false
                            ]);
                        }
                        if ($item) {
                            $item->update([
                                'quantity' => $itemData['quantity'] ?? $item->quantity,
                                'unit_price' => $itemData['unit_price'] ?? $item->unit_price,
                                'total_price' => ($itemData['quantity'] ?? $item->quantity) * ($itemData['unit_price'] ?? $item->unit_price)
                            ]);
                            $subtotal += $item->total_price;
                        }
                    }
                }

                // Recalculate totals
                $updateData['subtotal'] = $subtotal;
                $updateData['total_amount'] = $subtotal +
                    ($updateData['tax_amount'] ?? $order->tax_amount) +
                    ($updateData['shipping_amount'] ?? $order->shipping_amount) -
                    ($updateData['discount_amount'] ?? $order->discount_amount);
            }

            $order->update($updateData);

            DB::commit();

            $order->load(['customer', 'orderItems.productVariant.product', 'orderItems.seller']);

            // API Response
            if ($request->expectsJson()) {
                return response()->json([
                    'message' => 'Order updated successfully',
                    'data' => $order
                ]);
            }

            // Inertia Response
            return redirect()->route('orders.show', $order)
                ->with('success', 'Order updated successfully');

        } catch (\Exception $e) {
            DB::rollBack();

            if ($request->expectsJson()) {
                return response()->json([
                    'message' => 'Failed to update order',
                    'error' => config('app.debug') ? $e->getMessage() : null
                ], 500);
            }

            return back()->with('error', 'Failed to update order')->withInput();
        }
    }

    /**
     * Remove the specified resource from storage.
     */
    public function destroy(Request $request, Order $order)
    {
        try {
            // Check if order can be deleted (business logic)
            if (in_array($order->status, ['shipped', 'delivered'])) {
                $message = 'Cannot delete shipped or delivered orders';

                if ($request->expectsJson()) {
                    return response()->json(['message' => $message], 400);
                }
                return back()->with('error', $message);
            }

            DB::beginTransaction();

            // Soft delete the order and its items
            $order->orderItems()->delete();
            $order->delete();

            DB::commit();

            // API Response
            if ($request->expectsJson()) {
                return response()->json([
                    'message' => 'Order deleted successfully'
                ]);
            }

            // Inertia Response
            return redirect()->route('orders.index')
                ->with('success', 'Order deleted successfully');

        } catch (\Exception $e) {
            DB::rollBack();

            if ($request->expectsJson()) {
                return response()->json([
                    'message' => 'Failed to delete order',
                    'error' => config('app.debug') ? $e->getMessage() : null
                ], 500);
            }

            return back()->with('error', 'Failed to delete order');
        }
    }
    private function generateOrderCode(): string
    {
        $lastOrder = Order::orderByDesc('id')->first();

        if ($lastOrder && preg_match('/ORD(\d+)/', $lastOrder->order_code, $matches)) {
            $number = (int) $matches[1] + 1;
        } else {
            $number = 1;
        }
        return 'ORD' . str_pad($number, 5, '0', STR_PAD_LEFT);
    }

    public function bulkUpdate(Request $request): JsonResponse
    {
        $validator = Validator::make($request->all(), [
            'order_ids' => 'required|array|min:1',
            'order_ids.*' => 'integer|exists:orders,id',
            'status' => 'sometimes|string|in:pending,confirmed,processing,shipped,delivered,cancelled,refunded,partially_refunded',
            'payment_status' => 'sometimes|string|in:pending,paid,partially_paid,failed,refunded,partially_refunded',
            'fulfillment_status' => 'sometimes|string|in:unfulfilled,processing,partially_fulfilled,fulfilled,cancelled'
        ]);

        if ($validator->fails()) {
            return response()->json([
                'message' => 'Validation failed',
                'errors' => $validator->errors()
            ], 400);
        }

        try {
            DB::beginTransaction();

            $updateData = $request->only(['status', 'payment_status', 'fulfillment_status']);
            $updatedCount = Order::whereIn('id', $request->order_ids)->update($updateData);

            DB::commit();

            return response()->json([
                'message' => "Successfully updated {$updatedCount} orders",
                'updated_count' => $updatedCount
            ]);

        } catch (\Exception $e) {
            DB::rollBack();

            return response()->json([
                'message' => 'Failed to bulk update orders',
                'error' => config('app.debug') ? $e->getMessage() : null
            ], 500);
        }
    }


    public function myOrders(Request $request)
    {
        logger("orders");
        $user = $request->user();
        if (!$user) {
            // If not authenticated, send to login
            return redirect()->route('login');
        }
        $customer = Customer::where('user_id', $user->id)->first();
        if ($customer == null) {
            // If the user is not a customer, redirect sensibly
            if ($user->hasRole('admin') || $user->hasRole('seller')) {
                return redirect()->route('admin.dashboard')->with('error', 'Your account is not a customer account.');
            }
            return redirect()->back()->with('error', 'Customer record not found');
        }
        $orders = Order::with(['orderItems.productVariant.product:id,name', 'orderItems.seller:id,name'])
            ->where('customer_id', $customer->id)
            ->latest()
            ->paginate(10);
        $orders->getCollection()->transform(function ($order) {
            return [
                'id' => $order->id,
                'ulid' => $order->ulid,
                'order_code' => $order->order_code,
                'subtotal' => (float) $order->subtotal,
                'tax_amount' => (float) $order->tax_amount,
                'shipping_amount' => (float) $order->shipping_amount,
                'discount_amount' => (float) $order->discount_amount,
                'total_amount' => (float) $order->total_amount,
                'status' => $order->status,
                'payment_status' => $order->payment_status,
                'fulfillment_status' => $order->fulfillment_status,
                'currency' => $order->currency,
                'created_at' => $order->created_at ? $order->created_at->toDateTimeString() : null,
                'order_items' => $order->orderItems->map(function ($item) {
                    return [
                        'id' => $item->id,
                        'quantity' => $item->quantity,
                        'unit_price' => (float) $item->unit_price,
                        'total_price' => (float) $item->total_price,
                        'product' => $item->productVariant && $item->productVariant->product ? ['id' => $item->productVariant->product->id, 'name' => $item->productVariant->product->name] : null,
                        'seller' => $item->seller ? ['id' => $item->seller->id, 'name' => $item->seller->name] : null,
                    ];
                })->toArray(),
            ];
        });

        if ($request->expectsJson()) {
            return response()->json([
                'message' => 'Customer orders retrieved successfully',
                'data' => $orders
            ]);
        }

        return Inertia::render('Customer/MyOrders', [
            'orders' => $orders
        ]);
    }

    private function clearCurrentCart(Request $request): void
    {
        try {
            /** @var \App\Services\CartService $cartService */
            $cartService = app(\App\Services\CartService::class);
            $cart = $cartService->getCart($request);
            if ($cart) {
                $cart->items()->delete();
                // Also clear any active reservations for this cart
                try {
                    /** @var \App\Services\CartReservationService $reservationService */
                    $reservationService = app(\App\Services\CartReservationService::class);
                    $reservationService->releaseAllForCart($cart->id);
                } catch (\Throwable $e) {
                    \Log::warning('Failed to clear cart reservations', ['error' => $e->getMessage()]);
                }
            }
        } catch (\Throwable $e) {
            \Log::warning('Failed to clear cart after order creation', ['error' => $e->getMessage()]);
        }
    }

}
