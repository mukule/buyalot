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

    protected $appends = [
        'display_name',
        'has_discount',
        'discount_amount',
        'discount_percent',
        'final_price',
        'in_stock',
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

    // ----------------------
    // Accessors
    // ----------------------

    public function getDisplayNameAttribute(): string
    {
        // Join all variant values, e.g., "Large, Red"
        $variantValues = $this->values
            ->map(fn ($v) => $v->value) // safer than $v->variant->value
            ->join(', ');

        return $this->product
            ? "{$this->product->name}" . ($variantValues ? " - {$variantValues}" : '')
            : ($variantValues ?: 'Unnamed Variant');
    }

    public function getHasDiscountAttribute(): bool
    {
        return $this->regular_price > $this->selling_price;
    }

    public function getDiscountAmountAttribute(): float
    {
        return $this->has_discount
            ? $this->regular_price - $this->selling_price
            : 0;
    }

    public function getDiscountPercentAttribute(): ?int
    {
        if ($this->has_discount && $this->regular_price > 0) {
            return round(($this->discount_amount / $this->regular_price) * 100);
        }
        return null;
    }

    public function getFinalPriceAttribute(): float
    {
        return $this->selling_price;
    }

    public function getInStockAttribute(): bool
    {
        return $this->stock > 0;
    }
}
