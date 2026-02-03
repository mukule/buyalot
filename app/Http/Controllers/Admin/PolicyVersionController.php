<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\Policy\Policy;
use App\Models\Policy\PolicyVersion;
use Illuminate\Http\Request;
use Inertia\Inertia;

class PolicyVersionController extends Controller
{
    // List all versions of a policy
    public function index(Policy $policy)
    {
        $versions = $policy->policyVersions()->latest('version_number')->get();

        return Inertia::render('Admin/Policies/Versions', [
            'policy' => $policy,
            'versions' => $versions,
        ]);
    }

    // Show form to create a new version
    public function create(Policy $policy)
    {
        // Prefill content from latest version if exists
        $latest = $policy->policyVersions()->latest('version_number')->first();

        return Inertia::render('Admin/Policies/VersionForm', [
            'policy' => $policy,
            'version' => null,          // new version
            'content' => $latest?->content ?? '',
        ]);
    }

    
// -------------------
// Store a new version
// -------------------
public function store(Request $request, Policy $policy)
{
    $validated = $request->validate([
        'content' => 'required|string',
        'effective_from' => 'nullable|date',
        'status' => 'nullable', // we’ll cast manually
    ]);

    // Ensure status is numeric 0 or 1
    $validated['status'] = $request->has('status') && $request->status ? 1 : 0;

    // Determine version number
    $latestVersion = $policy->policyVersions()->latest('version_number')->first();
    $versionNumber = $latestVersion ? $latestVersion->version_number + 1 : 1;

    $policy->policyVersions()->create(array_merge($validated, [
        'version_number' => $versionNumber,
    ]));

    return redirect()->route('admin.policies.versions.index', ['policy' => $policy->id])
                     ->with('success', "Policy version $versionNumber created.");
}

// -------------------
// Update an existing version
// -------------------
public function update(Request $request, Policy $policy, PolicyVersion $version)
{
    $validated = $request->validate([
        'content' => 'required|string',
        'effective_from' => 'nullable|date',
        'status' => 'nullable', // we’ll cast manually
    ]);

    // Ensure status is numeric 0 or 1
    $validated['status'] = $request->has('status') && $request->status ? 1 : 0;

    $version->update($validated);

    return redirect()->route('admin.policies.versions.index', ['policy' => $policy->id])
                     ->with('success', "Policy version {$version->version_number} updated.");
}



    // Show form to edit an existing version
    public function edit(Policy $policy, PolicyVersion $version)
    {
        return Inertia::render('Admin/Policies/VersionForm', [
            'policy' => $policy,
            'version' => $version,
            'content' => $version->content,
        ]);
    }

   

    // Delete a version
    public function destroy(Policy $policy, PolicyVersion $version)
    {
        $version->delete();

        // ✅ Redirect to versions index
        return redirect()->route('admin.policies.versions.index', ['policy' => $policy->id])
                         ->with('success', "Policy version {$version->version_number} deleted.");
    }
}
