<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\Orders\Order;
use Illuminate\Http\Request;
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
            'customer',
            'orderItems.productVariant.product',
            'orderItems.productVariant.values',
            'orderItems.seller:id,name',
            'shippingAddress',
            'billingAddress',
            'assignedRider:id,name,email',
        ]);

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
            'order_items' => $order->orderItems->map(function ($item) {
                $variant = $item->productVariant;
                $product = $variant?->product;
                // Build variant label from variant values if present
                $variantLabel = null;
                if ($variant && $variant->relationLoaded('values')) {
                    $values = $variant->values->pluck('value')->filter()->values();
                    if ($values->isNotEmpty()) {
                        $variantLabel = $values->join(', ');
                    }
                }
                if (!$variantLabel && $variant?->sku) {
                    $variantLabel = $variant->sku;
                }

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
                        'label' => $variantLabel,
                    ] : null,
                    'seller' => $item->seller ? [
                        'id' => $item->seller->id,
                        'name' => $item->seller->name,
                    ] : null,
                ];
            })->toArray(),
            'rider_id' => $order->assignedRider?->id,
        ];

        return Inertia::render('Admin/Orders/Show', [
            'order' => $payload,
            'riders' => [], // can be populated if rider assignment feature is needed
            'breadcrumbs' => [
                ['title' => 'Dashboard', 'href' => '/admin/dashboard'],
                ['title' => 'Orders', 'href' => '/admin/orders'],
                ['title' => 'Order ' . $order->order_code, 'href' => '/admin/orders/' . $order->getRouteKey()],
            ],
        ]);
    }
}
