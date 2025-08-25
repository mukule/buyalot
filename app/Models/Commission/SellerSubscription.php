<?php

namespace App\Models\Commission;

use App\Models\User;
use Illuminate\Database\Eloquent\Model;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Carbon\Carbon;

class SellerSubscription extends Model
{
    use HasFactory;

    protected $fillable = [
        'seller_id', 'commission_plan_id', 'custom_base_fee',
        'custom_rates', 'status', 'started_at', 'expires_at',
        'cancelled_at', 'metadata'
    ];

    protected $casts = [
        'custom_base_fee' => 'decimal:2',
        'custom_rates' => 'array',
        'started_at' => 'datetime',
        'expires_at' => 'datetime',
        'cancelled_at' => 'datetime',
        'metadata' => 'array',
    ];

    public function seller(): BelongsTo
    {
        return $this->belongsTo(User::class, 'seller_id');
    }

    public function plan(): BelongsTo
    {
        return $this->belongsTo(CommissionPlan::class, 'commission_plan_id');
    }

    public function scopeActive($query)
    {
        return $query->where('status', 'active')
            ->where(function($q) {
                $q->whereNull('expires_at')
                    ->orWhere('expires_at', '>', now());
            });
    }

    public function isActive(): bool
    {
        return $this->status === 'active' &&
            (is_null($this->expires_at) || $this->expires_at->isFuture());
    }

    public function getEffectiveBaseFee(): float
    {
        return $this->custom_base_fee ?? $this->plan->base_fee;
    }
}
