<?php

namespace App\Contracts;

use App\Http\DTOs\PaymentRequest;
use App\Http\DTOs\PaymentResponse;
use App\Models\Payment\Payment;

interface PaymentProviderInterface
{
    public function initialize(Payment $payment, PaymentRequest $request): PaymentResponse;

    public function verify(Payment $payment): PaymentResponse;

    public function handleCallback(array $data): PaymentResponse;

    public function refund(Payment $payment, float $amount = null): PaymentResponse;

    public function getProvider(): string;

    public function isAvailable(): bool;
}
