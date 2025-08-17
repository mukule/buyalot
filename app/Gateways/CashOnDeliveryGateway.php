<?php

namespace App\Gateways;

use App\Interfaces\Payable;
use App\Interfaces\PaymentGatewayInterface;
use App\Models\Orders\Transaction;
use Illuminate\Http\Request;

class CashOnDeliveryGateway implements PaymentGatewayInterface
{
    public function processPayment(Request $request, Payable $payable)
    {
        Transaction::create([
            'user_id' => auth()->id(),
            'payable_id' => $payable->id,
            'payable_type' => get_class($payable),
            'gateway' => 'cash_on_delivery',
            'amount' => $payable->getAmount(),
            'transaction_id' => $payable->getTransactionId(),
            'status' => 'pending',
        ]);

        return redirect()->route('order.confirmation');
    }

    public function handleCallback(Request $request)
    {
        return;
    }
}
