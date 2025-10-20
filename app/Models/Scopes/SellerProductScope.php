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

        // Apply only if user is logged in and is a seller
        if ($user && $user->hasRole('seller')) {
            $sellerId = DB::table('seller_user')
                ->where('user_id', $user->id)
                ->value('seller_id');

            if ($sellerId) {
                $builder->where('owner_id', $sellerId);
            }
        }
    }
}
