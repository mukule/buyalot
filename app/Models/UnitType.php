<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Str;
use Vinkla\Hashids\Facades\Hashids;

class UnitType extends Model
{
    protected $fillable = ['name', 'slug'];

    protected $appends = ['hashid'];

    protected static function boot()
    {
        parent::boot();

        static::creating(function ($unitType) {
            if (empty($unitType->slug)) {
                $unitType->slug = static::generateUniqueSlug($unitType->name);
            }
        });

        static::updating(function ($unitType) {
            if ($unitType->isDirty('name')) {
                $unitType->slug = static::generateUniqueSlug($unitType->name, $unitType->id);
            }
        });
    }

    protected static function generateUniqueSlug(string $name, ?int $ignoreId = null): string
    {
        $slug = Str::slug($name);
        $originalSlug = $slug;
        $counter = 1;

        while (
            static::where('slug', $slug)
                ->when($ignoreId, fn($query) => $query->where('id', '!=', $ignoreId))
                ->exists()
        ) {
            $slug = $originalSlug . '-' . $counter++;
        }

        return $slug;
    }

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

    // Relationship: UnitType has many Units
    public function units()
    {
        return $this->hasMany(Unit::class);
    }
}
