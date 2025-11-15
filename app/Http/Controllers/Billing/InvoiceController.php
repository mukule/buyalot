<?php

namespace App\Http\Controllers\Billing;

use App\Http\Controllers\Controller;
use App\Models\Billing\Invoice;
use App\Models\Billing\InvoiceItem;
use App\Models\Billing\Receipt;
use App\Models\Billing\ReceiptAllocation;
use App\Services\DocumentNumberService;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Validation\ValidationException;

class InvoiceController extends Controller
{
    public function index(Request $request)
    {
        $query = Invoice::query()
            ->when($request->seller_id, fn($q) => $q->where('seller_id', $request->integer('seller_id')))
            ->when($request->status, fn($q) => $q->where('status', $request->string('status')))
            ->when($request->type, fn($q) => $q->where('type', $request->string('type')))
            ->orderByDesc('id');

        return response()->json($query->paginate(20));
    }

    public function show(Invoice $invoice)
    {
        $invoice->load('items');
        return response()->json($invoice);
    }

    public function store(Request $request)
    {
        $data = $request->validate([
            'seller_id' => 'required|exists:sellers,id',
            'buyer_id' => 'nullable|exists:users,id',
            'order_id' => 'nullable|exists:orders,id',
            'type' => 'nullable|string|in:tax_invoice,credit_note,debit_note,proforma,commercial',
            'currency' => 'required|string|size:3',
            'due_date' => 'nullable|date',
            'issue_now' => 'sometimes|boolean',
            'items' => 'required|array|min:1',
            'items.*.product_id' => 'nullable|exists:products,id',
            'items.*.description' => 'required|string',
            'items.*.quantity' => 'required|integer|min:1',
            'items.*.unit_price_minor' => 'required|integer|min:0',
            'items.*.discount_minor' => 'nullable|integer|min:0',
            'items.*.tax_rate' => 'nullable|numeric|min:0', // e.g., 0.16 for 16%
        ]);

        return DB::transaction(function () use ($data) {
            $type = $data['type'] ?? 'tax_invoice';

            $subtotal = 0; $discountTotal = 0; $taxTotal = 0; $grand = 0;
            $itemsPayload = $data['items'];

            /** @var Invoice $invoice */
            $invoice = Invoice::create([
                'seller_id' => $data['seller_id'],
                'buyer_id' => $data['buyer_id'] ?? null,
                'order_id' => $data['order_id'] ?? null,
                'number' => 'DRAFT',
                'type' => $type,
                'status' => 'draft',
                'issue_date' => null,
                'due_date' => $data['due_date'] ?? null,
                'currency' => strtoupper($data['currency']),
                'subtotal_minor' => 0,
                'discount_minor' => 0,
                'tax_minor' => 0,
                'total_minor' => 0,
                'balance_minor' => 0,
            ]);

            foreach ($itemsPayload as $it) {
                $qty = (int)$it['quantity'];
                $unit = (int)$it['unit_price_minor'];
                $disc = (int)($it['discount_minor'] ?? 0);
                $rate = isset($it['tax_rate']) ? (float)$it['tax_rate'] : 0.0;

                $lineNet = ($qty * $unit) - $disc; // minor units
                if ($lineNet < 0) {
                    throw ValidationException::withMessages(['items' => ['Line total cannot be negative.']]);
                }
                $lineTax = (int) round($lineNet * $rate, 0, PHP_ROUND_HALF_UP);
                $lineTotal = $lineNet + $lineTax;

                $subtotal += ($qty * $unit);
                $discountTotal += $disc;
                $taxTotal += $lineTax;
                $grand += $lineTotal;

                InvoiceItem::create([
                    'invoice_id' => $invoice->id,
                    'product_id' => $it['product_id'] ?? null,
                    'description' => $it['description'],
                    'quantity' => $qty,
                    'unit_price_minor' => $unit,
                    'discount_minor' => $disc,
                    'tax_rate' => $rate,
                    'tax_minor' => $lineTax,
                    'line_total_minor' => $lineTotal,
                ]);
            }

            $invoice->update([
                'subtotal_minor' => $subtotal,
                'discount_minor' => $discountTotal,
                'tax_minor' => $taxTotal,
                'total_minor' => $grand,
                'balance_minor' => $grand,
            ]);

            if (!empty($data['issue_now'])) {
                $this->issue($invoice);
            }

            $invoice->load('items');
            return response()->json($invoice, 201);
        });
    }

    public function issue(Invoice $invoice)
    {
        if ($invoice->status !== 'draft') {
            return response()->json(['message' => 'Only draft invoices can be issued.'], 422);
        }
        $year = (int) now()->format('Y');
        // Choose prefix and doc type per invoice type
        if ($invoice->type === 'proforma') {
            $prefix = 'PF-' . $invoice->seller_id . '-' . $year . '-';
            $number = DocumentNumberService::nextNumber($invoice->seller_id, 'proforma', $year, $prefix);
        } else {
            $prefix = 'INV-' . $invoice->seller_id . '-' . $year . '-';
            $number = DocumentNumberService::nextNumber($invoice->seller_id, 'invoice', $year, $prefix);
        }
        $invoice->number = $number;
        $invoice->markIssued();
        return response()->json($invoice);
    }

    /**
     * Convert a PROFORMA to a TAX INVOICE by cloning header and items.
     */
    public function convertToInvoice(Invoice $invoice)
    {
        if ($invoice->type !== 'proforma') {
            return response()->json(['message' => 'Only proformas can be converted.'], 422);
        }

        return DB::transaction(function () use ($invoice) {
            // Create new tax invoice as draft with same totals
            /** @var Invoice $new */
            $new = Invoice::create([
                'seller_id' => $invoice->seller_id,
                'buyer_id' => $invoice->buyer_id,
                'order_id' => $invoice->order_id,
                'number' => 'DRAFT',
                'type' => 'tax_invoice',
                'status' => 'draft',
                'issue_date' => null,
                'due_date' => $invoice->due_date,
                'currency' => $invoice->currency,
                'subtotal_minor' => $invoice->subtotal_minor,
                'discount_minor' => $invoice->discount_minor,
                'tax_minor' => $invoice->tax_minor,
                'total_minor' => $invoice->total_minor,
                'balance_minor' => $invoice->total_minor,
                'meta' => [
                    'converted_from_proforma_id' => $invoice->id,
                    'converted_from_number' => $invoice->number,
                ],
            ]);

            // Clone items
            foreach ($invoice->items as $item) {
                InvoiceItem::create([
                    'invoice_id' => $new->id,
                    'product_id' => $item->product_id,
                    'description' => $item->description,
                    'quantity' => $item->quantity,
                    'unit_price_minor' => $item->unit_price_minor,
                    'discount_minor' => $item->discount_minor,
                    'tax_rate' => $item->tax_rate,
                    'tax_minor' => $item->tax_minor,
                    'line_total_minor' => $item->line_total_minor,
                    'meta' => $item->meta,
                ]);
            }

            // Mark proforma as converted
            $meta = $invoice->meta ?? [];
            $meta['converted_to_invoice_id'] = $new->id;
            $invoice->meta = $meta;
            $invoice->status = 'converted';
            $invoice->save();

            return response()->json(['invoice' => $new], 201);
        });
    }

    /**
     * General allocation endpoint: either create-and-allocate or allocate from existing receipt.
     */
    public function allocate(Request $request, Invoice $invoice)
    {
        if (!in_array($invoice->status, ['issued','sent','partially_paid'])) {
            return response()->json(['message' => 'Invoice must be issued before payment.'], 422);
        }

        $data = $request->validate([
            'receipt_id' => 'nullable|exists:receipts,id',
            'amount_minor' => 'required|integer|min:1',
            'method' => 'nullable|string|max:32',
            'payer_id' => 'nullable|exists:users,id',
            'external_ref' => 'nullable|string|max:100',
        ]);

        return DB::transaction(function () use ($invoice, $data) {
            $amount = (int)$data['amount_minor'];

            if (!empty($data['receipt_id'])) {
                /** @var Receipt $receipt */
                $receipt = Receipt::lockForUpdate()->findOrFail($data['receipt_id']);
                if ($receipt->seller_id !== $invoice->seller_id) {
                    return response()->json(['message' => 'Receipt and invoice must belong to same seller.'], 422);
                }
                if ($receipt->currency !== $invoice->currency) {
                    return response()->json(['message' => 'Receipt currency must match invoice currency.'], 422);
                }
                // compute remaining on receipt
                $allocated = ReceiptAllocation::where('receipt_id', $receipt->id)->sum('amount_minor');
                $remaining = max(0, (int)$receipt->amount_minor - (int)$allocated);
                if ($remaining <= 0) {
                    return response()->json(['message' => 'Receipt has no remaining balance to allocate.'], 422);
                }
                $allocAmount = min($amount, $remaining, (int)$invoice->balance_minor);

                if ($allocAmount <= 0) {
                    return response()->json(['message' => 'Nothing to allocate.'], 422);
                }

                ReceiptAllocation::create([
                    'receipt_id' => $receipt->id,
                    'invoice_id' => $invoice->id,
                    'amount_minor' => $allocAmount,
                ]);

                $invoice->applyAllocation($allocAmount);

                return response()->json(['allocated' => $allocAmount]);
            } else {
                // Create receipt then allocate
                $allocAmount = min($amount, (int)$invoice->balance_minor);
                if ($allocAmount <= 0) {
                    return response()->json(['message' => 'Amount exceeds invoice balance.'], 422);
                }
                /** @var Receipt $receipt */
                $receipt = Receipt::create([
                    'seller_id' => $invoice->seller_id,
                    'payer_id' => $data['payer_id'] ?? $invoice->buyer_id,
                    'number' => DocumentNumberService::nextNumber($invoice->seller_id, 'receipt', (int) now()->format('Y'), 'RCPT-' . $invoice->seller_id . '-' . now()->format('Y') . '-'),
                    'type' => 'payment_receipt',
                    'currency' => $invoice->currency,
                    'amount_minor' => $allocAmount,
                    'method' => $data['method'] ?? null,
                    'external_ref' => $data['external_ref'] ?? null,
                    'paid_at' => now(),
                ]);

                ReceiptAllocation::create([
                    'receipt_id' => $receipt->id,
                    'invoice_id' => $invoice->id,
                    'amount_minor' => $allocAmount,
                ]);

                $invoice->applyAllocation($allocAmount);

                return response()->json(['receipt' => $receipt->load('allocations')], 201);
            }
        });
    }

    public function allocatePayment(Request $request, Invoice $invoice)
    {
        if (!in_array($invoice->status, ['issued','sent','partially_paid'])) {
            return response()->json(['message' => 'Invoice must be issued before payment.'], 422);
        }

        $data = $request->validate([
            'amount_minor' => 'required|integer|min:1',
            'method' => 'nullable|string|max:32',
            'payer_id' => 'nullable|exists:users,id',
            'external_ref' => 'nullable|string|max:100',
        ]);

        return DB::transaction(function () use ($invoice, $data) {
            $allocAmount = min((int)$data['amount_minor'], (int)$invoice->balance_minor);

            /** @var Receipt $receipt */
            $receipt = Receipt::create([
                'seller_id' => $invoice->seller_id,
                'payer_id' => $data['payer_id'] ?? $invoice->buyer_id,
                'number' => DocumentNumberService::nextNumber($invoice->seller_id, 'receipt', (int) now()->format('Y'), 'RCPT-' . $invoice->seller_id . '-' . now()->format('Y') . '-'),
                'type' => 'payment_receipt',
                'currency' => $invoice->currency,
                'amount_minor' => $allocAmount,
                'method' => $data['method'] ?? null,
                'external_ref' => $data['external_ref'] ?? null,
                'paid_at' => now(),
            ]);

            ReceiptAllocation::create([
                'receipt_id' => $receipt->id,
                'invoice_id' => $invoice->id,
                'amount_minor' => $allocAmount,
            ]);

            $invoice->applyAllocation($allocAmount);

            return response()->json(['receipt' => $receipt->load('allocations')], 201);
        });
    }
}
