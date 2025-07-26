<?php

namespace App\Models\Traits;

use Illuminate\Support\Str;

trait HasSlug
{
    public static function bootHasSlug(): void
    {
        static::creating(function ($model) {
            if (empty($model->slug)) {
                $model->slug = $model::generateUniqueSlug($model->getSlugSourceValue());
            }
        });

        static::updating(function ($model) {
            $field = $model->getSlugSourceField();
            if ($model->isDirty($field)) {
                $model->slug = $model::generateUniqueSlug($model->getSlugSourceValue(), $model->id);
            }
        });
    }

    /**
     * Get the attribute name that should be used as the slug source.
     *
     * Defaults to 'name' if the model doesn't specify $slugSource.
     *
     * @return string
     */
    protected function getSlugSourceField(): string
    {
        return property_exists($this, 'slugSource') ? static::$slugSource : 'name';
    }

    /**
     * Get the current value of the slug source field.
     *
     * @return string
     */
    protected function getSlugSourceValue(): string
    {
        $field = $this->getSlugSourceField();
        return $this->$field ?? '';
    }

    /**
     * Generate a unique slug based on a name, ignoring a specific ID if provided.
     *
     * @param string $name
     * @param int|null $ignoreId
     * @return string
     */
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
}
