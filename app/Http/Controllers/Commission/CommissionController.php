<?php

namespace App\Http\Controllers\Commission;

use App\Http\Controllers\Controller;
use App\Models\Commission\CommissionPlan;

class CommissionController extends Controller
{
    public function index()
    {
        $plans = CommissionPlan::with('rules')->ordered()->paginate(15);
        return view('admin.commission-plans.index', compact('plans'));
    }

    public function create()
    {
        return view('admin.commission-plans.create');
    }

    public function store(CommissionPlanRequest $request)
    {
        $plan = CommissionPlan::create($request->validated());
        return redirect()->route('admin.commission-plans.show', $plan)
            ->with('success', 'Commission plan created successfully.');
    }

    public function show(CommissionPlan $plan)
    {
        $plan->load(['rules.conditions', 'rules.tiers', 'subscriptions']);
        return view('admin.commission-plans.show', compact('plan'));
    }

    public function edit(CommissionPlan $plan)
    {
        return view('admin.commission-plans.edit', compact('plan'));
    }

    public function update(CommissionPlanRequest $request, CommissionPlan $plan)
    {
        $plan->update($request->validated());
        return redirect()->route('admin.commission-plans.show', $plan)
            ->with('success', 'Commission plan updated successfully.');
    }

    public function destroy(CommissionPlan $plan)
    {
        if ($plan->subscriptions()->exists()) {
            return back()->with('error', 'Cannot delete plan with active subscriptions.');
        }

        $plan->delete();
        return redirect()->route('admin.commission-plans.index')
            ->with('success', 'Commission plan deleted successfully.');
    }

    public function toggle(CommissionPlan $plan)
    {
        $plan->update(['is_active' => !$plan->is_active]);
        $status = $plan->is_active ? 'activated' : 'deactivated';
        return back()->with('success', "Commission plan {$status} successfully.");
    }
}
