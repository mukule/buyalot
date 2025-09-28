<?php

namespace App\Http\DTOs;

use Spatie\LaravelData\Data;

class PaymentResponse extends Data
{
    public function __construct(
        public bool $success,
        public string $message,
        public ?string $reference = null,
        public ?array $data = null,
        public ?string $redirectUrl = null,
        public ?array $errors = null,
    ) {}

    public static function success(string $message, array $data = null, string $reference = null): self
    {
        return new self(
            success: true,
            message: $message,
            reference: $reference,
            data: $data
        );
    }

    public static function failed(string $message, array $errors = null): self
    {
        return new self(
            success: false,
            message: $message,
            errors: $errors
        );
    }
}
