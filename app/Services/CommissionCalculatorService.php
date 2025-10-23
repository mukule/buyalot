<?php

namespace App\Services;
use App\Models\Commission\CommissionCalculation;
use App\Models\Commission\CommissionRule;
use App\Models\Commission\CommissionRuleCondition;
use App\Models\User;
use Illuminate\Support\Facades\Log;

class CommissionCalculatorService
{
    public function calculateForSeller(User $seller, array $saleData): ?CommissionCalculation
    {
        $subscription = $seller->subscription;
        if (!$subscription) {
            return null;
        }

        $applicableRules = $this->getApplicableRules($subscription->plan->rules, $saleData);

        if ($applicableRules->isEmpty()) {
            return null;
        }

        // Use highest priority rule
        $rule = $applicableRules->first();

        $calculationResult = $this->calculateCommissionAmount($rule, $saleData);

        return $this->createCalculationRecord($seller, $rule, $saleData, $calculationResult);
    }

    public function getApplicableRules($rules, array $saleData)
    {
        return $rules->active()
            ->byPriority()
            ->get()
            ->filter(function($rule) use ($saleData) {
                return $this->ruleApplies($rule, $saleData);
            });
    }

    public function ruleApplies(CommissionRule $rule, array $saleData): bool
    {
        if ($rule->conditions->isEmpty()) {
            return true; // Rule applies if no conditions
        }

        $andConditions = $rule->conditions->where('logic_operator', 'AND');
        $orConditions = $rule->conditions->where('logic_operator', 'OR');

        $andResult = $andConditions->isEmpty() ? true : $andConditions->every(function($condition) use ($saleData) {
            return $this->evaluateCondition($condition, $saleData);
        });

        $orResult = $orConditions->isEmpty() ? true : $orConditions->some(function($condition) use ($saleData) {
            return $this->evaluateCondition($condition, $saleData);
        });

        return $andResult && $orResult;
    }

    private function evaluateCondition(CommissionRuleCondition $condition, array $saleData): bool
    {
        $fieldValue = data_get($saleData, $condition->condition_type);
        $conditionValue = $condition->condition_value;

        return match($condition->operator) {
            'equals' => $fieldValue == $conditionValue[0] ?? $conditionValue,
            'not_equals' => $fieldValue != $conditionValue[0] ?? $conditionValue,
            'greater_than' => $fieldValue > ($conditionValue[0] ?? $conditionValue),
            'less_than' => $fieldValue < ($conditionValue[0] ?? $conditionValue),
            'greater_than_or_equal' => $fieldValue >= ($conditionValue[0] ?? $conditionValue),
            'less_than_or_equal' => $fieldValue <= ($conditionValue[0] ?? $conditionValue),
            'in' => in_array($fieldValue, is_array($conditionValue) ? $conditionValue : [$conditionValue]),
            'not_in' => !in_array($fieldValue, is_array($conditionValue) ? $conditionValue : [$conditionValue]),
            'contains' => str_contains($fieldValue, $conditionValue[0] ?? $conditionValue),
            'starts_with' => str_starts_with($fieldValue, $conditionValue[0] ?? $conditionValue),
            'ends_with' => str_ends_with($fieldValue, $conditionValue[0] ?? $conditionValue),
            'between' => $fieldValue >= $conditionValue[0] && $fieldValue <= $conditionValue[1],
            default => false
        };
    }

    public function calculateCommissionAmount(CommissionRule $rule, array $saleData): array
    {
        $saleAmount = $saleData['amount'] ?? 0;
        $commissionBase = $saleAmount;

        // Check free threshold
        if ($rule->free_threshold && $saleAmount < $rule->free_threshold) {
            return [
                'commission_amount' => 0,
                'commission_rate' => 0,
                'commission_base' => $commissionBase,
                'calculation_breakdown' => [
                    'reason' => 'Below free threshold',
                    'threshold' => $rule->free_threshold,
                    'sale_amount' => $saleAmount
                ]
            ];
        }

        $commissionAmount = match($rule->calculation_type) {
            'percentage' => $this->calculatePercentageCommission($rule, $commissionBase),
            'fixed_per_item' => $this->calculateFixedPerItemCommission($rule, $saleData),
            'fixed_per_order' => $rule->rate,
            'tiered' => $this->calculateTieredCommission($rule, $commissionBase),
            default => 0
        };

        // Apply min/max constraints
        if ($rule->min_charge && $commissionAmount < $rule->min_charge) {
            $commissionAmount = $rule->min_charge;
        }

        if ($rule->max_charge && $commissionAmount > $rule->max_charge) {
            $commissionAmount = $rule->max_charge;
        }

        return [
            'commission_amount' => round($commissionAmount, 2),
            'commission_rate' => $rule->rate,
            'commission_base' => $commissionBase,
            'calculation_breakdown' => [
                'calculation_type' => $rule->calculation_type,
                'base_rate' => $rule->rate,
                'min_charge_applied' => $rule->min_charge && $commissionAmount == $rule->min_charge,
                'max_charge_applied' => $rule->max_charge && $commissionAmount == $rule->max_charge,
            ]
        ];
    }

    private function calculatePercentageCommission(CommissionRule $rule, float $amount): float
    {
        return ($amount * $rule->rate) / 100;
    }

    private function calculateFixedPerItemCommission(CommissionRule $rule, array $saleData): float
    {
        $itemCount = $saleData['item_count'] ?? 1;
        return $rule->rate * $itemCount;
    }

    private function calculateTieredCommission(CommissionRule $rule, float $amount): float
    {
        $tiers = $rule->tiers()->ordered()->get();
        $totalCommission = 0;
        $remainingAmount = $amount;

        foreach ($tiers as $tier) {
            if ($remainingAmount <= 0) break;

            $tierMax = $tier->max_amount ?? $remainingAmount;
            $tierAmount = min($remainingAmount, $tierMax - $tier->min_amount);

            if ($tierAmount > 0) {
                $tierCommission = ($tierAmount * $tier->tier_rate) / 100 + $tier->tier_fixed_amount;
                $totalCommission += $tierCommission;
                $remainingAmount -= $tierAmount;
            }
        }

        return $totalCommission;
    }

    private function createCalculationRecord(User $seller, CommissionRule $rule, array $saleData, array $calculationResult): CommissionCalculation
    {
        return CommissionCalculation::create([
            'seller_id' => $seller->id,
            'commission_rule_id' => $rule->id,
            'calculable_type' => $saleData['calculable_type'] ?? 'orders',
            'calculable_id' => $saleData['calculable_id'] ?? null,
            'sale_amount' => $saleData['amount'],
            'commission_base' => $calculationResult['commission_base'],
            'commission_rate' => $calculationResult['commission_rate'],
            'commission_amount' => $calculationResult['commission_amount'],
            'calculation_details' => $calculationResult['calculation_breakdown'],
            'calculated_at' => now(),
            'due_at' => now()->addDays(30), // Default 30 days payment term
        ]);
    }
}
