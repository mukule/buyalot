<?php

namespace App\Http\Controllers\Delivery;

use App\Http\Controllers\Controller;
use App\Mail\OrderReadyForPickup;
use App\Mail\OrderReturnRaisedNotifyDispatch;
use App\Http\DTOs\PaymentRequest;
use App\Models\Orders\CodReconciliation;
use App\Models\Orders\Order;
use App\Models\Orders\OrderReturn;
use App\Models\Orders\OrderReturnItem;
use App\Models\Payment\Payment;
use App\Models\Payment\PaymentStatus;
use App\Services\PaymentService;
use App\Models\User;
use App\Models\Warehouse\WarehouseInventoryMovement;
use App\Models\Warehouse\WarehouseManager;
use App\Models\Warehouse\WarehouseProductInventory;
use App\Models\Warehouse\WarehouseReceivable;
use App\Notifications\OrderReadyForPickupNotification;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Mail;
use Inertia\Inertia;

class DeliveryController extends Controller
{
    /**
     * Delivery dashboard: stats and lists by section (pending, accepted, rejected, attended).
     */
    public function dashboard(Request $request)
    {
        $userId = $request->user()->id;
        $baseQuery = Order::assignedToDelivery($userId);

        $pendingCount = (clone $baseQuery)->deliveryAssignmentPending()
            ->whereNotIn('status', ['delivered', 'cancelled'])->count();
        $acceptedCount = (clone $baseQuery)->deliveryAssignmentAccepted()
            ->whereNotIn('status', ['delivered', 'cancelled'])->count();
        $rejectedCount = (clone $baseQuery)->deliveryRejected()->count();
        $attendedCount = (clone $baseQuery)
            ->whereIn('status', ['delivered', 'cancelled'])->count();
        $totalCount = (clone $baseQuery)->count();

        $pending = Order::with([
            'customer:id,first_name,last_name',
            'shippingAddress',
            'delivery.pickupWarehouse',
            'orderItems.productVariant.product:id,name',
        ])
            ->assignedToDelivery($userId)
            ->deliveryAssignmentPending()
            ->whereNotIn('status', ['delivered', 'cancelled'])
            ->orderByDesc('created_at')
            ->get()
            ->map(fn ($o) => $this->formatOrderForDelivery($o));

        $accepted = Order::with([
            'customer:id,first_name,last_name',
            'shippingAddress',
            'delivery.pickupWarehouse',
            'delivery.dispatchingWarehouse',
            'codReconciliation',
            'orderItems.productVariant.product:id,name',
        ])
            ->assignedToDelivery($userId)
            ->deliveryAssignmentAccepted()
            ->whereNotIn('status', ['delivered', 'cancelled'])
            ->orderByDesc('created_at')
            ->get()
            ->map(fn ($o) => $this->formatOrderForDelivery($o));

        $rejected = Order::with([
            'customer:id,first_name,last_name',
            'shippingAddress',
            'delivery',
        ])
            ->assignedToDelivery($userId)
            ->deliveryRejected()
            ->orderByDesc('updated_at')
            ->limit(50)
            ->get()
            ->map(fn ($o) => $this->formatOrderForDelivery($o));

        $attended = Order::with([
            'customer:id,first_name,last_name',
            'shippingAddress',
            'delivery.pickupWarehouse',
            'delivery.dispatchingWarehouse',
            'codReconciliation',
            'orderItems.productVariant.product:id,name',
        ])
            ->assignedToDelivery($userId)
            ->whereIn('status', ['delivered', 'cancelled'])
            ->orderByDesc('updated_at')
            ->limit(50)
            ->get()
            ->map(fn ($o) => $this->formatOrderForDelivery($o));

        $section = $request->get('section', 'dashboard');

        return Inertia::render('Delivery/Dashboard', [
            'section' => $section,
            'stats' => [
                'pending_count' => $pendingCount,
                'accepted_count' => $acceptedCount,
                'rejected_count' => $rejectedCount,
                'attended_count' => $attendedCount,
                'total_count' => $totalCount,
            ],
            'pending' => $pending,
            'accepted' => $accepted,
            'rejected' => $rejected,
            'attended' => $attended,
        ]);
    }

    /**
     * Accept an assigned delivery.
     */
    public function accept(Request $request, Order $order)
    {
        $delivery = $order->delivery;
        if (!$delivery || (int) $delivery->delivery_id !== (int) $request->user()->id) {
            abort(403, 'This delivery is not assigned to you.');
        }
        if ($delivery->assignment_status !== 'pending') {
            return back()->with('error', 'This assignment is no longer pending.');
        }

        $delivery->update([
            'assignment_status' => 'accepted',
            'rejection_reason' => null,
        ]);
        $order->update(['status' => 'processing']);

        return back()->with('success', 'Delivery accepted.');
    }

    /**
     * Reject an assigned delivery with a reason.
     */
    public function reject(Request $request, Order $order)
    {
        $request->validate([
            'reason' => 'required|string|max:1000',
        ]);

        $delivery = $order->delivery;
        if (!$delivery || (int) $delivery->delivery_id !== (int) $request->user()->id) {
            abort(403, 'This delivery is not assigned to you.');
        }
        if ($delivery->assignment_status !== 'pending') {
            return back()->with('error', 'This assignment is no longer pending.');
        }

        $delivery->update([
            'assignment_status' => 'rejected',
            'rejection_reason' => $request->reason,
        ]);

        return back()->with('success', 'Delivery rejected. Admin may assign another person.');
    }

    /**
     * Confirm items match delivery note and mark order as picked for delivery (status = out_for_delivery).
     */
    public function confirmPickedForDelivery(Request $request, Order $order)
    {
        $delivery = $order->delivery;
        if (!$delivery || (int) $delivery->delivery_id !== (int) $request->user()->id) {
            abort(403, 'This delivery is not assigned to you.');
        }
        if ($delivery->assignment_status !== 'accepted') {
            return back()->with('error', 'You must have accepted this delivery first.');
        }
        if (!$delivery->allocated_for_pickup_at) {
            return back()->with('error', 'Admin/seller must allocate this order for pickup first.');
        }
        if ($delivery->picked_at) {
            return back()->with('error', 'Already marked as picked for delivery.');
        }

        $delivery->update(['picked_at' => now()]);
        $order->update(['status' => 'out_for_delivery']);

        return back()->with('success', 'Order marked as picked for delivery.');
    }

    /**
     * Receive order at pickup point: counter-check with delivery note, mark receivables as received,
     * set order status to shipped, and notify customer that order is ready for pickup.
     */
    public function receiveAtPickupPoint(Request $request, Order $order)
    {
        $delivery = $order->delivery;
        if (! $delivery || (int) $delivery->delivery_id !== (int) $request->user()->id) {
            abort(403, 'This delivery is not assigned to you.');
        }
        if ($delivery->delivery_type !== 'pickup_point' || ! $delivery->pickup_warehouse_id) {
            return back()->with('error', 'This order is not a pickup point delivery.');
        }
        if (! in_array($order->status, ['out_for_delivery', 'processing'], true)) {
            return back()->with('error', 'Order status does not allow receive at pickup.');
        }

        $receivables = WarehouseReceivable::where('order_id', $order->id)
            ->where('warehouse_id', $delivery->pickup_warehouse_id)
            ->whereNull('order_return_id')
            ->where('status', 'pending')
            ->get();

        if ($receivables->isEmpty()) {
            return back()->with('error', 'No pending receivables found for this order at the pickup point.');
        }

        $warehouseId = $delivery->pickup_warehouse_id;
        $userId = $request->user()->id;

        DB::transaction(function () use ($receivables, $warehouseId, $userId) {
            foreach ($receivables as $r) {
                $receivable = WarehouseReceivable::lockForUpdate()->where('id', $r->id)->firstOrFail();
                if ($receivable->status !== 'pending') {
                    continue;
                }
                $productVariantId = $receivable->product_variant_id;
                $quantity = (int) $receivable->quantity;

                $inv = WarehouseProductInventory::lockForUpdate()->firstOrCreate([
                    'warehouse_id' => $warehouseId,
                    'product_variant_id' => $productVariantId,
                ], [
                    'stock' => 0,
                    'reserved_stock' => 0,
                    'damaged_stock' => 0,
                    'cost_price' => 0,
                ]);

                $before = $inv->stock;
                $inv->stock += $quantity;
                $inv->save();

                WarehouseInventoryMovement::create([
                    'warehouse_id' => $warehouseId,
                    'product_variant_id' => $productVariantId,
                    'type' => 'receive',
                    'quantity' => $quantity,
                    'user_id' => $userId,
                    'before_stock' => $before,
                    'after_stock' => $inv->stock,
                    'note' => 'Order receive at pickup point',
                ]);

                $receivable->update([
                    'status' => 'received',
                    'received_by' => $userId,
                    'received_at' => now(),
                ]);
            }
        });

        $order->update([
            'status' => 'delivered',
            'shipped_at' => $order->shipped_at ?? now(),
            'delivered_at' => now(),
        ]);

        $order->load('customer.user');
        $pickupWarehouse = $delivery->pickupWarehouse;
        $customer = $order->customer;
        if ($customer) {
            $pickupName = $pickupWarehouse ? $pickupWarehouse->name : 'Pick up point';
            $pickupAddress = $pickupWarehouse ? ($pickupWarehouse->address ?? $pickupWarehouse->location) : null;
            $pickupDetail = $pickupWarehouse && $pickupWarehouse->location ? $pickupWarehouse->location : null;
            if ($customer->email) {
                Mail::to($customer->email)->send(new OrderReadyForPickup($order, $pickupName, $pickupAddress, $pickupDetail));
            }
            $customer->user?->notify(new OrderReadyForPickupNotification($order, $pickupName, $pickupAddress));
        }

        return back()->with('success', 'Order received at pickup point. Customer has been notified.');
    }

    /**
     * Confirm cash payment received at delivery (COD). Records payment and marks order as paid.
     * After this, delivery person can mark as delivered.
     */
    public function confirmCashPayment(Request $request, Order $order)
    {
        $delivery = $order->delivery;
        if (! $delivery || (int) $delivery->delivery_id !== (int) $request->user()->id) {
            abort(403, 'This delivery is not assigned to you.');
        }
        if ($order->payment_status === 'paid') {
            return back()->with('info', 'Order is already paid.');
        }
        if (! $this->isPaymentMethodCod($order->payment_method)) {
            return back()->with('error', 'This order is not cash on delivery.');
        }
        if (! in_array($order->status, ['out_for_delivery', 'shipped'], true)) {
            return back()->with('error', 'Order status does not allow collecting payment.');
        }

        $reference = 'COD-CASH-' . $order->order_code . '-' . now()->format('YmdHisu');
        Payment::create([
            'payable_type' => Order::class,
            'payable_id' => $order->id,
            'amount' => $order->total_amount,
            'amount_paid' => $order->total_amount,
            'currency' => $order->currency ?? 'KES',
            'provider' => 'cod',
            'method' => 'cash',
            'status' => PaymentStatus::COMPLETED,
            'reference' => $reference,
            'provider_reference' => null,
            'completed_at' => now(),
        ]);
        $order->update(['payment_status' => 'paid']);

        return back()->with('success', 'Cash payment recorded. You can now mark the order as delivered.');
    }

    /**
     * Delivery person marks they have handed over the COD cash to the pickup/dispatch warehouse.
     * Creates a reconciliation record for admin/seller to confirm receipt.
     */
    public function reconcileCashToWarehouse(Request $request, Order $order)
    {
        $delivery = $order->delivery;
        if (! $delivery || (int) $delivery->delivery_id !== (int) $request->user()->id) {
            abort(403, 'This delivery is not assigned to you.');
        }
        if ($order->status !== 'delivered') {
            return back()->with('error', 'Order must be delivered first.');
        }
        if ($order->payment_status !== 'paid') {
            return back()->with('error', 'Order is not paid.');
        }
        if (! $this->isPaymentMethodCod($order->payment_method)) {
            return back()->with('error', 'This order is not cash on delivery.');
        }
        // Only reconcile CASH payments (M-Pesa goes to business directly)
        $cashPayment = $order->payments()
            ->where('provider', 'cod')
            ->where('method', 'cash')
            ->where('status', PaymentStatus::COMPLETED)
            ->latest()
            ->first();
        if (! $cashPayment) {
            return back()->with('error', 'No cash COD payment found for this order. M-Pesa payments do not need reconciliation.');
        }
        if ($order->codReconciliation) {
            return back()->with('info', 'Cash already reconciled for this order.');
        }
        $warehouseId = $delivery->dispatching_warehouse_id ?? $delivery->pickup_warehouse_id;
        if (! $warehouseId) {
            return back()->with('error', 'No pickup warehouse is set for this delivery. Contact admin.');
        }

        CodReconciliation::create([
            'order_id' => $order->id,
            'delivery_id' => $request->user()->id,
            'warehouse_id' => $warehouseId,
            'amount' => $order->total_amount,
            'currency' => $order->currency ?? 'KES',
            'reconciled_at' => now(),
        ]);

        return back()->with('success', 'Cash reconciliation recorded. The warehouse/admin will confirm receipt.');
    }

    /**
     * Initiate M-Pesa STK push for COD order at delivery. Customer receives prompt on their phone.
     */
    public function initiateMpesaForCod(Request $request, Order $order)
    {
        $delivery = $order->delivery;
        if (! $delivery || (int) $delivery->delivery_id !== (int) $request->user()->id) {
            abort(403, 'This delivery is not assigned to you.');
        }
        if ($order->payment_status === 'paid') {
            return response()->json(['success' => true, 'message' => 'Order is already paid.', 'already_paid' => true]);
        }
        if (! $this->isPaymentMethodCod($order->payment_method)) {
            return response()->json(['success' => false, 'message' => 'This order is not cash on delivery.'], 422);
        }
        if (! in_array($order->status, ['out_for_delivery', 'shipped'], true)) {
            return response()->json(['success' => false, 'message' => 'Order status does not allow collecting payment.'], 422);
        }

        $request->validate(['phone' => 'required|string|max:20']);

        $paymentService = app(PaymentService::class);
        $paymentRequest = new PaymentRequest(
            provider: 'mpesa',
            method: 'stk_push',
            amount: (float) $order->total_amount,
            currency: $order->currency ?? 'KES',
            phone: $request->phone,
            metadata: ['order_ulid' => $order->ulid, 'order_code' => $order->order_code],
        );

        $mpesaLog = $paymentService->getOrCreateMpesaRequest($order, $paymentRequest);
        $init = $paymentService->initializePayment($mpesaLog, $paymentRequest);

        if (! $init->success) {
            return response()->json(['success' => false, 'message' => $init->message], 422);
        }

        return response()->json([
            'success' => true,
            'message' => 'M-Pesa prompt sent to customer. Ask them to enter PIN on their phone.',
            'checkout_request_id' => $mpesaLog->checkout_request_id,
        ]);
    }

    /**
     * Mark order as delivered: customer picked up (pickup_point) or delivered to address (customer_address).
     * For COD orders, payment must already be confirmed (cash or M-Pesa) before this is allowed.
     */
    public function markDelivered(Request $request, Order $order)
    {
        $delivery = $order->delivery;
        if (! $delivery || (int) $delivery->delivery_id !== (int) $request->user()->id) {
            abort(403, 'This delivery is not assigned to you.');
        }
        if ($delivery->delivered_at) {
            return back()->with('error', 'Order is already marked as delivered.');
        }

        if ($delivery->delivery_type === 'pickup_point') {
            return back()->with('error', 'Pickup point orders must be marked as delivered by the store/pickup point attendant when the customer collects.');
        }

        if (! in_array($order->status, ['out_for_delivery'], true)) {
            return back()->with('error', 'Order status does not allow marking as delivered.');
        }

        $isCod = $this->isPaymentMethodCod($order->payment_method);
        if ($isCod && $order->payment_status !== 'paid') {
            return back()->with('error', 'Please collect and confirm payment (cash or M-Pesa) before marking as delivered.');
        }

        DB::transaction(function () use ($order, $delivery, $request, $isCod) {
            $delivery->update(['delivered_at' => now()]);
            $order->update([
                'status' => 'delivered',
                'delivered_at' => $order->delivered_at ?? now(),
            ]);

            // Auto-create COD cash reconciliation when marking delivered (cash must be handed over to warehouse)
            if ($isCod && $order->payment_status === 'paid' && ! $order->codReconciliation) {
                $cashPayment = $order->payments()
                    ->where('provider', 'cod')
                    ->where('method', 'cash')
                    ->where('status', PaymentStatus::COMPLETED)
                    ->latest()
                    ->first();
                $warehouseId = $delivery->dispatching_warehouse_id ?? $delivery->pickup_warehouse_id;
                if ($cashPayment && $warehouseId) {
                    CodReconciliation::create([
                        'order_id' => $order->id,
                        'delivery_id' => $request->user()->id,
                        'warehouse_id' => $warehouseId,
                        'amount' => $order->total_amount,
                        'currency' => $order->currency ?? 'KES',
                        'reconciled_at' => now(),
                    ]);
                }
            }
        });

        return back()->with('success', 'Order marked as delivered.');
    }

    /**
     * Raise a return (full or partial) for an order at pickup point. Updates order items and status,
     * creates return receivables at dispatching warehouse, and notifies dispatch.
     */
    public function raiseReturn(Request $request, Order $order)
    {
        $delivery = $order->delivery;
        if (! $delivery || (int) $delivery->delivery_id !== (int) $request->user()->id) {
            abort(403, 'This delivery is not assigned to you.');
        }
        if ($delivery->delivery_type !== 'pickup_point') {
            return back()->with('error', 'Returns are only for pickup point orders.');
        }
        if (! in_array($order->status, ['out_for_delivery', 'shipped', 'processing'], true)) {
            return back()->with('error', 'Order status does not allow raising a return.');
        }

        $reasons = array_keys(OrderReturn::reasonOptions());
        $request->validate([
            'reason' => 'required|string|in:' . implode(',', $reasons),
            'reason_notes' => 'nullable|string|max:2000',
            'is_full_return' => 'required|boolean',
            'items' => 'required_if:is_full_return,false|array',
            'items.*.order_item_id' => 'required_with:items|integer|exists:order_items,id',
            'items.*.quantity_returned' => 'required_with:items|integer|min:1',
        ]);

        $order->load('orderItems');
        $orderItemIds = $order->orderItems->pluck('id')->toArray();
        $isFullReturn = (bool) $request->is_full_return;

        $returnItems = [];
        if ($isFullReturn) {
            foreach ($order->orderItems as $item) {
                $maxReturn = $item->quantity - $item->quantity_returned;
                if ($maxReturn > 0) {
                    $returnItems[] = ['order_item' => $item, 'quantity_returned' => $maxReturn];
                }
            }
        } else {
            foreach ($request->items ?? [] as $row) {
                $item = $order->orderItems->firstWhere('id', (int) $row['order_item_id']);
                if (! $item || ! in_array($item->id, $orderItemIds, true)) {
                    continue;
                }
                $qty = (int) $row['quantity_returned'];
                $maxReturn = $item->quantity - $item->quantity_returned;
                if ($qty > 0 && $qty <= $maxReturn) {
                    $returnItems[] = ['order_item' => $item, 'quantity_returned' => $qty];
                }
            }
        }

        if (empty($returnItems)) {
            return back()->with('error', 'No valid items to return.');
        }

        $orderReturn = DB::transaction(function () use ($order, $delivery, $request, $returnItems, $isFullReturn) {
            $orderReturn = OrderReturn::create([
                'order_id' => $order->id,
                'delivery_id' => $delivery->id,
                'raised_by_type' => OrderReturn::RAISED_BY_DELIVERY_PERSON,
                'raised_by_id' => $request->user()->id,
                'reason' => $request->reason,
                'reason_notes' => $request->reason_notes,
                'is_full_return' => $isFullReturn,
                'status' => OrderReturn::STATUS_PENDING_RECEIVE,
            ]);

            foreach ($returnItems as $row) {
                $item = $row['order_item'];
                $qty = $row['quantity_returned'];
                OrderReturnItem::create([
                    'order_return_id' => $orderReturn->id,
                    'order_item_id' => $item->id,
                    'quantity_returned' => $qty,
                ]);
                $item->increment('quantity_returned', $qty);
            }

            $totalOrdered = $order->orderItems->sum('quantity');
            $totalReturned = $order->orderItems->sum('quantity_returned');
            $newStatus = $totalReturned >= $totalOrdered ? 'returned' : 'partially_returned';
            $order->update(['status' => $newStatus]);

            $dispatchWarehouseId = $delivery->dispatching_warehouse_id;
            if ($dispatchWarehouseId) {
                foreach ($returnItems as $row) {
                    WarehouseReceivable::create([
                        'warehouse_id' => $dispatchWarehouseId,
                        'order_return_id' => $orderReturn->id,
                        'from_warehouse_id' => $delivery->pickup_warehouse_id,
                        'product_variant_id' => $row['order_item']->product_variant_id,
                        'quantity' => $row['quantity_returned'],
                        'status' => 'pending',
                        'note' => 'Return for Order #' . $order->order_code . ' – ' . $request->reason,
                    ]);
                }
            }

            return $orderReturn;
        });

        $reasonLabel = OrderReturn::reasonOptions()[$request->reason] ?? $request->reason;
        $this->notifyDispatchOfReturn($order, $orderReturn, $reasonLabel, $isFullReturn);

        return back()->with('success', 'Return raised. Dispatch has been notified to receive items when they arrive.');
    }

    private function notifyDispatchOfReturn(Order $order, OrderReturn $orderReturn, string $reasonLabel, bool $isFullReturn): void
    {
        $delivery = $order->delivery;
        $emails = [];
        if ($delivery && $delivery->dispatching_warehouse_id) {
            $emails = WarehouseManager::where('warehouse_id', $delivery->dispatching_warehouse_id)
                ->where('active', true)
                ->whereNotNull('email')
                ->pluck('email')
                ->unique()
                ->filter()
                ->values()
                ->toArray();
        }
        if (empty($emails)) {
            $emails = User::role('admin')->pluck('email')->unique()->filter()->values()->toArray();
        }
        if (! empty($emails)) {
            Mail::to($emails)->send(new OrderReturnRaisedNotifyDispatch($order, $orderReturn, $reasonLabel, $isFullReturn));
        }
    }

    private function isPaymentMethodCod(?string $method): bool
    {
        $m = strtolower(str_replace(' ', '_', $method ?? ''));
        return $m === 'cash_on_delivery';
    }

    private function formatOrderForDelivery(Order $order): array
    {
        $shipping = $order->shippingAddress;
        $delivery = $order->delivery;
        $pickupReceivablesStatus = null;
        if ($delivery && $delivery->delivery_type === 'pickup_point' && $delivery->pickup_warehouse_id) {
            $receivables = WarehouseReceivable::where('order_id', $order->id)
                ->where('warehouse_id', $delivery->pickup_warehouse_id)
                ->whereNull('order_return_id')
                ->get();
            if ($receivables->isNotEmpty()) {
                if ($receivables->contains('status', 'rejected')) {
                    $pickupReceivablesStatus = 'rejected';
                } elseif ($receivables->every(fn ($r) => $r->status === 'received')) {
                    $pickupReceivablesStatus = 'received';
                } else {
                    $pickupReceivablesStatus = 'pending';
                }
            }
        }
        return [
            'id' => $order->id,
            'ulid' => $order->ulid,
            'order_code' => $order->order_code,
            'status' => $order->status,
            'payment_status' => $order->payment_status,
            'payment_method' => $order->payment_method,
            'delivery_type' => $delivery?->delivery_type,
            'pickup_warehouse_id' => $delivery?->pickup_warehouse_id,
            'pickup_warehouse' => $delivery?->pickupWarehouse ? [
                'id' => $delivery->pickupWarehouse->id,
                'name' => $delivery->pickupWarehouse->name,
                'address' => $delivery->pickupWarehouse->address,
                'location' => $delivery->pickupWarehouse->location,
                'latitude' => $delivery->pickupWarehouse->latitude ? (float) $delivery->pickupWarehouse->latitude : null,
                'longitude' => $delivery->pickupWarehouse->longitude ? (float) $delivery->pickupWarehouse->longitude : null,
            ] : null,
            'dispatching_warehouse' => $delivery?->dispatchingWarehouse ? [
                'id' => $delivery->dispatchingWarehouse->id,
                'name' => $delivery->dispatchingWarehouse->name,
                'address' => $delivery->dispatchingWarehouse->address,
                'location' => $delivery->dispatchingWarehouse->location,
            ] : null,
            'pickup_receivables_status' => $pickupReceivablesStatus,
            'cod_reconciliation' => $order->codReconciliation ? [
                'id' => $order->codReconciliation->id,
                'status' => $order->codReconciliation->confirmed_at ? 'confirmed' : 'pending_confirmation',
                'reconciled_at' => $order->codReconciliation->reconciled_at?->toDateTimeString(),
                'confirmed_at' => $order->codReconciliation->confirmed_at?->toDateTimeString(),
            ] : null,
            'delivery_assignment_status' => $delivery?->assignment_status,
            'delivery_rejection_reason' => $delivery?->rejection_reason,
            'allocated_for_pickup_at' => $delivery?->allocated_for_pickup_at?->toDateTimeString(),
            'picked_at' => $delivery?->picked_at?->toDateTimeString(),
            'total_amount' => (float) $order->total_amount,
            'currency' => $order->currency,
            'created_at' => $order->created_at?->toDateTimeString(),
            'customer' => $order->customer ? [
                'first_name' => $order->customer->first_name,
                'last_name' => $order->customer->last_name,
            ] : null,
            'shipping_address' => $shipping ? [
                'address_line_1' => $shipping->address_line_1,
                'address_line_2' => $shipping->address_line_2,
                'city' => $shipping->city,
                'state_province' => $shipping->state_province,
                'postal_code' => $shipping->postal_code,
                'country' => $shipping->country_name ?? $shipping->country_code,
                'phone' => $shipping->phone,
                'latitude' => $shipping->latitude ? (float) $shipping->latitude : null,
                'longitude' => $shipping->longitude ? (float) $shipping->longitude : null,
            ] : null,
            'delivery_note_summary' => $order->getDeliveryNoteSummary(),
            'order_items' => $order->orderItems
                ->whereNotIn('dispatch_status', ['declined', 'rejected'])
                ->map(function ($item) {
                    $p = $item->productVariant?->product;
                    return [
                        'id' => $item->id,
                        'quantity' => $item->quantity,
                        'quantity_returned' => $item->quantity_returned ?? 0,
                        'product_name' => $p?->name ?? '—',
                    ];
                })->values()->toArray(),
        ];
    }
}
