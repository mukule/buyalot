<?php

namespace App\Models\Payment;

use App\Models\Traits\HasHashid;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

use App\Traits\HasUlid;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\Relations\MorphTo;

class Payment extends Model
{
    use HasFactory, HasUlid, HasHashid;

    protected $fillable = [
        'payable_type',
        'payable_id',
        'amount',
        'currency',
        'provider',
        'method',
        'status',
        'reference',
        'provider_reference',
        'metadata',
        'failure_reason',
        'expires_at',
        'completed_at',
        'ulid'
    ];

    protected $casts = [
        'amount' => 'decimal:2',
        'metadata' => 'array',
        'expires_at' => 'datetime',
        'completed_at' => 'datetime',
        'status' => PaymentStatus::class,
    ];

    public function payable(): MorphTo
    {
        return $this->morphTo();
    }

    public function transactions(): HasMany
    {
        return $this->hasMany(PaymentTransaction::class);
    }

    public function scopeByProvider($query, string $provider)
    {
        return $query->where('provider', $provider);
    }

    public function scopeByStatus($query, PaymentStatus $status)
    {
        return $query->where('status', $status);
    }

    public function isPending(): bool
    {
        return $this->status === PaymentStatus::PENDING;
    }

    public function isCompleted(): bool
    {
        return $this->status === PaymentStatus::COMPLETED;
    }

    public function isFailed(): bool
    {
        return $this->status === PaymentStatus::FAILED;
    }

    public function isExpired(): bool
    {
        return $this->expires_at && $this->expires_at->isPast();
    }

    public function markAsCompleted(): void
    {
        $this->update([
            'status' => PaymentStatus::COMPLETED,
            'completed_at' => now(),
        ]);
    }

    public function markAsFailed(string $reason = null): void
    {
        $this->update([
            'status' => PaymentStatus::FAILED,
            'failure_reason' => $reason,
        ]);
    }
}
