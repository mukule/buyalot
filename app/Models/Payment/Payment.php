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
    use HasUlid;

    protected $fillable = [
        'ulid',
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
        'payable_type',
        'payable_id',
        'mpesa_receipt_number',
        'verified_at',
        'amount_paid',
    ];

    protected $casts = [
        'amount' => 'decimal:2',
        'amount_paid' => 'decimal:2',
        'metadata' => 'array',
        'expires_at' => 'datetime',
        'completed_at' => 'datetime',
        'status' => PaymentStatus::class,
    ];


    // Polymorphic: can belong to Order, Invoice, etc.
    public function payable(): MorphTo
    {
        return $this->morphTo();
    }
    public function mpesa()
    {
        return $this->hasOne(MpesaPayment::class);
    }

    /*
    |--------------------------------------------------------------------------
    | Query Scopes
    |--------------------------------------------------------------------------
    */

    public function scopeProvider($query, string $provider)
    {
        return $query->where('provider', $provider);
    }

    public function scopeSuccessful($query)
    {
        return $query->where('status', 'completed');
    }

    public function scopePending($query)
    {
        return $query->where('status', 'pending');
    }

    public function scopeFailed($query)
    {
        return $query->where('status', 'failed');
    }

    /*
    |--------------------------------------------------------------------------
    | Helpers
    |--------------------------------------------------------------------------
    */
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
        return $this->status === PaymentStatus::PENDING->value;
    }

    public function isCompleted(): bool
    {
        return $this->status === PaymentStatus::COMPLETED->value;
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

    // Use ulid for route model binding so web routes like payments.status accept the returned id
    public function getRouteKeyName(): string
    {
        return 'ulid';
    }
}
