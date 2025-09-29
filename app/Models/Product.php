<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Casts\Attribute;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\Relations\HasOne;
use App\Models\Traits\HasSlug;
use App\Models\Traits\HasHashid;
use App\Models\Warranty;
use Illuminate\Support\Facades\Storage;

class Product extends Model
{
    use HasSlug, HasHashid;
    protected static string $slugSource = 'name';

    protected $fillable = [
        'name',
        'slug',
        'description',
        'product_code',
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
        'category_id',
        'current_step',
        'unit_id',
        'max_step_completed',
        'status_id'
    ];

    protected $appends = [
        'hashid',
        'image_urls',
        'primary_image_url',
        'status_label',
        'company_legal_name',
        'min_price',
        'max_price',
        'in_stock',
        'pricing',
    ];

    protected $casts = [
        'status' => 'integer',
    ];

    // ----------------------
    // Status constants
    // ----------------------
    const STATUS_DRAFT    = 0; // Created but not submitted
    const STATUS_PENDING  = 1; // Submitted for review
    const STATUS_APPROVED = 2; // Approved and public
    const STATUS_REJECTED = 3; // Rejected / Not approved

    // ----------------------
    // Attributes
    // ----------------------

   protected function imageUrls(): Attribute
    {
        return Attribute::get(fn () =>
            $this->relationLoaded('images')
                ? $this->images
                    ->map(fn ($img) => Storage::disk('s3')->url($img->image_path))
                    ->toArray()
                : []
        );
    }

    protected function primaryImageUrl(): Attribute
    {
        return Attribute::get(fn () =>
            $this->relationLoaded('primaryImage') && $this->primaryImage
                ? Storage::disk('s3')->url($this->primaryImage->image_path)
                : null
        );
    }


    protected function statusLabel(): Attribute
    {
        return Attribute::get(fn () => match ((int) $this->status) {
            self::STATUS_DRAFT    => 'Draft',
            self::STATUS_PENDING  => 'Pending Review',
            self::STATUS_APPROVED => 'Public',
            self::STATUS_REJECTED => 'Rejected',
            default                => 'Unknown',
        });
    }

    protected function companyLegalName(): Attribute
    {
        return Attribute::get(function () {
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
        });
    }

    public function getMinPriceAttribute(): ?float
    {
        return $this->productVariants()->min('selling_price');
    }

    public function getMaxPriceAttribute(): ?float
    {
        return $this->productVariants()->max('selling_price');
    }

    public function getInStockAttribute(): bool
    {
        return $this->productVariants()->sum('stock') > 0;
    }

    protected function pricing(): Attribute
    {
        return Attribute::get(function () {
            $min = $this->min_price;
            $max = $this->max_price;

            if ($min === null) {
                return [
                    'price'           => null,
                    'discountedPrice' => null,
                    'hasDiscount'     => false,
                    'discount'        => null,
                    'label'           => 'N/A',
                ];
            }

            // Pick the cheapest variant
            $minVariant = $this->productVariants()->orderBy('selling_price')->first();

            $price           = $minVariant?->selling_price;
            $discountedPrice = $minVariant && $minVariant->discount_price
                ? $minVariant->discount_price
                : $price;

            $hasDiscount = $discountedPrice !== null && $discountedPrice < $price;
            $discount    = $hasDiscount
                ? round((1 - ($discountedPrice / $price)) * 100)
                : null;

            return [
                'price'           => $price,
                'discountedPrice' => $discountedPrice,
                'hasDiscount'     => $hasDiscount,
                'discount'        => $discount,
                'label'           => $min !== $max
                    ? 'KSh ' . number_format($min) . ' - ' . number_format($max)
                    : 'KSh ' . number_format($min),
            ];
        });
    }

    // ----------------------
    // Relationships
    // ----------------------

    public function owner(): BelongsTo
    {
        return $this->belongsTo(User::class, 'owner_id');
    }

    public function brand(): BelongsTo
    {
        return $this->belongsTo(Brand::class);
    }

    public function category(): BelongsTo
    {
        return $this->belongsTo(Category::class);
    }

    public function unit(): BelongsTo
    {
        return $this->belongsTo(Unit::class);
    }

    public function images(): HasMany
    {
        return $this->hasMany(ProductImage::class);
    }

    public function primaryImage(): HasOne
    {
        return $this->hasOne(ProductImage::class)->where('is_primary', true);
    }

    // ----------------------
    // Variant relationships
    // ----------------------

    public function productVariants(): HasMany
    {
        return $this->hasMany(ProductVariant::class);
    }

    public function variants(): HasMany
    {
        return $this->hasMany(ProductVariant::class);
    }

    public function variantValues(): HasMany
    {
        return $this->hasMany(ProductVariantValue::class);
    }

    // ----------------------
    // Scopes
    // ----------------------

    public function scopeLatestDraft($query)
    {
        return $query->where('max_step_completed', '<', 4)
                     ->latest('created_at');
    }

    public function updateStatus(int $statusId): bool
    {
        $statusExists = \App\Models\ProductStatus::where('id', $statusId)->exists();
        if (! $statusExists) {
            return false;
        }
        $this->status_id = $statusId;
        $this->save();
        return true;
    }


public function warranties(): HasMany
{
    return $this->hasMany(Warranty::class);
}

public function hasWarranty(): bool
{
    return $this->warranties()->exists();
}

public function activeWarranty(): ?Warranty
{
    return $this->warranties()->where('active', true)->first();
}

}
