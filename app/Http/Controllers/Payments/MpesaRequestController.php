<?php

namespace App\Http\Controllers\Payments;

use App\Http\Controllers\Controller;
use App\Http\DTOs\PaymentRequest;
use App\Http\Requests\InitiatePaymentRequest;
use App\Models\Payment\MpesaRequest;
use App\Services\PaymentService;
use Illuminate\Http\JsonResponse;

class MpesaRequestController extends Controller
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
    public function initiate(InitiatePaymentRequest $request): JsonResponse
    {
        try {
            $payable = $request->getPayable();
            // If paying for an order, ensure there is enough stock before allowing payment
            if ($request->input('payable_type') === 'order') {
                /** @var \App\Models\Orders\Order $order */
                $order = $payable->loadMissing(['orderItems.productVariant.product']);

                // If already paid, block re-initiation
                if (isset($order->payment_status) && $order->payment_status === 'paid') {
                    return response()->json([
                        'message' => 'This order has already been paid.',
                    ], 400);
                }

                $insufficient = [];
                foreach ($order->orderItems as $item) {
                    $variant = $item->productVariant;
                    $available = (int)($variant?->stock ?? 0);
                    $requested = (int)$item->quantity;
                    if ($requested > $available) {
                        $insufficient[] = [
                            'product_variant_id' => $item->product_variant_id,
                            'requested' => $requested,
                            'available' => $available,
                            'product_name' => $variant?->product?->name ?? 'Unknown Product',
                        ];
                    }
                }

                if (!empty($insufficient)) {
                    return response()->json([
                        'message' => 'Some items are out of stock or have insufficient quantity. Please modify your order before paying.',
                        'items' => $insufficient,
                    ], 409);
                }

                // Always trust server-side computed totals for the amount to be paid
                // Override any client-provided amount to prevent 0.0 or tampered values
                $request->merge([
                    'amount' => (float) $order->total_amount,
                    'currency' => $order->currency ?? 'KES',
                ]);
            }

            $paymentRequest = PaymentRequest::from($request->validated());
//            $payment = $this->paymentService->createPayment($payable, $paymentRequest);
            // STEP 1 — Create a payment log
            $paymentLog = $this->paymentService->createMpesaRequest($payable, $paymentRequest);
            // STEP 2 — Initialize remote payment using the payment log
            $response = $this->paymentService->initializePayment($paymentLog, $paymentRequest);
//            $response = $this->paymentService->initializePayment($payment, $paymentRequest);

            if (!$response->success) {
                return response()->json([
                    'message' => $response->message,
                    'errors' => $response->errors,
                ], 400);
            }

            return response()->json([
                'message' => $response->message,
                'payment' => [
                    // Return numeric id for MpesaRequest (no ULID on this model)
                    'id' => $paymentLog->id,
                    'reference' => $paymentLog->reference,
                    'amount' => $paymentLog->amount,
                    'currency' => $paymentLog->currency,
                    // Normalize status to a safe string to avoid object-to-string errors
                    'status' => is_string($paymentLog->status)
                        ? $paymentLog->status
                        : ((is_object($paymentLog->status) && property_exists($paymentLog->status, 'value'))
                            ? (string) $paymentLog->status->value
                            : (is_scalar($paymentLog->status) ? (string) $paymentLog->status : '')),
                ],
                'data' => $response->data,
            ], 201);

        } catch (\Exception $e) {
            return response()->json([
                'message' => 'A server error occurred while processing the payment. Please try again.',
                // Surface raw error only in debug to avoid leaking internals to customers
                'error' => config('app.debug') ? $e->getMessage() : null,
            ], 500);
        }
    }

    /**
     * Poll verification status for an M-Pesa request using the CheckoutRequestID (or reference)
     */
    public function statusByCheckoutId(string $checkout_request_id): JsonResponse
    {
        /** @var MpesaRequest|null $log */
        $log = MpesaRequest::query()
            ->where('checkout_request_id', $checkout_request_id)
            ->orWhere('reference', $checkout_request_id)
            ->first();

        if (!$log) {
            return response()->json([
                'message' => 'Payment request not found',
                'verification' => [
                    'success' => false,
                    'message' => 'Payment request not found',
                ],
            ], 404);
        }

        // Default state from stored status — normalize without forcing object-to-string casts
        if (is_string($log->status)) {
            $status = strtolower($log->status);
        } elseif (is_object($log->status) && property_exists($log->status, 'value')) {
            $status = strtolower((string) $log->status->value);
        } else {
            $status = '';
        }
        $success = false;
        $message = 'Awaiting payment confirmation.';

        // Prefer callback payload if available
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
            // Fall back to status field mapping
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

        return response()->json([
            'log' => [
                'id' => $log->id,
                'reference' => $log->reference,
                'checkout_request_id' => $log->checkout_request_id,
                'amount' => $log->amount,
                'currency' => $log->currency,
                'status' => is_string($log->status)
                    ? $log->status
                    : ((is_object($log->status) && property_exists($log->status, 'value'))
                        ? (string) $log->status->value
                        : (is_scalar($log->status) ? (string) $log->status : '')),
                'created_at' => $log->created_at,
            ],
            'verification' => [
                'success' => $success,
                'message' => $message,
            ],
        ]);
    }
}
