<?php

namespace App\Services;

use App\Models\Products\Product;
use App\Models\Products\ProductVariant;
use Illuminate\Contracts\Pagination\LengthAwarePaginator;
use Illuminate\Support\Facades\Cache;

class FrontendProductService
{
    protected \App\Services\DiscountService $discountService;

    public function __construct(\App\Services\DiscountService $discountService)
    {
        $this->discountService = $discountService;
    }

    /**
     * HOMEPAGE — High Performance with Redis
     */

    const HOMEPAGE_VERSION_KEY = 'homepage_cache_version';

    public function getProductsGroupedByCategory($categories, int $limit = 12)
{
    $version = Cache::get(self::HOMEPAGE_VERSION_KEY, 1);
    $cacheKey = "home_grouped_v{$version}_" . $categories->pluck('id')->implode('_');
    $lockKey = $cacheKey . '_lock';

    $cache = Cache::supportsTags()
        ? Cache::tags(['frontend_products', 'homepage'])
        : Cache::getFacadeRoot();

    $cachedData = $cache->get($cacheKey);
    if ($cachedData) {
        return $cachedData;
    }

    return Cache::lock($lockKey, 10)->block(5, function () use ($cache, $cacheKey, $categories, $limit) {

        // Re-check cache after acquiring lock
        $data = $cache->get($cacheKey);
        if ($data) return $data;

        // Collect all relevant category IDs
        $allCategoryIds = $categories
            ->flatMap(fn($cat) => $cat->getAllCategoryIds())
            ->unique()
            ->toArray();

        // Fetch variants
        // withoutGlobalScopes() inside whereHas prevents SellerProductScope from
        // filtering this public-cache query by the currently authenticated user.
        $allVariants = ProductVariant::query()
            ->with(['product.brand', 'product.primaryImage', 'product.category'])
            ->whereHas('product', function ($q) use ($allCategoryIds) {
                $q->withoutGlobalScopes()
                  ->whereIn('category_id', $allCategoryIds)
                  ->where('status_id', 2)
                  ->whereNotNull('slug')
                  ->where('slug', '!=', '');
            })
            ->orderBy('selling_price') // ensures cheapest variant per product is kept
            ->get()
            ->unique('product_id')     // keep only ONE variant per product
            ->values();

        $result = $categories->mapWithKeys(function ($category) use ($allVariants, $limit) {

            $specificCategoryIds = $category->getAllCategoryIds()->toArray();

            $categoryVariants = $allVariants
                ->filter(function ($variant) use ($specificCategoryIds) {
                    return $variant->product &&
                        in_array($variant->product->category_id, $specificCategoryIds);
                })
                ->take($limit);

            $priceData = $this->getPriceForVariants($categoryVariants);

            return [
                $category->id => $categoryVariants
                    ->map(fn($variant) => $this->normalizeVariant($variant, $priceData))
                    ->values(),
            ];
        });

        $cache->put($cacheKey, $result, now()->addHours(12));

        // Register key so ProductObserver can clear it on product save
        $registeredKeys = Cache::get('homepage_cache_keys', []);
        if (!in_array($cacheKey, $registeredKeys)) {
            $registeredKeys[] = $cacheKey;
            Cache::put('homepage_cache_keys', $registeredKeys, now()->addDays(2));
        }

        return $result;
    });
}

    /**
     * CATEGORY PAGE — paginated
     */
    public function getPaginatedProductsByCategory($category, int $perPage = 20): LengthAwarePaginator
    {
        $categoryIds = $category->getAllCategoryIds()->toArray();

        $query = ProductVariant::query()
            ->with(['product.brand', 'product.primaryImage', 'product.category'])
            ->whereHas('product', function ($q) use ($categoryIds) {
                $q->withoutGlobalScopes()
                  ->whereIn('category_id', $categoryIds)
                  ->where('status_id', 2)
                  ->whereNotNull('slug')
                  ->where('slug', '!=', '');
            })
            ->latest();

        $paginator = $query->paginate($perPage);
        $priceData = $this->getPriceForVariants($paginator->getCollection());

        $paginator->setCollection(
            $paginator->getCollection()->map(
                fn ($variant) => $this->normalizeVariant($variant, $priceData)
            )
        );

        return $paginator;
    }

    /**
     * FILTERED CATEGORY PAGE
     */
   public function getPaginatedProductsByCategoryIds(
    array $categoryIds,
    int $perPage = 20,
    ?float $minPrice = null,
    ?float $maxPrice = null,
    array $brandIds = []
): LengthAwarePaginator {

    // Subquery: get the minimum variant id (cheapest) per product
    $cheapestVariantIds = ProductVariant::query()
        ->selectRaw('MIN(id) as id')
        ->whereHas('product', function ($q) use ($categoryIds, $brandIds) {
            $q->withoutGlobalScopes()
              ->whereIn('category_id', $categoryIds)
              ->where('status_id', 2);
            if (!empty($brandIds)) {
                $q->whereIn('brand_id', $brandIds);
            }
        })
        ->when(!is_null($minPrice), fn ($q) => $q->where('selling_price', '>=', $minPrice))
        ->when(!is_null($maxPrice), fn ($q) => $q->where('selling_price', '<=', $maxPrice))
        ->groupBy('product_id');

    $query = ProductVariant::query()
        ->with(['product.brand', 'product.primaryImage', 'product.category'])
        ->whereIn('id', $cheapestVariantIds)
        ->when(!is_null($minPrice), fn ($q) => $q->where('selling_price', '>=', $minPrice))
        ->when(!is_null($maxPrice), fn ($q) => $q->where('selling_price', '<=', $maxPrice));

    $paginator = $query->latest()->paginate($perPage);

    $priceData = $this->getPriceForVariants($paginator->getCollection());

    $paginator->setCollection(
        $paginator->getCollection()->map(
            fn ($variant) => $this->normalizeVariant($variant, $priceData)
        )
    );

    return $paginator;
}


    /**
     * RELATED PRODUCTS
     */
  
    public function getRelatedProducts(ProductVariant $variant, int $limit = 12)
{
    $product = $variant->product;
    if (!$product?->category) return collect();

    $categoryIds = $product->category->getAllCategoryIds()->toArray();

    $variants = ProductVariant::query()
        ->with(['product.brand', 'product.primaryImage', 'product.category'])
        ->where('id', '!=', $variant->id)
        ->whereHas('product', function ($q) use ($categoryIds) {
            $q->withoutGlobalScopes()
              ->whereIn('category_id', $categoryIds)
              ->where('status_id', 2)
              ->whereNotNull('slug')
              ->where('slug', '!=', '');
        })
        ->orderBy('selling_price')   // choose cheapest variant per product
        ->get()
        ->unique('product_id')       // keep only one variant per product
        ->values()
        ->take($limit);              // limit after deduplication

    $priceData = $this->getPriceForVariants($variants);

    return $variants->map(
        fn ($v) => $this->normalizeVariant($v, $priceData)
    );
}

    /**
     * NORMALIZER — strictly using your original fields
     */
   public function normalizeVariant(ProductVariant $variant, array $priceData = []): array
{
    $product = $variant->product;

    $variantPrimaryImage = $variant->images?->firstWhere('is_primary', true);
    $image = ($variantPrimaryImage?->url)
        ?? ($variantPrimaryImage?->image_path ? asset('storage/' . $variantPrimaryImage->image_path) : null)
        ?? $product->primaryImageUrl
        ?? ($product->primaryImage?->image_path
            ? asset('storage/' . $product->primaryImage->image_path)
            : asset('images/fallback-image.png'));

    $markedPrice = $variant->marked_price ?? 0;
    $sellingPrice = $variant->selling_price ?? 0;
    $totalDiscount = $variant->discount ?? 0;

    $discountPercent = 0;
    if ($markedPrice > 0 && $totalDiscount > 0) {
        $discountPercent = round(($totalDiscount / $markedPrice) * 100, 2);
    }

    return [
        'id'                => $variant->id,
        'variant_hashid'    => $variant->hashid,
        'product_id'        => $product?->id,
        'category_slug'     => $product->category?->slug ?? '',
        'product_slug'      => $product?->slug ?? '',
        'name'              => $variant->display_name ?? $product?->name,
        'product_name'      => $product?->name,
        'marked_price'      => round($markedPrice, 2),
        'final_price'       => round($sellingPrice, 2),
        'discount_percent'  => $discountPercent,
        'has_discount'      => $totalDiscount > 0,
        'in_stock'          => $variant->in_stock,
        'brand'             => $product?->brand?->name,
        'primary_image_url' => $image,
    ];
}

    /**
     * PRICE MAP — strictly using your original logic
     */
    public function getPriceForVariants($variants): array
    {
        if ($variants->isEmpty()) return [];

        return $variants->mapWithKeys(function ($variant) {
            $markedPrice = (float) ($variant->marked_price ?? 0);
            $discountPercentage = $markedPrice > 0
                ? (int) round(($variant->discount / $markedPrice) * 100)
                : 0;

            return [
                $variant->id => [
                    'product_variant_id'  => $variant->id,
                    'marked_price'        => round($markedPrice, 2),
                    'discounts'           => [],
                    'total_discount'      => round($variant->discount, 2),
                    'discount_percentage' => $discountPercentage,
                    'final_price'         => round($variant->selling_price ?? 0, 2),
                    'has_discount'        => $variant->discount > 0,
                ],
            ];
        })->toArray();
    }

    /**
     * MARKETPLACE VERTICAL LISTING
     *
     * One cheapest variant per matching product, filtered by the vertical's
     * flexible `attributes` (equality selects + numeric ranges) and by variant
     * price. Sorting is done in PHP so it stays portable across DB drivers and
     * can order on JSON attributes (year/mileage) without driver-specific SQL.
     *
     * @param  array  $filters  [
     *     'selects'   => ['make' => 'Toyota', ...],       // attribute equality
     *     'ranges'    => ['year' => ['min'=>?, 'max'=>?]] // numeric attribute ranges
     *     'min_price' => ?float, 'max_price' => ?float,   // variant selling_price
     *     'sort'      => 'newest'|'price_asc'|'price_desc'|'year_desc'|'mileage_asc',
     * ]
     */
    public function getVerticalListing(string $verticalKey, array $filters = [], int $perPage = 24): LengthAwarePaginator
    {
        $page = \Illuminate\Pagination\Paginator::resolveCurrentPage('page');
        $path = \Illuminate\Pagination\Paginator::resolveCurrentPath();

        if ($verticalKey === '') {
            return new \Illuminate\Pagination\LengthAwarePaginator([], 0, $perPage, $page, ['path' => $path]);
        }

        $selects      = $filters['selects'] ?? [];
        $ranges       = $filters['ranges'] ?? [];
        $minPrice     = $filters['min_price'] ?? null;
        $maxPrice     = $filters['max_price'] ?? null;
        $availability = $filters['availability'] ?? null; // 'available' | 'reserved'
        $stockId      = $filters['stock_id'] ?? null;

        $applyProductFilters = function ($q) use ($verticalKey, $selects, $ranges, $availability, $stockId) {
            $q->withoutGlobalScopes()
              ->whereJsonContains('marketplaces', $verticalKey)
              ->where('status_id', 2)
              ->whereNotNull('slug')
              ->where('slug', '!=', '');

            foreach ($selects as $key => $value) {
                if ($value !== null && $value !== '') {
                    $q->where("attributes->{$key}", $value);
                }
            }
            foreach ($ranges as $key => $range) {
                // Attribute values live in a JSON column and are frequently stored
                // as strings (the product form submits them as strings). A plain
                // `attributes->key >= ?` comparison then binds as text and MySQL
                // compares lexicographically — e.g. "9000" > "50000" — which breaks
                // numeric range filters. Cast the extracted value to a number so the
                // comparison is always numeric regardless of how it was stored.
                // $key comes from the trusted marketplace config, so it is safe to
                // interpolate; we still guard against anything unexpected.
                if (! preg_match('/^[A-Za-z0-9_]+$/', $key)) {
                    continue;
                }
                $numeric = "CAST(JSON_UNQUOTE(JSON_EXTRACT(`attributes`, '$.\"{$key}\"')) AS DECIMAL(20,4))";

                if (($range['min'] ?? null) !== null) {
                    $q->whereRaw("{$numeric} >= ?", [$range['min']]);
                }
                if (($range['max'] ?? null) !== null) {
                    $q->whereRaw("{$numeric} <= ?", [$range['max']]);
                }
            }

            // Reservation status (a listing is reserved when reserved_at is set).
            if ($availability === 'available') {
                $q->whereNull('reserved_at');
            } elseif ($availability === 'reserved') {
                $q->whereNotNull('reserved_at');
            }

            // Stock ID search matches the product_code.
            if ($stockId !== null && $stockId !== '') {
                $q->where('product_code', 'like', '%' . $stockId . '%');
            }
        };

        $inStock = $availability === 'in_stock';

        // Cheapest variant id per matching product (mirrors getPaginatedProductsByCategoryIds).
        $cheapestVariantIds = ProductVariant::query()
            ->selectRaw('MIN(id) as id')
            ->whereHas('product', $applyProductFilters)
            ->when(! is_null($minPrice), fn ($q) => $q->where('selling_price', '>=', $minPrice))
            ->when(! is_null($maxPrice), fn ($q) => $q->where('selling_price', '<=', $maxPrice))
            ->when($inStock, fn ($q) => $q->where('stock', '>', 0))
            ->groupBy('product_id');

        $variants = ProductVariant::query()
            ->with(['product.brand', 'product.primaryImage', 'product.category', 'images'])
            ->whereIn('id', $cheapestVariantIds)
            ->when(! is_null($minPrice), fn ($q) => $q->where('selling_price', '>=', $minPrice))
            ->when(! is_null($maxPrice), fn ($q) => $q->where('selling_price', '<=', $maxPrice))
            ->get();

        $priceData = $this->getPriceForVariants($variants);

        $items = $variants
            ->map(fn ($variant) => $this->normalizeVerticalVariant($variant, $priceData));

        $items = $this->sortVerticalItems($items, $filters['sort'] ?? 'newest')->values();

        $slice = $items->forPage($page, $perPage)->values();

        return new \Illuminate\Pagination\LengthAwarePaginator(
            $slice,
            $items->count(),
            $perPage,
            $page,
            ['path' => $path]
        );
    }

    protected function sortVerticalItems($items, string $sort)
    {
        return match ($sort) {
            'price_asc'   => $items->sortBy('final_price'),
            'price_desc'  => $items->sortByDesc('final_price'),
            'year_desc'   => $items->sortByDesc(fn ($i) => (int) ($i['attributes']['year'] ?? 0)),
            'mileage_asc' => $items->sortBy(fn ($i) => (int) ($i['attributes']['mileage'] ?? PHP_INT_MAX)),
            default       => $items->sortByDesc('id'), // newest first (variant id proxy)
        };
    }

    /**
     * Card shape for a vertical listing = the standard card + the product's
     * flexible attributes (make/year/mileage/material/etc.) for chip display.
     */
    public function normalizeVerticalVariant(ProductVariant $variant, array $priceData = []): array
    {
        $base = $this->normalizeVariant($variant, $priceData);
        $product = $variant->product;
        $attributes = $product?->attributes ?? [];

        $base['attributes'] = $attributes;
        $base['location']   = $attributes['location'] ?? null;
        $base['stock_id']   = $product?->product_code;
        $base['reserved']   = $product?->reserved_at !== null;

        return $base;
    }

    /**
     * Distinct values per attribute key, for populating the vertical's select
     * filter dropdowns.
     */
    public function getVerticalFilterOptions(string $verticalKey, array $selectKeys): array
    {
        $options = [];
        if ($verticalKey === '' || empty($selectKeys)) {
            return $options;
        }

        $products = Product::withoutGlobalScopes()
            ->whereJsonContains('marketplaces', $verticalKey)
            ->where('status_id', 2)
            ->get(['id', 'attributes']);

        foreach ($selectKeys as $key) {
            $options[$key] = $products
                ->map(fn ($p) => $p->attributes[$key] ?? null)
                ->filter(fn ($v) => $v !== null && $v !== '')
                ->unique()
                ->sort(SORT_NATURAL | SORT_FLAG_CASE)
                ->values()
                ->all();
        }

        return $options;
    }
}
