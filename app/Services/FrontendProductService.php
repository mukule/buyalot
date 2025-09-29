<?php

namespace App\Services;

use App\Models\Product;
use App\Models\ProductVariant;
use Illuminate\Contracts\Pagination\LengthAwarePaginator;

class FrontendProductService
{
    /**
     * Get products grouped by top-level category.
     *
     * @param \Illuminate\Support\Collection $categories
     * @param int $limit
     * @return \Illuminate\Support\Collection
     */
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
                      ->where('status_id', 2);
                })
                ->take($limit)
                ->get()
                ->map(fn($variant) => $this->normalizeVariant($variant));

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
                  ->where('status_id', 3);
            })
            ->orderBy('created_at', 'desc');

        return $query->paginate($perPage)
                     ->through(fn($variant) => $this->normalizeVariant($variant));
    }

   
    public function getRelatedProducts(Product $product, int $limit = 12)
    {
        if (!$product->category) {
            return collect();
        }

        $categoryIds = $product->category->getAllCategoryIds();

        $variants = ProductVariant::with([
                'product.brand',
                'product.primaryImage',
                'product.images',
            ])
            ->whereHas('product', function ($q) use ($categoryIds, $product) {
                $q->whereIn('category_id', $categoryIds)
                  ->where('status_id', 3)
                  ->where('id', '!=', $product->id);
            })
            ->take($limit)
            ->get()
            ->map(fn($variant) => $this->normalizeVariant($variant));

        return $variants;
    }

   
   
    private function normalizeVariant(ProductVariant $variant): array
{
    $product = $variant->product;

    // Use S3 URLs
    $image = $product->primaryImageUrl 
        ?? ($product->images->first()?->image_path
            ? Storage::disk('s3')->url($product->images->first()->image_path)
            : '/fallback-image.png');

    return [
        'id'                => $variant->id,
        'variant_hashid'    => $variant->hashid,
        'product_id'        => $product?->id,
        'product_slug'      => $product?->slug ?? '',
        'name'              => $variant->display_name ?? $product?->name,
        'product_name'      => $product?->name,
        'regular_price'     => $variant->regular_price,
        'selling_price'     => $variant->selling_price,
        'discount'          => $variant->discount_percent,
        'in_stock'          => $variant->in_stock,
        'brand'             => $product?->brand?->name,
        'primary_image_url' => $image,
    ];
}

}
