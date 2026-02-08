<?php

namespace App\Http\Controllers\Orders;

use App\Http\Controllers\Controller;
use App\Models\Cart\Cart;
use App\Models\Customer\Customer;
use App\Models\Orders\Delivery;
use App\Models\Orders\Order;
use App\Models\Orders\OrderItem;
use App\Models\Products\ProductVariant;
use App\Models\User;
use App\Services\CartService;
use App\Services\OrderProcessingService;
use DB;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Validator;
use Inertia\Inertia;


class OrderController extends Controller
{
    /**
     * Display a listing of the resource.
     */
    public function index(Request $request)
    {
        $validator = Validator::make($request->all(), [
            'status' => 'sometimes|string|in:pending,confirmed,processing,on_hold,out_for_delivery,shipped,delivered,returned,partially_returned,refunded,partially_refunded,cancelled,failed',
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

        $query = Order::with(['customer:id,first_name,last_name', 'orderItems.productVariant.product:id,name', 'orderItems.seller:id,name', 'shippingAddress', 'billingAddress', 'delivery.deliveryUser:id,name,email'])
            ->when($request->status, fn($q) => $q->where('status', $request->status))
            ->when($request->payment_status, fn($q) => $q->where('payment_status', $request->payment_status))
            ->when($request->fulfillment_status, fn($q) => $q->where('fulfillment_status', $request->fulfillment_status))
            ->when($request->customer_id, fn($q) => $q->where('customer_id', $request->customer_id))
            ->when($request->order_code, fn($q) => $q->where('order_code', 'like', "%{$request->order_code}%"))
            ->when($request->date_from, fn($q) => $q->whereDate('created_at', '>=', $request->date_from))
            ->when($request->date_to, fn($q) => $q->whereDate('created_at', '<=', $request->date_to));

        // If the authenticated user is a seller, limit orders to those containing their items
        $authUser = $request->user();
        if ($authUser && method_exists($authUser, 'hasRole') && $authUser->hasRole('seller')) {
            $sellerIds = $authUser->sellers()->pluck('seller_applications.id');
            $query->forSeller($sellerIds);
        }

        $sortBy = $request->get('sort_by', 'created_at');
        $sortOrder = $request->get('sort_order', 'desc');
        $query->orderBy($sortBy, $sortOrder);

        $orders = $query->paginate($request->get('per_page', 15))->withQueryString();

        return Inertia::render('Orders/Index', [
            'orders' => $orders,
            'filters' => $request->only(['status', 'payment_status', 'fulfillment_status', 'customer_id', 'order_code', 'date_from', 'date_to']),
            'statusOptions' => [
                'pending', 'confirmed', 'processing', 'on_hold', 'out_for_delivery', 'shipped',
                'delivered', 'returned', 'partially_returned', 'refunded', 'partially_refunded', 'cancelled', 'failed'
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
        $order->load(['customer.defaultAddress', 'orderItems.productVariant.product', 'shippingAddress', 'billingAddress', 'delivery.deliveryUser:id,name,email']);
        logger($order);

        $shippingAddress = $order->shippingAddress ?: $order->customer?->getDefaultAddress();
        $billingAddress = $order->billingAddress ?: $order->customer?->getDefaultAddress();

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
            'shipping_address' => $shippingAddress ? [
                'first_name' => $shippingAddress->first_name,
                'last_name' => $shippingAddress->last_name,
                'address_line_1' => $shippingAddress->address_line_1,
                'address_line_2' => $shippingAddress->address_line_2,
                'city' => $shippingAddress->city,
                'state' => $shippingAddress->state ?? $shippingAddress->state_province,
                'state_province' => $shippingAddress->state_province,
                'postal_code' => $shippingAddress->postal_code,
                'country' => $shippingAddress->country ?? $shippingAddress->country_code,
                'country_code' => $shippingAddress->country_code,
                'country_name' => $shippingAddress->country_name,
                'phone' => $shippingAddress->phone,
            ] : null,
            'billing_address' => $billingAddress ? [
                'first_name' => $billingAddress->first_name,
                'last_name' => $billingAddress->last_name,
                'address_line_1' => $billingAddress->address_line_1,
                'address_line_2' => $billingAddress->address_line_2,
                'city' => $billingAddress->city,
                'state' => $billingAddress->state ?? $billingAddress->state_province,
                'state_province' => $billingAddress->state_province,
                'postal_code' => $billingAddress->postal_code,
                'country' => $billingAddress->country ?? $billingAddress->country_code,
                'country_code' => $billingAddress->country_code,
                'country_name' => $billingAddress->country_name,
                'phone' => $billingAddress->phone,
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

        Delivery::updateOrCreate(
            ['order_id' => $order->id],
            [
                'delivery_id' => $rider->id,
                'assignment_status' => 'pending',
                'rejection_reason' => null,
            ]
        );
        $order->update([
            'status' => 'confirmed',
            'confirmed_at' => $order->confirmed_at ?? now(),
        ]);

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

    public function store(Request $request, OrderProcessingService $service)
    {
        $validated = $request->validate([
            'cart_id'          => 'required|exists:carts,id',
            'payment_provider' => 'required|string|in:mpesa,cod',
            'phone'            => 'required_if:payment_provider,mpesa|string',
            'shipping_amount'  => 'nullable|numeric'
        ]);

        try {
            [$session, $paymentInit] = $service->process($request->cart_id, $validated);
            if ($request->expectsJson()) {
                return response()->json(['checkout_session' => $session, 'payment_init' => $paymentInit]);
            }
             $cart= Cart::with(['items.productVariant.product'])->findOrFail( $request->cart_id);
             return redirect()->route('checkout.payment', $cart)->with('success', 'Order processing initiated. Please scan the QR code to complete payment.');
        } catch (\Exception $e) {
            return back()->with('error', $e->getMessage())->withInput();
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
                    'pending', 'confirmed', 'processing', 'on_hold', 'out_for_delivery', 'shipped',
                    'delivered', 'returned', 'partially_returned', 'refunded', 'partially_refunded', 'cancelled', 'failed'
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
                'pending', 'confirmed', 'processing', 'on_hold', 'out_for_delivery', 'shipped',
                'delivered', 'returned', 'partially_returned', 'refunded', 'partially_refunded', 'cancelled', 'failed'
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
            'status' => 'sometimes|string|in:pending,confirmed,processing,on_hold,out_for_delivery,shipped,delivered,returned,partially_returned,refunded,partially_refunded,cancelled,failed',
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
            return redirect()->route('admin.orders.index')
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
            'status' => 'sometimes|string|in:pending,confirmed,processing,on_hold,out_for_delivery,shipped,delivered,returned,partially_returned,refunded,partially_refunded,cancelled,failed',
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
            'orders' => $orders,
            'customerPhone' => auth()->user()->phone ?? '',
        ]);
    }

    private function clearCurrentCart(Request $request): void
    {
        try {
            /** @var CartService $cartService */
            $cartService = app(CartService::class);
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
