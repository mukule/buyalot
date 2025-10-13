<?php

namespace App\Http\DTOs;

use Dflydev\DotAccessData\Data;

class PaymentRequest extends Data
{
    public function __construct(
        public string $provider,
        public string $method,
        public float $amount,
        public string $currency = 'KES',
        public ?string $phone = null,
        public ?string $email = null,
        public ?array $metadata = null,
        public ?string $callbackUrl = null,
        public ?string $returnUrl = null,
    ) {}

    /**
     * Create a PaymentRequest DTO from validated array input
     */
    public static function from(array $data): self
    {
        return new self(
            provider: (string)($data['provider'] ?? ''),
            method: (string)($data['method'] ?? ''),
            amount: (float)($data['amount'] ?? 0),
            currency: (string)($data['currency'] ?? 'KES'),
            phone: $data['phone'] ?? null,
            email: $data['email'] ?? null,
            metadata: isset($data['metadata']) && is_array($data['metadata']) ? $data['metadata'] : null,
            callbackUrl: $data['callback_url'] ?? ($data['callbackUrl'] ?? null),
            returnUrl: $data['return_url'] ?? ($data['returnUrl'] ?? null),
        );
    }

    /**
     * Export the DTO to a plain array (useful for logging)
     */
    public function toArray(): array
    {
        return [
            'provider' => $this->provider,
            'method' => $this->method,
            'amount' => $this->amount,
            'currency' => $this->currency,
            'phone' => $this->phone,
            'email' => $this->email,
            'metadata' => $this->metadata,
            'callback_url' => $this->callbackUrl,
            'return_url' => $this->returnUrl,
        ];
    }
}
