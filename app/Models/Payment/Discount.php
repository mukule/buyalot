<?php

namespace App\Models\Payment;

use App\Models\Category;
use App\Models\Customer\Customer;
use App\Models\Orders\Order;
use App\Models\Product;
use App\Models\Traits\HasHashid;
use App\Models\Traits\HasSlug;
use App\Models\User;
use Hashids\Hashids;
use Illuminate\Database\Eloquent\Attributes\Scope;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsToMany;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\SoftDeletes;
use LaravelIdea\Helper\App\Models\Payment\_IH_Discount_QB;

class Discount extends Model
{
    use HasFactory, HasSlug, SoftDeletes;

    protected $table = 'discounts';

    protected $fillable = [
        'name',
        'slug',
        'description',
        'code',
        'type', // percentage, fixed_amount, buy_x_get_y, free_shipping
        'value',
        'minimum_amount',
        'maximum_discount',
        'usage_limit',
        'usage_limit_per_customer',
        'used_count',
        'is_active',
        'starts_at',
        'expires_at',
        'applicable_to', // all, specific_products, specific_categories, specific_customers
        'conditions',
        'metadata',
        'created_by',
    ];

    protected $casts = [
        'value' => 'decimal:2',
        'minimum_amount' => 'decimal:2',
        'maximum_discount' => 'decimal:2',
        'usage_limit' => 'integer',
        'usage_limit_per_customer' => 'integer',
        'used_count' => 'integer',
        'is_active' => 'boolean',
        'starts_at' => 'datetime',
        'expires_at' => 'datetime',
        'conditions' => 'json',
        'metadata' => 'json',
    ];

    protected $dates = [
        'starts_at',
        'expires_at',
    ];

    // Slug configuration
//    public function getSlugOptions(): SlugOptions
//    {
//        return SlugOptions::create()
//            ->generateSlugsFrom('name')
//            ->saveSlugsTo('slug')
//            ->doNotGenerateSlugsOnUpdate();
//    }

    // Hashid configuration
    public function getHashidAttribute(): string
    {
        return Hashids::encode($this->id);
    }

    public static function findByHashid(string $hashid): ?self
    {
        $id = Hashids::decode($hashid);
        return $id ? static::find($id[0]) : null;
    }
    public function orders(): HasMany
    {
        return $this->hasMany(Order::class);
    }

    public function products(): BelongsToMany
    {
        return $this->belongsToMany(Product::class, 'discount_products');
    }

    public function categories(): BelongsToMany
    {
        return $this->belongsToMany(Category::class, 'discount_categories');
    }

    public function customers(): BelongsToMany
    {
        return $this->belongsToMany(Customer::class, 'discount_customers');
    }

    public function creator()
    {
        return $this->belongsTo(User::class, 'created_by');
    }

    // Scopes
    #[Scope]
    protected function active($query)
    {
        return $query->where('is_active', true)
            ->where('starts_at', '<=', now())
            ->where(function ($q) {
                $q->whereNull('expires_at')
                    ->orWhere('expires_at', '>=', now());
            });
    }

    #[Scope]
    protected function expired($query)
    {
        return $query->where('expires_at', '<', now());
    }

    /**
     * @param $query
     * @param string $code
     * @return _IH_Discount_QB|_IH_Discount_QB
     */
    #[Scope]
    protected function byCode($query, string $code)
    {
        return $query->where('code', $code);
    }

    /**
     * @param $query
     * @param string $type
     * @return _IH_Discount_QB|_IH_Discount_QB
     */
    #[Scope]
    protected function byType($query, string $type)
    {
        return $query->where('type', $type);
    }

    /**
     * @param $query
     * @return _IH_Discount_QB|_IH_Discount_QB
     */
    #[Scope]
    protected function usageLimitNotReached($query)
    {
        return $query->where(function ($q) {
            $q->whereNull('usage_limit')
                ->orWhereRaw('used_count < usage_limit');
        });
    }

    // Accessors & Mutators
    public function getFormattedValueAttribute(): string
    {
        return match ($this->type) {
            'percentage' => $this->value . '%',
            'fixed_amount' => 'KES ' . number_format($this->value, 2),
            'free_shipping' => 'Free Shipping',
            default => $this->value
        };
    }

    public function getStatusAttribute(): string
    {
        if (!$this->is_active) {
            return 'inactive';
        }

        if ($this->starts_at && $this->starts_at > now()) {
            return 'scheduled';
        }

        if ($this->expires_at && $this->expires_at < now()) {
            return 'expired';
        }

        if ($this->usage_limit && $this->used_count >= $this->usage_limit) {
            return 'exhausted';
        }

        return 'active';
    }

    public function getUsagePercentageAttribute(): float
    {
        if (!$this->usage_limit) {
            return 0;
        }

        return min(100, ($this->used_count / $this->usage_limit) * 100);
    }

    // Helper Methods
    public function isActive(): bool
    {
        return $this->is_active
            && ($this->starts_at === null || $this->starts_at <= now())
            && ($this->expires_at === null || $this->expires_at >= now())
            && ($this->usage_limit === null || $this->used_count < $this->usage_limit);
    }

    public function isExpired(): bool
    {
        return $this->expires_at && $this->expires_at < now();
    }

    public function isScheduled(): bool
    {
        return $this->starts_at && $this->starts_at > now();
    }

    public function isExhausted(): bool
    {
        return $this->usage_limit && $this->used_count >= $this->usage_limit;
    }

    public function canBeUsed(): bool
    {
        return $this->isActive();
    }

    public function canBeUsedByCustomer(int $customerId): bool
    {
        if (!$this->canBeUsed()) {
            return false;
        }

        // Check per-customer usage limit
        if ($this->usage_limit_per_customer) {
            $customerUsageCount = Order::where('customer_id', $customerId)
                ->where('discount_id', $this->id)
                ->count();

            if ($customerUsageCount >= $this->usage_limit_per_customer) {
                return false;
            }
        }

        // Check if customer is in allowed list (if applicable_to is specific_customers)
        if ($this->applicable_to === 'specific_customers') {
            return $this->customers()->where('customer_id', $customerId)->exists();
        }

        return true;
    }

    public function isApplicableToProduct(int $productId): bool
    {
        return match ($this->applicable_to) {
            'all' => true,
            'specific_products' => $this->products()->where('product_id', $productId)->exists(),
            'specific_categories' => $this->isApplicableToProductCategories($productId),
            default => false,
        };
    }

    private function isApplicableToProductCategories(int $productId): bool
    {
        $product = Product::with('categories')->find($productId);
        if (!$product) {
            return false;
        }

        $discountCategoryIds = $this->categories->pluck('id')->toArray();
        $productCategoryIds = $product->categories->pluck('id')->toArray();

        return !empty(array_intersect($discountCategoryIds, $productCategoryIds));
    }

    public function calculateDiscount(float $amount, array $items = []): float
    {
        if (!$this->canBeUsed() || $amount < ($this->minimum_amount ?? 0)) {
            return 0;
        }

        $discount = match ($this->type) {
            'percentage' => $amount * ($this->value / 100),
            'fixed_amount' => min($this->value, $amount),
            'free_shipping' => 0, // Handled separately
            'buy_x_get_y' => $this->calculateBuyXGetYDiscount($items),
            default => 0,
        };

        // Apply maximum discount limit
        if ($this->maximum_discount) {
            $discount = min($discount, $this->maximum_discount);
        }

        return round($discount, 2);
    }

    private function calculateBuyXGetYDiscount(array $items): float
    {
        // Implementation depends on your buy_x_get_y logic
        // This is a basic example
        if (!isset($this->conditions['buy_quantity'], $this->conditions['get_quantity'])) {
            return 0;
        }

        $buyQuantity = $this->conditions['buy_quantity'];
        $getQuantity = $this->conditions['get_quantity'];
        $discount = 0;

        foreach ($items as $item) {
            if ($this->isApplicableToProduct($item['product_id'])) {
                $freeItems = intval($item['quantity'] / $buyQuantity) * $getQuantity;
                $discount += $freeItems * $item['unit_price'];
            }
        }

        return $discount;
    }

    public function incrementUsage(): void
    {
        $this->increment('used_count');
    }

    public function decrementUsage(): void
    {
        if ($this->used_count > 0) {
            $this->decrement('used_count');
        }
    }

    // Route key binding
    public function getRouteKeyName(): string
    {
        return 'slug';
    }

    public function resolveRouteBinding($value, $field = null)
    {
        // Try to find by slug first, then by hashid
        return $this->where('slug', $value)->first()
            ?? static::findByHashid($value);
    }

    // Boot method
    protected static function boot()
    {
        parent::boot();

        static::creating(function ($discount) {
            if (empty($discount->code)) {
                $discount->code = strtoupper(\Illuminate\Support\Str::random(8));
            }
        });
    }
}
