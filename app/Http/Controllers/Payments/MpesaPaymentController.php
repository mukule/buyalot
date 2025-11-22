<?php

namespace App\Http\Controllers\Payments;

use App\Http\Controllers\Controller;
use App\Http\DTOs\PaymentRequest;
use App\Http\Requests\InitiatePaymentRequest;
use App\Services\PaymentService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

class MpesaPaymentController extends Controller
{
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
