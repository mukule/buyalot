<?php

namespace App\Http\Controllers\Orders;

use App\Http\Controllers\Controller;
use App\Models\Orders\Order;
use App\Models\Orders\OrderItem;
use App\Models\Product;
use App\Models\ProductVariant;
use App\Models\ProductVariantValue;
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
            if ($request->expectsJson()) {
                return response()->json([
                    'message' => 'Validation failed',
                    'errors' => $validator->errors()
                ], 400);
            }
            return back()->withErrors($validator)->withInput();
        }

        try {
            $query = Order::with(['customer:id,first_name,last_name', 'orderItems.productVariant.product:id,name', 'orderItems.seller:id,name','shippingAddress','billingAddress'])
                ->when($request->status, fn($q) => $q->where('status', $request->status))
                ->when($request->payment_status, fn($q) => $q->where('payment_status', $request->payment_status))
                ->when($request->fulfillment_status, fn($q) => $q->where('fulfillment_status', $request->fulfillment_status))
                ->when($request->customer_id, fn($q) => $q->where('customer_id', $request->customer_id))
                ->when($request->order_code, fn($q) => $q->where('order_code', 'like', "%{$request->order_code}%"))
                ->when($request->date_from, fn($q) => $q->whereDate('created_at', '>=', $request->date_from))
                ->when($request->date_to, fn($q) => $q->whereDate('created_at', '<=', $request->date_to));

            // Sorting
            $sortBy = $request->get('sort_by', 'created_at');
            $sortOrder = $request->get('sort_order', 'desc');
            $query->orderBy($sortBy, $sortOrder);

            // Pagination
            $perPage = $request->get('per_page', 15);
            $orders = $query->paginate($perPage)->withQueryString();

            // API Response
            if ($request->expectsJson()) {
                return response()->json([
                    'message' => 'Orders retrieved successfully',
                    'data' => $orders->items(),
                    'pagination' => [
                        'current_page' => $orders->currentPage(),
                        'per_page' => $orders->perPage(),
                        'total' => $orders->total(),
                        'last_page' => $orders->lastPage(),
                        'has_more_pages' => $orders->hasMorePages()
                    ]
                ]);
            }

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

        } catch (\Exception $e) {
            if ($request->expectsJson()) {
                if ($request->expectsJson()) {
                    return response()->json([
                        'message' => 'Failed to retrieve orders',
                        'error' => config('app.debug') ? $e->getMessage() : null
                    ], 500);
                }
                return back()->with('error', 'Failed to retrieve orders');
            }
        }
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
            'customer_id' => 'required|integer|exists:customers,id',
            'items' => 'required|array|min:1',
            'items.*.product_variant_id' => 'required|integer|exists:product_variants,id',
            'items.*.quantity' => 'required|integer|min:1',
            'items.*.unit_price' => 'required|numeric|min:0',
//            'billing_address' => 'required|array',
//            'billing_address.name' => 'required|string|max:255',
//            'billing_address.address' => 'required|string',
//            'billing_address.city' => 'required|string|max:100',
//            'billing_address.postal_code' => 'nullable|string|max:20',
//            'billing_address.country' => 'required|string|max:50',
            'billing_address_id'=>'sometimes:exists:customer_address,id',
            'shipping_address_id'=>'sometimes:exists:customer_address,id',
            'currency' => 'sometimes|string|size:3|in:KES,USD,EUR',
            'tax_amount' => 'sometimes|numeric|min:0',
            'shipping_amount' => 'sometimes|numeric|min:0',
            'discount_amount' => 'sometimes|numeric|min:0',
            'coupon_code' => 'sometimes|string|max:50',
            'notes' => 'sometimes|string|max:1000',
            'source' => 'sometimes|string|in:web,mobile,api,admin',
            'channel' => 'sometimes|string|max:50',
            'metadata' => 'sometimes|array'
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
            // Calculate totals
            $subtotal = 0;
            foreach ($request->items as $item) {
                $subtotal += $item['quantity'] * $item['unit_price'];
            }

            $taxAmount = $request->get('tax_amount', 0);
            $shippingAmount = $request->get('shipping_amount', 0);
            $discountAmount = $request->get('discount_amount', 0);
            $totalAmount = $subtotal + $taxAmount + $shippingAmount - $discountAmount;

            // Create order
            $order = Order::create([
                'order_code' => $this->generateOrderCode(),
                'customer_id' => $request->customer_id,
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
                'coupon_code' => $request->get('coupon_code'),
                'metadata' => $request->get('metadata', [])
            ]);

            // Create order items
            foreach ($request->items as $item) {
                $productVariant = ProductVariant::find($item['product_variant_id']);

                if (!$productVariant) {
                    return response()->json([
                        'success' => false,
                        'status' => 'error',
                        'message' => 'Product variant not found',
                    ]);
                }
                $product = Product::find($productVariant->product_id);
                if (!$product) {
                    return response()->json([
                        'success' => false,
                        'status' => 'error',
                        'message' => 'Product not found',
                    ]);
                }
                if ($item['quantity']>$productVariant->stock) {
                    $productname=$productVariant->product->name;
                    DB::rollBack();
                    return response()->json([
                        "message" => "Item $productname out of stock. Remaining stock: $productVariant->stock",
                        "success"=> false
                    ]);
                }

                $totalPrice = $item['quantity'] * $item['unit_price'];

                OrderItem::create([
                    'ulid' => Str::ulid(),
                    'order_id' => $order->id,
                    'product_variant_id' => $item['product_variant_id'],
                    'seller_id' => $product->owner_id,
                    'quantity' => $item['quantity'],
                    'unit_price' => $item['unit_price'],
                    'total_price' => $totalPrice,
                    'discount_amount' => 0,
                    'tax_amount' => 0,
                    'product_snapshot' => [
                        'id' => $product->id,
                        'sku' => $productVariant->sku,
                        'current_stock' => $productVariant->stock,
                    ]
                ]);
            }

            DB::commit();

            $order->load(['orderItems.product:id,name', 'orderItems.seller:id,name']);

            // API Response
            if ($request->expectsJson()) {
                return response()->json([
                    'message' => 'Order created successfully',
                    'data' => $order
                ], 201);
            }

            // Inertia Response
            return redirect()->route('orders.show', $order)
                ->with('success', 'Order created successfully');

        } catch (\Exception $e) {
            DB::rollBack();

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
    public function show(Request $request, Order $order)
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

            $order->load(['customer', 'orderItems.product', 'orderItems.seller']);

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
}
