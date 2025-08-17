<?php

namespace App\Http\Controllers\Payments;

use App\Http\Controllers\Controller;
use App\Models\Orders\Order;
use App\Services\PaymentService;
use Illuminate\Http\Request;

class PaymentController extends Controller
{
    public function process(Request $request, Order $order, PaymentService $paymentService)
    {
//        $this->authorize('pay', $order);
        return $paymentService->processPayment($request, $order);
    }

    public function callback(Request $request, PaymentService $paymentService)
    {
        return $paymentService->handleCallback($request);
    }
}
