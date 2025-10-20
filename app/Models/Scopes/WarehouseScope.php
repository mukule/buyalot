<?php

namespace App\Models\Scopes;

use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Scope;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;

class WarehouseScope implements Scope
{
    /**
     * Apply the scope to a given Eloquent query builder.
     */

    public function apply(Builder $builder, Model $model): void
    {
        $user = Auth::user();
        if ($user && $user->user_type="seller"){
                $builder->where('created_by', $user->id);
        }
    }
}
