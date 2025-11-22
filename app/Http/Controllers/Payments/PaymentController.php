<?php

namespace App\Http\Controllers\Payments;

use App\Http\Controllers\Controller;
use App\Http\DTOs\PaymentRequest;
use App\Http\Requests\InitiatePaymentRequest;
use App\Models\Payment\Payment;
use App\Services\PaymentService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Inertia\Inertia;

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

}
