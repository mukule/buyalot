<?php

namespace App\Http\Controllers\Seller;

use App\Http\Controllers\Controller;
use App\Models\Role;
use App\Models\Seller\SellerUser;
use App\Models\User;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Validation\Rule;
use Illuminate\Support\Facades\DB;

class UserManagementController extends Controller
{
    /**
     * List users associated with the authenticated seller account.
     */
    public function index(Request $request)
    {
        $authUser = Auth::user();
        $sellerIds = $authUser->sellers()->pluck('seller_id');

        if ($sellerIds->isEmpty()) {
            return response()->json(['users' => collect(), 'roles' => Role::allowedForSeller()->pluck('name')]);
        }

        $users = User::query()
            ->with(['roles:id,name'])
            ->whereHas('sellers', function ($q) use ($sellerIds) {
                $q->whereIn('seller_id', $sellerIds);
            })
            ->when($request->filled('search'), function ($q) use ($request) {
                $term = $request->get('search');
                $q->where(function ($sub) use ($term) {
                    $sub->where('name', 'like', "%$term%")->orWhere('email', 'like', "%$term%")->orWhere('phone', 'like', "%$term%");
                });
            })
            ->get()
            ->map(function (User $user) use ($sellerIds) {
                // Fetch pivot roles for this seller scope (first seller match)
                $pivot = SellerUser::query()
                    ->where('user_id', $user->id)
                    ->whereIn('seller_id', $sellerIds)
                    ->first();

                return [
                    'id' => $user->id,
                    'name' => $user->name,
                    'email' => $user->email,
                    'phone' => $user->phone,
                    'status' => (bool)($user->status ?? true),
                    'roles' => $user->roles->map(fn($r) => ['id' => $r->id, 'name' => $r->name]),
                    'seller_role' => $pivot?->role,
                ];
            });

        return response()->json([
            'users' => $users,
            'roles' => Role::allowedForSeller()->pluck('name'),
        ]);
    }

    /**
     * Create a new user and attach to current seller.
     */
    public function store(Request $request)
    {
        $authUser = Auth::user();
        $sellerId = $authUser->sellers()->value('seller_id');
        abort_if(!$sellerId, 403, 'Not associated with a seller account.');
        // Only seller account admins or managers can create
        abort_unless($authUser->hasAnyRole(['seller-account-admins','manager','admin']), 403);

        $validated = $request->validate([
            'name' => ['required', 'string', 'max:255'],
            'email' => ['required', 'email', Rule::unique('users', 'email')],
            'phone' => ['nullable', 'string', 'min:10', 'max:20'],
            'status' => ['nullable', 'boolean'],
            'role' => ['required', 'string', Rule::in(Role::allowedForSeller()->pluck('name')->all())],
            'password' => ['nullable', 'string', 'min:6'],
        ]);

        return DB::transaction(function () use ($validated, $sellerId) {
            $user = User::create([
                'name' => $validated['name'],
                'email' => $validated['email'],
                'phone' => $validated['phone'] ?? null,
                'status' => $validated['status'] ?? true,
                'password' => $validated['password'] ?? str()->random(12),
            ]);

            // Assign spatie role (global)
            $user->syncRoles([$validated['role']]);

            // Attach to seller with pivot role
            $user->sellers()->attach($sellerId, ['role' => $validated['role']]);

            return response()->json([
                'message' => 'User created and associated successfully',
                'user' => [
                    'id' => $user->id,
                    'name' => $user->name,
                    'email' => $user->email,
                    'phone' => $user->phone,
                    'status' => (bool)$user->status,
                    'roles' => $user->roles->pluck('name'),
                    'seller_role' => $validated['role'],
                ],
            ], 201);
        });
    }

    /**
     * Update an existing user in the seller account.
     */
    public function update(Request $request, User $user)
    {
        $authUser = Auth::user();
        $sellerId = $authUser->sellers()->value('seller_id');
        abort_if(!$sellerId, 403, 'Not associated with a seller account.');
        // Only seller account admins or managers can update
        abort_unless($authUser->hasAnyRole(['seller-account-admins','manager','admin']), 403);

        // Ensure the user belongs to this seller
        $belongs = SellerUser::where('seller_id', $sellerId)->where('user_id', $user->id)->exists();
        abort_unless($belongs, 404);

        $validated = $request->validate([
            'name' => ['sometimes', 'required', 'string', 'max:255'],
            'email' => ['sometimes', 'required', 'email', Rule::unique('users', 'email')->ignore($user->id)],
            'phone' => ['nullable', 'string', 'min:10', 'max:20'],
            'status' => ['nullable', 'boolean'],
            'role' => ['sometimes', 'required', 'string', Rule::in(Role::allowedForSeller()->pluck('name')->all())],
            'password' => ['nullable', 'string', 'min:6'],
        ]);

        $user->fill(collect($validated)->only(['name', 'email', 'phone'])->toArray());
        if (array_key_exists('status', $validated)) {
            $user->status = (bool)$validated['status'];
        }
        if (!empty($validated['password'])) {
            $user->password = $validated['password'];
        }
        $user->save();

        if (array_key_exists('role', $validated)) {
            // Update spatie roles to match chosen role (single-role model here)
            $user->syncRoles([$validated['role']]);
            // Update pivot role
            SellerUser::where('seller_id', $sellerId)->where('user_id', $user->id)->update(['role' => $validated['role']]);
        }

        return response()->json(['message' => 'User updated successfully']);
    }

    /**
     * Remove the user from the seller account (detach); do not delete globally.
     */
    public function destroy(User $user)
    {
        $authUser = Auth::user();
        $sellerId = $authUser->sellers()->value('seller_id');
        abort_if(!$sellerId, 403, 'Not associated with a seller account.');
        // Only seller account admins or managers can remove
        abort_unless($authUser->hasAnyRole(['seller-account-admins','manager','admin']), 403);

        $affected = SellerUser::where('seller_id', $sellerId)->where('user_id', $user->id)->delete();
        abort_unless($affected, 404);

        return response()->json(['message' => 'User removed from seller account']);
    }
}
