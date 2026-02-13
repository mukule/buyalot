<?php

namespace App\Models\Products;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Support\Facades\Storage;

class ProductVariantImage extends Model
{
    protected $fillable = [
        'product_variant_id',
        'image_path',
        'alt_text',
        'is_primary',
        'sort_order',
    ];

    protected $casts = [
        'is_primary' => 'boolean',
        'sort_order' => 'integer',
    ];

    protected $appends = ['url'];

    /**
     * Get the product variant this image belongs to
     */
    public function productVariant(): BelongsTo
    {
        return $this->belongsTo(ProductVariant::class);
    }

    /**
     * Get the full URL for the image
     * Handles both local storage paths and external URLs
     */
    public function getUrlAttribute(): string
    {
        // If it's already a full URL (http/https), return as-is
        if (preg_match('/^https?:\/\//', $this->image_path)) {
            return $this->image_path;
        }

        // If it's a blob URL (shouldn't be in DB, but just in case), return as-is
        if (str_starts_with($this->image_path, 'blob:')) {
            return $this->image_path;
        }

        // Otherwise, generate URL from storage
        return Storage::disk('public')->url($this->image_path);
    }

    /**
     * Scope to get only primary images
     */
    public function scopePrimary($query)
    {
        return $query->where('is_primary', true);
    }

    /**
     * Scope to get images ordered by sort order
     */
    public function scopeOrdered($query)
    {
        return $query->orderBy('sort_order')->orderBy('id');
    }

    /**
     * Scope to get non-primary images (gallery)
     */
    public function scopeGallery($query)
    {
        return $query->where('is_primary', false);
    }

    /**
     * Delete the image file from storage when model is deleted
     */
    protected static function booted()
    {
        static::deleting(function ($image) {
            // Only delete from storage if it's a local path (not external URL)
            if ($image->image_path && 
                !preg_match('/^https?:\/\//', $image->image_path) &&
                !str_starts_with($image->image_path, 'blob:')) {
                Storage::disk('public')->delete($image->image_path);
            }
        });
    }
}