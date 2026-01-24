<?php

namespace App\Models\Products;

use App\Models\Order;
use App\Models\OrderItem;
use App\Models\Payment\Discount;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Support\Carbon;

class ProductVariant extends Model
{
    protected $fillable = [
        'display_name',
        'product_id',
        'buying_price',
        'marked_price',
        'regular_price',
        'selling_price',
        'stock',
        'sku',
        'is_active',
        'discount',
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

    protected static function booted()
    {
        // static::created(fn() => \App\Services\SearchCacheService::refresh());
        // static::updated(fn() => \App\Services\SearchCacheService::refresh());
        // static::deleted(fn() => \App\Services\SearchCacheService::refresh());
    }

    public function product(): BelongsTo
    {
        return $this->belongsTo(Product::class);
    }
    public function discounts()
    {
        return $this->morphToMany(Discount::class, 'model', 'discount_applicable_tables')
            ->activeAndValid();
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

    /**
     * Scope: limit product variants to those whose parent product belongs to the given seller application id(s).
     * Uses seller_applications IDs via products.owner_id when owner_type = 'seller'.
     * @param Builder $query
     * @param int|array|\Illuminate\Support\Collection $sellerIds
     */
    public function scopeForSeller(Builder $query, $sellerIds): Builder
    {
        $ids = collect($sellerIds)->flatten()->filter()->values();
        if ($ids->isEmpty()) {
            return $query->whereRaw('1 = 0');
        }
        return $query->whereHas('product', function (Builder $q) use ($ids) {
            $q->where('owner_type', 'seller')->whereIn('owner_id', $ids);
        });
    }

    public function scopeActiveAndValid(Builder $query): Builder
    {
        $now = Carbon::now();

        return $query
            ->where('is_active', true)
            ->where(function ($q) use ($now) {
                $q->where('no_time_limit', true)
                    ->orWhere(function ($inner) use ($now) {
                        $inner->where(function ($d) use ($now) {
                            $d->whereNull('starts_at')->orWhere('starts_at', '<=', $now);
                        })->where(function ($d) use ($now) {
                            $d->whereNull('expires_at')->orWhere('expires_at', '>', $now);
                        });
                    });
            });
    }



    public function getOwnerInfo(): array
{
    if (! $this->product) {
        return [
            'type' => null,
            'name' => 'Unknown Seller',
        ];
    }

    if ($this->product->owner_type === 'admin') {
        return [
            'type' => 'admin',
            'name' => 'Buyalot Store',
        ];
    }

    // If owner is a seller
    $sellerName = $this->product->owner?->sellerApplication?->company_legal_name
        ?? $this->product->owner?->name
        ?? 'Unknown Seller';

    return [
        'type' => 'seller',
        'name' => $sellerName,
    ];
}


public function getActiveWarranty(): ?\App\Models\Warranty
{
    if (! $this->product) {
        return null;
    }

    return $this->product->warranties()
        ->where('active', true)
        ->orderBy('id')
        ->first();
}


 public function orderItems()
    {
        return $this->hasMany(OrderItem::class, 'product_variant_id');
    }

    public function orders()
    {
        // Optional: if you want orders directly
        return $this->hasManyThrough(Order::class, OrderItem::class, 'product_variant_id', 'id', 'id', 'order_id');
    }




//    public function discounts()
// {
//    return $this->belongsToMany(\App\Models\Payment\Discount::class, 'discount_product_variants', 'product_variant_id', 'discount_id')
//        ->where('is_active', true)
//        ->where(function ($q) {
//            $now = now();
//            $q->whereNull('starts_at')->orWhere('starts_at', '<=', $now);
//            $q->whereNull('expires_at')->orWhere('expires_at', '>=', $now);
//        });
// }

}
