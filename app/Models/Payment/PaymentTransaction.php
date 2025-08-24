<?php

namespace App\Models\Payment;

use Illuminate\Database\Eloquent\Model;


use App\Traits\HasUlid;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class PaymentTransaction extends Model
{
    use HasUlid;

    protected $fillable = [
        'payment_id',
        'type',
        'status',
        'request_data',
        'response_data',
        'provider_transaction_id',
        'error_message',
    ];

    protected $casts = [
        'request_data' => 'array',
        'response_data' => 'array',
    ];

    public function payment(): BelongsTo
    {
        return $this->belongsTo(Payment::class);
    }
}
