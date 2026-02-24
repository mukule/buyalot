<?php

namespace App\Http\Controllers\POS;

use App\Http\Controllers\Controller;
use App\Models\Orders\Order;
use App\Models\POS\PosSession;
use App\Models\POS\PosSetting;
use App\Models\ProductVariant;
use App\Services\SellerContext;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Str;

class PosOrderController extends Controller
{
    public function store(Request $request)
    {
        $request->validate([
            'pos_session_id' => 'required|exists:pos_sessions,id',
            'customer_id' => 'required|exists:customers,id',
            'items' => 'required|array|min:1',
            'items.*.product_variant_id' => 'required|exists:product_variants,id',
            'items.*.quantity' => 'required|integer|min:1',
            'payment_method' => 'required|string',
            'amount_paid' => 'required|numeric|min:0',
            'allocated_payment_ids' => 'nullable|array',
            'allocated_payment_ids.*' => 'exists:pos_unallocated_payments,id',
        ]);

        $user = $request->user() ?? auth()->user();
        $session = PosSession::forUser($user)->findOrFail($request->pos_session_id);

        if ($session->status !== 'open') {
            return response()->json(['message' => 'POS session is closed.'], 422);
        }

        return DB::transaction(function () use ($request, $session) {
            $totalAmount = 0;
            $items = [];
            $settings = PosSetting::getSettings();
            $vatPercentage = $settings->vat_enabled ? $settings->vat_percentage : 0;

            foreach ($request->items as $itemData) {
                $variant = ProductVariant::with('product')->findOrFail($itemData['product_variant_id']);
                $unitPrice = $variant->final_price;
                $lineSubtotal = $unitPrice * $itemData['quantity'];

                $lineTax = 0;
                if ($vatPercentage > 0) {
                    $lineTax = $lineSubtotal - ($lineSubtotal / (1 + ($vatPercentage / 100)));
                }

                $totalAmount += $lineSubtotal;

                $items[] = [
                    'product_variant_id' => $variant->id,
                    'seller_id' => $variant->product->owner_id,
                    'quantity' => $itemData['quantity'],
                    'unit_price' => $unitPrice,
                    'total_price' => $lineSubtotal,
                    'tax_amount' => $lineTax,
                    'product_snapshot' => [
                        'name' => $variant->product->name,
                        'sku' => $variant->sku,
                        'image' => $variant->product->primary_image_url,
                    ],
                ];
            }

            $totalTax = collect($items)->sum('tax_amount');

            // Handle Allocated Payments
            $totalAllocated = 0;
            if ($request->allocated_payment_ids) {
                $allocatedPayments = \App\Models\POS\PosUnallocatedPayment::whereIn('id', $request->allocated_payment_ids)
                    ->where('customer_id', $request->customer_id)
                    ->where('status', 'active')
                    ->get();

                foreach ($allocatedPayments as $payment) {
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
            }

            $remainingToPay = max(0, $totalAmount - $totalAllocated);
            $orderCode = $settings->receipt_prefix . strtoupper(Str::random(8));

            $order = Order::create([
                'order_code' => $orderCode,
                'customer_id' => $request->customer_id,
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
                    'paid_amount' => $request->amount_paid,
                    'payment_method' => $request->payment_method,
                ]
            ]);

            foreach ($items as $item) {
                $order->orderItems()->create($item);

                // Update Inventory
                $variant = ProductVariant::find($item['product_variant_id']);
                $variant->decrement('stock', $item['quantity']);
            }

            // Create Primary Payment
            if ($remainingToPay > 0) {
                $order->payments()->create([
                    'ulid' => (string) Str::ulid(),
                    'amount' => $remainingToPay,
                    'provider' => 'pos',
                    'method' => $request->payment_method,
                    'status' => 'completed',
                    'completed_at' => now(),
                ]);

                // Update session totals for the NEW payment part
                if ($request->payment_method === 'cash') {
                    $session->increment('cash_sales_total', $remainingToPay);
                } elseif ($request->payment_method === 'mpesa') {
                    $session->increment('mpesa_sales_total', $remainingToPay);
                } else {
                    $session->increment('other_sales_total', $remainingToPay);
                }
            }

            // Record the allocated part as well if needed for accounting
            if ($totalAllocated > 0) {
                 $order->payments()->create([
                    'ulid' => (string) Str::ulid(),
                    'amount' => $totalAllocated,
                    'provider' => 'pos',
                    'method' => 'allocated_payment',
                    'status' => 'completed',
                    'completed_at' => now(),
                ]);
                // Note: We don't increment session totals for allocated payments as they were
                // already counted when the unallocated payment was first recorded.
            }

            return response()->json([
                'message' => 'Order created successfully',
                'order' => $order->load(['orderItems', 'customer']),
                'settings' => $settings,
                'register' => $session->register,
            ]);
        });
    }
}
