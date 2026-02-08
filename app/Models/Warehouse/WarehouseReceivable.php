<?php

namespace App\Models\Warehouse;

use App\Models\Orders\Order;
use App\Models\Orders\OrderReturn;
use App\Models\Products\ProductVariant;
use App\Models\User;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class WarehouseReceivable extends Model
{
    use HasFactory;

    protected $fillable = [
        'warehouse_id',
        'order_id',
        'order_return_id',
        'from_warehouse_id',
        'product_variant_id',
        'quantity',
        'status',
        'note',
        'created_by',
        'received_by',
        'received_at',
        'rejected_reason_id',
        'rejected_by',
        'rejected_reason',
        'rejected_at',
    ];

    protected $casts = [
        'received_at' => 'datetime',
        'rejected_at' => 'datetime',
    ];

    public function warehouse(): BelongsTo
    {
        return $this->belongsTo(Warehouse::class);
    }

    public function order(): BelongsTo
    {
        return $this->belongsTo(Order::class);
    }

    public function orderReturn(): BelongsTo
    {
        return $this->belongsTo(OrderReturn::class);
    }

    public function productVariant(): BelongsTo
    {
        return $this->belongsTo(ProductVariant::class);
    }

    public function fromWarehouse(): BelongsTo
    {
        return $this->belongsTo(Warehouse::class, 'from_warehouse_id');
    }

    public function creator(): BelongsTo
    {
        return $this->belongsTo(User::class, 'created_by');
    }

    public function receiver(): BelongsTo
    {
        return $this->belongsTo(User::class, 'received_by');
    }

    public function rejectionReason(): BelongsTo
    {
        return $this->belongsTo(WarehouseRejectionReason::class, 'rejected_reason_id');
    }
}
