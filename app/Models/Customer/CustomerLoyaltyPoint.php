<?php

namespace App\Models\Customer;

use App\Models\Traits\HasHashid;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\MorphTo;

class CustomerLoyaltyPoint extends Model
{
    use HasFactory,HasHashid;

    protected $fillable = [
        'customer_id', 'points', 'type', 'description', 'reference_type',
        'reference_id', 'status', 'expires_at', 'awarded_at', 'redeemed_at'
    ];

    protected $casts = [
        'points' => 'integer',
        'expires_at' => 'datetime',
        'awarded_at' => 'datetime',
        'redeemed_at' => 'datetime',
    ];

    // Relationships
    public function customer(): BelongsTo
    {
        return $this->belongsTo(Customer::class);
    }

    public function reference(): MorphTo
    {
        return $this->morphTo();
    }

    // Scopes
    public function scopeActive($query)
    {
        return $query->where('status', 'active')
            ->where(function($q) {
                $q->whereNull('expires_at')
                    ->orWhere('expires_at', '>', now());
            });
    }

    public function scopeExpired($query)
    {
        return $query->where('expires_at', '<', now());
    }

    public function scopeEarned($query)
    {
        return $query->where('points', '>', 0);
    }

    public function scopeRedeemed($query)
    {
        return $query->where('points', '<', 0);
    }

    // Methods
    public function isExpired(): bool
    {
        return $this->expires_at && $this->expires_at->isPast();
    }

    public function redeem(): void
    {
        $this->update([
            'status' => 'redeemed',
            'redeemed_at' => now()
        ]);
    }
}
