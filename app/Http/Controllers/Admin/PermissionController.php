<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use Inertia\Inertia;
use App\Models\Permission;

class PermissionController extends Controller
{
    public function index(Request $request)
    {
        $query = Permission::query();

        // Filter by module if provided
        if ($request->has('module') && $request->module) {
            $query->where('module', $request->module);
        }

        // Search functionality
        if ($request->has('search') && $request->search) {
            $query->where('name', 'like', '%' . $request->search . '%');
        }

        $permissions = $query->orderBy('module')->orderBy('name')->get()->map(function ($permission) {
            $permission->hashid = $permission->getRouteKey();
            return $permission;
        });

        // Get unique modules for filter dropdown
        $modules = Permission::distinct()->pluck('module')->filter()->sort()->values();

        // Group permissions by module for better display
        $groupedPermissions = $permissions->groupBy('module');

        // Return JSON for API requests
        if ($request->wantsJson() || $request->is('api/*')) {
            return response()->json([
                'permissions' => $permissions,
                'grouped_permissions' => $groupedPermissions,
                'modules' => $modules,
            ]);
        }

        return Inertia::render('Admin/Permissions/Index', [
            'permissions' => $permissions,
            'grouped_permissions' => $groupedPermissions,
            'modules' => $modules,
            'filters' => [
                'module' => $request->module,
                'search' => $request->search,
            ]
        ]);
    }

    public function create()
    {
        // Get existing modules for the dropdown
        $modules = Permission::distinct()->pluck('module')->filter()->sort()->values();

        return Inertia::render('Admin/Permissions/Create', [
            'modules' => $modules,
        ]);
    }

    public function store(Request $request)
    {
        $validated = $request->validate([
            'name' => 'required|string|unique:permissions,name',
            'guard_name' => 'nullable|string',
            'module' => 'nullable|string',
            'description' => 'nullable|string',
        ]);

        $permission = Permission::create([
            'name' => $validated['name'],
            'guard_name' => $validated['guard_name'] ?? 'web',
            'module' => $validated['module'],
            'description' => $validated['description'],
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

        // Get roles that have this permission
        $rolesWithPermission = $permission->roles()->get()->map(function ($role) {
            $role->hashid = $role->getRouteKey();
            return $role;
        });

        // JSON for API requests
        if ($request->wantsJson() || $request->is('api/*')) {
            return response()->json([
                'permission' => $permission,
                'roles' => $rolesWithPermission,
            ]);
        }

        // Return Inertia view for web requests
        return Inertia::render('Admin/Permissions', [
            'permission' => $permission,
            'roles' => $rolesWithPermission,
        ]);
    }

    public function edit(Request $request, Permission $permission)
    {
        $permission->hashid = $permission->getRouteKey();

        // Get existing modules for the dropdown
        $modules = Permission::distinct()->pluck('module')->filter()->sort()->values();

        return Inertia::render('Admin/Permissions/Edit', [
            'permission' => $permission,
            'modules' => $modules,
        ]);
    }

    public function update(Request $request, Permission $permission)
    {
        $validated = $request->validate([
            'name' => 'required|string|unique:permissions,name,' . $permission->id,
            'guard_name' => 'nullable|string',
            'module' => 'nullable|string',
            'description' => 'nullable|string',
        ]);

        $permission->update([
            'name' => $validated['name'],
            'guard_name' => $validated['guard_name'] ?? $permission->guard_name,
            'module' => $validated['module'],
            'description' => $validated['description'],
        ]);

        $permission->hashid = $permission->getRouteKey();

        if ($request->wantsJson() || $request->is('api/*')) {
            return response()->json($permission);
        }

        return redirect()->route('admin.permissions.index')
            ->with('success', 'Permission updated successfully.');
    }

    public function destroy(Request $request, Permission $permission)
    {
        // Check if permission is assigned to any roles
        if ($permission->roles()->count() > 0) {
            if ($request->wantsJson() || $request->is('api/*')) {
                return response()->json([
                    'message' => 'Cannot delete permission that is assigned to roles'
                ], 422);
            }

            return redirect()->route('admin.permissions.index')
                ->with('error', 'Cannot delete permission that is assigned to roles.');
        }

        $permission->delete();

        if ($request->wantsJson() || $request->is('api/*')) {
            return response()->json(['message' => 'Permission deleted successfully']);
        }

        return redirect()->route('admin.permissions.index')
            ->with('success', 'Permission deleted successfully.');
    }

    public function bulkDelete(Request $request)
    {
        $validated = $request->validate([
            'permissions' => 'required|array',
            'permissions.*' => 'exists:permissions,id'
        ]);

        $permissions = Permission::whereIn('id', $validated['permissions'])->get();

        // Check if any permissions are assigned to roles
        $assignedPermissions = $permissions->filter(function ($permission) {
            return $permission->roles()->count() > 0;
        });

        if ($assignedPermissions->count() > 0) {
            if ($request->wantsJson() || $request->is('api/*')) {
                return response()->json([
                    'message' => 'Some permissions are assigned to roles and cannot be deleted',
                    'assigned_permissions' => $assignedPermissions->pluck('name')
                ], 422);
            }

            return redirect()->route('admin.permissions.index')
                ->with('error', 'Some permissions are assigned to roles and cannot be deleted.');
        }

        Permission::whereIn('id', $validated['permissions'])->delete();

        if ($request->wantsJson() || $request->is('api/*')) {
            return response()->json(['message' => 'Permissions deleted successfully']);
        }

        return redirect()->route('admin.permissions.index')
            ->with('success', 'Permissions deleted successfully.');
    }
}
