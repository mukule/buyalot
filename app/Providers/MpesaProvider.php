<?php

namespace App\Providers;

use App\Contracts\PaymentProviderInterface;
use App\Http\DTOs\PaymentRequest;
use App\Http\DTOs\PaymentResponse;
use App\Models\Payment\MpesaPayment;
use App\Models\Payment\MpesaRequest;
use App\Models\Payment\Payment;
use App\Models\Payment\PaymentStatus;
use App\Models\Payment\PaymentTransaction;
use Carbon\Carbon;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Log;

class MpesaProvider implements PaymentProviderInterface
{
    private array $config;
    private ?string $accessToken = null;

    public function __construct()
    {
        // Ensure config is always an array to satisfy typed property and avoid TypeErrors when config is missing
        $this->config = (array) (config('payment.providers.mpesa', []) ?? []);
    }

    public function initialize(MpesaRequest $payment, PaymentRequest $request): PaymentResponse
    {
        try {
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
                // Use plain string statuses on MpesaRequest to avoid enum-to-string errors
                $payment->update([
                    'status' => 'failed',
                    'result_desc' => $stkResponse['message'] ?? null,
                    'provider_response' => $stkResponse['data']
                ]);

                return PaymentResponse::failed($stkResponse['message']);
            }

            // Save MPESA request IDs
            // Use plain string statuses on MpesaRequest to avoid enum-to-string errors
            $payment->update([
                'status' => 'processing',
                'request_type' => 'STK_PUSH',
                'reference' => $stkResponse['data']['CheckoutRequestID'],
                'checkout_request_id' => $stkResponse['data']['CheckoutRequestID'],
                'merchant_request_id' => $stkResponse['data']['MerchantRequestID'],
                'phone' => $phone,
            ]);

            return PaymentResponse::success(
                'Payment initiated successfully. Please complete the payment on your phone.',
                [
                    'checkout_request_id' => $payment->checkout_request_id,
                    'phone_number' => $phone
                ]
            );

        } catch (\Throwable $e) {
            Log::error('M-Pesa initialization failed', [
                'payment_id' => $payment->id,
                'error' => $e->getMessage()
            ]);

            // Ensure we do not call methods that don't exist on MpesaRequest. Persist failure safely.
            $payment->update([
                'status' => 'failed',
                'result_desc' => 'System error during initialization',
            ]);
            return PaymentResponse::failed('Payment initialization failed, Please try again');
        }
    }

    public function handleCallback(array $data): PaymentResponse
    {
        try {
            Log::info('MPESA callback received', $data);

            $stk = $data['Body']['stkCallback'] ?? null;
            if (!$stk) {
                return PaymentResponse::failed('Invalid callback data');
            }

            $checkoutRequestId = $stk['CheckoutRequestID'];
            $mpesaRequest = MpesaRequest::where('checkout_request_id', $checkoutRequestId)->first();

            if (!$mpesaRequest) {
                return PaymentResponse::failed('Payment not found');
            }

            $mpesaRequest->callback_payload = $data;

            // Result Code
            $resultCode = $stk['ResultCode'];

            if ($resultCode == 0) {
                // SUCCESS
                $metadata = $this->parseCallbackMetadata(
                    $stk['CallbackMetadata']['Item'] ?? []
                );
            // 1. Mark MpesaRequest as completed
                $mpesaRequest->update([
                    'status' => PaymentStatus::COMPLETED->value,
                    'result_code' => 0,
                    'result_desc' => 'Success',
                    'completed_at' => now(),
                ]);

                // 2. Create Payment record
                $payment =Payment::create([
                    'ulid' => \Str::ulid(), // generate ULID
                    'amount' => $mpesaRequest->amount,
                    'currency' => 'KES', // or $mpesaRequest->currency
                    'provider' => 'mpesa',
                    'method' => $mpesaRequest->method ?? 'mobile_money',
                    'status' => PaymentStatus::COMPLETED->value,
                    'reference' => $mpesaRequest->reference,
                    'provider_reference' => $mpesaRequest->checkout_request_id,
                    'metadata' => [
                        'mpesa_callback' => $mpesaRequest->callback_payload,
                        'account_reference' => $mpesaRequest->account_reference,
                    ],
                    'completed_at' => now(),
                    'payable_type' => $mpesaRequest->payable_type,
                    'payable_id' => $mpesaRequest->payable_id,
                ]);

                $callbackMetadata = $mpesaRequest->callback_payload['Body']['stkCallback']['CallbackMetadata']['Item'] ?? [];
                $metadata = [];
                foreach ($callbackMetadata as $item) {
                    $metadata[strtolower($item['Name'])] = $item['Value'] ?? null;
                }

            // 4. Create MpesaPayment record
                MpesaPayment::create([
                    'payment_id' => $payment->id,
                    'mpesa_request_id' => $mpesaRequest->id,
                    'transaction_type' => 'STK_PUSH',
                    'phone' => $metadata['phonenumber'] ?? null,
                    'amount' => $metadata['amount'] ?? $mpesaRequest->amount,
                    'mpesa_receipt' => $metadata['mpesareceiptnumber'] ?? null,
                    'transaction_id' => $metadata['mpesareceiptnumber'] ?? null,
                    'account_reference' => $mpesaRequest->account_reference,
                    'result_code' => $mpesaRequest->result_code,
                    'result_desc' => $mpesaRequest->result_desc,
                    'transaction_date' => $metadata['transactiondate'] ?? null,
                    'raw_payload' => $mpesaRequest->callback_payload,
                ]);
                // Update related order
                $this->markPayableAsPaid($mpesaRequest);

                return PaymentResponse::success('Payment completed successfully');

            } else {
                // FAILURE
                $mpesaRequest->update([
                    'status' => PaymentStatus::FAILED->value,
                    'result_code' => $resultCode,
                    'result_desc' => $stk['ResultDesc'] ?? 'Failed',
                ]);

                return PaymentResponse::failed($stk['ResultDesc'] ?? 'Payment failed');
            }

        } catch (\Throwable $e) {
            Log::error('MPESA callback error', [
                'error' => $e->getMessage()
            ]);

            return PaymentResponse::failed('Callback processing failed');
        }
    }


    private function initiateStkPush(MpesaRequest $payment, string $phone): array
    {
        $timestamp = now()->format('YmdHis');

        $password = base64_encode(
            $this->config['business_short_code'] .
            $this->config['passkey'] .
            $timestamp
        );

        $description = sprintf(
            "Payment for order %s",
            $payment->payable->order_code ?? $payment->id
        );

        // Log request for auditing
        $payment->request_payload = [
            'stk_push_url' => $this->config['stk_push_url'],
            'payload' => [
                'BusinessShortCode' => $this->config['business_short_code'],
                'Password' => $password,
                'Timestamp' => $timestamp,
                'TransactionType' => 'CustomerPayBillOnline',
                'Amount' => (int)$payment->amount,
                'PartyA' => $phone,
                'PartyB' => $this->config['business_short_code'],
                'PhoneNumber' => $phone,
                'CallBackURL' => $this->config['callback_url'],
                'AccountReference' => $description,
                'TransactionDesc' => $description,
            ],
        ];
        $payment->save();

        try {
            $response = Http::withToken($this->accessToken)
                ->timeout(60)
                ->post($this->config['stk_push_url'], $payment->request_payload['payload']);

            if (!$response->successful()) {
                return [
                    'success' => false,
                    'message' => 'Failed to connect to M-Pesa',
                    'data' => $response->body()
                ];
            }

            $data = $response->json();

            if (isset($data['ResponseCode']) && $data['ResponseCode'] === '0') {
                return ['success' => true, 'data' => $data];
            }

            return [
                'success' => false,
                'message' => $data['errorMessage'] ?? 'STK push failed',
                'data' => $data
            ];
        } catch (\Throwable $e) {
            Log::error('M-Pesa STK push error', ['error' => $e->getMessage()]);
            return [
                'success' => false,
                'message' => 'Failed to connect to M-Pesa',
                'data' => $e->getMessage()
            ];
        }
    }

    public function refund(Payment $payment, float $amount = null): PaymentResponse
    {
        return PaymentResponse::failed('M-Pesa refunds must be processed manually');
    }

    public function verify(Payment $payment): PaymentResponse
    {
        try {
            $checkoutRequestId = $payment->metadata['checkout_request_id'] ?? null;

            $timeout = 20; // seconds
            $interval = 1; // seconds between retries
            $elapsed = 0;

            // Retry until we have a checkoutRequestId or timeout reached
            while ($checkoutRequestId === null && $elapsed < $timeout) {
                sleep($interval);
                $elapsed += $interval;

                $payment->refresh(); // reload latest data from DB
                $checkoutRequestId = $payment->metadata['checkout_request_id'] ?? null;
            }

            if ($checkoutRequestId === null) {
                return PaymentResponse::failed('CheckoutRequestID not available after 20 seconds.');
            }

            // Proceed with the usual verification
            $accessToken = $this->getAccessToken();
            if (!$accessToken) {
                return PaymentResponse::failed('Failed to authenticate with M-Pesa');
            }

            $queryResponse = $this->queryTransaction($checkoutRequestId);

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
            }

            $errorMessage = $queryResponse['data']['ResultDesc'] ?? 'Payment failed';
            $payment->markAsFailed($errorMessage);
            return PaymentResponse::failed($errorMessage);

        } catch (\Exception $e) {
            Log::error('M-Pesa verification failed', [
                'payment_id' => $payment->id,
                'error' => $e->getMessage(),
            ]);

            return PaymentResponse::failed('Payment verification failed');
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

    private function markPayableAsPaid(MpesaRequest $payment): void
    {
        try {
            $payable = $payment->payable;

            if (!$payable) {
                return;
            }

            // Update payment_status column if it exists
            if (isset($payable->payment_status)) {
                $payable->payment_status = 'paid';
            }

            // If the payable has a "status" field set it to confirmed
            if (isset($payable->status) && $payable->status === 'pending') {
                $payable->status = 'confirmed';
            }

            $payable->save();

        } catch (\Throwable $e) {
            \Log::warning('Failed to update payable on callback completion', [
                'payment_id' => $payment->id,
                'error' => $e->getMessage()
            ]);
        }
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
