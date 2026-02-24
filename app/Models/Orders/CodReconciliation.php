<?php

namespace App\Models\Orders;

use App\Models\User;
use App\Models\Warehouse\Warehouse;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class CodReconciliation extends Model
{
    protected $table = 'cod_reconciliations';

    protected $fillable = [
        'order_id',
        'delivery_id',
        'warehouse_id',
        'amount',
        'currency',
        'reconciled_at',
        'confirmed_by',
        'confirmed_at',
        'notes',
    ];

    protected $casts = [
        'amount' => 'decimal:2',
        'reconciled_at' => 'datetime',
        'confirmed_at' => 'datetime',
    ];

    public function order(): BelongsTo
    {
        return $this->belongsTo(Order::class);
    }

    public function deliveryUser(): BelongsTo
    {
        return $this->belongsTo(User::class, 'delivery_id');
    }

    public function warehouse(): BelongsTo
    {
        return $this->belongsTo(Warehouse::class)->withoutGlobalScopes();
    }

    public function confirmedByUser(): BelongsTo
    {
        return $this->belongsTo(User::class, 'confirmed_by');
    }

    public function isPendingConfirmation(): bool
    {
        return $this->reconciled_at !== null && $this->confirmed_at === null;
    }

    public function isConfirmed(): bool
    {
        return $this->confirmed_at !== null;
    }
}
