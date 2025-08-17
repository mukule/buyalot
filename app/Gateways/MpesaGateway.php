<?php

namespace App\Gateways;

use App\Interfaces\Payable;
use App\Interfaces\PaymentGatewayInterface;
use App\Models\Orders\Transaction;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Log;
use Safaricom\Mpesa\Mpesa;

class MpesaGateway implements PaymentGatewayInterface
{
    public function processPayment(Request $request, Payable $payable)
    {
        $mpesa = new Mpesa();
        // Generate a unique ID for this specific transaction attempt
        $transactionId = $payable->getTransactionId();

        $response = $mpesa->c2b(
            config('mpesa.shortcode'),
            'ordePayment',
            //            config('mpesa.passkey'),
            $payable->getAmount(),
            $request->phone_number,
            $payable->getDescription(),
//            route('payment.callback', ['gateway' => 'mpesa'])
        );
        Log::info('M-Pesa STK Push Response: ', (array) $response);
        if (isset($response->ResponseCode) && $response->ResponseCode == '0') {
            Transaction::create([
                'user_id' => auth()->id(),
                'payable_id' => $payable->id,
                'payable_type' => get_class($payable),
                'gateway' => 'mpesa',
                'amount' => $payable->getAmount(),
                'transaction_id' => $response->CheckoutRequestID,
                'status' => 'pending',
                'payload' => json_encode($response),
                'receipt_no'=>$response->TransID,
                'response_date' => $response->TransTime,
            ]);
        } else {
            // Handle cases where the STK push initiation failed
            Transaction::create([
                'user_id' => auth()->id(),
                'payable_id' => $payable->id,
                'payable_type' => get_class($payable),
                'gateway' => 'mpesa',
                'amount' => $payable->getAmount(),
                'transaction_id' => $transactionId,
                'status' => 'failed',
                'payload' => json_encode($response),
            ]);
        }


        return $response;
    }

    public function handleCallback(Request $request)
    {
        $callbackData = $request->all();
        Log::info('M-Pesa Callback Received: ', $callbackData);

        $checkoutRequestID = $callbackData['Body']['stkCallback']['CheckoutRequestID'];
        $resultCode = $callbackData['Body']['stkCallback']['ResultCode'];
        $transaction = Transaction::where('transaction_id', $checkoutRequestID)->first();

        if (!$transaction) {
            Log::error("Transaction not found for CheckoutRequestID: {$checkoutRequestID}");
            return response()->json(['status' => 'error', 'message' => 'Transaction not found'], 404);
        }
        if ($resultCode == 0) {
            $callbackMetadata = $callbackData['Body']['stkCallback']['CallbackMetadata']['Item'];
            $mpesaReceiptNumber = collect($callbackMetadata)->where('Name', 'MpesaReceiptNumber')->first()['Value'];

            $transaction->update([
                'status' => 'completed',
                'payload' => json_encode($callbackData),
                 'receipt_no' => $mpesaReceiptNumber,
            ]);

            // You can fire an event here to notify the user, update the order, etc.
            // event(new PaymentCompleted($transaction));

        } else {
            // If the payment failed or was cancelled
            $transaction->update([
                'status' => 'failed',
                'payload' => json_encode($callbackData),
            ]);
        }

        return response()->json(['status' => 'success']);
    }
}


