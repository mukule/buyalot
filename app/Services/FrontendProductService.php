<?php

namespace App\Services;

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
    public function getProductsGroupedByCategory($categories, int $limit = 12)
    {
        $cacheKey = 'home_grouped_v9_' . $categories->pluck('id')->implode('_');

        $cache = Cache::supportsTags()
            ? Cache::tags(['frontend_products', 'homepage'])
            : Cache::getFacadeRoot();

        return $cache->remember($cacheKey, now()->addHours(12), function () use ($categories, $limit) {

            // 1. Get all sub-category IDs
            $allCategoryIds = $categories->flatMap(fn($cat) => $cat->getAllCategoryIds())->unique()->toArray();

            // 2. Fetch variants without the restrictive select() that caused errors
            $allVariants = ProductVariant::query()
                ->with([
                    'product.brand',
                    'product.primaryImage',
                    'product.category'
                ])
                ->whereHas('product', function ($q) use ($allCategoryIds) {
                    $q->whereIn('category_id', $allCategoryIds)
                      ->where('status_id', 2)
                      ->whereNotNull('slug')
                      ->where('slug', '!=', '');
                })
                ->latest()
                ->get();

            // 3. Group and Normalize
            return $categories->mapWithKeys(function ($category) use ($allVariants, $limit) {
                $specificCategoryIds = $category->getAllCategoryIds()->toArray();

                $categoryVariants = $allVariants->filter(function ($variant) use ($specificCategoryIds) {
                    // Check if product exists and category matches
                    return $variant->product && in_array($variant->product->category_id, $specificCategoryIds);
                })->take($limit);

                $priceData = $this->getPriceForVariants($categoryVariants);

                return [
                    $category->id => $categoryVariants->map(
                        fn ($variant) => $this->normalizeVariant($variant, $priceData)
                    )->values(), // .values() ensures clean JSON arrays
                ];
            });
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
                $q->whereIn('category_id', $categoryIds)
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
                $q->whereIn('category_id', $categoryIds)
                  ->where('status_id', 2)
                  ->whereNotNull('slug')
                  ->where('slug', '!=', '');
            })
            ->latest()
            ->take($limit)
            ->get();

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

        $image = $product->primaryImageUrl
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
}
