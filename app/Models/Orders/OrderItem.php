<?php

namespace App\Models\Orders;

use App\Models\Product;
use App\Models\ProductVariant;
use App\Models\ProductVariantValue;
use App\Models\Seller\Seller;
use App\Models\Variant;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;


class OrderItem extends Model
{
    use HasFactory;

    protected $fillable = [
        'ulid',
        'order_id',
        'product_variant_id',
        'seller_id',
        'quantity',
        'unit_price',
        'total_price',
        'discount_amount',
        'tax_amount',
        'commission_id',
        'quantity_fulfilled',
        'quantity_cancelled',
        'quantity_returned',
        'product_snapshot',
        'metadata',
    ];

    protected $casts = [
        'quantity' => 'integer',
        'unit_price' => 'decimal:2',
        'total_price' => 'decimal:2',
        'discount_amount' => 'decimal:2',
        'tax_amount' => 'decimal:2',
        'commission_id' => 'decimal:2',
        'quantity_fulfilled' => 'integer',
        'quantity_cancelled' => 'integer',
        'quantity_returned' => 'integer',
        'product_snapshot' => 'json',
        'metadata' => 'json',
    ];

    // Relationships
    public function order(): BelongsTo
    {
        return $this->belongsTo(Order::class);
    }

    public function productVariant(): BelongsTo
    {
        return $this->belongsTo(ProductVariant::class, 'product_variant_id');
    }
    public function product(): BelongsTo
    {
        return $this->belongsTo(Product::class, 'product_id')
            ->through(ProductVariant::class, 'product_variant_id');
    }
    public function variant(): BelongsTo
    {
        return $this->belongsTo(Variant::class, 'variant_id')
            ->through(ProductVariant::class, 'product_variant_id');
    }

    public function seller(): BelongsTo
    {
        return $this->belongsTo(Seller::class);
    }

    // Scopes
    public function scopeBySeller($query, $sellerId)
    {
        return $query->where('seller_id', $sellerId);
    }

    public function scopeByProduct($query, $productId)
    {
        return $query->where('product_id', $productId);
    }

    public function scopeFulfilled($query)
    {
        return $query->where('quantity_fulfilled', '>=', $query->getModel()->quantity);
    }

    public function scopePendingFulfillment($query)
    {
        return $query->where('quantity_fulfilled', '<', $query->getModel()->quantity);
    }

    // Accessors & Mutators
    public function getFormattedTotalAttribute(): string
    {
        return $this->order->currency . ' ' . number_format($this->total_price, 2);
    }

    public function getFormattedUnitPriceAttribute(): string
    {
        return $this->order->currency . ' ' . number_format($this->unit_price, 2);
    }

    public function getProductNameAttribute(): string
    {
        return $this->product_snapshot['name'] ?? $this->product?->name ?? 'Unknown Product';
    }

    public function getProductSkuAttribute(): string
    {
        return $this->product_snapshot['sku'] ?? $this->product?->sku ?? '';
    }

    public function getProductImageAttribute(): ?string
    {
        return $this->product_snapshot['image'] ?? $this->product?->featured_image ?? null;
    }

    // Helper Methods
    public function isFulfilled(): bool
    {
        return $this->quantity_fulfilled >= $this->quantity;
    }

    public function isPartiallyFulfilled(): bool
    {
        return $this->quantity_fulfilled > 0 && $this->quantity_fulfilled < $this->quantity;
    }

    public function isPendingFulfillment(): bool
    {
        return $this->quantity_fulfilled < $this->quantity;
    }

    public function getRemainingQuantity(): int
    {
        return max(0, $this->quantity - $this->quantity_fulfilled - $this->quantity_cancelled);
    }

    public function canBeFulfilled(): bool
    {
        return $this->getRemainingQuantity() > 0;
    }

    public function canBeCancelled(): bool
    {
        return $this->getRemainingQuantity() > 0 && $this->order->canBeCancelled();
    }

    public function fulfill(int $quantity): bool
    {
        if ($quantity <= $this->getRemainingQuantity()) {
            $this->increment('quantity_fulfilled', $quantity);
            return true;
        }
        return false;
    }

    public function cancel(int $quantity): bool
    {
        if ($quantity <= $this->getRemainingQuantity()) {
            $this->increment('quantity_cancelled', $quantity);
            return true;
        }
        return false;
    }

    // Route key binding
    public function getRouteKeyName(): string
    {
        return 'ulid';
    }

    // Boot method for model events
    protected static function boot()
    {
        parent::boot();

        static::creating(function ($orderItem) {
            if (empty($orderItem->ulid)) {
                $orderItem->ulid = \Illuminate\Support\Str::ulid();
            }
        });
    }
}
