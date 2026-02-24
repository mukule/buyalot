<?php

namespace App\Models\Scopes;

use App\Services\SellerContext;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Scope;
use Illuminate\Support\Facades\Auth;

class SellerProductScope implements Scope
{
    public function apply(Builder $builder, Model $model): void
    {
        $user = Auth::user();

        if (! $user || ! SellerContext::isSeller($user)) {
            return;
        }

        $relatedUserIds = SellerContext::relatedUserIds($user);

        if ($relatedUserIds->isEmpty()) {
            $builder->whereRaw('1 = 0');
            return;
        }

        $builder
            ->whereIn('owner_type', ['seller', 'vendor'])
            ->whereIn('owner_id', $relatedUserIds);
    }
}
