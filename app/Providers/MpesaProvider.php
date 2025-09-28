<?php

namespace App\Providers;

use App\Contracts\PaymentProviderInterface;
use App\Http\DTOs\PaymentRequest;
use App\Http\DTOs\PaymentResponse;
use App\Models\Payment\Payment;
use App\Models\Payment\PaymentStatus;
use App\Models\Payment\PaymentTransaction;
use Carbon\Carbon;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Log;
use Mockery\Exception;

class MpesaProvider implements PaymentProviderInterface
{
    private array $config;
    private ?string $accessToken = null;

    public function __construct()
    {
        $this->config = config('payment.providers.mpesa');
    }

    public function initialize(Payment $payment, PaymentRequest $request): PaymentResponse
    {
        try {
            $this->logTransaction($payment, 'initialize', 'started', $request->toArray());

            if (!$request->phone) {
                return PaymentResponse::failed('Phone number is required for M-Pesa payments');
            }

            $phone = $this->formatPhoneNumber($request->phone);
            if (!$phone) {
                return PaymentResponse::failed('Invalid phone number format');
            }

            $accessToken = $this->getAccessToken();
            if (!$accessToken) {
                return PaymentResponse::failed('Failed to authenticate with M-Pesa');
            }

            $stkResponse = $this->initiateStkPush($payment, $phone);

            if (!$stkResponse['success']) {
                $this->logTransaction($payment, 'initialize', 'failed', [], $stkResponse['data']);
                $payment->markAsFailed($stkResponse['message']);
                return PaymentResponse::failed($stkResponse['message']);
            }

            // Update payment with M-Pesa details
            $payment->update([
                'status' => PaymentStatus::PROCESSING,
                'provider_reference' => $stkResponse['data']['CheckoutRequestID'],
                'metadata' => array_merge($payment->metadata ?? [], [
                    'checkout_request_id' => $stkResponse['data']['CheckoutRequestID'],
                    'merchant_request_id' => $stkResponse['data']['MerchantRequestID'],
                    'phone_number' => $phone,
                ]),
            ]);

            $this->logTransaction(
                $payment,
                'initialize',
                'success',
                ['phone' => $phone],
                $stkResponse['data']
            );

            return PaymentResponse::success(
                'Payment initiated successfully. Please complete the payment on your phone.',
                [
                    'checkout_request_id' => $stkResponse['data']['CheckoutRequestID'],
                    'phone_number' => $phone,
                ]
            );

        } catch (\Exception $e) {
            Log::error('M-Pesa initialization failed', [
                'payment_id' => $payment->id,
                'error' => $e->getMessage(),
                'trace' => $e->getTraceAsString(),
            ]);

            $payment->markAsFailed('System error: ' . $e->getMessage());
            return PaymentResponse::failed('Payment initialization failed');
        }
    }

    public function verify(Payment $payment): PaymentResponse
    {
        try {
            $checkoutRequestId = $payment->metadata['checkout_request_id'] ?? null;

            if (!$checkoutRequestId) {
                return PaymentResponse::failed('Invalid payment reference');
            }

            $accessToken = $this->getAccessToken();
            if (!$accessToken) {
                return PaymentResponse::failed('Failed to authenticate with M-Pesa');
            }

            $queryResponse = $this->queryTransaction($checkoutRequestId);

            $this->logTransaction($payment, 'verify', 'processed', [], $queryResponse);

            if (!$queryResponse['success']) {
                return PaymentResponse::failed('Failed to verify payment status');
            }

            $resultCode = $queryResponse['data']['ResultCode'] ?? null;

            if ($resultCode === '0') {
                if (!$payment->isCompleted()) {
                    $payment->markAsCompleted();
                    $payment->update([
                        'metadata' => array_merge($payment->metadata ?? [], [
                            'mpesa_receipt_number' => $queryResponse['data']['MpesaReceiptNumber'] ?? null,
                            'verified_at' => now()->toISOString(),
                        ])
                    ]);
                }
                return PaymentResponse::success('Payment completed successfully');
            } elseif ($resultCode === '1032') {
                return PaymentResponse::success('Payment is being processed');
            } else {
                $errorMessage = $queryResponse['data']['ResultDesc'] ?? 'Payment failed';
                $payment->markAsFailed($errorMessage);
                return PaymentResponse::failed($errorMessage);
            }

        } catch (\Exception $e) {
            Log::error('M-Pesa verification failed', [
                'payment_id' => $payment->id,
                'error' => $e->getMessage(),
            ]);

            return PaymentResponse::failed('Payment verification failed');
        }
    }

    public function handleCallback(array $data): PaymentResponse
    {
        try {
            Log::info('M-Pesa callback received', $data);

            $stkCallback = $data['Body']['stkCallback'] ?? null;
            if (!$stkCallback) {
                return PaymentResponse::failed('Invalid callback data');
            }

            $checkoutRequestId = $stkCallback['CheckoutRequestID'] ?? null;
            if (!$checkoutRequestId) {
                return PaymentResponse::failed('Missing checkout request ID');
            }

            $payment = Payment::where('provider_reference', $checkoutRequestId)->first();
            if (!$payment) {
                Log::warning('Payment not found for callback', ['checkout_request_id' => $checkoutRequestId]);
                return PaymentResponse::failed('Payment not found');
            }

            $this->logTransaction($payment, 'callback', 'received', [], $stkCallback);

            $resultCode = $stkCallback['ResultCode'] ?? null;

            if ($resultCode == 0) {
                // Payment successful
                $callbackMetadata = $stkCallback['CallbackMetadata']['Item'] ?? [];
                $metadata = $this->parseCallbackMetadata($callbackMetadata);

                $payment->update([
                    'status' => PaymentStatus::COMPLETED,
                    'completed_at' => now(),
                    'metadata' => array_merge($payment->metadata ?? [], [
                        'mpesa_receipt_number' => $metadata['mpesa_receipt_number'] ?? null,
                        'transaction_date' => $metadata['transaction_date'] ?? null,
                        'phone_number' => $metadata['phone_number'] ?? null,
                        'amount_paid' => $metadata['amount'] ?? null,
                        'callback_processed_at' => now()->toISOString(),
                    ])
                ]);

                Log::info('M-Pesa payment completed', [
                    'payment_id' => $payment->id,
                    'receipt_number' => $metadata['mpesa_receipt_number'] ?? null,
                ]);

            } else {
                // Payment failed
                $errorMessage = $stkCallback['ResultDesc'] ?? 'Payment failed';
                $payment->markAsFailed($errorMessage);

                Log::info('M-Pesa payment failed', [
                    'payment_id' => $payment->id,
                    'result_code' => $resultCode,
                    'error_message' => $errorMessage,
                ]);
            }

            return PaymentResponse::success('Callback processed successfully');

        } catch (\Exception $e) {
            Log::error('M-Pesa callback processing failed', [
                'error' => $e->getMessage(),
                'data' => $data,
            ]);

            return PaymentResponse::failed('Callback processing failed');
        }
    }
    public function refund(Payment $payment, float $amount = null): PaymentResponse
    {
        return PaymentResponse::failed('M-Pesa refunds must be processed manually');
    }

    private function initiateStkPush(Payment $payment, string $phone): array
    {
        $timestamp = Carbon::now()->format('YmdHis');
        $password = base64_encode(
            $this->config['business_short_code'] .
            $this->config['passkey'] .
            $timestamp
        );
        info("initiated stk push: " .$timestamp);
        info("M-Pesa payment id: " . $payment->id);
        info("business_short_code ".$this->config['business_short_code']);

        try {
            $response = Http::withToken($this->accessToken)
                ->timeout(60)
                ->post($this->config['stk_push_url'], [
                    'BusinessShortCode' => $this->config['business_short_code'],
                    'Password' => $password,
                    'Timestamp' => $timestamp,
                    'TransactionType' => 'CustomerPayBillOnline',
                    'Amount' => (int)$payment->amount,
                    'PartyA' => $phone,
                    'PartyB' => $this->config['business_short_code'],
                    'PhoneNumber' => $phone,
                    'CallBackURL' => $this->config['callback_url'],
                    'AccountReference' => $payment->ulid,
                    'TransactionDesc' => $this->config['transaction_desc'] ?? "Payment for {$payment->ulid}",
                ]);
            info("initiated stk push response");
            info($response->body());
            if ($response->successful()) {
                $data = $response->json();
                if (isset($data['ResponseCode']) && $data['ResponseCode'] == '0') {
                    return ['success' => true, 'data' => $data];
                } else {
                    return [
                        'success' => false,
                        'message' => $data['errorMessage'] ?? 'STK push failed',
                        'data' => $data
                    ];
                }
            }

            return [
                'success' => false,
                'message' => 'Failed to connect to M-Pesa',
                'data' => $response->body()
            ];
        }catch (Exception $ex){
            \Illuminate\Log\log($ex);
            return [
                'success' => false,
                'message' => 'Failed to connect to M-Pesa',
                'data' => $ex->getMessage()
            ];
    }
    }

    private function queryTransaction(string $checkoutRequestId): array
    {
        $timestamp = Carbon::now()->format('YmdHis');
        $password = base64_encode(
            $this->config['business_short_code'] .
            $this->config['passkey'] .
            $timestamp
        );

        $response = Http::withToken($this->accessToken)
            ->timeout(30)
            ->post($this->config['query_url'], [
                'BusinessShortCode' => $this->config['business_short_code'],
                'Password' => $password,
                'Timestamp' => $timestamp,
                'CheckoutRequestID' => $checkoutRequestId,
            ]);

        if ($response->successful()) {
            return ['success' => true, 'data' => $response->json()];
        }

        return [
            'success' => false,
            'message' => 'Query request failed',
            'data' => $response->json()
        ];
    }

    private function getAccessToken(): ?string
    {
        if ($this->accessToken) {
            return $this->accessToken;
        }

        try {
            $credentials = base64_encode(
                $this->config['consumer_key'] . ':' . $this->config['consumer_secret']
            );

            $response = Http::withHeaders([
                'Authorization' => 'Basic ' . $credentials,
            ])->timeout(30)->get($this->config['auth_url']);

            if ($response->successful()) {
                $data = $response->json();
                $this->accessToken = $data['access_token'] ?? null;
                return $this->accessToken;
            }

            Log::error('M-Pesa auth failed', $response->json());
            return null;

        } catch (\Exception $e) {
            Log::error('M-Pesa auth exception', ['error' => $e->getMessage()]);
            return null;
        }
    }

    private function formatPhoneNumber(string $phone): ?string
    {
        // Remove all non-numeric characters
        $phone = preg_replace('/\D/', '', $phone);

        // Handle different formats
        if (preg_match('/^254\d{9}$/', $phone)) {
            return $phone; // Already in correct format
        } elseif (preg_match('/^0\d{9}$/', $phone)) {
            return '254' . substr($phone, 1); // Remove leading 0 and add 254
        } elseif (preg_match('/^\d{9}$/', $phone)) {
            return '254' . $phone; // Add 254 prefix
        }

        return null; // Invalid format
    }

    private function parseCallbackMetadata(array $items): array
    {
        $metadata = [];

        foreach ($items as $item) {
            $name = $item['Name'] ?? '';
            $value = $item['Value'] ?? '';

            match ($name) {
                'Amount' => $metadata['amount'] = $value,
                'MpesaReceiptNumber' => $metadata['mpesa_receipt_number'] = $value,
                'TransactionDate' => $metadata['transaction_date'] = $value,
                'PhoneNumber' => $metadata['phone_number'] = $value,
                default => null,
            };
        }

        return $metadata;
    }

    private function logTransaction(
        Payment $payment,
        string $type,
        string $status,
        array $requestData = [],
        array $responseData = []
    ): void {
        PaymentTransaction::create([
            'payment_id' => $payment->id,
            'type' => $type,
            'status' => $status,
            'request_data' => $requestData ?: null,
            'response_data' => $responseData ?: null,
        ]);
    }

    public function getProvider(): string
    {
        return 'mpesa';
    }

    public function isAvailable(): bool
    {
        return !empty($this->config['consumer_key']) &&
            !empty($this->config['consumer_secret']) &&
            !empty($this->config['business_short_code']) &&
            !empty($this->config['passkey']);
    }
}
