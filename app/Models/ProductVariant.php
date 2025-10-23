<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

class ProductVariant extends Model
{
    protected $fillable = [
        'product_id',
        'buying_price',
        'marked_price',
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
        'profit_margin',
        'markup_percent',
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

    public function warehouseInventories(): HasMany
    {
        return $this->hasMany(\App\Models\Warehouse\WarehouseProductInventory::class, 'product_variant_id');
    }

    // ----------------------
    // Accessors
    // ----------------------

    public function getDisplayNameAttribute(): string
    {
        $variantValues = $this->values
            ->map(fn($v) => $v->value)
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
        // Final price: use selling price if available, else marked, else regular
        $price = $this->selling_price ?? $this->marked_price ?? $this->regular_price ?? 0.0;
        return round((float) $price, 2);
    }

    public function getInStockAttribute(): bool
    {
        return $this->stock > 0;
    }

    // ----------------------
    // Profit / Markup Helpers
    // ----------------------

    public function getProfitMarginAttribute(): float
    {
        if ($this->buying_price <= 0) {
            return 0;
        }

        return round((($this->final_price - $this->buying_price) / $this->buying_price) * 100, 2);
    }

    public function getMarkupPercentAttribute(): float
    {
        if ($this->buying_price <= 0) {
            return 0;
        }

        return round((($this->marked_price - $this->buying_price) / $this->buying_price) * 100, 2);
    }
}
