<?php

namespace App\Domains\Invoicing\DTOs;

final readonly class CreateInvoiceDTO
{
    public function __construct(
        public int $sellerId,
        public ?int $buyerId,
        public ?string $buyerName,
        public ?string $buyerKraPin,
        public ?string $buyerEmail,
        public ?string $buyerPhone,
        public ?int $orderId,
        public string $type,
        public string $currency,
        public ?string $dueDate,
        /** @var array<int, array{product_id?: int, description: string, quantity: int, unit_price_minor: int, discount_minor?: int, tax_rate?: float}> */
        public array $items,
    ) {}
}
