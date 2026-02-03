<?php

namespace App\Models\Payment;

use Illuminate\Database\Eloquent\Model;

class MpesaPayment extends Model
{
    protected $fillable = [
        'payment_id',
        'mpesa_request_id',
        'transaction_type',
        'phone',
        'amount',
        'mpesa_receipt',
        'transaction_id',
        'account_reference',
        'result_code',
        'result_desc',
        'transaction_date',
        'raw_payload',
        'amount_paid',
    ];

    protected $casts = [
        'raw_payload' => 'array',
        'transaction_date' => 'datetime',
    ];

    public function payment()
    {
        return $this->belongsTo(Payment::class);
    }

    public function request()
    {
        return $this->belongsTo(MpesaRequest::class, 'mpesa_request_id');
    }
}
