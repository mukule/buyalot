<?php

namespace App\Http\Controllers\Billing;

use App\Http\Controllers\Controller;
use App\Models\Billing\Invoice;
use App\Models\Billing\Receipt;
use App\Models\Billing\ReceiptAllocation;
use App\Services\DocumentNumberService;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;

class ReceiptController extends Controller
{
    /**
     * Create a payment receipt without allocations.
     */
    public function store(Request $request)
    {
        $data = $request->validate([
            'seller_id' => 'required|exists:sellers,id',
            'payer_id' => 'nullable|exists:users,id',
            'currency' => 'required|string|size:3',
            'amount_minor' => 'required|integer|min:1',
            'method' => 'nullable|string|max:32',
            'external_ref' => 'nullable|string|max:100',
            'paid_at' => 'nullable|date',
        ]);

        $year = (int) now()->format('Y');
        $prefix = 'RCPT-' . $data['seller_id'] . '-' . $year . '-';

        /** @var Receipt $receipt */
        $receipt = Receipt::create([
            'seller_id' => (int)$data['seller_id'],
            'payer_id' => $data['payer_id'] ?? null,
            'number' => DocumentNumberService::nextNumber((int)$data['seller_id'], 'receipt', $year, $prefix),
            'type' => 'payment_receipt',
            'currency' => strtoupper($data['currency']),
            'amount_minor' => (int)$data['amount_minor'],
            'method' => $data['method'] ?? null,
            'external_ref' => $data['external_ref'] ?? null,
            'paid_at' => $data['paid_at'] ?? now(),
        ]);

        return response()->json(['receipt' => $receipt], 201);
    }

    /**
     * Allocate a receipt to multiple invoices (split payment).
     * Body: { allocations: [{invoice_id, amount_minor}, ...] }
     */
    public function allocate(Request $request, Receipt $receipt)
    {
        $data = $request->validate([
            'allocations' => 'required|array|min:1',
            'allocations.*.invoice_id' => 'required|exists:invoices,id',
            'allocations.*.amount_minor' => 'required|integer|min:1',
        ]);

        return DB::transaction(function () use ($receipt, $data) {
            // Ensure remaining balance on receipt
            $already = (int) ReceiptAllocation::where('receipt_id', $receipt->id)->sum('amount_minor');
            $remaining = (int) $receipt->amount_minor - $already;
            if ($remaining <= 0) {
                return response()->json(['message' => 'Receipt has no remaining balance.'], 422);
            }

            $toAllocateTotal = 0;
            foreach ($data['allocations'] as $alloc) {
                $toAllocateTotal += (int)$alloc['amount_minor'];
            }
            if ($toAllocateTotal > $remaining) {
                return response()->json(['message' => 'Allocation exceeds receipt remaining balance.'], 422);
            }

            $applied = [];

            foreach ($data['allocations'] as $alloc) {
                /** @var Invoice $invoice */
                $invoice = Invoice::lockForUpdate()->findOrFail((int)$alloc['invoice_id']);

                // Seller and currency consistency
                if ($invoice->seller_id !== $receipt->seller_id) {
                    return response()->json(['message' => "Invoice {$invoice->id} belongs to a different seller."], 422);
                }
                if ($invoice->currency !== $receipt->currency) {
                    return response()->json(['message' => "Currency mismatch for invoice {$invoice->id}."], 422);
                }

                // Only issued/sent/partial invoices can be paid
                if (!in_array($invoice->status, ['issued','sent','partially_paid'])) {
                    return response()->json(['message' => "Invoice {$invoice->id} is not eligible for allocation."], 422);
                }

                $amt = min((int)$alloc['amount_minor'], (int)$invoice->balance_minor);
                if ($amt <= 0) {
                    continue; // nothing to apply to this invoice
                }

                ReceiptAllocation::create([
                    'receipt_id' => $receipt->id,
                    'invoice_id' => $invoice->id,
                    'amount_minor' => $amt,
                ]);

                $invoice->applyAllocation($amt);

                $applied[] = [
                    'invoice_id' => $invoice->id,
                    'applied_minor' => $amt,
                ];
            }

            return response()->json([
                'receipt_id' => $receipt->id,
                'applied' => $applied,
            ]);
        });
    }
}
