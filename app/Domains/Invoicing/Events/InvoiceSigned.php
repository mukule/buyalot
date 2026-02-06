<?php

namespace App\Domains\Invoicing\Events;

use App\Models\Billing\Invoice;
use Illuminate\Foundation\Events\Dispatchable;
use Illuminate\Queue\SerializesModels;

class InvoiceSigned
{
    use Dispatchable, SerializesModels;

    public function __construct(
        public Invoice $invoice,
        public string $etimsReference
    ) {}
}
