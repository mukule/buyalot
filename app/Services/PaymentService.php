<?php
namespace App\Services;

use App\Interfaces\PaymentGatewayInterface;
use App\Interfaces\Payable;
use Illuminate\Http\Request;

class PaymentService
{
    protected $gateway;

    public function __construct(PaymentGatewayInterface $gateway)
    {
        $this->gateway = $gateway;
    }

    public function processPayment(Request $request, Payable $payable)
    {
        return $this->gateway->processPayment($request, $payable);
    }

    public function handleCallback(Request $request)
    {
        return $this->gateway->handleCallback($request);
    }
}
