<?php

namespace App\Models\Commission;

use App\Models\User;
use Illuminate\Database\Eloquent\Model;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

class CommissionInvoice extends Model
{
    use HasFactory;

    protected $fillable = [
        'invoice_number', 'seller_id', 'period_start', 'period_end',
        'base_fee', 'commission_total', 'tax_amount', 'discount_amount',
        'total_amount', 'status', 'issued_at', 'due_at', 'paid_at', 'invoice_data'
    ];

    protected $casts = [
        'period_start' => 'date',
        'period_end' => 'date',
        'base_fee' => 'decimal:2',
        'commission_total' => 'decimal:2',
        'tax_amount' => 'decimal:2',
        'discount_amount' => 'decimal:2',
        'total_amount' => 'decimal:2',
        'issued_at' => 'datetime',
        'due_at' => 'datetime',
        'paid_at' => 'datetime',
        'invoice_data' => 'array',
    ];

    public function seller(): BelongsTo
    {
        return $this->belongsTo(User::class, 'seller_id');
    }

    public function items(): HasMany
    {
        return $this->hasMany(CommissionInvoiceItem::class);
    }

    public function payments(): HasMany
    {
        return $this->hasMany(CommissionPayment::class);
    }

    public function adjustments(): HasMany
    {
        return $this->hasMany(CommissionAdjustment::class);
    }

    public function scopePending($query)
    {
        return $query->where('status', 'pending');
    }

    public function scopeOverdue($query)
    {
        return $query->where('status', 'overdue')
            ->orWhere(function($q) {
                $q->where('status', 'pending')
                    ->where('due_at', '<', now());
            });
    }

    public function isPaid(): bool
    {
        return $this->status === 'paid';
    }

    public function isOverdue(): bool
    {
        return $this->due_at && $this->due_at->isPast() && !$this->isPaid();
    }
}
