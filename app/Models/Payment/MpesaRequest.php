<?php

namespace App\Models\Payment;

use Illuminate\Database\Eloquent\Model;

class MpesaRequest extends Model
{
    protected $fillable = [
        'request_type',
        'phone',
        'amount',
        'account_reference',
        'request_payload',
        'checkout_request_id',
        'merchant_request_id',
        'callback_payload',
        'result_code',
        'result_desc',
        'status',
        'payable_type',
        'payable_id',
        'reference',
        'request_code',
        'currency',
        'provider',
        'provider_request',
        'provider_response',
        'method',
        'user_id',
    ];

    protected $casts = [
        'provider_request' => 'array',
        'provider_response' => 'array',
        'callback_payload' => 'array',
        'request_payload' => 'array',
    ];

    public function mpesaPayments()
    {
        return $this->hasMany(MpesaPayment::class);
    }
}
