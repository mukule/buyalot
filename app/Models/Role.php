<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Model;
use Spatie\Permission\Models\Role as SpatieRole;
use Vinkla\Hashids\Facades\Hashids;

class Role extends SpatieRole
{
    public const SELLER_ALLOWED = [
        'store_keeper',
        'cashier',
        'marketer',
        'seller_account_admins',
        'manager',
        'staff',
        'delivery',
        'store_keeper',
        'store_manager',
        'store_account-admins',
        'warehouse_manager',
        'assistant_store_manager',
        'warehouse_clerk',
        'loader',
        'record_keeper',
        'clerk'
    ];

    public function getRouteKey(): string
    {
        return Hashids::encode((array)$this->id);
    }

    /**
     * @param  string  $value
     * @param  string|null  $field
     * @return Model
     */
    public function resolveRouteBinding($value, $field = null): Model
    {
        $decoded = Hashids::decode($value);
        if (count($decoded) !== 1) {
            abort(404);
        }

        return $this->where('id', $decoded[0])->firstOrFail();
    }

    /**
     * Scope: allowed roles for seller user management.
     * Should only restrict to SELLER_ALLOWED when the authenticated user is a seller.
     * Otherwise, return all roles for the specified guard (defaults to web).
     *
     * @param Builder $query
     * @param string|null $guard
     * @return Builder
     */
    public function scopeAllowedForSeller(Builder $query, ?string $guard = null): Builder
    {
        $guard = $guard ?? config('auth.defaults.guard', 'web');

        // Always constrain by guard and use deterministic ordering
        $query = $query->where('guard_name', $guard)->orderBy('name');

        $user = auth()->user();
        $isSellerOrVendor = $user && (
            (isset($user->user_type) && in_array($user->user_type, ['seller', 'vendor']))
            || (method_exists($user, 'hasRole') && ($user->hasRole('seller') || $user->hasRole('vendor')))
        );

        if ($isSellerOrVendor) {
            // Sellers can only pick from the allowed set
            return $query->whereIn('name', self::SELLER_ALLOWED);
        }

        // Non-sellers get the full role list for the guard
        return $query;
    }
}
