<?php

namespace App\Http\Controllers\Payments;

use App\Http\Controllers\Controller;
use App\Http\DTOs\PaymentRequest;
use App\Http\Requests\InitiatePaymentRequest;
use App\Models\Payment\Payment;
use App\Services\PaymentService;
use Illuminate\Http\Request;
use Illuminate\Http\JsonResponse;

class PaymentTransactionController extends Controller
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
            }

            $paymentRequest = PaymentRequest::from($request->validated());

            $payment = $this->paymentService->createPayment($payable, $paymentRequest);
            $response = $this->paymentService->initializePayment($payment, $paymentRequest);

            if (!$response->success) {
                return response()->json([
                    'message' => $response->message,
                    'errors' => $response->errors,
                ], 400);
            }

            return response()->json([
                'message' => $response->message,
                'payment' => [
                    'id' => $payment->ulid,
                    'reference' => $payment->reference,
                    'amount' => $payment->amount,
                    'currency' => $payment->currency,
                    'status' => $payment->status->value,
                    'expires_at' => $payment->expires_at,
                ],
                'data' => $response->data,
            ], 201);

        } catch (\Exception $e) {
            return response()->json([
                'message' => 'Payment initiation failed',
                'error' => app()->isDebug() ? $e->getMessage() : null,
            ], 500);
        }
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
}
