<?php

namespace App\Domains\Invoicing\DTOs;

final readonly class CreateCreditNoteDTO
{
    public function __construct(
        public int $invoiceId,
        public int $amountMinor,
        public string $currency,
        public ?string $reason = null,
    ) {}
}
