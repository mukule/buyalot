<?php

namespace App\Models\Orders;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

class OrderReturn extends Model
{
    public const REASON_BREAKAGES = 'breakages';
    public const REASON_EXPIRY = 'expiry';
    public const REASON_WRONG_ITEMS = 'wrong_items';
    public const REASON_SPOILED = 'spoiled';
    public const REASON_WRONG_QUANTITIES = 'wrong_quantities';
    public const REASON_ORDER_CANCELLATION = 'order_cancellation';
    public const REASON_PICKUP_POINT_CLOSED = 'pickup_point_closed';
    public const REASON_OTHER = 'other';

    public const STATUS_PENDING_RECEIVE = 'pending_receive';
    public const STATUS_RECEIVED_AT_DISPATCH = 'received_at_dispatch';

    public const RAISED_BY_DELIVERY_PERSON = 'delivery_person';
    public const RAISED_BY_WAREHOUSE = 'warehouse';

    protected $fillable = [
        'order_id',
        'delivery_id',
        'raised_by_type',
        'raised_by_id',
        'reason',
        'reason_notes',
        'is_full_return',
        'status',
        'received_at_dispatch_at',
        'received_at_dispatch_by',
    ];

    protected $casts = [
        'is_full_return' => 'boolean',
        'received_at_dispatch_at' => 'datetime',
    ];

    public function order(): BelongsTo
    {
        return $this->belongsTo(Order::class);
    }

    public function delivery(): BelongsTo
    {
        return $this->belongsTo(Delivery::class);
    }

    public function items(): HasMany
    {
        return $this->hasMany(OrderReturnItem::class, 'order_return_id');
    }

    public static function reasonOptions(): array
    {
        return [
            self::REASON_BREAKAGES => 'Breakages',
            self::REASON_EXPIRY => 'Expiry',
            self::REASON_WRONG_ITEMS => 'Wrong items',
            self::REASON_SPOILED => 'Spoiled items',
            self::REASON_WRONG_QUANTITIES => 'Unmatched quantities',
            self::REASON_ORDER_CANCELLATION => 'Order cancellation',
            self::REASON_PICKUP_POINT_CLOSED => 'Pick up point closed',
            self::REASON_OTHER => 'Other',
        ];
    }
}
