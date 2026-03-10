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
use Illuminate\Support\Facades\Log;

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
        $payment->load(['payable']);

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

    //     \Log::info('Payment status checked', [
    //     'payment_ulid' => $payment->ulid,
    //     'payment_id' => $payment->id,
    //     'status' => $payment->status->value,
    //     'verification_success' => $response->success,
    //     'verification_message' => $response->message,
    // ]);

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
       // \Log::info("MPESA callback received", $request->all());
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
//    Log::info('Flexible payment status check hit', [
//        'id' => $id,
//        'ip' => request()->ip(),
//        'user_id' => auth()->id(),
//    ]);

    // 1) Check Payment model first
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
                'resultCode' => $response->resultCode ?? null,
                'state' => $response->state ?? 'PENDING',
            ],
        ]);
    }

    // 2) Check MpesaRequest
    $log = $this->findMpesaLogByAnyId($id);
    if ($log) {
        [$success, $message, $normalizedStatus, $resultCode, $state] = $this->mapMpesaVerification($log);

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
                'resultCode' => $resultCode,
                'state' => $state,
            ],
        ]);
    }

    // 3) Payment not found
    return response()->json([
        'message' => 'Payment not found',
        'verification' => [
            'success' => false,
            'message' => 'Payment not found',
            'resultCode' => null,
            'state' => 'PENDING',
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
    $normalizedStatus = $this->normalizeStatus($log);

    $resultCode = null;
    $resultDesc = null;

    // Extract STK callback if available
    if (is_array($log->callback_payload)) {
        $stk = $log->callback_payload['Body']['stkCallback'] ?? null;
        if (is_array($stk)) {
            $resultCode = isset($stk['ResultCode']) ? (string) $stk['ResultCode'] : null;
            $resultDesc = $stk['ResultDesc'] ?? null;
        }
    }

    // Determine if this request is truly finalized
    $isFinalized =
        in_array($normalizedStatus, ['COMPLETED', 'SUCCESS', 'FAILED', 'CANCELED'], true)
        || $log->completed_at !== null;

    // Determine success / message
    $success = null; // null = still pending
    $message = 'Payment is being processed.';

    if ($resultCode !== null) {
        switch ($resultCode) {
            case '0': // Success
                $success = true;
                $message = 'Payment completed successfully.';
                break;

            case '1032':
            case '1037':
                if ($isFinalized) {
                    $success = false;
                    $message = $resultDesc
                        ?? ($resultCode === '1032'
                            ? 'Payment cancelled by user.'
                            : 'Payment request expired or no response from user.');
                } else {

                    $success = null;
                    $message = 'Awaiting user confirmation.';
                }
                break;

            default:
                $success = null;
                $message = $resultDesc ?? 'Payment is being processed.';
                break;
        }
    }

    // Canonical state machine
    $state = match (true) {
        $success === true => 'SUCCESS',
        $success === false && $isFinalized => 'CANCELED',
        $success === false => 'FAILED',
        default => 'PENDING',
    };

    return [
        $success,
        $message,
        $normalizedStatus,
        $resultCode,
        $state,
    ];
}



private function normalizeStatus(MpesaRequest $log): string
{
    if (is_string($log->status)) {
        return $log->status;
    }

    if (is_object($log->status) && property_exists($log->status, 'value')) {
        return (string) $log->status->value;
    }

    return '';
}


}
