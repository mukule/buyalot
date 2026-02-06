<?php

namespace App\Domains\Invoicing\Services;

use App\Domains\Invoicing\DTOs\CreateCreditNoteDTO;
use App\Domains\Invoicing\Enums\EtimsStatus;
use App\Domains\Invoicing\Events\CreditNoteCreated;
use App\Models\Billing\CreditNote;
use App\Models\Billing\Invoice;
use App\Services\DocumentNumberService;
use Illuminate\Support\Facades\DB;
use Illuminate\Validation\ValidationException;

class CreditNoteService
{
    public function create(CreateCreditNoteDTO $dto): CreditNote
    {
        return DB::transaction(function () use ($dto) {
            $invoice = Invoice::lockForUpdate()->findOrFail($dto->invoiceId);

            if (! in_array($invoice->status, ['issued', 'sent', 'partially_paid', 'paid'], true)) {
                throw ValidationException::withMessages(['invoice' => ['Credit notes can only be created for issued or paid invoices.']]);
            }

            $balanceMinor = (int) $invoice->balance_minor;
            if ($dto->amountMinor <= 0 || $dto->amountMinor > $balanceMinor) {
                throw ValidationException::withMessages(['amount_minor' => ['Credit amount must be positive and not exceed invoice balance.']]);
            }

            $year = (int) now()->format('Y');
            $prefix = 'CN-' . $invoice->seller_id . '-' . $year . '-';
            $number = DocumentNumberService::nextNumber($invoice->seller_id, 'credit_note', $year, $prefix);

            $creditNote = CreditNote::create([
                'invoice_id' => $invoice->id,
                'number' => $number,
                'amount_minor' => $dto->amountMinor,
                'currency' => $dto->currency,
                'reason' => $dto->reason,
                'etims_status' => EtimsStatus::PENDING,
            ]);

            $newBalance = max(0, $balanceMinor - $dto->amountMinor);
            $invoice->balance_minor = $newBalance;
            $invoice->status = $newBalance === 0 ? 'paid' : 'partially_paid';
            $invoice->save();

            CreditNoteCreated::dispatch($creditNote, $invoice);

            return $creditNote->load('invoice');
        });
    }

    /**
     * Mark credit note as signed by ETIMS (middleware integration point).
     */
    public function markSigned(CreditNote $creditNote, string $etimsReference): CreditNote
    {
        $creditNote->update([
            'etims_status' => EtimsStatus::SIGNED,
            'etims_reference' => $etimsReference,
        ]);

        return $creditNote->fresh();
    }
}
