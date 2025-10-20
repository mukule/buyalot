<?php

namespace App\Models\Scopes;

use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Scope;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;

class SellerProductScope implements Scope
{
    /**
     * Apply the scope to a given Eloquent query builder.
     */
    public function apply(Builder $builder, Model $model): void
    {
        $user = Auth::user();

        if (!$user) {
            return; // no filtering for guests
        }

        // Only apply to sellers or vendors
        if (in_array($user->user_type, ['seller', 'vendor'])) {
            // Get all seller IDs linked to this user
            $sellerIds = DB::table('seller_user')
                ->where('user_id', $user->id)
                ->pluck('seller_id');

            if ($sellerIds->isNotEmpty()) {
                // Get all user IDs who share those seller IDs
                $relatedUserIds = DB::table('seller_user')
                    ->whereIn('seller_id', $sellerIds)
                    ->pluck('user_id');

                // Filter products where:
                // - owner_type is seller or vendor
                // - owner_id is any of the related user IDs
                $builder->where(function ($query) use ($relatedUserIds) {
                    $query->whereIn('owner_type', ['seller', 'vendor'])
                        ->whereIn('owner_id', $relatedUserIds);
                });
            }
        }
    }

}
