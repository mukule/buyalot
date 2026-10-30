<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Inertia\Inertia;
use App\Models\Role;
use App\Models\Permission;
use App\Support\ProtectedRoles;

class RoleController extends Controller
{
    public function index(Request $request)
    {
        $roles = Role::visibleToViewer()->with('permissions')->get()->map(function ($role) {
            $role->hashid = $role->getRouteKey();
            return $role;
        });

        if ($request->wantsJson() || $request->is('api/*')) {
            return response()->json($roles);
        }

        return Inertia::render('Admin/Roles/Index', [
            'roles' => $roles,
            'permissions' => $this->grantablePermissions()->get(),
        ]);
    }

    public function create()
    {
        logger("create role");
        return Inertia::render('Admin/Roles/Create', [
            'permissions' => $this->grantablePermissions()->get(),
        ]);
    }

    public function store(Request $request)
    {
        $validated = $request->validate([
            'name' => ['required', 'string', 'unique:roles,name', $this->protectedNameRule()],
            'guard_name' => 'nullable|string',
            'permissions' => 'array',
            'permissions.*' => 'exists:permissions,id',
        ]);
        $validated['permissions'] = ProtectedRoles::resolvePermissionIds($validated['permissions'] ?? []);

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
        ProtectedRoles::authorizeRole($role);

        $role->load(['permissions' => function ($query) {
            $query->orderBy('module')->orderBy('name');
        }]);

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
        ProtectedRoles::authorizeRole($role);

        $role->load('permissions');
        $role->hashid = $role->getRouteKey();

        return Inertia::render('Admin/Roles/Edit', [
            'role' => $role,
            'permissions' => $this->grantablePermissions()
                ->orderBy('module')
                ->orderBy('name')
                ->get(),
        ]);
    }

    public function update(Request $request, Role $role)
    {
        ProtectedRoles::authorizeRole($role);

        $validated = $request->validate([
            'name' => ['sometimes', 'string', 'unique:roles,name,' . $role->id, $this->protectedNameRule($role)],
            'guard_name' => 'nullable|string',
            'permissions' => 'array',
            'permissions.*' => 'exists:permissions,id',
        ]);
        if (isset($validated['name'])) {
            $role->update([
                'name' => $validated['name'],
                'guard_name' => $validated['guard_name'] ?? $role->guard_name,
            ]);
        }

        if (isset($validated['permissions'])) {
            $ids = ProtectedRoles::resolvePermissionIds($validated['permissions'], $role->permissions()->pluck('id')->all());
            $role->syncPermissions(Permission::whereIn('id', $ids)->get());
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
        ProtectedRoles::authorizeRole($role);
        abort_if(ProtectedRoles::isProtected($role), 422, 'The super-admin and admin roles cannot be deleted.');

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

        ProtectedRoles::authorizeRole($role);
        $ids = ProtectedRoles::resolvePermissionIds($validated['permissions'], $role->permissions()->pluck('id')->all());
        $role->syncPermissions(Permission::whereIn('id', $ids)->get());

        $role->load('permissions');
        $role->hashid = $role->getRouteKey();

        return response()->json($role);
    }

    /**
     * Permissions the current user may grant: all for super-admins, otherwise
     * only the ones they hold themselves.
     */
    protected function grantablePermissions()
    {
        $ids = ProtectedRoles::grantablePermissionIds();

        return Permission::query()->when($ids !== null, fn ($q) => $q->whereIn('id', $ids));
    }

    /**
     * Non-super-admins can't create or rename a role to super-admin/admin.
     */
    protected function protectedNameRule(?Role $current = null): \Closure
    {
        return function ($attribute, $value, $fail) use ($current) {
            if (ProtectedRoles::isProtected((string) $value)
                && $current?->name !== $value
                && ! ProtectedRoles::viewerIsSuperAdmin()) {
                $fail('This role name is reserved.');
            }
        };
    }
}
