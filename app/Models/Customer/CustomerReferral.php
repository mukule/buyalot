<?php

namespace App\Models\Customer;

use App\Models\Traits\HasHashid;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class CustomerReferral extends Model
{
    use HasFactory,HasHashid;

    protected $fillable = [
        'referrer_customer_id', 'referred_customer_id', 'referral_code',
        'status', 'referred_at', 'converted_at', 'reward_type',
        'reward_amount', 'reward_currency', 'referrer_reward_given',
        'referred_reward_given', 'campaign_id', 'source', 'metadata'
    ];

    protected $casts = [
        'referred_at' => 'datetime',
        'converted_at' => 'datetime',
        'reward_amount' => 'decimal:2',
        'referrer_reward_given' => 'boolean',
        'referred_reward_given' => 'boolean',
        'metadata' => 'array',
    ];

    // Relationships
    public function referrer(): BelongsTo
    {
        return $this->belongsTo(Customer::class, 'referrer_customer_id');
    }

    public function referred(): BelongsTo
    {
        return $this->belongsTo(Customer::class, 'referred_customer_id');
    }

    // Scopes
    public function scopeSuccessful($query)
    {
        return $query->where('status', 'converted');
    }

    public function scopePending($query)
    {
        return $query->where('status', 'pending');
    }

    // Methods
    public function markAsConverted(): void
    {
        $this->update([
            'status' => 'converted',
            'converted_at' => now()
        ]);
    }

    public function isSuccessful(): bool
    {
        return $this->status === 'converted';
    }
}
