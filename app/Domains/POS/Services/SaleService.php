<?php

namespace App\Domains\POS\Services;

use App\Domains\POS\Enums\SaleStatus;
use App\Models\Orders\Order;
use App\Models\POS\PosSession;
use App\Models\POS\PosSetting;
use App\Models\ProductVariant;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Str;

class SaleService
{
    /**
     * Create a completed sale (order) from cart items. VAT and totals are calculated here.
     * Controllers must NOT contain this business logic.
     */
    public function createSale(PosSession $session, int $customerId, array $items, string $paymentMethod, float $amountPaid, array $allocatedPaymentIds = []): Order
    {
        $settings = PosSetting::getSettings();
        $vatPercentage = $settings->vat_enabled ? (float) $settings->vat_percentage : 0;

        $lineItems = [];
        $totalAmount = 0;

        foreach ($items as $itemData) {
            $variant = ProductVariant::with('product')->findOrFail($itemData['product_variant_id']);
            $unitPrice = $variant->final_price;
            $lineSubtotal = $unitPrice * $itemData['quantity'];

            $lineTax = 0;
            if ($vatPercentage > 0) {
                $lineTax = $lineSubtotal - ($lineSubtotal / (1 + ($vatPercentage / 100)));
            }

            $totalAmount += $lineSubtotal;

            $lineItems[] = [
                'product_variant_id' => $variant->id,
                'seller_id' => $variant->product->owner_id,
                'quantity' => $itemData['quantity'],
                'unit_price' => $unitPrice,
                'total_price' => $lineSubtotal,
                'tax_amount' => $lineTax,
                'product_snapshot' => [
                    'name' => $variant->product->name,
                    'sku' => $variant->sku,
                    'image' => $variant->product->primary_image_url ?? null,
                ],
            ];
        }

        $totalTax = collect($lineItems)->sum('tax_amount');

        $totalAllocated = $this->applyAllocatedPayments($customerId, $totalAmount, $allocatedPaymentIds);
        $remainingToPay = max(0, $totalAmount - $totalAllocated);

        $orderCode = $settings->receipt_prefix . strtoupper(Str::random(8));

        return DB::transaction(function () use ($session, $customerId, $lineItems, $totalAmount, $totalTax, $totalAllocated, $remainingToPay, $orderCode, $settings, $paymentMethod, $amountPaid, $totalAllocated) {
            $order = Order::create([
                'order_code' => $orderCode,
                'customer_id' => $customerId,
                'pos_session_id' => $session->id,
                'status' => 'confirmed',
                'payment_status' => 'paid',
                'fulfillment_status' => 'fulfilled',
                'subtotal' => $totalAmount - $totalTax,
                'tax_amount' => $totalTax,
                'total_amount' => $totalAmount,
                'currency' => $settings->currency_symbol,
                'source' => 'pos',
                'metadata' => [
                    'allocated_amount' => $totalAllocated,
                    'paid_amount' => $amountPaid,
                    'payment_method' => $paymentMethod,
                ],
            ]);

            foreach ($lineItems as $item) {
                $order->orderItems()->create($item);
                ProductVariant::find($item['product_variant_id'])->decrement('stock', $item['quantity']);
            }

            if ($remainingToPay > 0) {
                $order->payments()->create([
                    'ulid' => (string) Str::ulid(),
                    'amount' => $remainingToPay,
                    'provider' => 'pos',
                    'method' => $paymentMethod,
                    'status' => 'completed',
                    'completed_at' => now(),
                ]);
                $this->incrementSessionTotals($session, $paymentMethod, $remainingToPay);
            }

            if ($totalAllocated > 0) {
                $order->payments()->create([
                    'ulid' => (string) Str::ulid(),
                    'amount' => $totalAllocated,
                    'provider' => 'pos',
                    'method' => 'allocated_payment',
                    'status' => 'completed',
                    'completed_at' => now(),
                ]);
            }

            return $order->load(['orderItems', 'customer']);
        });
    }

    protected function applyAllocatedPayments(int $customerId, float $totalAmount, array $allocatedPaymentIds): float
    {
        if (empty($allocatedPaymentIds)) {
            return 0.0;
        }

        $payments = \App\Models\POS\PosUnallocatedPayment::whereIn('id', $allocatedPaymentIds)
            ->where('customer_id', $customerId)
            ->where('status', 'active')
            ->get();

        $totalAllocated = 0.0;

        foreach ($payments as $payment) {
            $available = $payment->amount - $payment->used_amount;
            $toUse = min($available, $totalAmount - $totalAllocated);

            if ($toUse > 0) {
                $payment->increment('used_amount', $toUse);
                if ($payment->used_amount >= $payment->amount) {
                    $payment->update(['status' => 'fully_used']);
                }
                $totalAllocated += $toUse;
            }
        }

        return $totalAllocated;
    }

    protected function incrementSessionTotals(PosSession $session, string $paymentMethod, float $amount): void
    {
        if ($paymentMethod === 'cash') {
            $session->increment('cash_sales_total', $amount);
        } elseif ($paymentMethod === 'mpesa') {
            $session->increment('mpesa_sales_total', $amount);
        } else {
            $session->increment('other_sales_total', $amount);
        }
    }
}
