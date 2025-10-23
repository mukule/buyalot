<?php

namespace App\Observers;

use App\Jobs\CalculateCommissionJob;
use App\Models\Orders\Order;

class OrderObserver
{
    public function created(Order $order): void
    {
        // Automatically calculate commission when an order is created
        if ($order->seller_id && $order->total > 0) {
            $saleData = [
                'amount' => $order->total,
                'calculable_type' => 'orders',
                'calculable_id' => $order->id,
                'item_count' => $order->items->count(),
                'payment_method' => $order->payment_method,
                'product_category' => $order->items->first()?->product?->category,
                'customer_type' => $order->customer?->type,
            ];

            CalculateCommissionJob::dispatch($order->seller_id, $saleData);
        }
    }

    public function updated(Order $order): void
    {
        // Recalculate commission if order amount changes
        if ($order->wasChanged('total') && $order->seller_id) {
            $saleData = [
                'amount' => $order->total,
                'calculable_type' => 'orders',
                'calculable_id' => $order->id,
                'item_count' => $order->items->count(),
                'payment_method' => $order->payment_method,
                'product_category' => $order->items->first()?->product?->category,
                'customer_type' => $order->customer?->type,
            ];

            // Delete existing calculations for this order
            $order->commissionCalculations()->delete();

            // Recalculate
            CalculateCommissionJob::dispatch($order->seller_id, $saleData);
        }
    }
}
