<?php

namespace App\Models;

use App\Models\Products\Product;
use Illuminate\Database\Eloquent\Casts\Attribute;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsToMany;
use Illuminate\Support\Str;

/**
 * Merchandising campaign — Today's Deals, Offers or Seasonal promotions.
 * Products are attached through the `campaign_product` pivot; an optional
 * discount_percent (campaign default or per-product override) is applied to
 * the variant selling price by DealPricingService.
 */
class Campaign extends Model
{
    public const TYPE_DAILY_DEAL = 'daily_deal';
    public const TYPE_OFFER      = 'offer';
    public const TYPE_SEASONAL   = 'seasonal';

    public const TYPES = [
        self::TYPE_DAILY_DEAL => "Today's Deals",
        self::TYPE_OFFER      => 'Offers',
        self::TYPE_SEASONAL   => 'Seasonal Promotions',
    ];

    // Slugs used by the storefront shortcut routes; campaigns cannot take them.
    public const RESERVED_SLUGS = ['today', 'offers', 'seasonal'];

    protected $fillable = [
        'title',
        'slug',
        'type',
        'subtitle',
        'description',
        'banner_image',
        'theme_color',
        'discount_percent',
        'starts_at',
        'ends_at',
        'is_active',
        'show_on_home',
        'priority',
    ];

    protected $casts = [
        'discount_percent' => 'float',
        'starts_at'        => 'datetime',
        'ends_at'          => 'datetime',
        'is_active'        => 'boolean',
        'show_on_home'     => 'boolean',
        'priority'         => 'integer',
    ];

    protected $appends = ['banner_url'];

    protected static function booted(): void
    {
        static::saving(function (Campaign $campaign) {
            if (blank($campaign->slug)) {
                $campaign->slug = static::uniqueSlug($campaign->title, $campaign->id);
            }
        });
    }

    public static function uniqueSlug(string $title, ?int $ignoreId = null): string
    {
        $base = Str::slug($title) ?: 'campaign';
        if (in_array($base, self::RESERVED_SLUGS, true)) {
            $base .= '-sale';
        }

        $slug = $base;
        $i = 2;
        while (static::where('slug', $slug)->when($ignoreId, fn ($q) => $q->where('id', '!=', $ignoreId))->exists()) {
            $slug = "{$base}-{$i}";
            $i++;
        }

        return $slug;
    }

    public function products(): BelongsToMany
    {
        return $this->belongsToMany(Product::class, 'campaign_product')
            ->withPivot(['discount_percent', 'sort_order'])
            ->withTimestamps();
    }

    /**
     * Running right now: enabled and inside its time window.
     */
    public function scopeLive($query)
    {
        return $query->where('is_active', true)
            ->where(fn ($q) => $q->whereNull('starts_at')->orWhere('starts_at', '<=', now()))
            ->where(fn ($q) => $q->whereNull('ends_at')->orWhere('ends_at', '>=', now()));
    }

    public function scopeOfType($query, ?string $type)
    {
        return $type ? $query->where('type', $type) : $query;
    }

    public function getStatusAttribute(): string
    {
        if (! $this->is_active) return 'inactive';
        if ($this->starts_at && $this->starts_at->isFuture()) return 'scheduled';
        if ($this->ends_at && $this->ends_at->isPast()) return 'ended';

        return 'live';
    }

    protected function bannerUrl(): Attribute
    {
        return Attribute::get(fn () => $this->banner_image ? url("storage/{$this->banner_image}") : null);
    }
}
