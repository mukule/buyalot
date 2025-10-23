<?php

namespace App\Http\DTOs;

use Spatie\LaravelData\Data;

class PaymentRequest extends Data
{
    public function __construct(
        public string $provider,
        public string $method,
        public float $amount,
        public string $currency,
        public ?string $phone = null,
        public ?string $email = null,
        public ?array $metadata = null,
        public ?string $callbackUrl = null,
        public ?string $returnUrl = null,
    ) {}
}
