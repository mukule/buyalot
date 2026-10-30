<?php

namespace App\Support;

use App\Models\Role;
use App\Models\User;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Support\Collection;
use Spatie\Permission\Models\Role as SpatieRole;

/**
 * The super-admin and admin roles (and their permissions, and the users holding
 * them) are visible to and manageable by super-admins only. Everyone else —
 * including admins — never sees them in role lists, can't open/edit/delete
 * them, can't assign/revoke them, and can't manage users who hold them.
 *
 * Non-super-admins also can't grant permissions they don't hold themselves, so
 * a custom role can't be used to escalate privileges.
 */
class ProtectedRoles
{
    public const NAMES = ['super-admin', 'admin'];

    public static function viewerIsSuperAdmin(?User $viewer = null): bool
    {
        $viewer ??= auth()->user();

        return (bool) $viewer?->hasRole('super-admin');
    }

    public static function isProtected(SpatieRole|string $role): bool
    {
        return in_array(is_string($role) ? $role : $role->name, self::NAMES, true);
    }

    /**
     * Limit a roles query to what the viewer may see.
     */
    public static function scopeVisible(Builder $query, ?User $viewer = null): Builder
    {
        return self::viewerIsSuperAdmin($viewer) ? $query : $query->whereNotIn('name', self::NAMES);
    }

    /**
     * Limit a users query to users the viewer may see/manage.
     */
    public static function scopeVisibleUsers(Builder $query, ?User $viewer = null): Builder
    {
        return self::viewerIsSuperAdmin($viewer)
            ? $query
            : $query->whereDoesntHave('roles', fn ($q) => $q->whereIn('name', self::NAMES));
    }

    /**
     * 404 (not 403, so the role's existence isn't confirmed) for hidden roles.
     */
    public static function authorizeRole(SpatieRole $role, ?User $viewer = null): void
    {
        abort_if(self::isProtected($role) && ! self::viewerIsSuperAdmin($viewer), 404);
    }

    /**
     * 404 when a non-super-admin targets a user who holds a protected role.
     */
    public static function authorizeUser(User $target, ?User $viewer = null): void
    {
        abort_if(! self::viewerIsSuperAdmin($viewer) && $target->hasRole(self::NAMES), 404);
    }

    /**
     * Reject attempts to assign/revoke protected roles by non-super-admins.
     *
     * @param  iterable<SpatieRole|string>  $roles
     */
    public static function authorizeAssignment(iterable $roles, ?User $viewer = null): void
    {
        if (self::viewerIsSuperAdmin($viewer)) {
            return;
        }
        foreach ($roles as $role) {
            abort_if(self::isProtected($role), 403, 'Only a super admin can assign or remove this role.');
        }
    }

    /**
     * Permission ids the viewer may grant to a role (all for super-admins,
     * otherwise only permissions the viewer holds).
     */
    public static function grantablePermissionIds(?User $viewer = null): ?Collection
    {
        $viewer ??= auth()->user();
        if (self::viewerIsSuperAdmin($viewer)) {
            return null; // unrestricted
        }

        return $viewer ? $viewer->getAllPermissions()->pluck('id') : collect();
    }

    /**
     * Work out a role's new permission ids from a request without letting a
     * non-super-admin add — or strip — permissions outside what they hold.
     *
     * @param  array<int>  $requested
     * @param  array<int>  $current
     * @return array<int>
     */
    public static function resolvePermissionIds(array $requested, array $current = [], ?User $viewer = null): array
    {
        $grantable = self::grantablePermissionIds($viewer);
        if ($grantable === null) {
            return array_values(array_unique(array_map('intval', $requested)));
        }

        $grantable = $grantable->map(fn ($id) => (int) $id);
        $requested = collect($requested)->map(fn ($id) => (int) $id);

        $denied = $requested->diff($grantable);
        abort_if($denied->isNotEmpty(), 403, 'You can only grant permissions you have yourself.');

        // Keep permissions the viewer can't see/grant exactly as they were.
        $untouchable = collect($current)->map(fn ($id) => (int) $id)->diff($grantable);

        return $requested->merge($untouchable)->unique()->values()->all();
    }
}
