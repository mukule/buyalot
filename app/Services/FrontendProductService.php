<?php

namespace App\Services;

use App\Models\ProductVariant;
use Illuminate\Contracts\Pagination\LengthAwarePaginator;

class FrontendProductService
{
    protected \App\Services\DiscountService $discountService;

    public function __construct(\App\Services\DiscountService $discountService)
    {
        $this->discountService = $discountService;
    }

    /**
     * HOMEPAGE — optimized, same output
     */
    public function getProductsGroupedByCategory($categories, int $limit = 12)
    {
        return $categories->mapWithKeys(function ($category) use ($limit) {

            $categoryIds = $category->getAllCategoryIds();

            $variants = ProductVariant::query()
                ->with([
                    'product.brand',
                    'product.primaryImage',
                ])
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

            return [
                $category->id => $variants->map(
                    fn ($variant) => $this->normalizeVariant($variant, $priceData)
                ),
            ];
        });
    }

    /**
     * CATEGORY PAGE — optimized
     */
    public function getPaginatedProductsByCategory($category, int $perPage = 20): LengthAwarePaginator
    {
        $categoryIds = $category->getAllCategoryIds();

        $query = ProductVariant::query()
            ->with([
                'product.brand',
                'product.primaryImage',
            ])
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
     * FILTERED CATEGORY PAGE — optimized
     */
    public function getPaginatedProductsByCategoryIds(
        array $categoryIds,
        int $perPage = 20,
        ?float $minPrice = null,
        ?float $maxPrice = null,
        array $brandIds = []
    ): LengthAwarePaginator {

        $query = ProductVariant::query()
            ->with([
                'product.brand',
                'product.primaryImage',
            ])
            ->whereHas('product', function ($q) use ($categoryIds, $brandIds) {
                $q->whereIn('category_id', $categoryIds)
                  ->where('status_id', 2);

                if (!empty($brandIds)) {
                    $q->whereIn('brand_id', $brandIds);
                }
            });

        if (!is_null($minPrice)) {
            $query->where('selling_price', '>=', $minPrice);
        }

        if (!is_null($maxPrice)) {
            $query->where('selling_price', '<=', $maxPrice);
        }

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
     * RELATED PRODUCTS — optimized
     */
    public function getRelatedProducts(ProductVariant $variant, int $limit = 12)
    {
        $product = $variant->product;

        if (!$product?->category) {
            return collect();
        }

        $categoryIds = $product->category->getAllCategoryIds();

        $variants = ProductVariant::query()
            ->with([
                'product.brand',
                'product.primaryImage',
            ])
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
     * NORMALIZER — unchanged output
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
     * PRICE MAP — unchanged logic
     */
    public function getPriceForVariants($variants): array
    {
        if ($variants->isEmpty()) {
            return [];
        }

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
