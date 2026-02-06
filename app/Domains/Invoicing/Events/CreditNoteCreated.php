<?php

namespace App\Domains\Invoicing\Events;

use App\Models\Billing\CreditNote;
use App\Models\Billing\Invoice;
use Illuminate\Foundation\Events\Dispatchable;
use Illuminate\Queue\SerializesModels;

class CreditNoteCreated
{
    use Dispatchable, SerializesModels;

    public function __construct(
        public CreditNote $creditNote,
        public Invoice $invoice
    ) {}
}
