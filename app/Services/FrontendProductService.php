<?php

namespace App\Services;

use App\Models\ProductVariant;
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
     * HOMEPAGE — High Performance Optimization
     * Single SQL Query + Redis Caching with Tagging
     */
    public function getProductsGroupedByCategory($categories, int $limit = 12)
    {
        // 1. Create a unique cache key based on the categories requested
        $cacheKey = 'home_grouped_v5_' . $categories->pluck('id')->implode('_');

        // 2. Safely use tags only if the driver (Redis) supports them
        $cache = Cache::supportsTags() 
            ? Cache::tags(['frontend_products', 'homepage']) 
            : Cache::getFacadeRoot();

        return $cache->remember($cacheKey, now()->addHours(12), function () use ($categories, $limit) {
            
            // 3. Single-Query logic: Flatten category IDs
            $allCategoryIds = $categories->flatMap(fn($cat) => $cat->getAllCategoryIds())->unique()->toArray();

            // 4. Selective SELECT: Only pull columns used by normalizeVariant to save RAM
            $allVariants = ProductVariant::query()
                ->select(['id', 'product_id', 'marked_price', 'selling_price', 'discount', 'display_name', 'created_at'])
                ->with([
                    'product' => fn($q) => $q->select(['id', 'brand_id', 'category_id', 'slug', 'name', 'status_id']),
                    'product.brand:id,name',
                    'product.primaryImage:id,product_id,image_path',
                    'product.category:id,slug'
                ])
                ->whereHas('product', function ($q) use ($allCategoryIds) {
                    $q->whereIn('category_id', $allCategoryIds)
                      ->where('status_id', 2)
                      ->whereNotNull('slug')
                      ->where('slug', '!=', '');
                })
                ->latest()
                ->get();

            // 5. Group in memory
            return $categories->mapWithKeys(function ($category) use ($allVariants, $limit) {
                $specificCategoryIds = $category->getAllCategoryIds();

                $categoryVariants = $allVariants->filter(function ($variant) use ($specificCategoryIds) {
                    return in_array($variant->product->category_id, $specificCategoryIds);
                })->take($limit);

                return [
                    $category->id => $categoryVariants->map(fn($v) => $this->normalizeVariant($v))->values(),
                ];
            });
        });
    }

    /**
     * CATEGORY PAGE — Paginated with eager loading
     */
    public function getPaginatedProductsByCategory($category, int $perPage = 20): LengthAwarePaginator
    {
        $categoryIds = $category->getAllCategoryIds();

        $paginator = ProductVariant::query()
            ->with(['product.brand', 'product.primaryImage', 'product.category'])
            ->whereHas('product', function ($q) use ($categoryIds) {
                $q->whereIn('category_id', $categoryIds)
                  ->where('status_id', 2)
                  ->whereNotNull('slug')
                  ->where('slug', '!=', '');
            })
            ->latest()
            ->paginate($perPage);

        $paginator->getCollection()->transform(fn($v) => $this->normalizeVariant($v));

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

        $query = ProductVariant::query()
            ->with(['product.brand', 'product.primaryImage', 'product.category'])
            ->whereHas('product', function ($q) use ($categoryIds, $brandIds) {
                $q->whereIn('category_id', $categoryIds)
                  ->where('status_id', 2);

                if (!empty($brandIds)) {
                    $q->whereIn('brand_id', $brandIds);
                }
            });

        if (!is_null($minPrice)) $query->where('selling_price', '>=', $minPrice);
        if (!is_null($maxPrice)) $query->where('selling_price', '<=', $maxPrice);

        $paginator = $query->latest()->paginate($perPage);
        $paginator->getCollection()->transform(fn($v) => $this->normalizeVariant($v));

        return $paginator;
    }

    /**
     * RELATED PRODUCTS
     */
    public function getRelatedProducts(ProductVariant $variant, int $limit = 12)
    {
        $product = $variant->product;
        if (!$product?->category) return collect();

        $categoryIds = $product->category->getAllCategoryIds();

        return ProductVariant::query()
            ->with(['product.brand', 'product.primaryImage', 'product.category'])
            ->where('id', '!=', $variant->id)
            ->whereHas('product', function ($q) use ($categoryIds) {
                $q->whereIn('category_id', $categoryIds)
                  ->where('status_id', 2)
                  ->whereNotNull('slug')
                  ->where('slug', '!=', '');
            })
            ->latest()
            ->take($limit)
            ->get()
            ->map(fn($v) => $this->normalizeVariant($v));
    }

    /**
     * NORMALIZER — Efficiently formats the variant data
     */
    public function normalizeVariant(ProductVariant $variant): array
    {
        $product = $variant->product;
        $primaryImage = $product->primaryImage;

        $imageUrl = $product->primaryImageUrl
            ?? ($primaryImage?->image_path
                ? asset('storage/' . $primaryImage->image_path)
                : asset('images/fallback-image.png'));

        $markedPrice = (float)($variant->marked_price ?? 0);
        $sellingPrice = (float)($variant->selling_price ?? 0);
        $totalDiscount = (float)($variant->discount ?? 0);

        $discountPercent = ($markedPrice > 0 && $totalDiscount > 0)
            ? round(($totalDiscount / $markedPrice) * 100, 2)
            : 0;

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
            'in_stock'          => (bool)($variant->in_stock ?? true),
            'brand'             => $product?->brand?->name,
            'primary_image_url' => $imageUrl,
        ];
    }
}