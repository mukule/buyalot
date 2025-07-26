<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Str;
use Vinkla\Hashids\Facades\Hashids;

class Warehouse extends Model
{
    protected $fillable = [
        'name',
        'slug',
        'location',
        'active',
    ];

    protected $casts = [
        'active' => 'boolean',
    ];

    protected $appends = ['hashid'];

    
    public static function boot()
    {
        parent::boot();

        static::creating(function ($warehouse) {
            if (empty($warehouse->slug)) {
                $warehouse->slug = static::generateUniqueSlug($warehouse->name);
            }
        });

        static::updating(function ($warehouse) {
            if ($warehouse->isDirty('name')) {
                $warehouse->slug = static::generateUniqueSlug($warehouse->name, $warehouse->id);
            }
        });
    }

    // Generate a unique slug for the warehouse
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

    // Route model binding key: use encoded hashid instead of id
    public function getRouteKey(): string
    {
        return Hashids::encode($this->id);
    }

    // Hashid attribute accessor
    public function getHashidAttribute(): string
    {
        return Hashids::encode($this->id);
    }

    // Resolve route binding using the hashid
    public function resolveRouteBinding($value, $field = null): ?Model
    {
        $decoded = Hashids::decode($value);

        if (count($decoded) !== 1) {
            abort(404);
        }

        return $this->where('id', $decoded[0])->firstOrFail();
    }
}
