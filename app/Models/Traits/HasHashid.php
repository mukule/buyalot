<?php

namespace App\Models\Traits;

use Illuminate\Database\Eloquent\Model;
use Vinkla\Hashids\Facades\Hashids;

trait HasHashid
{
    public function getHashidAttribute(): string
    {
        return Hashids::encode($this->id);
    }

    public function getRouteKey(): string
    {
        return $this->hashid;
    }

   public function resolveRouteBinding($value, $field = null): ?Model
{

    // $field parameter required by parent method signature but unused here
    
    $decoded = Hashids::decode($value);

    if (count($decoded) !== 1) {
        abort(404);
    }

    return $this->where('id', $decoded[0])->firstOrFail();
}

}
