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
    /**
     * Whenever a variant is updated, notify the parent Product.
     * This triggers the ProductObserver to sync Meilisearch and clear Redis.
     */
    protected $touches = ['product'];

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

    // ----------------------
    // Scopes
    // ----------------------

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
            return ['type' => null, 'name' => 'Unknown Seller'];
        }

        if ($this->product->owner_type === 'admin') {
            return ['type' => 'admin', 'name' => 'Buyalot Store'];
        }

        $sellerName = $this->product->owner?->sellerApplication?->company_legal_name
            ?? $this->product->owner?->name
            ?? 'Unknown Seller';

        return ['type' => 'seller', 'name' => $sellerName];
    }

    public function getActiveWarranty(): ?\App\Models\Warranty
    {
        return $this->product ? $this->product->activeWarranty() : null;
    }

    public function orderItems()
    {
        return $this->hasMany(OrderItem::class, 'product_variant_id');
    }

    public function orders()
    {
        return $this->hasManyThrough(Order::class, OrderItem::class, 'product_variant_id', 'id', 'id', 'order_id');
    }
}