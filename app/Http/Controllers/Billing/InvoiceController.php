<?php

namespace App\Http\Controllers\Billing;

use App\Domains\Invoicing\DTOs\CreateInvoiceDTO;
use App\Domains\Invoicing\Services\InvoiceService;
use App\Http\Controllers\Controller;
use App\Models\Billing\Invoice;
use App\Models\Billing\InvoiceItem;
use App\Models\Billing\Receipt;
use App\Models\Billing\ReceiptAllocation;
use App\Services\DocumentNumberService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;

class InvoiceController extends Controller
{
    public function __construct(
        private InvoiceService $invoiceService
    ) {}

    public function index(Request $request): JsonResponse
    {
        $query = Invoice::query()
            ->when($request->seller_id, fn ($q) => $q->where('seller_id', $request->integer('seller_id')))
            ->when($request->status, fn ($q) => $q->where('status', $request->string('status')))
            ->when($request->type, fn ($q) => $q->where('type', $request->string('type')))
            ->when($request->etims_status, fn ($q) => $q->where('etims_status', $request->string('etims_status')))
            ->with(['creditNotes', 'deliveryNotes'])
            ->orderByDesc('id');

        return response()->json($query->paginate(20));
    }

    public function show(Invoice $invoice): JsonResponse
    {
        $invoice->load(['items', 'creditNotes', 'deliveryNotes.items']);
        return response()->json($invoice);
    }

    public function store(Request $request): JsonResponse
    {
        $data = $request->validate([
            'seller_id' => 'required|exists:sellers,id',
            'buyer_id' => 'nullable|exists:users,id',
            'buyer_name' => 'nullable|string|max:255',
            'buyer_kra_pin' => 'nullable|string|max:32',
            'buyer_email' => 'nullable|email|max:255',
            'buyer_phone' => 'nullable|string|max:32',
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
            'items.*.tax_rate' => 'nullable|numeric|min:0',
        ]);

        $dto = new CreateInvoiceDTO(
            sellerId: $data['seller_id'],
            buyerId: $data['buyer_id'] ?? null,
            buyerName: $data['buyer_name'] ?? null,
            buyerKraPin: $data['buyer_kra_pin'] ?? null,
            buyerEmail: $data['buyer_email'] ?? null,
            buyerPhone: $data['buyer_phone'] ?? null,
            orderId: $data['order_id'] ?? null,
            type: $data['type'] ?? 'tax_invoice',
            currency: strtoupper($data['currency']),
            dueDate: $data['due_date'] ?? null,
            items: $data['items'],
        );

        $invoice = $this->invoiceService->create($dto, ! empty($data['issue_now']));
        return response()->json($invoice, 201);
    }

    public function issue(Invoice $invoice): JsonResponse
    {
        try {
            $invoice = $this->invoiceService->issue($invoice);
            return response()->json($invoice);
        } catch (\Illuminate\Validation\ValidationException $e) {
            return response()->json(['message' => $e->getMessage()], 422);
        }
    }

    /**
     * Mark invoice as signed by ETIMS (middleware integration point).
     */
    public function etimsSigned(Request $request, Invoice $invoice): JsonResponse
    {
        $data = $request->validate([
            'etims_reference' => 'required|string|max:128',
        ]);

        try {
            $invoice = $this->invoiceService->markSigned($invoice, $data['etims_reference']);
            return response()->json($invoice);
        } catch (\Throwable $e) {
            return response()->json(['message' => $e->getMessage()], 422);
        }
    }

    /**
     * Mark invoice ETIMS submission as failed.
     */
    public function etimsFailed(Invoice $invoice): JsonResponse
    {
        try {
            $invoice = $this->invoiceService->markEtimsFailed($invoice);
            return response()->json($invoice);
        } catch (\Throwable $e) {
            return response()->json(['message' => $e->getMessage()], 422);
        }
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
                'buyer_name' => $invoice->buyer_name,
                'buyer_kra_pin' => $invoice->buyer_kra_pin,
                'buyer_email' => $invoice->buyer_email,
                'buyer_phone' => $invoice->buyer_phone,
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
                'etims_status' => $invoice->etims_status?->value ?? 'pending',
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
