<?php

namespace App\Models\Products;

use App\Models\User;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class ProductQuoteRequest extends Model
{
    protected $fillable = [
        'product_id',
        'vertical',
        'user_id',
        'name',
        'email',
        'phone',
        'quantity',
        'unit',
        'message',
        'status',
    ];

    public function product(): BelongsTo
    {
        return $this->belongsTo(Product::class);
    }

    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }
}
