<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use App\Models\Traits\HasSlug;
use App\Models\Traits\HasHashid;
use Illuminate\Database\Eloquent\Relations\BelongsToMany;
use Illuminate\Database\Eloquent\SoftDeletes;

class Category extends Model
{
    use HasSlug, HasHashid, SoftDeletes;

    protected $fillable = ['name', 'slug', 'active', 'description', 'parent_id'];

    protected $casts = [
        'active' => 'boolean',
    ];

    protected $appends = ['hashid', 'parent_name'];

    protected static string $slugSource = 'name';

    /**
     * Booted callbacks for model events
     */
    protected static function booted()
    {
        static::created(fn() => \App\Services\SearchCacheService::refresh());
        static::updated(fn() => \App\Services\SearchCacheService::refresh());
        static::deleted(fn() => \App\Services\SearchCacheService::refresh());
        static::restored(fn() => \App\Services\SearchCacheService::refresh());
    }

    /**
     * Parent category relationship
     * Include soft-deleted parent if exists
     */
    public function parent()
    {
        return $this->belongsTo(Category::class, 'parent_id')->withTrashed();
    }

    /**
     * Children categories relationship (recursive)
     * Include soft-deleted children
     */
    public function children()
    {
        return $this->hasMany(Category::class, 'parent_id')
                    ->with('children')
                    ->withTrashed();
    }

    /**
     * Many-to-many relationship with VariantCategory
     */
    public function variantCategories(): BelongsToMany
    {
        return $this->belongsToMany(
            VariantCategory::class,
            'category_variants',  // pivot table
            'category_id',        // this model's FK
            'variant_category_id' // related model's FK
        )->withTimestamps();
    }

    /**
     * Accessor for parent's name
     */
    public function getParentNameAttribute(): ?string
    {
        return $this->parent?->name;
    }

    /**
     * Scope for active categories (excludes soft-deleted by default)
     */
    public function scopeActive($query)
    {
        return $query->where('active', true);
    }

    /**
     * Helper: Get full parent hierarchy as array (root first)
     */
    public function getHierarchy(): array
    {
        $categories = [];
        $current = $this;

        while ($current) {
            $categories[] = [
                'id' => $current->id,
                'name' => $current->name,
                'slug' => $current->slug,
            ];
            $current = $current->parent;
        }

        return array_reverse($categories);
    }

    /**
     * Optional: Get breadcrumb-friendly string
     */
    public function getBreadcrumb(): string
    {
        return implode(' > ', array_map(fn($cat) => $cat['name'], $this->getHierarchy()));
    }

    /**
     * Get all category IDs including children recursively
     */
    public function getAllCategoryIds(): \Illuminate\Support\Collection
    {
        $ids = collect([$this->id]);

        foreach ($this->children as $child) {
            $ids = $ids->merge($child->getAllCategoryIds());
        }

        return $ids;
    }

    /**
     * Get all parent category IDs (recursive)
     */
    public function getParentCategoryIds(): \Illuminate\Support\Collection
    {
        $ids = collect();
        $current = $this->parent;

        while ($current) {
            $ids->push($current->id);
            $current = $current->parent;
        }

        return $ids;
    }

    public function products()
{
    return $this->hasMany(Product::class);
}

}
