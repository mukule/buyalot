<?php

namespace App\Domains\Invoicing\Services;

use App\Domains\Invoicing\DTOs\CreateInvoiceDTO;
use App\Domains\Invoicing\Enums\EtimsStatus;
use App\Domains\Invoicing\Events\InvoiceCreated;
use App\Domains\Invoicing\Events\InvoiceSigned;
use App\Models\Billing\Invoice;
use App\Models\Billing\InvoiceItem;
use App\Services\DocumentNumberService;
use Illuminate\Support\Facades\DB;
use Illuminate\Validation\ValidationException;

class InvoiceService
{
    public function create(CreateInvoiceDTO $dto, bool $issueNow = false): Invoice
    {
        return DB::transaction(function () use ($dto, $issueNow) {
            $invoice = Invoice::create([
                'seller_id' => $dto->sellerId,
                'buyer_id' => $dto->buyerId,
                'buyer_name' => $dto->buyerName,
                'buyer_kra_pin' => $dto->buyerKraPin,
                'buyer_email' => $dto->buyerEmail,
                'buyer_phone' => $dto->buyerPhone,
                'order_id' => $dto->orderId,
                'number' => 'DRAFT',
                'type' => $dto->type,
                'status' => 'draft',
                'issue_date' => null,
                'due_date' => $dto->dueDate,
                'currency' => strtoupper($dto->currency),
                'subtotal_minor' => 0,
                'discount_minor' => 0,
                'tax_minor' => 0,
                'total_minor' => 0,
                'balance_minor' => 0,
                'etims_status' => EtimsStatus::PENDING,
            ]);

            $subtotal = 0;
            $discountTotal = 0;
            $taxTotal = 0;
            $grand = 0;

            foreach ($dto->items as $it) {
                $qty = (int) $it['quantity'];
                $unit = (int) $it['unit_price_minor'];
                $disc = (int) ($it['discount_minor'] ?? 0);
                $rate = isset($it['tax_rate']) ? (float) $it['tax_rate'] : 0.0;

                $lineNet = ($qty * $unit) - $disc;
                if ($lineNet < 0) {
                    throw ValidationException::withMessages(['items' => ['Line total cannot be negative.']]);
                }
                $lineTax = (int) round($lineNet * $rate, 0, PHP_ROUND_HALF_UP);
                $lineTotal = $lineNet + $lineTax;

                $subtotal += $qty * $unit;
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

            if ($issueNow) {
                $this->issue($invoice);
            }

            InvoiceCreated::dispatch($invoice);

            return $invoice->fresh(['items']);
        });
    }

    public function issue(Invoice $invoice): Invoice
    {
        if ($invoice->status !== 'draft') {
            throw ValidationException::withMessages(['invoice' => ['Only draft invoices can be issued.']]);
        }

        $year = (int) now()->format('Y');
        $sellerId = $invoice->seller_id;

        if ($invoice->type === 'proforma') {
            $prefix = 'PF-' . $sellerId . '-' . $year . '-';
            $number = DocumentNumberService::nextNumber($sellerId, 'proforma', $year, $prefix);
        } else {
            $prefix = 'INV-' . $sellerId . '-' . $year . '-';
            $number = DocumentNumberService::nextNumber($sellerId, 'invoice', $year, $prefix);
        }

        $invoice->number = $number;
        $invoice->markIssued();

        return $invoice->fresh();
    }

    /**
     * Mark invoice as signed by ETIMS (middleware integration point).
     */
    public function markSigned(Invoice $invoice, string $etimsReference): Invoice
    {
        $invoice->update([
            'etims_status' => EtimsStatus::SIGNED,
            'etims_reference' => $etimsReference,
        ]);

        InvoiceSigned::dispatch($invoice, $etimsReference);

        return $invoice->fresh();
    }

    /**
     * Mark invoice ETIMS submission as failed.
     */
    public function markEtimsFailed(Invoice $invoice): Invoice
    {
        $invoice->update(['etims_status' => EtimsStatus::FAILED]);
        return $invoice->fresh();
    }
}
