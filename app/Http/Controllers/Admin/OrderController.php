<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Mail\DeliveryOrderAssigned;
use App\Models\Orders\Delivery;
use App\Models\Orders\Order;
use App\Models\User;
use App\Models\Warehouse\Warehouse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Mail;
use Inertia\Inertia;

class OrderController extends Controller
{
    /**
     * Display a single order with its items, including product variant, quantity, and seller.
     */
    public function show(Order $order)
    {
        // Eager-load relations to avoid N+1
        $order->load([
            'customer.defaultAddress',
            'orderItems.productVariant' => fn ($q) => $q->with(['product' => fn ($pq) => $pq->withoutGlobalScopes(), 'values']),
            'orderItems.seller:id,name',
            'shippingAddress.pickupWarehouse' => fn ($q) => $q->withoutGlobalScopes(),
            'billingAddress',
            'delivery.deliveryUser:id,name,email',
            'delivery.pickupWarehouse',
        ]);

        $shippingAddress = $order->shippingAddress ?: $order->customer?->getDefaultAddress();
        $billingAddress = $order->billingAddress ?: $order->customer?->getDefaultAddress();

        // Transform payload for frontend simplicity
        $payload = [
            'id' => $order->id,
            'ulid' => $order->ulid,
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
                        'name' => $item->seller->name,
                    ] : null,
                ];
            })->toArray(),
            'rider_id' => $order->delivery?->deliveryUser?->id,
            'delivery_id' => $order->delivery?->delivery_id,
            'delivery_type' => $order->delivery?->delivery_type ?? 'customer_address',
            'pickup_warehouse_id' => $order->delivery?->pickup_warehouse_id,
            'dispatching_warehouse_id' => $order->delivery?->dispatching_warehouse_id,
            'pickup_warehouse' => $order->delivery?->pickupWarehouse ? [
                'id' => $order->delivery->pickupWarehouse->id,
                'name' => $order->delivery->pickupWarehouse->name,
                'address' => $order->delivery->pickupWarehouse->address,
                'location' => $order->delivery->pickupWarehouse->location,
            ] : null,
            'customer_selected_pickup_warehouse' => $order->shippingAddress?->pickup_warehouse_id && $order->shippingAddress?->pickupWarehouse ? [
                'id' => $order->shippingAddress->pickupWarehouse->id,
                'name' => $order->shippingAddress->pickupWarehouse->name,
                'address' => $order->shippingAddress->pickupWarehouse->address,
                'location' => $order->shippingAddress->pickupWarehouse->location,
            ] : null,
            'delivery_assignment_status' => $order->delivery?->assignment_status,
            'allocated_for_pickup_at' => $order->delivery?->allocated_for_pickup_at?->toDateTimeString(),
            'picked_at' => $order->delivery?->picked_at?->toDateTimeString(),
            'payment_method' => $order->payment_method ?? null,
            'delivery_note_summary' => $order->getDeliveryNoteSummary(),
            'assigned_rider' => $order->delivery?->deliveryUser ? [
                'id' => $order->delivery->deliveryUser->id,
                'name' => $order->delivery->deliveryUser->name,
                'email' => $order->delivery->deliveryUser->email,
            ] : null,
        ];

        $deliveryUsers = User::whereHas('roles', fn ($q) => $q->where('name', 'delivery'))
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

        return Inertia::render('Admin/Orders/Show', [
            'order' => $payload,
            'riders' => $deliveryUsers,
            'delivery_users' => $deliveryUsers,
            'warehouses' => $warehouses,
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
        $request->validate([
            'delivery_id' => 'required|exists:users,id',
            'delivery_type' => 'required|in:customer_address,pickup_point',
            'pickup_warehouse_id' => 'required_if:delivery_type,pickup_point|nullable|exists:warehouses,id',
            'dispatching_warehouse_id' => 'nullable|exists:warehouses,id',
        ]);

        $deliveryUser = User::whereHas('roles', fn ($q) => $q->where('name', 'delivery'))
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
}
