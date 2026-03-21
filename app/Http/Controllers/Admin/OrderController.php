<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Mail\DeliveryOrderAssigned;
use App\Models\Orders\Delivery;
use App\Models\Orders\Order;
use App\Models\Orders\OrderItem;
use App\Models\User;
use App\Models\Warehouse\Warehouse;
use App\Models\Warehouse\WarehouseReceivable;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Mail;
use Inertia\Inertia;

class OrderController extends Controller
{
    /**
     * Display a single order with its items, including product variant, quantity, and seller.
     */
    public function show(Order $order)
    {
        // Get currently logged-in user
        $user = auth()->user();
        $isSeller = $user?->hasPortalRole('seller');
        $sellerIds = $isSeller ? $user->sellers->pluck('id')->toArray() : [];

        // Eager-load relations to avoid N+1
        $order->load([
            'customer.defaultAddress',
            'orderItems' => function ($q) use ($isSeller, $sellerIds) {
                if ($isSeller) {
                    $q->whereIn('seller_id', $sellerIds);
                }
                $q->with([
                    'productVariant' => fn ($pq) => $pq->with(['product' => fn ($ppq) => $ppq->withoutGlobalScopes(), 'values']),
                    'seller:id,company_legal_name',
                    'dispatchCenter:id,name,latitude,longitude,address'
                ]);
            },
            'shippingAddress.pickupWarehouse' => fn ($q) => $q->withoutGlobalScopes(),
            'billingAddress',
            'delivery.deliveryUser:id,name,email',
            'delivery.pickupWarehouse',
        ]);

        if ($isSeller) {
            $order->subtotal = $order->orderItems->sum('total_price');
            $order->tax_amount = $order->orderItems->sum('tax_amount');
            // If we have shipping/discount per item or per seller, we could use that.
            // For now, setting them to zero if not specifically for this seller.
            $order->shipping_amount = 0;
            $order->discount_amount = 0;
            $order->total_amount = $order->subtotal + $order->tax_amount;
        }

        $shippingAddress = $order->shippingAddress ?: $order->customer?->getDefaultAddress();
        $billingAddress = $order->billingAddress ?: $order->customer?->getDefaultAddress();

        // Check if all deliverable items are received (declined/rejected items are excluded from delivery)
        $all_items_received = false;
        if (!$isSeller && $order->orderItems->count() > 0) {
            $deliverableItems = $order->orderItems->whereNotIn('dispatch_status', [
                OrderItem::DISPATCH_STATUS_DECLINED,
                OrderItem::DISPATCH_STATUS_REJECTED,
            ]);
            $all_items_received = $deliverableItems->isNotEmpty() && $deliverableItems->every(
                fn($item) => $item->dispatch_status === OrderItem::DISPATCH_STATUS_RECEIVED
            );
        }

        // Transform payload for frontend simplicity
        $payload = [
            'id' => $order->id,
            'ulid' => $order->ulid,
            'is_seller' => $isSeller,
            'all_items_received' => $all_items_received,
            'order_code' => $order->order_code,
            'status' => $order->status,
            'payment_status' => $order->payment_status,
            'fulfillment_status' => $order->fulfillment_status,
            'currency' => $order->currency,
            'subtotal' => (float) $order->subtotal,
            'tax_amount' => (float) $order->tax_amount,
            'shipping_amount' => (float) $order->shipping_amount,
            'discount_amount' => (float) $order->discount_amount,
            'total_amount' => (float) $order->total_amount,
            'created_at' => optional($order->created_at)?->toDateTimeString(),
            'customer' => $order->customer ? [
                'id' => $order->customer->id,
                'first_name' => $order->customer->first_name,
                'last_name' => $order->customer->last_name,
                'email' => $order->customer->email ?? null,
            ] : null,
            'shipping_address' => ($shippingAddress && !$isSeller) ? [
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
            'billing_address' => ($billingAddress && !$isSeller) ? [
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
            'order_items' => $order->orderItems->map(function ($item) {
                $variant = $item->productVariant;
                $product = $variant?->product;
                // Variant display name: product name + variant values, or SKU fallback
                $variantDisplay = $variant
                    ? ($variant->display_name !== 'Unnamed Variant' ? $variant->display_name : ($variant->sku ?: '—'))
                    : '—';

                return [
                    'id' => $item->id,
                    'quantity' => (int) $item->quantity,
                    'unit_price' => (float) $item->unit_price,
                    'total_price' => (float) $item->total_price,
                    'dispatch_status' => $item->dispatch_status,
                    'dispatch_center' => $item->dispatchCenter ? [
                        'id' => $item->dispatchCenter->id,
                        'name' => $item->dispatchCenter->name,
                        'latitude' => $item->dispatchCenter->latitude,
                        'longitude' => $item->dispatchCenter->longitude,
                        'address' => $item->dispatchCenter->address,
                    ] : null,
                    'dispatch_decline_reason' => $item->dispatch_decline_reason,
                    'rejection_reason' => $item->rejection_reason,
                    'product' => $product ? [
                        'id' => $product->id,
                        'name' => $product->name,
                    ] : null,
                    'variant' => $variant ? [
                        'id' => $variant->id,
                        'sku' => $variant->sku,
                        'label' => $variantDisplay,
                    ] : null,
                    'seller' => $item->seller ? [
                        'id' => $item->seller->id,
                        'name' => $item->seller->company_legal_name ?: 'Unknown Seller',
                    ] : [
                        'id' => 0,
                        'name' => config('app.name', 'System'),
                    ],
                ];
            })->toArray(),
            'rider_id' => $order->delivery?->deliveryUser?->id,
            'delivery_id' => $order->delivery?->delivery_id,
            'delivery_type' => $order->delivery?->delivery_type ?? 'customer_address',
            'pickup_warehouse_id' => $order->delivery?->pickup_warehouse_id,
            'dispatching_warehouse_id' => $order->delivery?->dispatching_warehouse_id,
            'pickup_warehouse' => (!$isSeller && $order->delivery?->pickupWarehouse) ? [
                'id' => $order->delivery->pickupWarehouse->id,
                'name' => $order->delivery->pickupWarehouse->name,
                'address' => $order->delivery->pickupWarehouse->address,
                'location' => $order->delivery->pickupWarehouse->location,
            ] : null,
            'customer_selected_pickup_warehouse' => (!$isSeller && $order->shippingAddress?->pickup_warehouse_id && $order->shippingAddress?->pickupWarehouse) ? [
                'id' => $order->shippingAddress->pickupWarehouse->id,
                'name' => $order->shippingAddress->pickupWarehouse->name,
                'address' => $order->shippingAddress->pickupWarehouse->address,
                'location' => $order->shippingAddress->pickupWarehouse->location,
            ] : null,
            'delivery_assignment_status' => !$isSeller ? $order->delivery?->assignment_status : null,
            'allocated_for_pickup_at' => !$isSeller ? $order->delivery?->allocated_for_pickup_at?->toDateTimeString() : null,
            'picked_at' => !$isSeller ? $order->delivery?->picked_at?->toDateTimeString() : null,
            'payment_method' => $order->payment_method ?? null,
            'delivery_note_summary' => !$isSeller ? $order->getDeliveryNoteSummary() : null,
            'assigned_rider' => (!$isSeller && $order->delivery?->deliveryUser) ? [
                'id' => $order->delivery->deliveryUser->id,
                'name' => $order->delivery->deliveryUser->name,
                'email' => $order->delivery->deliveryUser->email,
            ] : null,
        ];

        $deliveryUsers = User::whereHas('roles', fn ($q) => $q->where('name', 'delivery'))
            ->whereNull('suspended_at')
            ->where(function ($q) {
                $q->where('status', 'active')->orWhere('status', 1);
            })
            ->orderBy('name')
            ->get(['id', 'name', 'email'])
            ->map(fn ($u) => ['id' => $u->id, 'name' => $u->name, 'email' => $u->email]);

        $warehouses = Warehouse::visibleTo(auth()->user())
            ->where('active', true)
            ->orderBy('name')
            ->get(['id', 'name', 'address', 'location'])
            ->map(fn ($w) => ['id' => $w->id, 'name' => $w->name, 'address' => $w->address, 'location' => $w->location]);

        // Active dispatch centers — bypass WarehouseScope so all dispatch centers are visible regardless of creator
        $dispatchCenters = Warehouse::withoutGlobalScope(\App\Models\Scopes\WarehouseScope::class)->where('type', 'dispatch_center')
            ->where('active', true)
            ->orderBy('name')
            ->get(['id', 'name', 'address', 'location', 'latitude', 'longitude'])
            ->map(fn ($w) => [
                'id'        => $w->id,
                'name'      => $w->name,
                'address'   => $w->address,
                'location'  => $w->location,
                'latitude'  => $w->latitude  ? (float) $w->latitude  : null,
                'longitude' => $w->longitude ? (float) $w->longitude : null,
            ])
            ->values();

        return Inertia::render('Admin/Orders/Show', [
            'order' => $payload,
            'riders' => $deliveryUsers,
            'delivery_users' => $deliveryUsers,
            'warehouses' => $warehouses,
            'dispatch_centers' => $dispatchCenters,
            'googleMapsApiKey' => config('services.google.maps_api_key'),
            'rejectionReasons' => array_map(fn($key, $label) => ['id' => $key, 'name' => $label], array_keys(OrderItem::REJECTION_REASONS), OrderItem::REJECTION_REASONS),
            'declineReasons' => array_map(fn($key, $label) => ['id' => $key, 'name' => $label], array_keys(OrderItem::DECLINE_REASONS), OrderItem::DECLINE_REASONS),
            'breadcrumbs' => [
                ['title' => 'Dashboard', 'href' => '/admin/dashboard'],
                ['title' => 'Orders', 'href' => '/admin/orders'],
                ['title' => 'Order ' . $order->order_code, 'href' => '/admin/orders/' . $order->getRouteKey()],
            ],
        ]);
    }

    /**
     * Assign a delivery person to the order. Sends email to the assigned person.
     */
    public function assignDelivery(Request $request, Order $order)
    {
        // Declined/rejected items are excluded from delivery — only check deliverable items
        $deliverableItems = $order->orderItems->whereNotIn('dispatch_status', [
            OrderItem::DISPATCH_STATUS_DECLINED,
            OrderItem::DISPATCH_STATUS_REJECTED,
        ]);

        if ($deliverableItems->isEmpty()) {
            if ($request->expectsJson()) {
                return response()->json(['message' => 'Cannot assign delivery: all items have been declined or rejected.'], 422);
            }
            return back()->with('error', 'Cannot assign delivery: all items have been declined or rejected.');
        }

        $all_received = $deliverableItems->every(fn($item) => $item->dispatch_status === OrderItem::DISPATCH_STATUS_RECEIVED);

        if (!$all_received) {
            if ($request->expectsJson()) {
                return response()->json([
                    'message' => 'Cannot assign delivery until all remaining items are confirmed received from sellers.'
                ], 422);
            }
            return back()->with('error', 'Cannot assign delivery until all remaining items are confirmed received from sellers.');
        }

        $request->validate([
            'delivery_id' => 'required|exists:users,id',
            'delivery_type' => 'required|in:customer_address,pickup_point',
            'pickup_warehouse_id' => 'required_if:delivery_type,pickup_point|nullable|exists:warehouses,id',
            'dispatching_warehouse_id' => 'nullable|exists:warehouses,id',
        ]);

        $deliveryUser = User::whereHas('roles', fn ($q) => $q->where('name', 'delivery'))
            ->whereNull('suspended_at')
            ->where(function ($q) {
                $q->where('status', 'active')->orWhere('status', 1);
            })
            ->findOrFail($request->delivery_id);

        $deliveryType = $request->delivery_type;
        $pickupWarehouseId = $deliveryType === 'pickup_point' ? $request->pickup_warehouse_id : null;
        $dispatchingWarehouseId = $request->dispatching_warehouse_id;

        Delivery::updateOrCreate(
            ['order_id' => $order->id],
            [
                'delivery_id' => $deliveryUser->id,
                'delivery_type' => $deliveryType,
                'pickup_warehouse_id' => $pickupWarehouseId,
                'dispatching_warehouse_id' => $dispatchingWarehouseId,
                'assignment_status' => 'pending',
                'rejection_reason' => null,
                'allocated_for_pickup_at' => null,
                'picked_at' => null,
            ]
        );
        $order->load('delivery');
        $order->createReceivablesForPickupPoint();
        $order->update([
            'status' => 'confirmed',
            'confirmed_at' => $order->confirmed_at ?? now(),
        ]);

        $deliveryNoteSummary = $order->getDeliveryNoteSummary();
        $loginUrl = route('delivery.login');

        Mail::to($deliveryUser->email)->send(new DeliveryOrderAssigned(
            $order,
            $deliveryUser,
            $deliveryNoteSummary,
            $loginUrl
        ));

        return back()->with('success', 'Delivery person assigned and notified by email.');
    }

    /**
     * Change the delivery person (admins/sellers). Optionally notify the new person.
     */
    public function changeDelivery(Request $request, Order $order)
    {
        $request->validate([
            'delivery_id' => 'required|exists:users,id',
            'delivery_type' => 'required|in:customer_address,pickup_point',
            'pickup_warehouse_id' => 'required_if:delivery_type,pickup_point|nullable|exists:warehouses,id',
            'dispatching_warehouse_id' => 'nullable|exists:warehouses,id',
        ]);

        $deliveryUser = User::whereHas('roles', fn ($q) => $q->where('name', 'delivery'))
            ->whereNull('suspended_at')
            ->where(function ($q) {
                $q->where('status', 'active')->orWhere('status', 1);
            })
            ->findOrFail($request->delivery_id);

        $deliveryType = $request->delivery_type;
        $pickupWarehouseId = $deliveryType === 'pickup_point' ? $request->pickup_warehouse_id : null;
        $dispatchingWarehouseId = $request->dispatching_warehouse_id;

        $delivery = $order->delivery;
        if (! $delivery) {
            Delivery::create([
                'order_id' => $order->id,
                'delivery_id' => $deliveryUser->id,
                'delivery_type' => $deliveryType,
                'pickup_warehouse_id' => $pickupWarehouseId,
                'dispatching_warehouse_id' => $dispatchingWarehouseId,
                'assignment_status' => 'pending',
                'rejection_reason' => null,
            ]);
        } else {
            $delivery->update([
                'delivery_id' => $deliveryUser->id,
                'delivery_type' => $deliveryType,
                'pickup_warehouse_id' => $pickupWarehouseId,
                'dispatching_warehouse_id' => $dispatchingWarehouseId,
                'assignment_status' => 'pending',
                'rejection_reason' => null,
                'allocated_for_pickup_at' => null,
                'picked_at' => null,
            ]);
        }
        $order->load('delivery');
        $order->createReceivablesForPickupPoint();
        $order->update([
            'status' => 'confirmed',
            'confirmed_at' => $order->confirmed_at ?? now(),
        ]);

        $deliveryNoteSummary = $order->getDeliveryNoteSummary();
        $loginUrl = route('delivery.login');

        Mail::to($deliveryUser->email)->send(new DeliveryOrderAssigned(
            $order,
            $deliveryUser,
            $deliveryNoteSummary,
            $loginUrl
        ));

        return back()->with('success', 'Delivery person updated and notified by email.');
    }

    /**
     * Allocate the order for pickup so the delivery person can confirm items and mark as picked for delivery.
     */
    public function allocateForPickup(Order $order)
    {
        $delivery = $order->delivery;
        if (!$delivery) {
            return back()->with('error', 'No delivery assigned to this order.');
        }
        if ($delivery->assignment_status !== 'accepted') {
            return back()->with('error', 'Delivery person must accept the assignment first.');
        }
        if (!in_array($order->status, ['processing', 'confirmed'], true)) {
            return back()->with('error', 'Order must be in processing (or confirmed) to allocate for pickup.');
        }

        $delivery->update(['allocated_for_pickup_at' => now()]);

        return back()->with('success', 'Order allocated for pickup. Delivery person can confirm items and mark as picked.');
    }

    /**
     * Return delivery note content as plain text (for display or download).
     */
    public function deliveryNote(Order $order)
    {
        $order->load(['shippingAddress', 'customer']);
        $summary = $order->getDeliveryNoteSummary();
        $address = $order->shippingAddress ?? $order->customer?->defaultAddress;

        return response()->json([
            'order_code' => $order->order_code,
            'delivery_note' => $summary,
            'shipping_address' => $address ? [
                'first_name' => $address->first_name,
                'last_name' => $address->last_name,
                'address_line_1' => $address->address_line_1,
                'address_line_2' => $address->address_line_2,
                'city' => $address->city,
                'postal_code' => $address->postal_code,
                'country' => $address->country_name ?? $address->country_code,
                'phone' => $address->phone,
            ] : null,
            'total_amount' => $order->total_amount,
            'currency' => $order->currency,
        ]);
    }
    /**
     * Mark an order item as received by the admin at the dispatch center.
     */
    public function receiveItem(Request $request, Order $order, OrderItem $item)
    {
        if ($item->order_id !== $order->id) {
            abort(404);
        }

        if ($item->dispatch_status !== OrderItem::DISPATCH_STATUS_DISPATCHED && $item->dispatch_status !== OrderItem::DISPATCH_STATUS_REJECTED) {
            return back()->with('error', 'Item must be dispatched before it can be received.');
        }

        DB::transaction(function () use ($item) {
            $item->update([
                'dispatch_status' => OrderItem::DISPATCH_STATUS_RECEIVED,
                'received_at'     => now(),
                'received_by'     => auth()->id(),
                'rejection_reason' => null,
                'rejected_at'     => null,
                'rejected_by'     => null,
            ]);

            // Sync the warehouse receivable so dispatch center records stay consistent
            WarehouseReceivable::where('warehouse_id', $item->dispatch_center_id)
                ->where('order_id', $item->order_id)
                ->where('product_variant_id', $item->product_variant_id)
                ->where('status', 'pending')
                ->update([
                    'status'      => 'received',
                    'received_by' => auth()->id(),
                    'received_at' => now(),
                ]);
        });

        return back()->with('success', 'Item confirmed as received.');
    }

    /**
     * Reject an order item at the dispatch center.
     */
    public function rejectItem(Request $request, Order $order, OrderItem $item, \App\Services\ProfanityFilterService $profanityFilter)
    {
        if ($item->order_id !== $order->id) {
            abort(404);
        }

        $request->validate([
            'reason' => 'required|string|in:' . implode(',', array_keys(OrderItem::REJECTION_REASONS)),
            'other_reason' => 'nullable|string|max:500',
        ]);

        if ($request->reason === 'other') {
            if (empty($request->other_reason)) {
                return back()->withErrors(['other_reason' => 'Please provide a reason.']);
            }
            if ($profanityFilter->isAbusive($request->other_reason)) {
                return back()->withErrors(['other_reason' => 'Your reason contains prohibited words. Please use professional language.']);
            }
        }

        $reasonLabel = OrderItem::REJECTION_REASONS[$request->reason];
        if ($request->reason === 'other') {
            $reasonLabel = 'Other: ' . $request->other_reason;
        }

        DB::transaction(function () use ($item, $reasonLabel) {
            $item->update([
                'dispatch_status'  => OrderItem::DISPATCH_STATUS_REJECTED,
                'rejection_reason' => $reasonLabel,
                'rejected_at'      => now(),
                'rejected_by'      => auth()->id(),
                'received_at'      => null,
                'received_by'      => null,
            ]);

            // Sync the warehouse receivable so dispatch center records stay consistent
            WarehouseReceivable::where('warehouse_id', $item->dispatch_center_id)
                ->where('order_id', $item->order_id)
                ->where('product_variant_id', $item->product_variant_id)
                ->where('status', 'pending')
                ->update([
                    'status'          => 'rejected',
                    'rejected_by'     => auth()->id(),
                    'rejected_at'     => now(),
                    'rejected_reason' => $reasonLabel,
                ]);
        });

        // Send email to seller
        $sellerEmail = $item->seller?->email ?? $item->seller?->user?->email;
        if ($sellerEmail) {
            Mail::to($sellerEmail)->send(new \App\Mail\OrderItemRejectedMail($item, $request->reason));
        }

        return back()->with('success', 'Item rejected.');
    }

    /**
     * Confirm a system-owned item is available at the dispatch center.
     */
    public function confirmItemAvailable(Request $request, Order $order, OrderItem $item)
    {
        if ($item->order_id !== $order->id) {
            abort(404);
        }

        // Only for system items (seller_id is null)
        if ($item->seller_id !== null) {
            return back()->with('error', 'Only system items can be confirmed available directly.');
        }

        $item->update([
            'dispatch_status' => OrderItem::DISPATCH_STATUS_RECEIVED,
            'received_at' => now(),
            'received_by' => auth()->id(),
        ]);

        return back()->with('success', 'Item confirmed as available.');
    }
}
