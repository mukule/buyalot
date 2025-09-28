<?php

namespace App\Models\Orders;

use App\Models\Customer\Customer;
use App\Models\Customer\CustomerAddress;
use App\Models\Payment\Discount;
use App\Models\User;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\SoftDeletes;

class Order extends Model
{
    use HasFactory, SoftDeletes;

    protected $fillable = [
        'order_code',
        'customer_id',
        'status',
        'subtotal',
        'tax_amount',
        'shipping_amount',
        'discount_amount',
        'total_amount',
        'currency',
        'billing_address_id',
        'shipping_address_id',
        'notes',
        'metadata',
        'source',
        'channel',
        'fulfillment_status',
        'fulfillment_info',
        'payment_status',
        'applied_discounts',
        'discount_id',
        'coupon_code',
        'confirmed_at',
        'shipped_at',
        'delivered_at',
        'cancelled_at',
    ];

    protected $casts = [
        'subtotal' => 'decimal:2',
        'tax_amount' => 'decimal:2',
        'shipping_amount' => 'decimal:2',
        'discount_amount' => 'decimal:2',
        'total_amount' => 'decimal:2',
        'metadata' => 'json',
        'fulfillment_info' => 'json',
        'applied_discounts' => 'json',
        'confirmed_at' => 'datetime',
        'shipped_at' => 'datetime',
        'delivered_at' => 'datetime',
        'cancelled_at' => 'datetime',
    ];

    protected $dates = [
        'confirmed_at',
        'shipped_at',
        'delivered_at',
        'cancelled_at',
    ];

    // Relationships
    public function customer(): BelongsTo
    {
        return $this->belongsTo(Customer::class);
    }

    public function orderItems(): HasMany
    {
        return $this->hasMany(OrderItem::class);
    }

    public function discount(): BelongsTo
    {
        return $this->belongsTo(Discount::class);
    }
    public function shippingAddress()
    {
        return $this->belongsTo(CustomerAddress::class, 'shipping_address_id')
            ->where('type', 'shipping');
    }

    public function billingAddress()
    {
        return $this->belongsTo(CustomerAddress::class, 'billing_address_id')
            ->where('type', 'billing');
    }

    // Scopes
    public function scopePending($query)
    {
        return $query->where('status', 'pending');
    }

    public function scopeConfirmed($query)
    {
        return $query->where('status', 'confirmed');
    }

    public function scopeByStatus($query, $status)
    {
        return $query->where('status', $status);
    }

    public function scopeByPaymentStatus($query, $paymentStatus)
    {
        return $query->where('payment_status', $paymentStatus);
    }

    public function scopeByFulfillmentStatus($query, $fulfillmentStatus)
    {
        return $query->where('fulfillment_status', $fulfillmentStatus);
    }

    public function scopeByCustomer($query, $customerId)
    {
        return $query->where('customer_id', $customerId);
    }

    public function scopeByDateRange($query, $startDate, $endDate)
    {
        return $query->whereBetween('created_at', [$startDate, $endDate]);
    }

    // Accessors & Mutators
    public function getFormattedTotalAttribute(): string
    {
        return $this->currency . ' ' . number_format($this->total_amount, 2);
    }

    public function getStatusLabelAttribute(): string
    {
        return ucfirst(str_replace('_', ' ', $this->status));
    }

    public function getPaymentStatusLabelAttribute(): string
    {
        return ucfirst(str_replace('_', ' ', $this->payment_status));
    }

    public function getFulfillmentStatusLabelAttribute(): string
    {
        return ucfirst(str_replace('_', ' ', $this->fulfillment_status));
    }

    // Helper Methods
    public function isPending(): bool
    {
        return $this->status === 'pending';
    }

    public function isConfirmed(): bool
    {
        return $this->status === 'confirmed';
    }

    public function isProcessing(): bool
    {
        return $this->status === 'processing';
    }

    public function isShipped(): bool
    {
        return $this->status === 'shipped';
    }

    public function isDelivered(): bool
    {
        return $this->status === 'delivered';
    }

    public function isCancelled(): bool
    {
        return $this->status === 'cancelled';
    }

    public function isRefunded(): bool
    {
        return in_array($this->status, ['refunded', 'partially_refunded']);
    }

    public function isPaid(): bool
    {
        return $this->payment_status === 'paid';
    }

    public function isPaymentPending(): bool
    {
        return $this->payment_status === 'pending';
    }

    public function isFulfilled(): bool
    {
        return $this->fulfillment_status === 'fulfilled';
    }

    public function canBeCancelled(): bool
    {
        return in_array($this->status, ['pending', 'confirmed']) && !$this->isCancelled();
    }

    public function canBeRefunded(): bool
    {
        return $this->isPaid() && in_array($this->status, ['delivered', 'shipped']);
    }

    public function getTotalItemsCount(): int
    {
        return $this->orderItems->sum('quantity');
    }

    public function getUniqueProductsCount(): int
    {
        return $this->orderItems->count();
    }

    public function getSellersCount(): int
    {
        return $this->orderItems->pluck('seller_id')->unique()->count();
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

        static::creating(function ($order) {
            if (empty($order->ulid)) {
                $order->ulid = \Illuminate\Support\Str::ulid();
            }
        });
    }

    public function assignedRider()
    {
        return $this->belongsTo(User::class, 'delivery_id');
    }

}
