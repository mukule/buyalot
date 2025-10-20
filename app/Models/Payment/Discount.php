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
    use HasFactory, SoftDeletes;

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
        return (new \Hashids\Hashids)->encode($this->id);
    }

    public static function findByHashid(string $hashid): ?self
    {
        $id = (new \Hashids\Hashids)->decode($hashid);
        return $id ? static::find($id[0]) : null;
    }
    public function resolveRouteBinding($value, $field = null)
    {
        // If it's numeric, treat it as ID
        if (is_numeric($value)) {
            return $this->find($value);
        }

        // Try slug first
        $bySlug = $this->where('slug', $value)->first();
        if ($bySlug) {
            return $bySlug;
        }

        // Then try hashid
        return static::findByHashid($value);
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
        // Consider both legacy (start_date/end_date) and new (starts_at/expires_at) columns
        return $query->where('is_active', true)
            ->where(function ($q) {
                $now = now();
                $q->whereNull('starts_at')
                  ->orWhere('starts_at', '<=', $now);
            })
            ->where(function ($q) {
                $now = now();
                $q->whereNull('expires_at')
                  ->orWhere('expires_at', '>=', $now);
            })
            ->where(function ($q) {
                $now = now();
                $q->whereNull('start_date')
                  ->orWhere('start_date', '<=', $now);
            })
            ->where(function ($q) {
                $now = now();
                $q->whereNull('end_date')
                  ->orWhere('end_date', '>=', $now);
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
        $type = $this->normalizeType($this->type);
        return match ($type) {
            'percentage' => $this->value . '%',
            'fixed_amount' => 'KES ' . number_format($this->value, 2),
            'free_shipping' => 'Free Shipping',
            default => (string)$this->value,
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
    private function normalizeType(?string $type): ?string
    {
        return match ($type) {
            'fixed' => 'fixed_amount',
            'bogo' => 'buy_x_get_y',
            default => $type,
        };
    }

    private function effectiveStart(): ?\Carbon\CarbonInterface
    {
        $starts = $this->starts_at ?? $this->start_date ?? null;
        return $starts ? \Carbon\Carbon::parse($starts) : null;
    }

    private function effectiveEnd(): ?\Carbon\CarbonInterface
    {
        $ends = $this->expires_at ?? $this->end_date ?? null;
        return $ends ? \Carbon\Carbon::parse($ends) : null;
    }

    private function totalUsedCount(): int
    {
        return (int)($this->used_count ?? $this->times_used ?? 0);
    }

    private function perCustomerLimit(): ?int
    {
        return $this->usage_limit_per_customer ?? $this->per_user_limit ?? null;
    }
    public function isActive(): bool
    {
        $start = $this->effectiveStart();
        $end = $this->effectiveEnd();
        $limit = $this->usage_limit ?? null;
        $used = $this->totalUsedCount();

        return (bool)$this->is_active
            && (!$start || $start->lessThanOrEqualTo(now()))
            && (!$end || $end->greaterThanOrEqualTo(now()))
            && ($limit === null || $used < (int)$limit);
    }

    public function isExpired(): bool
    {
        $end = $this->effectiveEnd();
        return $end !== null && $end->lessThan(now());
    }

    public function isScheduled(): bool
    {
        $start = $this->effectiveStart();
        return $start !== null && $start->greaterThan(now());
    }

    public function isExhausted(): bool
    {
        $limit = $this->usage_limit ?? null;
        return $limit !== null && $this->totalUsedCount() >= (int)$limit;
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

        // Check per-customer usage limit (supports usage_limit_per_customer or per_user_limit)
        $limitPerCustomer = $this->perCustomerLimit();
        if ($limitPerCustomer) {
            $customerUsageCount = Order::where('customer_id', $customerId)
                ->where('discount_id', $this->id)
                ->count();

            if ($customerUsageCount >= $limitPerCustomer) {
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

    public function calculateDiscount(float $amount, array $items = [], array $context = []): float
    {
        if (!$this->canBeUsed() || $amount < ($this->minimum_amount ?? 0)) {
            return 0;
        }

        // Evaluate order-level constraints from conditions
        $conditions = $this->conditions ?? [];
        $matchMode = ($conditions['match'] ?? 'any') === 'all' ? 'all' : 'any';
        $appliesTo = $conditions['applies_to'] ?? 'items'; // items|order|shipping

        $orderSubtotal = (float)($context['order_subtotal'] ?? $amount);
        $customerId = $context['customer_id'] ?? null;
        $regionId = $context['region_id'] ?? null;

        // Customer-scoped gates (new/specific/segments)
        if (!empty($conditions['applies_to']) && $conditions['applies_to'] === 'customers') {
            if ($customerId === null) {
                return 0; // cannot evaluate customer-based discount without customer context
            }
            $scope = $conditions['scope'] ?? 'all';
            if ($scope === 'specific' && !empty($conditions['customer_ids'])) {
                if (!$this->valueInList($customerId, $conditions['customer_ids'])) {
                    return 0;
                }
            } elseif ($scope === 'new') {
                $withinDays = (int)($conditions['within_days'] ?? 30);
                $customer = Customer::find($customerId);
                if (!$customer) return 0;
                $cutoff = now()->subDays(max(1, $withinDays));
                if ($customer->created_at < $cutoff) {
                    return 0;
                }
            } elseif ($scope === 'by_order_count') {
                $minOrders = (int)($conditions['min_orders'] ?? 1);
                $orderCount = Order::where('customer_id', $customerId)->count();
                if ($orderCount < max(1, $minOrders)) {
                    return 0;
                }
            } elseif ($scope === 'by_total_spend') {
                $minSpend = (float)($conditions['min_total_spend'] ?? 0);
                $total = (float) Order::where('customer_id', $customerId)->sum('total');
                if ($total < $minSpend) {
                    return 0;
                }
            }
        }

        // Order-level gates
        if (isset($conditions['min_order_total']) && $orderSubtotal < (float)$conditions['min_order_total']) {
            return 0;
        }
        if (isset($conditions['max_order_total']) && $orderSubtotal > (float)$conditions['max_order_total']) {
            return 0;
        }
        if (!empty($conditions['customer_ids']) && $customerId !== null) {
            if (!$this->valueInList($customerId, $conditions['customer_ids'])) {
                return 0;
            }
        }
        if (!empty($conditions['region_ids']) && $regionId !== null) {
            if (!$this->valueInList($regionId, $conditions['region_ids'])) {
                return 0;
            }
        }

        // Filter items by applicability
        $applicableItems = [];
        foreach ($items as $item) {
            if ($this->isApplicableToItem($item, $conditions, $matchMode)) {
                $applicableItems[] = $item;
            }
        }

        // If applies_to = order we still might want to require at least one matching item when product constraints exist
        if ($appliesTo !== 'order' && empty($applicableItems)) {
            return 0;
        }

        $baseAmount = $appliesTo === 'order' ? $amount : $this->sumItems($applicableItems);

        $type = $this->normalizeType($this->type);
        $discount = match ($type) {
            'percentage' => $baseAmount * ((float)$this->value / 100),
            'fixed_amount' => min((float)$this->value, $baseAmount),
            'free_shipping' => 0, // handled by caller using shipping rules
            'buy_x_get_y' => $this->calculateBuyXGetYDiscount($applicableItems),
            default => 0,
        };

        // Cap maximum discount
        if ($this->maximum_discount) {
            $discount = min($discount, (float)$this->maximum_discount);
        }

        return round($discount, 2);
    }

    private function sumItems(array $items): float
    {
        $sum = 0.0;
        foreach ($items as $it) {
            $qty = (int)($it['quantity'] ?? 1);
            $price = (float)($it['unit_price'] ?? 0);
            $sum += $qty * $price;
        }
        return $sum;
    }

    private function isApplicableToItem(array $item, array $conditions, string $matchMode = 'any'): bool
    {
        // Collect checks according to available condition keys
        $checks = [];

        if (!empty($conditions['product_ids'])) {
            $checks[] = $this->valueInList($item['product_id'] ?? null, $conditions['product_ids']);
        }
        if (!empty($conditions['variant_ids'])) {
            $checks[] = $this->valueInList($item['product_variant_id'] ?? null, $conditions['variant_ids']);
        }
        if (!empty($conditions['seller_ids'])) {
            $checks[] = $this->valueInList($item['seller_id'] ?? null, $conditions['seller_ids']);
        }
        if (!empty($conditions['brand_ids'])) {
            $checks[] = $this->valueInList($item['brand_id'] ?? null, $conditions['brand_ids']);
        }
        if (!empty($conditions['sub_brand_ids'])) {
            $checks[] = $this->valueInList($item['sub_brand_id'] ?? null, $conditions['sub_brand_ids']);
        }
        if (!empty($conditions['category_ids'])) {
            $includeChildren = (bool)($conditions['include_children'] ?? true);
            $checks[] = $this->matchesCategories($item['category_ids'] ?? [], $conditions['category_ids'], $includeChildren);
        }
        if (!empty($conditions['all_variants'])) {
            // If true and product_id matches, any variant qualifies regardless of variant filter
            if (!empty($conditions['product_ids']) && isset($item['product_id'])) {
                $checks[] = $this->valueInList($item['product_id'], $conditions['product_ids']);
            }
        }

        // If no specific item-level conditions provided, consider item eligible
        if (empty($checks)) {
            return true;
        }

        // Evaluate match mode
        if ($matchMode === 'all') {
            return !in_array(false, $checks, true);
        }
        // any
        return in_array(true, $checks, true);
    }

    private function valueInList($value, array $list): bool
    {
        if ($value === null) return false;
        return in_array($value, $list);
    }

    private function matchesCategories(array $itemCategoryIds, array $conditionCategoryIds, bool $includeChildren = true): bool
    {
        if (empty($itemCategoryIds) || empty($conditionCategoryIds)) {
            return false;
        }

        // If includeChildren, expand conditionCategoryIds by their descendants using Category model
        $expanded = collect($conditionCategoryIds);
        if ($includeChildren) {
            $all = collect();
            foreach ($conditionCategoryIds as $catId) {
                $cat = Category::with('children')->find($catId);
                if ($cat) {
                    $all = $all->merge($cat->getAllCategoryIds());
                }
            }
            if ($all->isNotEmpty()) {
                $expanded = $all->unique();
            }
        }

        return !empty(array_intersect($expanded->all(), $itemCategoryIds));
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
        // Prefer times_used if available (legacy schema), otherwise used_count
        if (array_key_exists('times_used', $this->getAttributes())) {
            $this->increment('times_used');
        } else {
            $this->increment('used_count');
        }
    }

    public function decrementUsage(): void
    {
        if (array_key_exists('times_used', $this->getAttributes())) {
            if ((int)($this->times_used ?? 0) > 0) {
                $this->decrement('times_used');
            }
        } else {
            if ((int)($this->used_count ?? 0) > 0) {
                $this->decrement('used_count');
            }
        }
    }

    // Route key binding
    public function getRouteKeyName()
    {
        return 'id';
    }

//    public function resolveRouteBinding($value, $field = null)
//    {
//        // Try to find by slug first, then by hashid
//        return $this->where('slug', $value)->first()
//            ?? static::findByHashid($value);
//    }

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
