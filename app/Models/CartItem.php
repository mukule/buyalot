<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class CartItem extends Model
{
    protected $fillable = [
        'cart_id',
        'product_id',
        'seller_id',
        'product_variant_id',
        'quantity',
        'unit_price',
        'total_price',
        'marked_price',
        'discount_amount',
        'discount_percentage',
        'metadata',
    ];

    protected $casts = [
        'metadata' => 'array', 
    ];

   
    public function cart(): BelongsTo
    {
        return $this->belongsTo(Cart::class);
    }

   
    public function productVariant(): BelongsTo
    {
        return $this->belongsTo(ProductVariant::class);
    }

    /**
     * The main product in this cart item
     */
    public function product(): BelongsTo
    {
        return $this->belongsTo(Product::class);
    }

    /**
     * The seller/owner of the product
     */
    public function seller(): BelongsTo
    {
        return $this->belongsTo(User::class, 'seller_id');
    }

    
}
