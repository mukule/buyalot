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
use App\Models\Role;
use App\Models\Seller\Seller;
use App\Models\Seller\SellerUser;
use Illuminate\Support\Str;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Mail;
use App\Mail\NewUserCredentialsMail;

class UserController extends Controller
{
    public function index(Request $request)
    {
        $query = User::query()->with('roles')->whereNot('user_type', 'customer')
            ->whereDoesntHave('roles', fn ($q) => $q->where('name', 'delivery'));

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
            $statusValue = filter_var($request->status, FILTER_VALIDATE_BOOLEAN);
            $query->where('status', $statusValue);
        }

        // If the authenticated user is a seller, restrict users to their seller account(s)
        $authUser = $request->user();
        if ($authUser && method_exists($authUser, 'hasRole') && $authUser->hasRole('seller')) {
            $sellerTable = (new \App\Models\Seller\Seller())->getTable();
            $sellerIds = $authUser->sellers()->pluck($sellerTable . '.id');
            $query->forSeller($sellerIds);
        }

        $users = $query->latest()->get();
        $roles = Role::allowedForSeller()->where('name', '!=', 'delivery')->pluck('name');

        $isSellerContext = $authUser && (
            (isset($authUser->user_type) && in_array($authUser->user_type, ['seller','vendor']))
            || (method_exists($authUser, 'hasRole') && ($authUser->hasRole('seller') || $authUser->hasRole('vendor')))
        );

        // If seller context, we will display the pivot role from seller_user table instead of Spatie roles
        $sellerIdsForPivot = collect();
        if ($isSellerContext) {
            $sellerTable = (new Seller())->getTable();
            $sellerIdsForPivot = $authUser->sellers()->pluck($sellerTable . '.id');
        }

        $userData = $users->map(function ($user) use ($isSellerContext, $sellerIdsForPivot) {
            $base = [
                'id' => $user->hashid,
                'name' => $user->name,
                'email' => $user->email ?? '',
                'phone' => $user->phone ?? '',
                'gender' => $user->userDetail->gender ?? '',
                'idno' => $user->userDetail->idno ?? '',
                'status' => (bool) $user->status,
                'created_at' => $user->created_at,
            ];

            if ($isSellerContext) {
                // Fetch pivot role for the first matching seller
                $pivot = SellerUser::query()
                    ->where('user_id', $user->id)
                    ->whereIn('seller_id', $sellerIdsForPivot)
                    ->first();

                $pivotRoleName = $pivot?->role;
                $base['roles'] = $pivotRoleName
                    ? collect([[ 'id' => null, 'name' => $pivotRoleName ]])
                    : collect();

                return $base;
            }

            $base['roles'] = $user->roles->map(fn($role) => [
                'id' => $role->id,
                'name' => $role->name,
            ]);

            return $base;
        });
        if ($request->wantsJson() || $request->is('api/*')) {
            return response()->json([
                'users' => $userData,
                'roles' => $roles,
                'isSellerContext' => (bool) $isSellerContext,
            ]);
        }

        return Inertia::render('Admin/Users/Index', [
            'users' => $userData,
            'roles' => $roles,
            'filters' => $request->only(['search', 'role', 'status']),
            'isSellerContext' => (bool) $isSellerContext,
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
        $user_details=UserDetail::where('user_id', $user->id)->first();
        info($user_details->gender);

        $userData = [
            'id' => $user->id,
            'name' => $user->name,
            'email' => $user->email,
            'status' => $user->status ?? true,
            'gender' => $user_details->gender ?? '',
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
            'roles' => Role::allowedForSeller()->get(),
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
            'roles' => Role::allowedForSeller()->get(),
        ]);
    }

    public function updateRoles(Request $request, User $user): RedirectResponse
    {
        $roleNames = Role::allowedForSeller()->pluck('name')->all();

        $validated = $request->validate([
            'roles' => 'required|array|size:1',
            'roles.*' => ['string', Rule::in($roleNames)],
        ], [
            'roles.required' => 'A user must have at least one role.',
            'roles.size' => 'Please select exactly one role.',
        ]);

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
            'idno' => 'nullable|string|max:20',
            'pos_pin' => 'nullable|string|size:4',
        ]);

        $user_details=UserDetail::where('user_id',$user->id)->first();
        if ($user_details){
            $user_details->update([
                "gender"=>$validated['gender'],
                "phone"=>$validated['phone'],
                "contact_name"=>$validated['name'],
                "idno" => $validated['idno'],
            ]);
        }
        $user->update([
            'name' => $validated['name'],
            'email' => $validated['email'],
            'phone' => $validated['phone'],
            'status' => (bool) $validated['status'],
            'pos_pin' => $validated['pos_pin'],
        ]);
        if ($request->wantsJson() || $request->is('api/*')) {
            $userData = [
                'id' => $user->hashid,
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
                'id' => $user->hashid,
                'name' => $user->name,
                'status' => (bool) $user->status,
            ]
        ]);
    }

    public function store(Request $request)
    {
        $roleNames = Role::allowedForSeller()->pluck('name')->all();
        $validated = $request->validate([
            'name' => 'required|string|max:255',
            'email' => 'required|email|unique:users,email',
            'phone' => 'nullable|string|min:10|max:15',
            'gender' => 'nullable|in:male,female,other',
            'status' => 'required|boolean',
            'idno' => 'nullable|string|max:20',
            'pos_pin' => 'nullable|string|size:4',
            'roles' => 'required|array|size:1',
            'roles.*' => ['string', Rule::in($roleNames)],
        ], [
            'roles.required' => 'A user must have at least one role.',
            'roles.size' => 'You must assign exactly one role.',
        ]);
        // Determine if the creator is a seller/vendor
        $authUser = $request->user();
        $isSellerContext = $authUser && (
            (isset($authUser->user_type) && in_array($authUser->user_type, ['seller','vendor']))
            || (method_exists($authUser, 'hasRole') && ($authUser->hasRole('seller') || $authUser->hasRole('vendor')))
        );

        // Generate a secure random password for the new user
        $generatedPassword = Str::random(12);

        $user = User::create([
            'name' => $validated['name'],
            'email' => $validated['email'],
            'phone' => $validated['phone'] ?? null,
            'password' => Hash::make($generatedPassword),
            'status' => (bool) $validated['status'],
            'pos_pin' => $validated['pos_pin'] ?? null,
            'email_verified_at' => now(),
            'user_type' => $isSellerContext ? 'seller' : 'user',
        ]);
        // Assign roles (exactly one)
        $user->assignRole($validated['roles'][0]);

        // If the creator is a seller/vendor, attach the new user to the same seller account(s)
        if ($isSellerContext) {
            $sellerTable = (new Seller())->getTable();
            $sellerIds = $authUser->sellers()->pluck($sellerTable . '.id');
            $pivotRole = $validated['roles'][0]; // selected role for pivot
            foreach ($sellerIds as $sid) {
                $user->sellers()->attach($sid, ['role' => $pivotRole]);
            }
        }

        if (!$user->hasAnyRole(['customer', 'seller']) && !$isSellerContext) {
            UserDetail::create([
                'user_id' => $user->id,
                'gender' => $validated['gender'] ?? null,
                'phone'  => $validated['phone'] ?? null,
                'idno'   => $validated['idno'] ?? null,
            ]);
        }

        // Email the generated password to the user with the login URL
        $emailWarning = null;
        try {
            $loginUrl = route('login');
            Mail::to($user->email)->send(new NewUserCredentialsMail($user, $generatedPassword, $loginUrl));
        } catch (\Throwable $e) {
            // Even if email fails, we keep the user creation but note the warning
            $emailWarning = 'User created but email could not be sent. Please share credentials manually.';
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
                'message' => $emailWarning ?? 'User created successfully. Login credentials have been emailed to the user.',
                'user' => $userData
            ], 201);
        }
        if ($emailWarning) {
            return redirect()->route('admin.users.index')->with('warning', $emailWarning);
        }
        return redirect()->route('admin.users.index')->with('success', 'User created successfully. Credentials emailed to the user.');
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
