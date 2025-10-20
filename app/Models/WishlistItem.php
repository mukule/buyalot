<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Support\Str;

class WishlistItem extends Model
{
    use HasFactory;

    protected $fillable = [
        'uuid',
        'wishlist_id',
        'product_variant_id',
    ];

    // ----------------------
    // Boot: auto-generate UUID
    // ----------------------
    protected static function boot()
    {
        parent::boot();

        static::creating(function ($model) {
            if (empty($model->uuid)) {
                $model->uuid = (string) Str::uuid();
            }
        });
    }

    // ----------------------
    // Relationships
    // ----------------------

    public function wishlist(): BelongsTo
    {
        return $this->belongsTo(Wishlist::class);
    }

    public function productVariant(): BelongsTo
    {
        return $this->belongsTo(ProductVariant::class);
    }

    /**
     * Optional: shortcut to access product directly
     */
    public function product(): BelongsTo
    {
        return $this->belongsTo(Product::class, 'product_id');
    }

    // ----------------------
    // Accessors / Helpers
    // ----------------------

    /**
     * Retrieve item by its UUID (for route binding or API use).
     */
    public function getRouteKeyName(): string
    {
        return 'uuid';
    }

    /**
     * Quick helper to get product name safely.
     */
    public function getProductNameAttribute(): ?string
    {
        return $this->productVariant?->product?->name;
    }
}
