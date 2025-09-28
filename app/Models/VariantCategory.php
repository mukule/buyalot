<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Vinkla\Hashids\Facades\Hashids;
use Illuminate\Database\Eloquent\Relations\HasMany;

class VariantCategory extends Model
{
    protected $fillable = [
        'name',
    ];

    protected $appends = ['hashid'];

    
    public function getHashidAttribute(): string
    {
        return Hashids::encode($this->id);
    }

     public function getRouteKey(): string
    {
        return Hashids::encode($this->id);
    }

    public function resolveRouteBinding($value, $field = null): ?Model
    {
        $decoded = Hashids::decode($value);
        if (count($decoded) !== 1) {
            abort(404);
        }
        return $this->where('id', $decoded[0])->firstOrFail();
    }

    public function variants(): HasMany
    {
        return $this->hasMany(Variant::class);
    }
}
