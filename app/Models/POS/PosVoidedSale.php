<?php

namespace App\Models\POS;

use Illuminate\Database\Eloquent\Model;

class PosVoidedSale extends Model
{
    protected $fillable = [
        'pos_session_id',
        'user_id',
        'customer_id',
        'cart_data',
        'total_amount',
        'reason',
        'voided_at',
        'recalled',
        'recalled_at',
    ];

    protected $casts = [
        'cart_data' => 'json',
        'voided_at' => 'datetime',
        'recalled_at' => 'datetime',
        'recalled' => 'boolean',
        'total_amount' => 'decimal:2',
    ];

    public function session()
    {
        return $this->belongsTo(PosSession::class, 'pos_session_id');
    }

    public function user()
    {
        return $this->belongsTo(\App\Models\User::class);
    }

    public function customer()
    {
        return $this->belongsTo(\App\Models\Customer\Customer::class);
    }
}
