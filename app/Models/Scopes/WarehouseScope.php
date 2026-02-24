<?php

namespace App\Models\Scopes;

use App\Services\SellerContext;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Scope;
use Illuminate\Support\Facades\Auth;

class WarehouseScope implements Scope
{
    public function apply(Builder $builder, Model $model): void
    {
        $user = Auth::user();

        if (! $user) {
            return;
        }

        if (SellerContext::isAdmin($user)) {
            return;
        }

        if (SellerContext::isSeller($user)) {
            $relatedUserIds = SellerContext::relatedUserIds($user);
            $builder->whereIn('created_by', $relatedUserIds);
        }
    }
}
