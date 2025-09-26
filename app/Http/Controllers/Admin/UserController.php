<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\User;
use App\Models\UserDetail;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Validation\Rule;
use Inertia\Inertia;
use Spatie\Permission\Models\Role;

class UserController extends Controller
{
    public function index(Request $request)
    {
        $query = User::query()->with('roles');

        if ($request->filled('search')) {
            $query->where(function ($searchQuery) use ($request) {
                $searchQuery->where('name', 'like', '%' . $request->search . '%')
                    ->orWhere('email', 'like', '%' . $request->search . '%')
                    ->orWhere('phone', 'like', '%' . $request->search . '%');
            });
        }

        if ($request->filled('role')) {
            $query->whereHas('roles', function ($roleQuery) use ($request) {
                $roleQuery->where('name', $request->role);
            });
        } else {
            $query->whereHas('roles', function ($roleQuery) {
                $roleQuery->where('name', '!=', 'user');
            });
        }

        if ($request->filled('status')) {
            $statusValue = $request->status === true;
            $query->where('status', $statusValue);
        }

        $users = $query->latest()->get();
        $roles = Role::pluck('name');
        $userData = $users->map(fn($user) => [
            'id' => $user->id,
            'name' => $user->name,
            'email' => $user->email ?? '',
            'phone' => $user->phone ?? '',
            'gender' => $user->gender ?? '',
            'status' => (bool) $user->status,
            'created_at' => $user->created_at,
            'roles' => $user->roles->map(fn($role) => [
                'id' => $role->id,
                'name' => $role->name,
            ]),
        ]);
        if ($request->wantsJson() || $request->is('api/*')) {
            return response()->json([
                'users' => $userData,
                'roles' => $roles,
            ]);
        }

        return Inertia::render('Admin/Users/Index', [
            'users' => $userData,
            'roles' => $roles,
            'filters' => $request->only(['search', 'role', 'status'])
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
                'phone' => $user->phone,
                'gender' => $user->gender,
                'status' => $user->status ?? true,
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
            'status' => $user->status ?? true,
            'gender' => $user->gender,
            'phone' => $user->phone,
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
            'gender' => $user->gender,
            'phone' => $user->phone,
            'status' => $user->status ?? true,
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

    public function updateRoles(Request $request, User $user): RedirectResponse
    {
        $validated = $request->validate([
            'roles' => 'required|array|min:1',
            'roles.*' => 'string|exists:roles,name',
        ], [
            'roles.required' => 'A user must have at least one role.',
            'roles.min' => 'A user must have at least one role.',
        ]);
        if (empty($validated['roles'])) {
            return redirect()->back()->withErrors(['roles' => 'A user must have at least one role.']);
        }

        $user->syncRoles($validated['roles']);

        return redirect()->back()->with('success', 'User roles updated successfully.');
    }


    public function update(Request $request, User $user)
    {
        $validated = $request->validate([
            'name' => 'required|string|max:255',
            'email' => ['required', 'email', Rule::unique('users')->ignore($user->id)],
            'status' => 'required|boolean',
            'phone' => 'nullable|min:10|max:15',
            'gender'=>'nullable|in:male,female,other',
        ]);

        $user_details=UserDetail::find($user->id);
        if ($user_details){
            $user_details->update([
                "gender"=>$validated['gender'],
            ]);
        }
        $user->update([
            'name' => $validated['name'],
            'email' => $validated['email'],
            'phone' => $validated['phone'],
            'status' => (bool) $validated['status'],
        ]);
        if ($request->wantsJson() || $request->is('api/*')) {
            $userData = [
                'id' => $user->id,
                'name' => $user->name,
                'email' => $user->email,
                'phone' => $user->phone,
                'gender' => $user->gender,
                'status' => (bool) $user->status,
            ];
            return response()->json($userData);
        }
        return redirect()->route('admin.users.index')->with('success', 'User updated successfully.');
    }

    public function updateStatus(Request $request, User $user): JsonResponse
    {
        $validated = $request->validate([
            'status' => 'required|boolean',
        ]);
        $user->update( ['status' => (bool) $validated['status']]);

        return response()->json([
            'message' => 'User status updated successfully',
            'user' => [
                'id' => $user->id,
                'name' => $user->name,
                'status' => (bool) $user->status,
            ]
        ]);
    }

    public function store(Request $request)
    {
        $validated = $request->validate([
            'name' => 'required|string|max:255',
            'email' => 'required|email|unique:users,email',
            'phone' => 'nullable|string|min:10|max:15',
            'gender' => 'nullable|in:male,female,other',
            'password' => 'required|string|min:8|confirmed',
            'status' => 'required|boolean',
            'roles' => 'required|array|min:1',
            'roles.*' => 'string|exists:roles,name',
        ], [
            'roles.required' => 'A user must have at least one role.',
            'roles.min' => 'A user must have at least one role.',
            'password.confirmed' => 'The password confirmation does not match.',
        ]);
        $user = User::create([
            'name' => $validated['name'],
            'email' => $validated['email'],
            'phone' => $validated['phone'] ?? null,
            'password' => bcrypt($validated['password']),
            'status' => (bool) $validated['status'],
            'email_verified_at' => now(),
        ]);
        $user->assignRole($validated['roles']);

        if (!$user->hasAnyRole(['customer', 'seller'])) {
            UserDetail::create([
                'user_id' => $user->id,
                'gender' => $validated['gender'] ?? null,
                'phone'  => $validated['phone'] ?? null,
            ]);
        }

        if ($request->wantsJson() || $request->is('api/*')) {
            $user->load('roles', 'permissions');

            $userData = [
                'id' => $user->id,
                'name' => $user->name,
                'email' => $user->email,
                'created_at' => $user->created_at
            ];

            return response()->json([
                'message' => 'User created successfully',
                'user' => $userData
            ], 201);
        }
        return redirect()->route('admin.users.index')->with('success', 'User created successfully.');
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
