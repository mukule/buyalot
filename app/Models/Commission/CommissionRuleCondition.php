<?php

namespace App\Models\Commission;

use Illuminate\Database\Eloquent\Model;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class CommissionRuleCondition extends Model
{
    use HasFactory;

    protected $fillable = [
        'commission_rule_id', 'condition_type', 'operator',
        'condition_value', 'logic_operator'
    ];

    protected $casts = [
        'condition_value' => 'array',
    ];

    public function rule(): BelongsTo
    {
        return $this->belongsTo(CommissionRule::class, 'commission_rule_id');
    }
}
