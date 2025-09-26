<?php

namespace App\Services;

use App\Models\Commission\CommissionCalculation;
use App\Models\Commission\CommissionInvoice;
use App\Models\User;
use Carbon\Carbon;
use Illuminate\Support\Str;

class CommissionInvoiceService
{
    public function generateInvoiceForPeriod(User $seller, Carbon $startDate, Carbon $endDate): CommissionInvoice
    {
        // Get all confirmed calculations for the period
        $calculations = CommissionCalculation::where('seller_id', $seller->id)
            ->where('status', 'confirmed')
            ->whereBetween('calculated_at', [$startDate, $endDate])
            ->whereDoesntHave('invoiceItem')
            ->get();

        $commissionTotal = $calculations->sum('commission_amount');
        $baseFee = $seller->subscription?->getEffectiveBaseFee() ?? 0;

        $invoice = CommissionInvoice::create([
            'invoice_number' => $this->generateInvoiceNumber(),
            'seller_id' => $seller->id,
            'period_start' => $startDate->toDateString(),
            'period_end' => $endDate->toDateString(),
            'base_fee' => $baseFee,
            'commission_total' => $commissionTotal,
            'tax_amount' => 0, // Calculate tax if needed
            'discount_amount' => 0,
            'total_amount' => $baseFee + $commissionTotal,
            'status' => 'draft',
            'due_at' => now()->addDays(30),
        ]);

        // Create invoice items
        foreach ($calculations as $calculation) {
            CommissionInvoiceItem::create([
                'commission_invoice_id' => $invoice->id,
                'commission_calculation_id' => $calculation->id,
                'amount' => $calculation->commission_amount,
                'description' => "Commission for {$calculation->calculable_type} #{$calculation->calculable_id}",
            ]);
        }

        return $invoice;
    }

    public function markInvoiceAsPaid(CommissionInvoice $invoice, array $paymentData): void
    {
        $invoice->update([
            'status' => 'paid',
            'paid_at' => now(),
        ]);

        $invoice->payments()->create($paymentData);
    }

    private function generateInvoiceNumber(): string
    {
        $prefix = 'INV-' . now()->format('Ym');
        $lastInvoice = CommissionInvoice::where('invoice_number', 'like', $prefix . '%')
            ->orderBy('invoice_number', 'desc')
            ->first();

        if (!$lastInvoice) {
            return $prefix . '-0001';
        }

        $lastNumber = (int) substr($lastInvoice->invoice_number, -4);
        return $prefix . '-' . str_pad($lastNumber + 1, 4, '0', STR_PAD_LEFT);
    }
}
