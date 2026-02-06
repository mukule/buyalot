<?php

namespace App\Domains\Invoicing\Services;

use App\Domains\Invoicing\DTOs\CreateDeliveryNoteDTO;
use App\Domains\Invoicing\Enums\EtimsStatus;
use App\Models\Billing\DeliveryNote;
use App\Models\Billing\DeliveryNoteItem;
use App\Models\Billing\Invoice;
use App\Services\DocumentNumberService;
use Illuminate\Support\Facades\DB;
use Illuminate\Validation\ValidationException;

class DeliveryNoteService
{
    public function create(CreateDeliveryNoteDTO $dto): DeliveryNote
    {
        return DB::transaction(function () use ($dto) {
            $invoice = Invoice::findOrFail($dto->invoiceId);

            if (! in_array($invoice->status, ['issued', 'sent', 'partially_paid', 'paid'], true)) {
                throw ValidationException::withMessages(['invoice' => ['Delivery notes can only be created for issued or paid invoices.']]);
            }

            $year = (int) now()->format('Y');
            $prefix = 'DN-' . $invoice->seller_id . '-' . $year . '-';
            $number = DocumentNumberService::nextNumber($invoice->seller_id, 'delivery_note', $year, $prefix);

            $deliveryNote = DeliveryNote::create([
                'invoice_id' => $invoice->id,
                'number' => $number,
                'delivery_address' => $dto->deliveryAddress,
                'status' => 'pending',
                'etims_status' => EtimsStatus::PENDING,
                'notes' => $dto->notes,
            ]);

            foreach ($dto->items as $item) {
                DeliveryNoteItem::create([
                    'delivery_note_id' => $deliveryNote->id,
                    'invoice_item_id' => $item['invoice_item_id'] ?? null,
                    'description' => $item['description'],
                    'quantity' => (int) $item['quantity'],
                ]);
            }

            return $deliveryNote->fresh('items');
        });
    }

    /**
     * Update delivery note status (dispatched / delivered).
     */
    public function updateStatus(DeliveryNote $deliveryNote, string $status): DeliveryNote
    {
        if (! in_array($status, ['pending', 'dispatched', 'delivered'], true)) {
            throw ValidationException::withMessages(['status' => ['Invalid delivery note status.']]);
        }

        $deliveryNote->update(['status' => $status]);
        return $deliveryNote->fresh();
    }

    /**
     * Mark delivery note as signed by ETIMS (middleware integration point).
     */
    public function markSigned(DeliveryNote $deliveryNote, string $etimsReference): DeliveryNote
    {
        $deliveryNote->update([
            'etims_status' => EtimsStatus::SIGNED,
            'etims_reference' => $etimsReference,
        ]);

        return $deliveryNote->fresh();
    }
}
