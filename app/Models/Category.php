<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Str;
use App\Models\Traits\HasHashid;
use Illuminate\Database\Eloquent\Relations\BelongsToMany;

class Category extends Model
{
    use HasHashid;

    protected $fillable = ['name', 'slug', 'active', 'description', 'parent_id'];

    protected $casts = [
        'active' => 'boolean',
    ];

    protected $appends = ['hashid', 'parent_name'];

    /**
     * Booted callbacks for model events
     */
    protected static function booted(): void
    {
        static::creating(function ($category) {
            if (empty($category->slug)) {
                $category->slug = static::generateUniqueSlug($category->name);
            }
        });

        static::updating(function ($category) {
            if ($category->isDirty('name') && empty($category->slug)) {
                $category->slug = static::generateUniqueSlug($category->name, $category->id);
            }
        });

        //added category refresh cache jobs
//        static::updated(fn($category)  => \App\Jobs\RefreshCategoryCache::dispatch($category)->delay(now()->addSeconds(5)));
//        static::created(fn($category) => \App\Jobs\RefreshCategoryCache::dispatch($category)->delay(now()->addSeconds(5)));
//        static::deleted(fn($category) => \App\Jobs\RefreshCategoryCache::dispatch($category)->delay(now()->addSeconds(5)));
    }



    /**
     * Parent category relationship
     */
    public function parent()
    {
        return $this->belongsTo(Category::class, 'parent_id');
    }

    /**
     * Children relationship (non-recursive)
     */
    // public function children()
    // {
    //     return $this->hasMany(Category::class, 'parent_id');
    // }

    public function children()
    {
        return $this->hasMany(Category::class, 'parent_id')
            ->where('active', true)
            ->with('children'); // recursion
    }


    /**
     * Many-to-many relationship with VariantCategory
     */
    public function variantCategories(): BelongsToMany
    {
        return $this->belongsToMany(
            VariantCategory::class,
            'category_variants',
            'category_id',
            'variant_category_id'
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
     * Scope for active categories
     */
    public function scopeActive($query)
    {
        return $query->where('active', true);
    }

    /**
     * Get full parent hierarchy as array (root first)
     */
    public function getHierarchy(): array
    {
        $visited = [];
        $categories = [];
        $current = $this;

        while ($current && !in_array($current->id, $visited)) {
            $visited[] = $current->id;

            $categories[] = [
                'id'   => $current->id,
                'name' => $current->name,
                'slug' => $current->slug,
            ];

            $current = $current->parent;
        }

        return array_reverse($categories);
    }

    /**
     * Breadcrumb-friendly string
     */
    public function getBreadcrumb(): string
    {
        return implode(' > ', array_map(fn ($cat) => $cat['name'], $this->getHierarchy()));
    }

    /**
     * Get all category IDs including children recursively
     */
    public function getAllCategoryIds(&$visited = []): \Illuminate\Support\Collection
    {
        if (in_array($this->id, $visited)) {
            return collect();
        }

        $visited[] = $this->id;
        $ids = collect([$this->id]);

        foreach ($this->children as $child) {
            $ids = $ids->merge($child->getAllCategoryIds($visited));
        }

        return $ids;
    }

    /**
     * Get all parent category IDs
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

    /**
     * Relationship with products
     */
    public function products()
    {
        return $this->hasMany(Product::class);
    }

    /**
     * Generate a unique slug
     */
    protected static function generateUniqueSlug(string $name, ?int $ignoreId = null): string
    {
        $slug = Str::slug($name);
        $originalSlug = $slug;
        $count = 1;

        while (static::where('slug', $slug)
            ->when($ignoreId, fn ($q) => $q->where('id', '!=', $ignoreId))
            ->exists()) {

            $slug = $originalSlug . '-' . $count;
            $count++;
        }

        return $slug;
    }
}
