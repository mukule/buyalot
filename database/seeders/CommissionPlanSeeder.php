<?php

namespace Database\Seeders;

use App\Models\Commission\CommissionPlan;
use App\Models\Commission\CommissionRule;
use Illuminate\Database\Console\Seeds\WithoutModelEvents;
use Illuminate\Database\Seeder;

class CommissionPlanSeeder extends Seeder
{
    /**
     * Run the database seeds.
     */
    public function run(): void
    {
        // Starter Plan
        $starterPlan = CommissionPlan::create([
            'name' => 'Starter Plan',
            'slug' => 'starter',
            'description' => 'Perfect for small businesses just getting started',
            'billing_cycle' => 'monthly',
            'base_fee' => 29.99,
            'is_active' => true,
            'features' => [
                'max_products' => 100,
                'support_level' => 'basic',
                'analytics' => false,
            ],
            'sort_order' => 1,
        ]);

        // Starter plan commission rule
        CommissionRule::create([
            'commission_plan_id' => $starterPlan->id,
            'name' => 'Standard Commission',
            'description' => '3.5% transaction fee',
            'calculation_type' => 'percentage',
            'rate' => 3.5,
            'min_charge' => 0.30,
            'is_active' => true,
            'priority' => 1,
        ]);

        // Pro Plan
        $proPlan = CommissionPlan::create([
            'name' => 'Professional Plan',
            'slug' => 'professional',
            'description' => 'For growing businesses with more features',
            'billing_cycle' => 'monthly',
            'base_fee' => 79.99,
            'is_active' => true,
            'features' => [
                'max_products' => 1000,
                'support_level' => 'premium',
                'analytics' => true,
                'api_access' => true,
            ],
            'sort_order' => 2,
        ]);

        // Pro plan commission rule with tiers
        $proRule = CommissionRule::create([
            'commission_plan_id' => $proPlan->id,
            'name' => 'Tiered Commission',
            'description' => 'Lower rates for higher volumes',
            'calculation_type' => 'tiered',
            'rate' => 0, // Will use tiers
            'min_charge' => 0.30,
            'is_active' => true,
            'priority' => 1,
        ]);

        // Create tiers for pro plan
        $proRule->tiers()->createMany([
            ['min_amount' => 0, 'max_amount' => 10000, 'tier_rate' => 3.0, 'tier_order' => 1, 'tier_name' => 'First $10k'],
            ['min_amount' => 10000, 'max_amount' => 50000, 'tier_rate' => 2.5, 'tier_order' => 2, 'tier_name' => '$10k - $50k'],
            ['min_amount' => 50000, 'max_amount' => null, 'tier_rate' => 2.0, 'tier_order' => 3, 'tier_name' => 'Above $50k'],
        ]);

        // Enterprise Plan
        $enterprisePlan = CommissionPlan::create([
            'name' => 'Enterprise Plan',
            'slug' => 'enterprise',
            'description' => 'Custom solutions for large businesses',
            'billing_cycle' => 'annually',
            'base_fee' => 999.99,
            'is_active' => true,
            'features' => [
                'max_products' => -1, // Unlimited
                'support_level' => 'enterprise',
                'analytics' => true,
                'api_access' => true,
                'custom_integrations' => true,
                'dedicated_support' => true,
            ],
            'sort_order' => 3,
        ]);

        // Enterprise plan commission rule
        CommissionRule::create([
            'commission_plan_id' => $enterprisePlan->id,
            'name' => 'Enterprise Commission',
            'description' => 'Lowest commission rates',
            'calculation_type' => 'percentage',
            'rate' => 1.5,
            'min_charge' => 0.25,
            'max_charge' => null,
            'is_active' => true,
            'priority' => 1,
        ]);
    }
}
