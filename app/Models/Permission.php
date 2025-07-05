<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Spatie\Permission\Models\Permission as SpatiePermission;
use Vinkla\Hashids\Facades\Hashids;

class Permission extends SpatiePermission
{
    public function getRouteKey(): string
    {
        return Hashids::encode($this->id);
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
}
