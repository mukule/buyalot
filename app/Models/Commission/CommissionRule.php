<?php

namespace App\Models\Commission;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

class CommissionRule extends Model
{
    use HasFactory;

    protected $fillable = [
        'commission_plan_id', 'name', 'description', 'calculation_type',
        'rate', 'min_charge', 'max_charge', 'free_threshold',
        'compound_with_base_fee', 'calculation_config', 'is_active', 'priority'
    ];

    protected $casts = [
        'rate' => 'decimal:4',
        'min_charge' => 'decimal:2',
        'max_charge' => 'decimal:2',
        'free_threshold' => 'decimal:2',
        'compound_with_base_fee' => 'boolean',
        'calculation_config' => 'array',
        'is_active' => 'boolean',
        'priority' => 'integer',
    ];

    public function plan(): BelongsTo
    {
        return $this->belongsTo(CommissionPlan::class, 'commission_plan_id');
    }

    public function conditions(): HasMany
    {
        return $this->hasMany(CommissionRuleCondition::class);
    }

    public function tiers(): HasMany
    {
        return $this->hasMany(CommissionRuleTier::class);
    }

    public function calculations(): HasMany
    {
        return $this->hasMany(CommissionCalculation::class);
    }

    public function scopeActive($query)
    {
        return $query->where('is_active', true);
    }

    public function scopeByPriority($query)
    {
        return $query->orderBy('priority', 'desc');
    }
}
