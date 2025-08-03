<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Inertia\Inertia;
use App\Models\Role;
use App\Models\Permission;

class RoleController extends Controller
{
    public function index(Request $request)
    {
        $roles = Role::with('permissions')->get()->map(function ($role) {
            $role->hashid = $role->getRouteKey();
            return $role;
        });

        if ($request->wantsJson() || $request->is('api/*')) {
            return response()->json($roles);
        }

        return Inertia::render('Admin/Roles/Index', [
            'roles' => $roles,
            'permissions' => Permission::all(),
        ]);
    }

    public function create()
    {
        return Inertia::render('Admin/Roles/Create', [
            'permissions' => Permission::all(),
        ]);
    }

    public function store(Request $request)
    {
        $validated = $request->validate([
            'name' => 'required|string|unique:roles,name',
            'guard_name' => 'nullable|string',
            'permissions' => 'array',
            'permissions.*' => 'exists:permissions,id',
        ]);

        $role = Role::create([
            'name' => $validated['name'],
            'guard_name' => $validated['guard_name'] ?? 'web'
        ]);

        if (!empty($validated['permissions'])) {
            $permissions = Permission::whereIn('id', $validated['permissions'])->get();
            $role->syncPermissions($permissions);
        }

        $role->load('permissions');
        $role->hashid = $role->getRouteKey();


        if ($request->wantsJson() || $request->is('api/*')) {
            return response()->json($role, 201);
        }

        return redirect()->route('admin.roles.index')
            ->with('success', 'Role created successfully.');
    }

    public function show(Request $request, Role $role)
    {
        $role->load('permissions');
        $role->hashid = $role->getRouteKey();

        if ($request->wantsJson() || $request->is('api/*')) {
            return response()->json($role);
        }

        return Inertia::render('Admin/Roles/Show', [
            'role' => $role,
        ]);
    }

    public function edit(Role $role)
    {
        $role->load('permissions');
        $role->hashid = $role->getRouteKey();

        return Inertia::render('Admin/Roles/Edit', [
            'role' => $role,
            'permissions' => Permission::all(),
        ]);
    }

    public function update(Request $request, Role $role)
    {
        $validated = $request->validate([
            'name' => 'required|string|unique:roles,name,' . $role->id,
            'guard_name' => 'nullable|string',
            'permissions' => 'array',
            'permissions.*' => 'exists:permissions,id',
        ]);

        $role->update([
            'name' => $validated['name'],
            'guard_name' => $validated['guard_name'] ?? $role->guard_name
        ]);

        if (isset($validated['permissions'])) {
            $permissions = Permission::whereIn('id', $validated['permissions'])->get();
            $role->syncPermissions($permissions);
        }

        $role->load('permissions');
        $role->hashid = $role->getRouteKey();

        if ($request->wantsJson() || $request->is('api/*')) {
            return response()->json($role);
        }

        return redirect()->route('admin.roles.index')
            ->with('success', 'Role updated successfully.');
    }
    public function destroy(Request $request,Role $role)
    {
        $role->delete();
        if ($request->wantsJson() || $request->is('api/*')) {
            return response()->json(['message' => 'Role deleted successfully']);
        }
        return redirect()->back()->with('success', 'Role deleted.');
    }

    public function assignPermissions(Request $request, Role $role): JsonResponse
    {
        $validated = $request->validate([
            'permissions' => 'required|array',
            'permissions.*' => 'exists:permissions,id'
        ]);

        $permissions = Permission::whereIn('id', $validated['permissions'])->get();
        $role->syncPermissions($permissions);

        $role->load('permissions');
        $role->hashid = $role->getRouteKey();

        return response()->json($role);
    }
}
