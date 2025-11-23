<?php

namespace App\Http\Controllers\Payments;

use App\Http\Controllers\Controller;
use App\Http\DTOs\PaymentRequest;
use App\Http\Requests\InitiatePaymentRequest;
use App\Models\Payment\Payment;
use App\Models\Payment\MpesaRequest;
use App\Services\PaymentService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Inertia\Inertia;
use Illuminate\Support\Str;

class PaymentController extends Controller
{
    public function __construct(
        private readonly PaymentService $paymentService
    ) {}

    public function providers(): JsonResponse
    {
        return response()->json([
            'data' => $this->paymentService->getAvailableProviders(),
        ]);
    }
    public function index(Request $request)
    {
        $query = Payment::query()
            ->with('payable')
            ->orderBy('created_at', 'desc');

        if ($request->filled('search')) {
            $search = $request->get('search');
            $query->where(function ($q) use ($search) {
                $q->where('reference', 'like', "%{$search}%")
                    ->orWhere('provider', 'like', "%{$search}%")
                    ->orWhere('method', 'like', "%{$search}%")
                    ->orWhere('status', 'like', "%{$search}%");
            });
        }

        $payments = $query->paginate(15)->withQueryString();

        return Inertia::render('Admin/Payments/Index', [
            'payments' => $payments,
            'filters' => $request->only('search'),
        ]);
    }

    public function show(Payment $payment): \Inertia\Response
    {
        $payment->load(['transactions', 'payable']);

        return Inertia::render('Admin/Payments/Show', [
            'payment' => $payment,
        ]);
    }

    public function verify(Request $request, Payment $payment): \Illuminate\Http\JsonResponse
    {
        /** @var PaymentService $service */
        $service = app(PaymentService::class);
        $result = $service->verifyPayment($payment);

        return response()->json([
            'success' => $result->success,
            'message' => $result->message,
            'data' => $result->data,
        ], $result->success ? 200 : 422);
    }


    public function status(Payment $payment): JsonResponse
    {
        $response = $this->paymentService->verifyPayment($payment);

        return response()->json([
            'payment' => [
                'id' => $payment->ulid,
                'reference' => $payment->reference,
                'amount' => $payment->amount,
                'currency' => $payment->currency,
                'status' => $payment->status->value,
                'provider' => $payment->provider,
                'method' => $payment->method,
                'expires_at' => $payment->expires_at,
                'completed_at' => $payment->completed_at,
                'created_at' => $payment->created_at,
            ],
            'verification' => [
                'success' => $response->success,
                'message' => $response->message,
            ],
        ]);
    }

    public function callback(string $provider, Request $request): JsonResponse
    {
        $response = $this->paymentService->handleCallback($provider, $request->all());

        return response()->json([
            'message' => $response->message,
            'success' => $response->success,
        ], $response->success ? 200 : 400);
    }

    /**
     * Flexible status endpoint that tolerates eventual creation of Payment records.
     * It waits up to 20 seconds, retrying every 2 seconds. Accepts either:
     * - Payment ULID or numeric ID, or
     * - MpesaRequest ID/reference/CheckoutRequestID
     */
    public function statusFlexible(string $id): JsonResponse
    {
        $maxTries = 10; // 10 * 2s = 20 seconds
        $sleepSeconds = 2;
        // In tests, do not actually sleep or loop for long
        if (app()->environment('testing')) {
            $maxTries = 1;
            $sleepSeconds = 0;
        }

        for ($i = 0; $i < $maxTries; $i++) {
            // 1) Try to resolve a Payment by ULID or numeric id
            $payment = $this->findPaymentByAnyId($id);
            if ($payment) {
                $response = $this->paymentService->verifyPayment($payment);

                return response()->json([
                    'payment' => [
                        'id' => $payment->ulid ?? (string)$payment->id,
                        'reference' => $payment->reference,
                        'amount' => $payment->amount,
                        'currency' => $payment->currency,
                        'status' => is_string($payment->status)
                            ? $payment->status
                            : ((is_object($payment->status) && property_exists($payment->status, 'value'))
                                ? (string)$payment->status->value
                                : (is_scalar($payment->status) ? (string)$payment->status : '')),
                        'provider' => $payment->provider,
                        'method' => $payment->method,
                        'expires_at' => $payment->expires_at,
                        'completed_at' => $payment->completed_at,
                        'created_at' => $payment->created_at,
                    ],
                    'verification' => [
                        'success' => $response->success,
                        'message' => $response->message,
                    ],
                ]);
            }

            // 2) Try to resolve MpesaRequest by id/reference/checkout id
            $log = $this->findMpesaLogByAnyId($id);
            if ($log) {
                [$success, $message, $normalizedStatus] = $this->mapMpesaVerification($log);
                // If we have a terminal state (success or explicit failure), return immediately
                if ($success === true || ($success === false && $message !== 'Payment is being processed.' && $message !== 'Awaiting payment confirmation.')) {
                    return response()->json([
                        'log' => [
                            'id' => $log->id,
                            'reference' => $log->reference,
                            'checkout_request_id' => $log->checkout_request_id,
                            'amount' => $log->amount,
                            'currency' => $log->currency,
                            'status' => $normalizedStatus,
                            'created_at' => $log->created_at,
                        ],
                        'verification' => [
                            'success' => $success,
                            'message' => $message,
                        ],
                    ]);
                }
                // else: still processing → keep waiting
            }

            // If not last try, wait before retrying
            if ($i < $maxTries - 1 && $sleepSeconds > 0) {
                sleep($sleepSeconds);
            }
        }

        return response()->json([
            'message' => 'Payment verification timed out. If you approved on your phone, your order will update shortly. Please try again.',
            'verification' => [
                'success' => false,
                'message' => 'Payment verification timed out. If you approved on your phone, your order will update shortly. Please try again.',
            ],
        ], 404);
    }

    private function findPaymentByAnyId(string $id): ?Payment
    {
        // ULID-like check; still safe to query both
        $query = Payment::query();
        return $query->where('ulid', $id)
            ->orWhere(function ($q) use ($id) {
                if (ctype_digit($id)) {
                    $q->orWhere('id', (int)$id);
                }
            })
            ->first();
    }

    private function findMpesaLogByAnyId(string $id): ?MpesaRequest
    {
        $q = MpesaRequest::query();
        if (ctype_digit($id)) {
            $q->where('id', (int)$id);
        }
        return $q->orWhere('reference', $id)
            ->orWhere('checkout_request_id', $id)
            ->first();
    }

    /**
     * Map MpesaRequest row to verification tuple [success, message, normalizedStatus]
     */
    private function mapMpesaVerification(MpesaRequest $log): array
    {
        // Normalize status to safe string
        if (is_string($log->status)) {
            $status = strtolower($log->status);
            $normalizedStatus = $log->status;
        } elseif (is_object($log->status) && property_exists($log->status, 'value')) {
            $status = strtolower((string)$log->status->value);
            $normalizedStatus = (string)$log->status->value;
        } else {
            $status = '';
            $normalizedStatus = '';
        }

        $success = false;
        $message = 'Awaiting payment confirmation.';

        $resultCode = null;
        $resultDesc = null;
        if (is_array($log->callback_payload)) {
            $stk = $log->callback_payload['Body']['stkCallback'] ?? null;
            if (is_array($stk)) {
                $resultCode = $stk['ResultCode'] ?? null;
                $resultDesc = $stk['ResultDesc'] ?? null;
            }
        }

        if ($resultCode !== null) {
            if ((string)$resultCode === '0' || $resultCode === 0) {
                $success = true;
                $message = 'Payment completed successfully.';
            } else {
                $success = false;
                $message = $resultDesc ?: 'Payment failed.';
            }
        } else {
            if (in_array($status, ['completed', 'success', 'paid'], true)) {
                $success = true;
                $message = 'Payment completed successfully.';
            } elseif (in_array($status, ['failed', 'cancelled', 'canceled', 'expired'], true)) {
                $success = false;
                $message = $log->result_desc ?: 'Payment failed.';
            } else {
                $success = false;
                $message = 'Payment is being processed.';
            }
        }

        return [$success, $message, $normalizedStatus];
    }

}
