<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use App\Models\Traits\HasSlug;
use App\Models\Traits\HasHashid;

class Category extends Model
{
    use HasSlug, HasHashid;

    protected $fillable = ['name', 'slug', 'active', 'parent_id'];

    protected $casts = [
        'active' => 'boolean',
    ];

    protected $appends = ['hashid', 'parent_name'];

    protected static string $slugSource = 'name';

    /**
     * Parent category relationship
     */
    public function parent()
    {
        return $this->belongsTo(Category::class, 'parent_id');
    }

    /**
     * Children categories relationship (recursive)
     */
    public function children()
    {
        return $this->hasMany(Category::class, 'parent_id')
                    ->with('children'); 
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
     * Helper: Get full parent hierarchy as array (root first)
     */
    public function getHierarchy(): array
    {
        $categories = [];
        $current = $this;

        while ($current) {
            array_unshift($categories, [
                'id' => $current->id,
                'name' => $current->name,
                'slug' => $current->slug,
            ]);
            $current = $current->parent;
        }

        return $categories;
    }

    /**
     * Optional: Get breadcrumb-friendly string
     */
    public function getBreadcrumb(): string
    {
        return implode(' > ', array_map(fn($cat) => $cat['name'], $this->getHierarchy()));
    }


    public function getAllCategoryIds(): \Illuminate\Support\Collection
{
    $ids = collect([$this->id]);

    foreach ($this->children as $child) {
        $ids = $ids->merge($child->getAllCategoryIds());
    }

    return $ids;
}
}
