<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Str;
use Vinkla\Hashids\Facades\Hashids;

class Brand extends Model
{
    protected $fillable = [
        'name',
        'slug',
        'active',
        'logo_path', // Only brand-specific fields
    ];

    protected $casts = [
        'active' => 'boolean',
    ];

    protected $appends = [
        'hashid',
        'logo_url', // Removed 'category_name'
    ];

    protected static function boot()
    {
        parent::boot();

        static::creating(function ($brand) {
            if (empty($brand->slug)) {
                $brand->slug = static::generateUniqueSlug($brand->name);
            }
        });

        static::updating(function ($brand) {
            if ($brand->isDirty('name')) {
                $brand->slug = static::generateUniqueSlug($brand->name, $brand->id);
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

    // Hashid
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
        if (count($decoded) !== 1) abort(404);
        return $this->where('id', $decoded[0])->firstOrFail();
    }

    // Logo URL (only brand-specific attribute)
    public function getLogoUrlAttribute(): ?string
    {
        return $this->logo_path ? asset('storage/' . $this->logo_path) : null;
    }

    // Removed: category(), getCategoryNameAttribute()
}