<?php

namespace App\Domains\Invoicing\DTOs;

final readonly class CreateDeliveryNoteDTO
{
    public function __construct(
        public int $invoiceId,
        /** @var array<string, mixed>|null */
        public ?array $deliveryAddress,
        /** @var array<int, array{invoice_item_id?: int, description: string, quantity: int}> */
        public array $items,
        public ?string $notes = null,
    ) {}
}
