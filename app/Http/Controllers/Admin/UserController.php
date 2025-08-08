<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\User;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Validation\Rule;
use Inertia\Inertia;
use Spatie\Permission\Models\Role;

class UserController extends Controller
{
    public function index(Request $request)
    {
        $query = User::with('roles');

        // Search functionality
        if ($request->has('search') && $request->search) {
            $query->where(function ($q) use ($request) {
                $q->where('name', 'like', '%' . $request->search . '%')
                    ->orWhere('email', 'like', '%' . $request->search . '%');
            });
        }

        // Role filter
        if ($request->has('role') && $request->role) {
            $query->whereHas('roles', function ($q) use ($request) {
                $q->where('name', $request->role);
            });
        }

        // Status filter
        if ($request->has('status') && $request->status) {
            $query->where('status', $request->status);
        }

        // Exclude users with only 'user' role for admin interface
        $users = $query->whereHas('roles', function ($q) {
            $q->where('name', '<>', 'user');
        })
            ->orWhereDoesntHave('roles')
            ->get()
            ->filter(function ($user) {
                return !($user->roles->count() === 1 && $user->roles->first()->name === 'user');
            })
            ->values();

        $roles = Role::pluck('name');

        $userData = $users->map(fn($user) => [
            'id' => $user->id,
            'name' => $user->name,
            'email' => $user->email ?? '',
            'status' => $user->status ?? 'active',
            'created_at' => $user->created_at,
            'roles' => $user->roles->map(function($role) {
                return [
                    'id' => $role->id,
                    'name' => $role->name,
                ];
            }),
        ]);

        // Return JSON for API requests
        if ($request->wantsJson() || $request->is('api/*')) {
            return response()->json([
                'users' => $userData,
                'roles' => $roles,
            ]);
        }

        // Return Inertia view for web requests
        return Inertia::render('Admin/Users/Index', [
            'users' => $userData,
            'roles' => $roles,
            'filters' => [
                'search' => $request->search,
                'role' => $request->role,
                'status' => $request->status,
            ]
        ]);
    }

    //API to get all users
    public function allUsers(Request $request): JsonResponse
    {
        $users = User::with('roles', 'permissions')->get()->map(function($user) {
            return [
                'id' => $user->id,
                'name' => $user->name,
                'email' => $user->email,
                'status' => $user->status ?? 'active',
                'created_at' => $user->created_at,
                'roles' => $user->roles->map(function($role) {
                    return [
                        'id' => $role->id,
                        'name' => $role->name,
                    ];
                }),
                'permissions' => $user->permissions->map(function($permission) {
                    return [
                        'id' => $permission->id,
                        'name' => $permission->name,
                    ];
                }),
            ];
        });

        return response()->json($users);
    }

    public function show(Request $request, User $user)
    {
        $user->load('roles', 'permissions');

        $userData = [
            'id' => $user->id,
            'name' => $user->name,
            'email' => $user->email,
            'status' => $user->status ?? 'active',
            'created_at' => $user->created_at,
            'updated_at' => $user->updated_at,
            'email_verified_at' => $user->email_verified_at,
            'roles' => $user->roles->map(function($role) {
                return [
                    'id' => $role->id,
                    'name' => $role->name,
                ];
            }),
            'permissions' => $user->permissions->map(function($permission) {
                return [
                    'id' => $permission->id,
                    'name' => $permission->name,
                ];
            }),
        ];

        // Return JSON for API requests
        if ($request->wantsJson() || $request->is('api/*')) {
            return response()->json($userData);
        }

        // Return Inertia view for web requests
        return Inertia::render('Admin/Users/Show', [
            'user' => $userData,
            'roles' => Role::all(),
        ]);
    }

    public function edit(Request $request, User $user)
    {
        $user->load('roles');

        $userData = [
            'id' => $user->id,
            'name' => $user->name,
            'email' => $user->email,
            'status' => $user->status ?? 'active',
            'roles' => $user->roles->map(function($role) {
                return [
                    'id' => $role->id,
                    'name' => $role->name,
                ];
            }),
        ];

        return Inertia::render('Admin/Users/Edit', [
            'user' => $userData,
            'roles' => Role::all(),
        ]);
    }

    public function update(Request $request, User $user)
    {
        $validated = $request->validate([
            'name' => 'required|string|max:255',
            'email' => ['required', 'email', Rule::unique('users')->ignore($user->id)],
            'status' => 'required|in:active,inactive,suspended',
            'roles' => 'array',
            'roles.*' => 'string|exists:roles,name',
            'password' => 'nullable|min:8|confirmed',
        ]);

        // Update user basic info
        $user->update([
            'name' => $validated['name'],
            'email' => $validated['email'],
            'status' => $validated['status'],
            'password' => $validated['password'] ? bcrypt($validated['password']) : $user->password,
        ]);

        // Update roles if provided
        if (isset($validated['roles'])) {
            $rolesToKeep = $user->hasRole('user') ? ['user'] : [];
            $user->syncRoles(array_merge($rolesToKeep, $validated['roles']));
        }

        // Return JSON for API requests
        if ($request->wantsJson() || $request->is('api/*')) {
            $user->load('roles', 'permissions');
            $userData = [
                'id' => $user->id,
                'name' => $user->name,
                'email' => $user->email,
                'status' => $user->status,
                'roles' => $user->roles->map(function($role) {
                    return [
                        'id' => $role->id,
                        'name' => $role->name,
                    ];
                }),
                'permissions' => $user->permissions->map(function($permission) {
                    return [
                        'id' => $permission->id,
                        'name' => $permission->name,
                    ];
                }),
            ];
            return response()->json($userData);
        }

        // Return redirect for web requests
        return redirect()->route('admin.users.index')->with('success', 'User updated successfully.');
    }

    public function updateStatus(Request $request, User $user): JsonResponse
    {
        $validated = $request->validate([
            'status' => 'required|in:active,inactive,suspended'
        ]);

        $user->update(['status' => $validated['status']]);

        return response()->json([
            'message' => 'User status updated successfully',
            'user' => [
                'id' => $user->id,
                'name' => $user->name,
                'status' => $user->status,
            ]
        ]);
    }

    public function destroy(Request $request, User $user)
    {
        // Prevent deletion of super admin users
        if ($user->hasRole('super-admin')) {
            if ($request->wantsJson() || $request->is('api/*')) {
                return response()->json(['message' => 'Cannot delete super admin user'], 422);
            }
            return redirect()->back()->with('error', 'Cannot delete super admin user.');
        }

        $user->delete();

        if ($request->wantsJson() || $request->is('api/*')) {
            return response()->json(['message' => 'User deleted successfully']);
        }

        return redirect()->route('admin.users.index')->with('success', 'User deleted successfully.');
    }

    // API role assignment by ID
    public function assignRoles(Request $request, User $user): JsonResponse
    {
        $validated = $request->validate([
            'roles' => 'required|array',
            'roles.*' => 'exists:roles,id'
        ]);

        $roles = Role::whereIn('id', $validated['roles'])->get();
        $rolesToKeep = $user->hasRole('user') ? Role::where('name', 'user')->get() : collect();

        $allRoles = $rolesToKeep->merge($roles);
        $user->syncRoles($allRoles);

        $user->load('roles', 'permissions');
        $userData = [
            'id' => $user->id,
            'name' => $user->name,
            'email' => $user->email,
            'status' => $user->status,
            'roles' => $user->roles->map(function($role) {
                return [
                    'id' => $role->id,
                    'name' => $role->name,
                ];
            }),
            'permissions' => $user->permissions->map(function($permission) {
                return [
                    'id' => $permission->id,
                    'name' => $permission->name,
                ];
            }),
        ];

        return response()->json($userData);
    }

    public function removeRole(Request $request, User $user): JsonResponse
    {
        $validated = $request->validate([
            'role_id' => 'required|exists:roles,id'
        ]);

        $role = Role::findById($validated['role_id']);

        // Don't remove 'user' role if it exists
        if ($role->name !== 'user') {
            $user->removeRole($role);
        }

        $user->load('roles', 'permissions');
        $userData = [
            'id' => $user->id,
            'name' => $user->name,
            'email' => $user->email,
            'status' => $user->status,
            'roles' => $user->roles->map(function($role) {
                return [
                    'id' => $role->id,
                    'name' => $role->name,
                ];
            }),
            'permissions' => $user->permissions->map(function($permission) {
                return [
                    'id' => $permission->id,
                    'name' => $permission->name,
                ];
            }),
        ];

        return response()->json($userData);
    }
}
