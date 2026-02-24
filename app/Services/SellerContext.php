<?php

namespace App\Services;

use App\Models\User;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\DB;

/**
 * Per-request seller context resolver.
 *
 * Provides a cached, reusable way to determine which seller(s) a user
 * belongs to and enforce tenant-level authorization.
 */
class SellerContext
{
    protected static array $cache = [];

    /**
     * Get the seller IDs (from seller_applications) for the given user.
     * Result is cached per user ID for the duration of the request.
     */
    public static function sellerIds(User $user): Collection
    {
        if (isset(static::$cache[$user->id])) {
            return static::$cache[$user->id];
        }

        $ids = DB::table('seller_user')
            ->where('user_id', $user->id)
            ->pluck('seller_id');

        return static::$cache[$user->id] = $ids;
    }

    /**
     * Get all user IDs that share the same seller(s) — i.e. all co-workers.
     */
    public static function relatedUserIds(User $user): Collection
    {
        $sellerIds = static::sellerIds($user);

        if ($sellerIds->isEmpty()) {
            return collect([$user->id]);
        }

        return DB::table('seller_user')
            ->whereIn('seller_id', $sellerIds)
            ->pluck('user_id')
            ->merge([$user->id])
            ->unique()
            ->values();
    }

    public static function isAdmin(User $user): bool
    {
        return $user->hasRole(['admin', 'super-admin']);
    }

    public static function isSeller(User $user): bool
    {
        return in_array($user->user_type, ['seller', 'vendor'], true)
            || $user->hasRole(['seller', 'vendor']);
    }

    /**
     * Check if the user belongs to the given seller_id.
     */
    public static function belongsToSeller(User $user, ?int $sellerId): bool
    {
        if ($sellerId === null) {
            return false;
        }

        return static::sellerIds($user)->contains($sellerId);
    }

    /**
     * Check if the user can access a POS session (owns it or is admin).
     */
    public static function canAccessSession(User $user, \App\Models\POS\PosSession $session): bool
    {
        if (static::isAdmin($user)) {
            return true;
        }

        if ((int) $session->user_id === (int) $user->id) {
            return true;
        }

        $register = $session->register;
        if (! $register) {
            return false;
        }

        return static::belongsToSeller($user, $register->seller_id);
    }

    /**
     * Flush the per-request cache (useful in tests or queue workers).
     */
    public static function flush(): void
    {
        static::$cache = [];
    }
}
