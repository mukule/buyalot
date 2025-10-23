<?php

namespace App\Models\Commission;

use Illuminate\Database\Eloquent\Model;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class CommissionRuleTier extends Model
{
    use HasFactory;

    protected $fillable = [
        'commission_rule_id', 'min_amount', 'max_amount',
        'tier_rate', 'tier_fixed_amount', 'tier_order', 'tier_name'
    ];

    protected $casts = [
        'min_amount' => 'decimal:2',
        'max_amount' => 'decimal:2',
        'tier_rate' => 'decimal:4',
        'tier_fixed_amount' => 'decimal:2',
        'tier_order' => 'integer',
    ];

    public function rule(): BelongsTo
    {
        return $this->belongsTo(CommissionRule::class, 'commission_rule_id');
    }

    public function scopeOrdered($query)
    {
        return $query->orderBy('tier_order');
    }
}

