<?php

namespace App\Models\Orders;

use App\Models\Customer\Customer;
use App\Models\Customer\CustomerAddress;
use App\Models\Payment\Discount;
use App\Models\User;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use App\Traits\HasPayments;
use Illuminate\Database\Eloquent\Relations\HasOne;
use Illuminate\Database\Eloquent\Relations\MorphMany;
use Illuminate\Database\Eloquent\SoftDeletes;

class Order extends Model
{
    use HasPayments, SoftDeletes;

    protected $fillable = [
        'order_code',
        'customer_id',
        'checkout_session_id',
        'pos_session_id',
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
        'payment_method',
        'applied_discounts',
        'discount_id',
        'coupon_code',
        'confirmed_at',
        'shipped_at',
        'delivered_at',
        'cancelled_at',
        'amount_paid',
        'balance',
        'ulid',
    ];

    protected $casts = [
        'subtotal' => 'decimal:2',
        'tax_amount' => 'decimal:2',
        'shipping_amount' => 'decimal:2',
        'discount_amount' => 'decimal:2',
        'total_amount' => 'decimal:2',
        'amount_paid'=> 'decimal:2',
        'balance'=> 'decimal:2',
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

    protected $appends = ['assigned_rider', 'delivery_assignment_status'];

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
        return $this->belongsTo(CustomerAddress::class, 'shipping_address_id');
    }

    public function billingAddress()
    {
        return $this->belongsTo(CustomerAddress::class, 'billing_address_id');
    }

    public function delivery(): HasOne
    {
        return $this->hasOne(Delivery::class);
    }

    public function orderReturns(): HasMany
    {
        return $this->hasMany(OrderReturn::class);
    }

    public function codReconciliation(): HasOne
    {
        return $this->hasOne(CodReconciliation::class);
    }

    public function payments(): MorphMany
    {
        return $this->morphMany(Payment::class, 'payable');
    }

    /** @deprecated Use delivery->deliveryUser instead; kept for backward compatibility. */
    public function assignedRider()
    {
        return $this->hasOneThrough(User::class, Delivery::class, 'order_id', 'id', 'id', 'delivery_id');
    }

    public function getAssignedRiderAttribute(): ?array
    {
        $user = $this->delivery?->deliveryUser;
        if (!$user) {
            return null;
        }
        return ['id' => $user->id, 'name' => $user->name, 'email' => $user->email];
    }

    public function getDeliveryAssignmentStatusAttribute(): ?string
    {
        return $this->delivery?->assignment_status;
    }

    public function posSession()
    {
        return $this->belongsTo(\App\Models\POS\PosSession::class, 'pos_session_id');
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

    /**
     * Scope: limit orders to those that have at least one order item belonging to the given seller(s).
     * @param \Illuminate\Database\Eloquent\Builder $query
     * @param int|array|\Illuminate\Support\Collection $sellerIds
     */
    public function scopeForSeller($query, $sellerIds)
    {
        $ids = collect($sellerIds)->flatten()->filter()->values();
        if ($ids->isEmpty()) {
            // Force empty result if no seller id provided
            return $query->whereRaw('1 = 0');
        }
        return $query->whereHas('orderItems', function ($q) use ($ids) {
            $q->whereIn('seller_id', $ids);
        });
    }

    /**
     * Scope: orders assigned to a specific delivery user.
     */
    public function scopeAssignedToDelivery($query, $userId)
    {
        return $query->whereHas('delivery', fn ($q) => $q->where('delivery_id', $userId));
    }

    /**
     * Scope: orders pending delivery person acceptance.
     */
    public function scopeDeliveryAssignmentPending($query)
    {
        return $query->whereHas('delivery', fn ($q) => $q->where('assignment_status', 'pending'));
    }

    /**
     * Scope: orders accepted by delivery person.
     */
    public function scopeDeliveryAssignmentAccepted($query)
    {
        return $query->whereHas('delivery', fn ($q) => $q->where('assignment_status', 'accepted'));
    }

    /**
     * Scope: orders rejected by delivery person.
     */
    public function scopeDeliveryRejected($query)
    {
        return $query->whereHas('delivery', fn ($q) => $q->where('assignment_status', 'rejected'));
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

    /**
     * Build delivery note summary: paid status, payment method, order items (product name, variant, quantity), and cash-on-delivery notice.
     */
    public function getDeliveryNoteSummary(): string
    {
        $this->loadMissing(
            'orderItems.productVariant.values.variant',
            'orderItems.productVariant.product',
            'delivery.pickupWarehouse',
            'shippingAddress'
        );

        $lines = [];
        $lines[] = 'Order #' . $this->order_code;
        $lines[] = 'Already paid: ' . ($this->payment_status === 'paid' ? 'Yes' : 'No');
        $method = $this->payment_method ? str_replace('_', ' ', $this->payment_method) : 'Not specified';
        $lines[] = 'Method of payment: ' . ucfirst($method);
        $lines[] = '';
        $lines[] = 'Items:';
        foreach ($this->orderItems as $item) {
            $name = $item->product_snapshot['name'] ?? $item->productVariant?->product?->name ?? 'Item';
            $variantStr = '';
            if ($item->productVariant && $item->productVariant->relationLoaded('values')) {
                $variantStr = $item->productVariant->values
                    ->map(fn ($v) => $v->relationLoaded('variant') ? $v->variant?->value : null)
                    ->filter()
                    ->join(', ');
            }
            if ($variantStr === '' && $item->productVariant) {
                $variantStr = $item->product_snapshot['sku'] ?? $item->productVariant->sku ?? '';
            } elseif ($variantStr === '') {
                $variantStr = $item->product_snapshot['sku'] ?? '';
            }
            if ($variantStr !== '') {
                $variantStr = ' — ' . $variantStr;
            }
            $lines[] = '  • ' . $name . $variantStr . ' × ' . $item->quantity;
        }
        $lines[] = '';
        $lines[] = 'Delivery:';
        if ($this->delivery) {
            $deliveryType = $this->delivery->delivery_type ?? 'customer_address';
            if ($deliveryType === 'pickup_point' && $this->delivery->pickupWarehouse) {
                $w = $this->delivery->pickupWarehouse;
                $lines[] = '  Pickup point: ' . $w->name;
                if (! empty($w->address)) {
                    $lines[] = '  ' . $w->address;
                }
                if (! empty($w->location)) {
                    $lines[] = '  ' . $w->location;
                }
            } else {
                $addr = $this->shippingAddress ?: $this->customer?->getDefaultAddress();
                if ($addr) {
                    $lines[] = '  Deliver to customer address:';
                    $parts = array_filter([
                        $addr->address_line_1,
                        $addr->address_line_2,
                        $addr->city,
                        $addr->state_province ?? $addr->state ?? null,
                        $addr->postal_code,
                        $addr->country_name ?? $addr->country ?? $addr->country_code ?? null,
                    ]);
                    $lines[] = '  ' . implode(', ', $parts);
                    if (! empty($addr->phone)) {
                        $lines[] = '  Phone: ' . $addr->phone;
                    }
                } else {
                    $lines[] = '  (Customer address not set)';
                }
            }
        } else {
            $lines[] = '  (Delivery not assigned)';
        }
        $lines[] = '';
        if (strtolower($this->payment_method ?? '') === 'cash_on_delivery') {
            $lines[] = '*** Payment on delivery: collect ' . $this->currency . ' ' . number_format($this->total_amount, 2) . ' from customer. ***';
        }
        return implode("\n", $lines);
    }

    /**
     * Create warehouse receivables at the pickup point warehouse for this order (one per order item).
     * Called when delivery is assigned with delivery_type = pickup_point. Skips if receivables already exist for this order.
     */
    public function createReceivablesForPickupPoint(): void
    {
        $delivery = $this->delivery;
        if (! $delivery || $delivery->delivery_type !== 'pickup_point' || ! $delivery->pickup_warehouse_id) {
            return;
        }

        $warehouseId = $delivery->pickup_warehouse_id;
        $fromWarehouseId = $delivery->dispatching_warehouse_id;

        $existingQuery = WarehouseReceivable::where('order_id', $this->id)->whereNull('order_return_id');
        if ((clone $existingQuery)->where('warehouse_id', $warehouseId)->exists()) {
            return;
        }
        $existingQuery->delete();

        $note = 'Order #' . $this->order_code;
        foreach ($this->orderItems as $item) {
            WarehouseReceivable::create([
                'warehouse_id' => $warehouseId,
                'order_id' => $this->id,
                'from_warehouse_id' => $fromWarehouseId,
                'product_variant_id' => $item->product_variant_id,
                'quantity' => $item->quantity,
                'status' => 'pending',
                'note' => $note,
            ]);
        }
    }

    public function getUniqueProductsCount(): int
    {
        return $this->orderItems->count();
    }

    public function getSellersCount(): int
    {
        return $this->orderItems->pluck('seller_id')->unique()->count();
    }

    // Route key binding: resolve by ulid or by id (for legacy orders without ulid)
    public function getRouteKeyName(): string
    {
        return 'ulid';
    }

    public function resolveRouteBinding($value, $field = null)
    {
        if (is_numeric($value)) {
            return static::where('id', (int) $value)->first();
        }

        return static::where($field ?? $this->getRouteKeyName(), $value)->first();
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

    public function items(): HasMany
{
    return $this->orderItems();
}

    public static function generateUniqueOrderCode(): string
    {
        // Get the last record's ID
        $lastId = self::max('id') ?? 01;

        $nextId = $lastId + 1;

        // str_pad(input, length, character, side)
        return "ORD-" . str_pad($nextId, 6, '0', STR_PAD_LEFT);
    }

}
