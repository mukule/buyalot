<?php

namespace App\Models\Billing;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

class Receipt extends Model
{
    protected $table = 'receipts';
    protected $guarded = [];

    protected $casts = [
        'paid_at' => 'datetime',
        'meta' => 'array',
    ];

    public function allocations(): HasMany
    {
        return $this->hasMany(ReceiptAllocation::class);
    }

    public function seller(): BelongsTo
    {
        return $this->belongsTo(\App\Models\Seller\SellerAccount::class, 'seller_id');
    }
}
