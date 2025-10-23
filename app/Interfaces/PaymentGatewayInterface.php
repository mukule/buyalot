<?php

namespace App\Interfaces;
use Illuminate\Http\Request;
use App\Interfaces\Payable;
interface PaymentGatewayInterface
{
    public function processPayment(Request $request, Payable $payable);
    public function handleCallback(Request $request);
}
