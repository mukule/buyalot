<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Str;
use Vinkla\Hashids\Facades\Hashids;

class BrandCategory extends Model
{
    protected $fillable = ['name', 'slug', 'parent_id', 'active'];

    protected $casts = [
        'active' => 'boolean',
    ];

    protected $appends = ['hashid', 'parent_name'];

    protected static function boot()
    {
        parent::boot();

        static::creating(function ($category) {
            if (empty($category->slug)) {
                $category->slug = static::generateUniqueSlug($category->name);
            }
        });

        static::updating(function ($category) {
            if ($category->isDirty('name')) {
                $category->slug = static::generateUniqueSlug($category->name, $category->id);
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

    // Hashid Accessor
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

    // Parent name accessor
    public function getParentNameAttribute(): ?string
    {
        return $this->parent?->name;
    }

    // Relationships
    public function parent()
    {
        return $this->belongsTo(BrandCategory::class, 'parent_id');
    }

    public function children()
    {
        return $this->hasMany(BrandCategory::class, 'parent_id');
    }

    public function brands()
    {
        return $this->hasMany(Brand::class, 'brand_category_id');
    }
}
