<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Support\Str;

class Cart extends Model
{
    protected $fillable = [
        'user_id',
        'cart_token',
    ];

    protected static function booted()
    {
        static::creating(function ($cart) {
            // Generate a UUID token for guests if not provided
            if (!$cart->cart_token) {
                $cart->cart_token = (string) Str::uuid();
            }
        });
    }

    /**
     * The user who owns the cart (nullable for guests)
     */
    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }

    /**
     * Items in the cart
     */
    public function items(): HasMany
    {
        return $this->hasMany(CartItem::class);
    }
}
