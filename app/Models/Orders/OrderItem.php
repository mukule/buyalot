<?php

namespace App\Models\Orders;

use App\Models\Products\Product;
use App\Models\Products\ProductVariant;
use App\Models\Seller\SellerAccount;
use App\Models\Variant;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;


class OrderItem extends Model
{
    use HasFactory;

    public const DISPATCH_STATUS_PENDING = 'pending';
    public const DISPATCH_STATUS_DISPATCHED = 'dispatched';
    public const DISPATCH_STATUS_DECLINED = 'declined';
    public const DISPATCH_STATUS_RECEIVED = 'received';
    public const DISPATCH_STATUS_REJECTED = 'rejected';

    public const DECLINE_REASONS = [
        'out_of_stock' => 'Out of stock',
        'price_changed' => 'Price changed',
        'dispatch_center_too_far' => 'Dispatch center is too far',
        'store_temporarily_closed' => 'Store temporarily closed',
        'other' => 'Other',
    ];

    public const REJECTION_REASONS = [
        'spoiled' => 'Spoiled',
        'substandard' => 'Substandard',
        'wrong_item' => 'Not the ordered item',
        'damaged_packaging' => 'Damaged packaging',
        'expired' => 'Expired',
        'other' => 'Other',
    ];

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
        'dispatch_status',
        'dispatched_at',
        'dispatch_center_id',
        'dispatch_decline_reason',
        'received_at',
        'received_by',
        'rejection_reason',
        'rejected_at',
        'rejected_by',
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
        'dispatched_at' => 'datetime',
        'received_at' => 'datetime',
        'rejected_at' => 'datetime',
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

    public function dispatchCenter(): BelongsTo
    {
        return $this->belongsTo(\App\Models\Warehouse\Warehouse::class, 'dispatch_center_id');
    }

    public function receiver(): BelongsTo
    {
        return $this->belongsTo(\App\Models\User::class, 'received_by');
    }

    public function rejector(): BelongsTo
    {
        return $this->belongsTo(\App\Models\User::class, 'rejected_by');
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
        return $this->belongsTo(\App\Models\Seller\Seller::class, 'seller_id');
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
