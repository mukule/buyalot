<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use Inertia\Inertia;
use App\Models\Permission;

class PermissionController1 extends Controller
{
    public function index(Request $request)
    {
        $permissions = Permission::all()->map(function ($permission) {
            $permission->hashid = $permission->getRouteKey();
            return $permission;
        });
        // Return JSON for API requests
        if ($request->wantsJson() || $request->is('api/*')) {
            return response()->json($permissions);
        }

        return Inertia::render('Admin/Permissions/Index', [
            'permissions' => $permissions,
        ]);
    }

    public function create()
    {
        return Inertia::render('Admin/Permissions/Create');
    }

    public function store(Request $request)
    {
        $validated = $request->validate([
            'name' => 'required|string|unique:permissions,name',
            'guard_name' => 'nullable|string',
        ]);

        $permission= Permission::create([
            'name' => $validated['name'],
            'guard_name' => $validated['guard_name'] ?? 'web',
        ]);
        $permission->hashid = $permission->getRouteKey();

        // Return JSON for API requests
        if ($request->wantsJson() || $request->is('api/*')) {
            return response()->json($permission, 201);
        }

        return redirect()->route('admin.permissions.index')
                         ->with('success', 'Permission created successfully.');
    }

    public function show(Request $request, Permission $permission)
    {
        $permission->hashid = $permission->getRouteKey();

        // JSON for API requests
        if ($request->wantsJson() || $request->is('api/*')) {
            return response()->json($permission);
        }

        // Return Inertia view for web requests
        return Inertia::render('Admin/Permissions/Show', [
            'permission' => $permission,
        ]);
    }

    public function edit(Request $request,Permission $permission)
    {
        $permission->hashid = $permission->getRouteKey();

        return Inertia::render('Admin/Permissions/Edit', [
            'permission' => $permission,
        ]);
    }

    public function update(Request $request, Permission $permission)
    {
        $validated = $request->validate([
            'name' => 'required|string|unique:permissions,name,' . $permission->id,
            'guard_name' => 'nullable|string',
        ]);

        $permission->update([
            'name' => $validated['name'],
            'guard_name' => $validated['guard_name'] ?? $permission->guard_name,
        ]);

        if ($request->wantsJson() || $request->is('api/*')) {
            return response()->json($permission);
        }
        return redirect()->route('admin.permissions.index')
                         ->with('success', 'Permission updated successfully.');
    }

    public function destroy(Request $request,Permission $permission)
    {
        $permission->delete();
        if ($request->wantsJson() || $request->is('api/*')) {
            return response()->json(['message' => 'Permission deleted successfully']);
        }
        return redirect()->route('admin.permissions.index')
                         ->with('success', 'Permission deleted successfully.');
    }
}
