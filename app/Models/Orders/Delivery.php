<?php

namespace App\Models\Orders;

use App\Models\User;
use App\Models\Warehouse\Warehouse;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class Delivery extends Model
{
    protected $table = 'deliveries';

    protected $fillable = [
        'order_id',
        'delivery_id',
        'delivery_type',
        'pickup_warehouse_id',
        'dispatching_warehouse_id',
        'assignment_status',
        'rejection_reason',
        'allocated_for_pickup_at',
        'picked_at',
        'delivered_at',
    ];

    protected $casts = [
        'allocated_for_pickup_at' => 'datetime',
        'picked_at' => 'datetime',
        'delivered_at' => 'datetime',
    ];

    public function order(): BelongsTo
    {
        return $this->belongsTo(Order::class);
    }

    public function deliveryUser(): BelongsTo
    {
        return $this->belongsTo(User::class, 'delivery_id');
    }

    public function pickupWarehouse(): BelongsTo
    {
        return $this->belongsTo(Warehouse::class, 'pickup_warehouse_id')->withoutGlobalScopes();
    }

    public function dispatchingWarehouse(): BelongsTo
    {
        return $this->belongsTo(Warehouse::class, 'dispatching_warehouse_id')->withoutGlobalScopes();
    }

    public function isPending(): bool
    {
        return $this->assignment_status === 'pending';
    }

    public function isAccepted(): bool
    {
        return $this->assignment_status === 'accepted';
    }

    public function isRejected(): bool
    {
        return $this->assignment_status === 'rejected';
    }
}
