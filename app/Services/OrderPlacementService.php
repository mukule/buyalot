<?php

namespace App\Services;

use App\Mail\AdminOrderNotification;
use App\Mail\CustomerOrderConfirmation;
use App\Models\CheckoutSession;
use App\Models\Customer\CustomerAddress;
use App\Models\Orders\Delivery;
use App\Models\Orders\Order;
use App\Models\Orders\OrderItem;
use App\Models\Payment\Payment;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Mail;
use Illuminate\Support\Str;

class OrderPlacementService
{
    /**
     * Place/finalize an order from a completed checkout session.
     *
     * @throws \Exception
     */
    public function placeOrder(int $checkoutSessionId, $paidAmount, $reference, $paymentMethod, ?int $shippingAddressId = null, ?int $billingAddressId = null): Order
    {
        $checkoutSession = CheckoutSession::with('cart.items.productVariant.product.owner')
            ->findOrFail($checkoutSessionId);

        if ($checkoutSession->status !== 'completed') {
            throw new \Exception("Checkout session is not completed");
        }

        $cart = $checkoutSession->cart;

        if (!$cart || $cart->items->isEmpty()) {
            throw new \Exception("Cart is empty or not found");
        }

        // // Log cart and cart items for debugging
        // Log::info('Placing order for checkout session', [
        //     'checkout_session_id' => $checkoutSessionId,
        //     'cart_id' => $cart->id,
        //     'cart_items_count' => $cart->items->count(),
        //     'cart_items' => $cart->items->map(fn($ci) => [
        //         'cart_item_id' => $ci->id,
        //         'product_variant_id' => $ci->product_variant_id,
        //         'quantity' => $ci->quantity,
        //         'unit_price' => $ci->unit_price,
        //         'discount_amount' => $ci->discount_amount,
        //     ])->toArray(),
        // ]);

        DB::beginTransaction();

        try {
            $customerId = $checkoutSession->customer_id;

            // Use checkout-selected addresses if provided; otherwise fall back to customer default
            $billingAddress = $billingAddressId
                ? CustomerAddress::where('customer_id', $customerId)->where('id', $billingAddressId)->first()
                : null;
            $shippingAddress = $shippingAddressId
                ? CustomerAddress::where('customer_id', $customerId)->where('id', $shippingAddressId)->first()
                : null;
            $billingAddress = $billingAddress ?? CustomerAddress::where('customer_id', $customerId)->default()->first()
                ?? CustomerAddress::where('customer_id', $customerId)->first();
            $shippingAddress = $shippingAddress ?? CustomerAddress::where('customer_id', $customerId)->default()->first()
                ?? CustomerAddress::where('customer_id', $customerId)->first();

            $itemsInput = $cart->items->map(function ($ci) {
                if (!$ci->product_variant_id) {
                    // Log::error('Cart item missing product_variant_id', [
                    //     'cart_item_id' => $ci->id,
                    //     'cart_id' => $ci->cart_id,
                    //     'ci' => $ci->toArray(),
                    // ]);
                    throw new \Exception("Cart item missing product_variant_id");
                }

                $variant = $ci->productVariant;
                if (!$variant) {
                    // Log::error('Product variant not found for cart item', [
                    //     'cart_item_id' => $ci->id,
                    //     'product_variant_id' => $ci->product_variant_id,
                    // ]);
                    throw new \Exception("Product variant not found: {$ci->product_variant_id}");
                }

                $product = $variant->product;

                return [
                    'variant' => $variant,
                    'product' => $product,
                    'quantity' => (int) $ci->quantity,
                    'unit_price' => (float) ($ci->unit_price ?? 0),
                    'discount_amount' => (float) ($ci->discount_amount ?? 0),
                    'line_subtotal' => ((float) ($ci->unit_price ?? 0)) * ((int) $ci->quantity),
                    'line_discount' => ((float) ($ci->discount_amount ?? 0)) * ((int) $ci->quantity),
                ];
            })->toArray();

            // Use amounts from checkout session
            $subtotal = $checkoutSession->cart_amount;
            $discount = $checkoutSession->discount_amount;
            $tax = $checkoutSession->tax_amount;
            $shipping = $checkoutSession->shipping_amount;
            $total = $checkoutSession->amount;

            $gen_order_code = Order::generateUniqueOrderCode();
            if ($reference =='cod'){
                $payment_status='pending';
            }else{
                $payment_status='paid';
            }

            // Create the order
            $order = Order::create([
                'ulid' => Str::ulid(),
                'order_code' => $gen_order_code,
                'customer_id' => $customerId,
                'checkout_session_id' => $checkoutSession->id,
                'subtotal' => $subtotal,
                'tax_amount' => $tax,
                'shipping_amount' => $shipping,
                'discount_amount' => $discount,
                'total_amount' => $total,
                'amount_paid' => $paidAmount,
                'balance' =>(($total??0.00)-($paidAmount??0.00)),
                'currency' => $cart->currency ?? 'KES',
                'billing_address_id' => $billingAddress?->id,
                'shipping_address_id' => $shippingAddress?->id ?? $billingAddress?->id,
                'payment_status' => $payment_status,
                'status' => 'pending',
                'notes' => $cart->notes,
                'payment_method' => $paymentMethod,
            ]);

            // Create order items
            foreach ($itemsInput as $ci) {
                // Determine seller_id based on owner_type.
                // If owner_type is 'seller', owner_id in Product refers to the User who owns it.
                // We need to find that user's seller_application_id.
                $sellerId = null;
                if ($ci['product']->owner_type === 'seller') {
                    $owner = $ci['product']->owner;
                    if ($owner) {
                        $sellerId = $owner->seller_application_id;
                    }
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
                    'dispatch_status' => OrderItem::DISPATCH_STATUS_PENDING,
                    'product_snapshot' => [
                        'id' => $ci['product']->id,
                        'name' => $ci['product']->name,
                        'sku' => $ci['variant']->sku,
                        'current_stock' => $ci['variant']->stock,
                    ],
                ]);
            }

            // Release reservations WITHOUT returning stock (already deducted)
            /** @var \App\Services\CartReservationService $reservationService */
            $reservationService = app(\App\Services\CartReservationService::class);
            $reservationService->releaseAllForCart($cart->id, false);

            // Soft delete the cart and mark as ordered
            $cart->status = 'ordered';
            $cart->save();
            $cart->delete(); // soft delete

            // Create Delivery from customer's shipping address (customer-selected pickup or customer address)
            $deliveryType = 'customer_address';
            $pickupWarehouseId = null;
            if ($shippingAddress && $shippingAddress->pickup_warehouse_id) {
                $deliveryType = 'pickup_point';
                $pickupWarehouseId = $shippingAddress->pickup_warehouse_id;
            }
            Delivery::create([
                'order_id' => $order->id,
                'delivery_type' => $deliveryType,
                'pickup_warehouse_id' => $pickupWarehouseId,
                'assignment_status' => 'pending',
            ]);

            // Mark checkout session as order created
            $checkoutSession->order_status = 'created';
            $checkoutSession->save();

            if ($reference !='cod'){
            //update payment records to link payment and order code
            $payment_record=Payment::where('reference',$reference)->first();
            if ($payment_record) {
                $payment_record->reference = $gen_order_code;
                $payment_record->save();
            }
            }

            DB::commit();

//            Log::info('Order placed successfully', [
//                'order_id' => $order->id,
//                'checkout_session_id' => $checkoutSessionId,
//                'cart_id' => $cart->id,
//            ]);

            //TODO: GENERATE INVOICE AND eTIMS INTEGRATION

            // ------------------- Queue Emails (avoid blocking/timeout) -------------------
            try {
                // Customer confirmation
                if ($order->customer?->email) {
                    Mail::to($order->customer->email)
                        ->queue(new CustomerOrderConfirmation($order));
                }

                // Admin notifications
                $adminEmails = config('mail.admin_address');
                if (!empty($adminEmails)) {
                    $addresses = is_string($adminEmails) ? array_map('trim', explode(',', $adminEmails)) : (array) $adminEmails;
                    if (!empty($addresses)) {
                        Mail::to($addresses)
                            ->queue(new AdminOrderNotification($order));
                    }
                }
            } catch (\Throwable $e) {
                Log::error('Failed to queue order emails', [
                    'order_id' => $order->id,
                    'error' => $e->getMessage(),
                    'stack' => $e->getTraceAsString(),
                ]);
            }

            return $order;

        } catch (\Exception $e) {
            DB::rollBack();
            Log::error('Order placement failed', [
                'checkout_session_id' => $checkoutSessionId,
                'error' => $e->getMessage(),
                'stack' => $e->getTraceAsString(),
            ]);
            throw $e;
        }
    }
}
