<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

class ProductVariant extends Model
{
    protected $fillable = [
        'product_id',
        'regular_price',
        'selling_price',
        'stock',
        'sku',
    ];

    // ----------------------
    // Relationships
    // ----------------------

    public function product(): BelongsTo
    {
        return $this->belongsTo(Product::class);
    }

    public function values(): HasMany
    {
        return $this->hasMany(ProductVariantValue::class);
    }


    public function getDisplayNameAttribute(): string
{
    // Join all variant values, e.g., "Large, Red"
    $variantValues = $this->values->map(fn($v) => $v->variant->value)->join(', ');

    return "{$this->product->name}" . ($variantValues ? " - {$variantValues}" : '');
}


public function getHasDiscountAttribute(): bool
{
    return $this->regular_price > $this->selling_price;
}

public function getDiscountAmountAttribute(): float
{
    return $this->has_discount ? $this->regular_price - $this->selling_price : 0;
}


    
}
