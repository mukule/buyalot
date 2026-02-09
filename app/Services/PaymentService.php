<?php

namespace App\Services;
use App\Contracts\PaymentProviderInterface;
use App\Http\DTOs\PaymentRequest;
use App\Http\DTOs\PaymentResponse;
use App\Models\Payment\MpesaRequest;
use App\Models\Payment\Payment;
use App\Models\Payment\PaymentProvider;
use App\Models\Payment\PaymentStatus;
use App\Providers\MpesaProvider;
use Illuminate\Database\Eloquent\Model;

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
            'status' => PaymentStatus::PENDING->value,
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

    try {
        $response = $provider->initialize($log, $request);
        // \Log::info('Payment provider response', [
        //     'payment_id' => $log->id,
        //     'response' => $response->toArray()
        // ]);
        return $response;
    } catch (\Exception $e) {
        // \Log::error('Payment initialization failed', [
        //     'payment_id' => $log->id,
        //     'error' => $e->getMessage(),
        //     'trace' => $e->getTraceAsString(),
        // ]);
        return PaymentResponse::failed('Server error while processing payment: ' . $e->getMessage());
    }
}


public function getOrCreateMpesaRequest($payable, PaymentRequest $request)
{
    // Only consider records that can still be updated
    $updatableStatuses = [
        PaymentStatus::INITIALIZED->value,
        PaymentStatus::PROCESSING->value,
        PaymentStatus::FAILED->value,
        PaymentStatus::EXPIRED->value,
    ];

    // Get the latest updatable payment request for this payable
    $mpesaLog = MpesaRequest::where('payable_type', get_class($payable))
        ->where('payable_id', $payable->id)
        ->whereIn('status', $updatableStatuses)
        ->latest('created_at')
        ->first();

    if ($mpesaLog) {
        // Update the existing one
        $mpesaLog->update([
            'amount'           => $request->amount,
            'currency'         => $request->currency,
            'phone'            => $request->phone,
            'provider_request' => $request->toArray(),
            'updated_at'       => now(),
        ]);

        return $mpesaLog;
    }

    // No updatable record found — create a new one
    $reference = $payable->ref_num;

    return MpesaRequest::create([
        'payable_type'      => get_class($payable),
        'payable_id'        => $payable->id,
        'reference'         => $reference,
        'account_reference' => $reference,
        'request_code'      => $reference,
        'phone'             => $request->phone,
        'amount'            => $request->amount,
        'currency'          => $request->currency,
        'status'            => PaymentStatus::INITIALIZED->value,
        'provider'          => $request->provider,
        'provider_request'  => $request->toArray(),
        'provider_response' => [],
        'method'            => $request->method,
        'callback_payload'  => '',
        'user_id'           => auth()->id(),
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
