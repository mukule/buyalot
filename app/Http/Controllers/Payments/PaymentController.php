<?php

namespace App\Http\Controllers\Payments;

use App\Http\Controllers\Controller;
use App\Http\DTOs\PaymentRequest;
use App\Models\Payment\Payment;
use App\Services\PaymentService;
use Illuminate\Http\Request;
use Inertia\Inertia;

class PaymentController extends Controller
{
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

    public function providers()
    {
        return response()->json([
            'success' => true,
            'providers' => [
                'mpesa',
                'credit card',
                'debit card',
                'cash_on_delivery',
            ],
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

    public function mpesaCallback(Request $request)
    {
        /** @var PaymentService $service */
        $service = app(PaymentService::class);
        $result = $service->handleCallback('mpesa', $request->all());

        // Safaricom expects a 200 even on logical failures after we process
        return response()->json([
            'ResultCode' => $result->success ? 0 : 1,
            'ResultDesc' => $result->message,
        ]);
    }
}
