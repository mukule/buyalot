<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\MorphTo;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use App\Models\Traits\HasSlug;
use App\Models\Traits\HasHashid;

class Product extends Model
{
    use HasSlug, HasHashid;


    protected static string $slugSource = 'name';

    protected $fillable = [
        'name',
        'slug',
        'description',
        'features',
        'specifications',
        'whats_in_the_box',
        'meta_title',
        'meta_keywords',
        'meta_description',
        'status',
        'owner_type',
        'owner_id',
        'brand_id',
        'subcategory_id',
        'unit_id',
        'price',
        'discount',
        'stock',
    ];

    protected $appends = [
        'hashid',
        'image_urls',
        'primary_image_url',
        'discounted_price',
        'has_discount',
        'in_stock',
        'is_out_of_stock',
        'status_label',
        'company_legal_name',
    ];

    protected $casts = [
        'price' => 'float',
        'discount' => 'float',
        'stock' => 'integer',
        'status' => 'integer',
    ];



    public function getImageUrlsAttribute(): array
    {
        if ($this->relationLoaded('images') && $this->images->isNotEmpty()) {
            return $this->images->map(fn ($image) => asset('storage/' . $image->image_path))->toArray();
        }

        return [];
    }

    public function getPrimaryImageUrlAttribute(): ?string
    {
        if ($this->relationLoaded('primaryImage') && $this->primaryImage) {
            return asset('storage/' . $this->primaryImage->image_path);
        }

        return null;
    }

    public function getHasDiscountAttribute(): bool
    {
        return $this->discount > 0;
    }

    public function getDiscountedPriceAttribute(): float
    {
        $price = (float) ($this->price ?? 0);
        $discount = (float) ($this->discount ?? 0);

        if ($this->has_discount && $discount > 0) {
            return round($price * (1 - ($discount / 100)), 2);
        }

        return $price;
    }

    public function getInStockAttribute(): bool
    {
        return $this->stock > 0;
    }

    public function getIsOutOfStockAttribute(): bool
    {
        return $this->stock === 0;
    }

    public function getStatusLabelAttribute(): string
    {
        return match ((int) $this->status) {
            0 => 'Pending',
            1 => 'Public',
            3 => 'Private',
            default => 'Unknown',
        };
    }

    public function getCompanyLegalNameAttribute(): ?string
    {
        if ($this->owner_type === 'admin') {
            return 'Buyalot';
        }

        if (
            $this->owner_type === 'seller' &&
            $this->owner instanceof \App\Models\User &&
            $this->owner->sellerApplication
        ) {
            return $this->owner->sellerApplication->company_legal_name;
        }

        return null;
    }

    // Relationships

    public function owner(): MorphTo
    {
        return $this->morphTo();
    }

    public function brand(): BelongsTo
    {
        return $this->belongsTo(Brand::class);
    }

    public function subcategory(): BelongsTo
    {
        return $this->belongsTo(Subcategory::class);
    }

    public function unit(): BelongsTo
    {
        return $this->belongsTo(Unit::class);
    }

    public function category(): BelongsTo
    {
        return $this->belongsTo(Category::class);
    }

    public function images()
    {
        return $this->hasMany(ProductImage::class);
    }

    public function primaryImage()
    {
        return $this->hasOne(ProductImage::class)->where('is_primary', true);
    }

    public function variantValues()
    {
        return $this->hasMany(ProductVariantValue::class);
    }

    public function variants()
    {
        return $this->belongsToMany(Variant::class, 'product_variant_values');
    }
}
