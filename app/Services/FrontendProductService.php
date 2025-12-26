<?php

namespace App\Services;

use App\Models\Product;
use App\Models\ProductVariant;
use Illuminate\Contracts\Pagination\LengthAwarePaginator;
use Illuminate\Support\Facades\Storage;

class FrontendProductService
{
    protected \App\Services\DiscountService $discountService;

    public function __construct(\App\Services\DiscountService $discountService)
    {
        $this->discountService = $discountService;
    }

    public function getProductsGroupedByCategory($categories, int $limit = 12)
    {
        return $categories->mapWithKeys(function ($category) use ($limit) {
            $categoryIds = $category->getAllCategoryIds();

            $variants = ProductVariant::with([
                    'product.brand',
                    'product.primaryImage',
                    'product.images',
                ])
                ->whereHas('product', function ($q) use ($categoryIds) {
                    $q->whereIn('category_id', $categoryIds)
                      ->where('status_id', 2)
                      ->whereNotNull('slug')
                      ->where('slug', '!=', '');
                })
                ->take($limit)
                ->get();

            $priceData = $this->getPriceForVariants($variants);

            $variants = $variants->map(fn($variant) => $this->normalizeVariant($variant, $priceData));

            return [$category->id => $variants];
        });
    }

    public function getPaginatedProductsByCategory($category, int $perPage = 20): LengthAwarePaginator
    {
        $categoryIds = $category->getAllCategoryIds();

        $query = ProductVariant::with([
                'product.brand',
                'product.primaryImage',
                'product.images',
            ])
            ->whereHas('product', function ($q) use ($categoryIds) {
                $q->whereIn('category_id', $categoryIds)
                  ->where('status_id', 2)
                  ->whereNotNull('slug')
                  ->where('slug', '!=', '');
            })
            ->orderBy('created_at', 'desc');

        $paginator = $query->paginate($perPage);

        $priceData = $this->getPriceForVariants($paginator->getCollection());

        $paginator->setCollection(
            $paginator->getCollection()->map(fn($variant) => $this->normalizeVariant($variant, $priceData))
        );

        return $paginator;
    }




public function getPaginatedProductsByCategoryIds(
    array $categoryIds,
    int $perPage = 20,
    ?float $minPrice = null,
    ?float $maxPrice = null,
    array $brandIds = []
): LengthAwarePaginator {
    $query = ProductVariant::with([
        'product.brand',
        'product.primaryImage',
        'product.images',
    ])
    ->whereHas('product', function ($q) use ($categoryIds, $brandIds) {
        $q->whereIn('category_id', $categoryIds)
          ->where('status_id', 2);

        if (!empty($brandIds)) {
            $q->whereIn('brand_id', $brandIds);
        }
    });

    if (!is_null($minPrice)) {
        $query->where('marked_price', '>=', $minPrice);
    }

    if (!is_null($maxPrice)) {
        $query->where('marked_price', '<=', $maxPrice);
    }

    $query->orderBy('created_at', 'desc');

    $paginator = $query->paginate($perPage);

    $priceData = $this->getPriceForVariants($paginator->getCollection());

    $paginator->setCollection(
        $paginator->getCollection()->map(fn($variant) => $this->normalizeVariant($variant, $priceData))
    );

    return $paginator;
}




    public function getRelatedProducts(ProductVariant $variant, int $limit = 12)
{
    $product = $variant->product;

    if (!$product->category) {
        return collect();
    }

    $category = $product->category;
    $collectedVariants = collect();

    $categoryIds = $category->getAllCategoryIds();

    $collectedVariants = $this->fetchRelatedVariants($variant, $categoryIds, $limit);

    if ($collectedVariants->count() < $limit) {
        $currentParent = $category->parent;

        while ($currentParent && $collectedVariants->count() < $limit) {
            $parentCategoryIds = $currentParent->getAllCategoryIds();
            $additional = $this->fetchRelatedVariants($variant, $parentCategoryIds, $limit, $collectedVariants);
            $collectedVariants = $collectedVariants->merge($additional);
            $currentParent = $currentParent->parent;
        }
    }

    $collectedVariants = $collectedVariants->unique('id')->take($limit);
    $priceData = $this->getPriceForVariants($collectedVariants);

    return $collectedVariants->map(fn($v) => $this->normalizeVariant($v, $priceData));
}

protected function fetchRelatedVariants(ProductVariant $variant, $categoryIds, $limit, $existing = null)
{
    $existingIds = $existing?->pluck('id') ?? collect();

    return ProductVariant::with([
            'product.brand',
            'product.primaryImage',
            'product.images',
        ])
        ->whereNotIn('id', $existingIds)
        ->where('id', '!=', $variant->id)
        ->whereHas('product', function ($q) use ($categoryIds) {
            $q->whereIn('category_id', $categoryIds)
              ->where('status_id', 2)
              ->whereNotNull('slug')
              ->where('slug', '!=', '');
        })
        ->take($limit)
        ->get();
}


private function normalizeVariant(ProductVariant $variant, array $priceData = []): array
{
    $product = $variant->product;

    $image = $product->primaryImageUrl
        ?? ($product->images->first()?->image_path
            ? Storage::disk('s3')->url($product->images->first()->image_path)
            : '/fallback-image.png');

    $priceInfo = $priceData[$variant->id] ?? [];

    $markedPrice = $variant->marked_price ?? 0;
    $totalDiscount = $priceInfo['total_discount'] ?? 0;

    // Compute final price by subtracting discount from marked price
    $finalPrice = max(0, $markedPrice - $totalDiscount);

    $discountPercent = 0;
    if ($markedPrice > 0 && $totalDiscount > 0) {
        $discountPercent = round(($totalDiscount / $markedPrice) * 100, 2);
    }

    return [
        'id'                => $variant->id,
        'variant_hashid'    => $variant->hashid,
        'product_id'        => $product?->id,
        'product_slug'      => $product?->slug ?? '',
        'name'              => $variant->display_name ?? $product?->name,
        'product_name'      => $product?->name,
        'marked_price'      => round($markedPrice, 2),
        'final_price'       => round($finalPrice, 2),
        'discount_percent'  => $discountPercent,
        'has_discount'      => $totalDiscount > 0,
        'in_stock'          => $variant->in_stock,
        'brand'             => $product?->brand?->name,
        'primary_image_url' => $image,
    ];
}


    public function getPriceForVariants($variants): array
    {
        $variantIds = $variants->pluck('id')->all();

        if (empty($variantIds)) {
            return [];
        }

        $results = $this->discountService->calculateDiscounts($variantIds);

        return collect($results)->keyBy('product_variant_id')->toArray();
    }
}
