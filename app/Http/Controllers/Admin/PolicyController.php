<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\Policy\Policy;
use Illuminate\Http\Request;
use Inertia\Inertia;

class PolicyController extends Controller
{
    
    public function index()
{
    $policies = Policy::with(['policyVersions' => function($q) {
        $q->latest('version_number')->limit(1);
    }])
    ->orderByDesc('status')
    ->orderByDesc('is_mandatory')
    ->latest()
    ->paginate(10);

    // Transform items inside the paginator
    $policies->getCollection()->transform(function ($policy) {
        return [
            'id' => $policy->id,
            'title' => $policy->title,
            'scope' => $policy->scope,
            'status' => $policy->status,
            'code' => $policy->code,
            'is_mandatory' => $policy->is_mandatory,
            'latest_version' => $policy->policyVersions->first()?->version_number ?? null,
        ];
    });

    return Inertia::render('Admin/Policies/Index', [
        'policies' => $policies,
        'filters' => request()->only(['scope', 'status']),
    ]);
}


    /**
     * Show the form for creating a new policy or editing an existing one.
     */
    public function create(?Policy $policy = null)
    {
        // Use the same template for create and edit
        return Inertia::render('Admin/Policies/Form', [
            'policy' => $policy,
            'scopes' => ['seller', 'customer'], // dropdown for scope
        ]);
    }

    /**
     * Store a newly created policy.
     */
    public function store(Request $request)
    {
        $validated = $request->validate([
            'title' => 'required|string|max:255',
            'scope' => 'required|in:seller,customer',
            'is_mandatory' => 'boolean',
            'status' => 'boolean',
        ]);

        $policy = Policy::create($validated);

        return redirect()->route('admin.policies.index')
                         ->with('success', 'Policy created successfully.');
    }

    /**
     * Display the specified policy.
     */
    public function show(Policy $policy)
    {
        return Inertia::render('Admin/Policies/Show', [
            'policy' => $policy->load('policyVersions'),
        ]);
    }

    /**
     * Show the form for editing the specified policy (uses same template as create).
     */
    public function edit(Policy $policy)
    {
        return $this->create($policy);
    }

    /**
     * Update the specified policy.
     */
    public function update(Request $request, Policy $policy)
    {
        $validated = $request->validate([
            'title' => 'required|string|max:255',
            'scope' => 'required|in:seller,customer',
            'is_mandatory' => 'boolean',
            'status' => 'boolean',
        ]);

        $policy->update($validated);

        return redirect()->route('admin.policies.index')
                         ->with('success', 'Policy updated successfully.');
    }

    /**
     * Remove the specified policy from storage.
     */
    public function destroy(Policy $policy)
    {
        $policy->delete();

        return redirect()->route('admin.policies.index')
                         ->with('success', 'Policy deleted successfully.');
    }
}
