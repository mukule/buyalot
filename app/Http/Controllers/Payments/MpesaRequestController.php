<?php

namespace App\Http\Controllers\Payments;

use App\Http\Controllers\Controller;
use App\Http\DTOs\PaymentRequest;
use App\Http\Requests\InitiatePaymentRequest;
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
                    'id' => $paymentLog->ulid,
                    'reference' => $paymentLog->reference,
                    'amount' => $paymentLog->amount,
                    'currency' => $paymentLog->currency,
                    'status' => $paymentLog->status->value,
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
}
