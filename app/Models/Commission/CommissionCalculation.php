<?php

namespace App\Models\Commission;

use App\Models\User;
use Illuminate\Database\Eloquent\Model;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\MorphTo;

class CommissionCalculation extends Model
{
    use HasFactory;

    protected $fillable = [
        'seller_id', 'commission_rule_id', 'calculable_type', 'calculable_id',
        'sale_amount', 'commission_base', 'commission_rate', 'commission_amount',
        'calculation_details', 'status', 'calculated_at', 'due_at'
    ];

    protected $casts = [
        'sale_amount' => 'decimal:2',
        'commission_base' => 'decimal:2',
        'commission_rate' => 'decimal:4',
        'commission_amount' => 'decimal:2',
        'calculation_details' => 'array',
        'calculated_at' => 'datetime',
        'due_at' => 'datetime',
    ];

    public function seller(): BelongsTo
    {
        return $this->belongsTo(User::class, 'seller_id');
    }

    public function rule(): BelongsTo
    {
        return $this->belongsTo(CommissionRule::class, 'commission_rule_id');
    }

    public function calculable(): MorphTo
    {
        return $this->morphTo();
    }

    public function scopePending($query)
    {
        return $query->where('status', 'pending');
    }

    public function scopeConfirmed($query)
    {
        return $query->where('status', 'confirmed');
    }
}
