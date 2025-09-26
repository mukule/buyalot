<?php

namespace App\Traits;

use App\Models\Commission\CommissionCalculation;
use App\Models\Commission\CommissionRule;
use App\Services\CommissionCalculatorService;

/**
 * @method hasActiveSubscription()
 */
trait CalculatesCommissions
{
    public function calculateCommission(array $saleData): ?CommissionCalculation
    {
        if (!$this->hasActiveSubscription()) {
            return null;
        }

        $calculator = app(CommissionCalculatorService::class);
        return $calculator->calculateForSeller($this, $saleData);
    }

    public function getApplicableRules(array $saleData): \Illuminate\Support\Collection
    {
        if (!$this->hasActiveSubscription()) {
            return collect();
        }

        return $this->subscription->plan->rules()
            ->active()
            ->byPriority()
            ->get()
            ->filter(function($rule) use ($saleData) {
                return $this->ruleApplies($rule, $saleData);
            });
    }

    private function ruleApplies(CommissionRule $rule, array $saleData): bool
    {
        $calculator = app(CommissionCalculatorService::class);
        return $calculator->ruleApplies($rule, $saleData);
    }
}
