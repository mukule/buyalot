<?php

namespace App\Models\POS;

use Illuminate\Database\Eloquent\Model;

class PosUnallocatedPayment extends Model
{
    protected $fillable = [
        'customer_id',
        'pos_session_id',
        'user_id',
        'amount',
        'used_amount',
        'payment_method',
        'reference',
        'status',
        'notes',
    ];

    protected $casts = [
        'amount' => 'decimal:2',
        'used_amount' => 'decimal:2',
    ];

    public function customer()
    {
        return $this->belongsTo(\App\Models\Customer\Customer::class);
    }

    public function session()
    {
        return $this->belongsTo(PosSession::class, 'pos_session_id');
    }

    public function user()
    {
        return $this->belongsTo(\App\Models\User::class);
    }

    public function getRemainingAmountAttribute()
    {
        return $this->amount - $this->used_amount;
    }
}
