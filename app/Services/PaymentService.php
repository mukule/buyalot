<?php

namespace App\Services;

use App\Contracts\PaymentProviderInterface;
use App\Http\DTOs\PaymentRequest;
use App\Http\DTOs\PaymentResponse;
use App\Models\Payment\MpesaRequest;
use App\Models\Payment\Payment;
use App\Models\Payment\PaymentLog;
use App\Models\Payment\PaymentProvider;
use App\Models\Payment\PaymentStatus;
use App\Providers\MpesaProvider;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Str;

class PaymentService
{
    private array $providers = [];

    public function __construct()
    {
        $this->registerProviders();
    }

    private function registerProviders(): void
    {
        $this->providers[PaymentProvider::MPESA->value] = app(MpesaProvider::class);
    }

    public function createPayment(Model $payable, PaymentRequest $request): Payment
    {
        $payment= Payment::create([
            'payable_type' => get_class($payable),
            'payable_id' => $payable->id,
            'amount' => $request->amount,
            'currency' => $request->currency,
            'provider' => $request->provider,
            'method' => $request->method,
            'status' => PaymentStatus::PENDING,
            'reference' => $this->generateReference(),
            'metadata' => $request->metadata,
            'expires_at' => now()->addMinutes(config('payment.expiry_minutes', 15)),
        ]);
        \Illuminate\Log\log($payment);

        return $payment;
    }

    public function initializePayment(MpesaRequest $log, PaymentRequest $request): PaymentResponse
    {
        $provider = $this->getProvider($log->provider);

        if (!$provider) {
            return PaymentResponse::failed('Payment provider not supported');
        }

        if (!$provider->isAvailable()) {
            return PaymentResponse::failed('Payment provider is not available');
        }

        return $provider->initialize($log, $request);
    }

    public function createMpesaRequest($payable, PaymentRequest $request)
    {
        $reference = $this->generateReference();
        return MpesaRequest::create([
            'payable_type' => get_class($payable),
            'payable_id' => $payable->id,
            'reference' => $reference,
            'request_code' => $reference,
            'amount' => $request->amount,
            'currency' => $request->currency,
            'status' => 'initialized',
            'provider' => $request->provider,
            'provider_request' => $request->toArray(),
            'provider_response' => [],
            'method' => $request->method,
            'callback_payload'=>'',
            'user_id' => auth()->id(),
        ]);
    }

    public function verifyPayment(Payment $payment): PaymentResponse
    {
        $provider = $this->getProvider($payment->provider);

        if (!$provider) {
            return PaymentResponse::failed('Payment provider not supported');
        }

        return $provider->verify($payment);
    }

    public function handleCallback(string $providerName, array $data): PaymentResponse
    {
        $provider = $this->getProvider($providerName);

        if (!$provider) {
            return PaymentResponse::failed('Payment provider not supported');
        }

        return $provider->handleCallback($data);
    }

    public function getAvailableProviders(): array
    {
        $available = [];

        foreach ($this->providers as $key => $provider) {
            if ($provider->isAvailable()) {
                $available[] = [
                    'key' => $key,
                    'name' => PaymentProvider::from($key)->label(),
                ];
            }
        }

        return $available;
    }

    private function getProvider(string $provider): ?PaymentProviderInterface
    {
        return $this->providers[$provider] ?? null;
    }

    private function generateReference(): string
    {
        return 'PAY_' . strtoupper(uniqid()) . '_' . time();
    }
}
