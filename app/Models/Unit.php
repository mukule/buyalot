<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Str;
use Vinkla\Hashids\Facades\Hashids;

class Unit extends Model
{
    protected $fillable = [
        'name',
        'symbol',
        'unit_type_id',
        'active',
    ];

    protected $casts = [
        'active' => 'boolean',
    ];

    protected $appends = ['hashid'];

    protected static function boot()
    {
        parent::boot();

        static::creating(function ($unit) {
            $unit->name = trim($unit->name);
            $unit->symbol = strtoupper(trim($unit->symbol));
        });
    }

    public function unitType()
    {
        return $this->belongsTo(UnitType::class);
    }

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
        $decoded = Hashids::decode($value);

        if (count($decoded) !== 1) {
            abort(404);
        }

        return $this->where('id', $decoded[0])->firstOrFail();
    }
}
