<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Str;
use Vinkla\Hashids\Facades\Hashids;

class Subcategory extends Model
{
    protected $fillable = [
        'category_id',
        'name',
        'slug',
        'active',
    ];

    protected $casts = [
        'active' => 'boolean',
    ];

    protected $appends = ['hashid'];

    protected static function boot()
    {
        parent::boot();

        static::creating(function ($subcategory) {
            if (empty($subcategory->slug)) {
                $subcategory->slug = static::generateUniqueSlug($subcategory->name);
            }
        });

        static::updating(function ($subcategory) {
            if ($subcategory->isDirty('name')) {
                $subcategory->slug = static::generateUniqueSlug($subcategory->name, $subcategory->id);
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
                ->when($ignoreId, fn ($query) => $query->where('id', '!=', $ignoreId))
                ->exists()
        ) {
            $slug = $originalSlug . '-' . $counter++;
        }

        return $slug;
    }

    // Hashid accessor
    public function getHashidAttribute(): string
    {
        return Hashids::encode($this->id);
    }

    // Route key for implicit route model binding (using hashid)
    public function getRouteKey(): string
    {
        return $this->hashid;
    }

    // Resolve route binding by decoding hashid
    public function resolveRouteBinding($value, $field = null)
    {
        $decoded = Hashids::decode($value);

        if (count($decoded) !== 1) {
            abort(404);
        }

        return $this->where('id', $decoded[0])->firstOrFail();
    }

    // Relationship: Subcategory belongs to a Category
    public function category()
    {
        return $this->belongsTo(Category::class);
    }

    public function brands()
{
    return $this->hasMany(Brand::class);
}
}
