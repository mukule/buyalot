<?php

namespace App\Services;

use App\Models\Brand;
use App\Models\Category;
use App\Models\Products\Product;
use App\Models\Products\ProductVariant;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\Log;
use Psr\SimpleCache\InvalidArgumentException;

class SearchCacheService
{
    const CACHE_KEY = 'search:data:v1';
    const CACHE_TTL = 21600;

    public static function rebuild(): void
    {
        $data = [
            'products' => Product::withoutGlobalScopes()
                ->select('id', 'name', 'slug', 'brand_id', 'category_id', 'status_id')
                ->with(['brand:id,name','primaryImage:id,product_id,image_path,is_primary'])
                ->where('status_id', 2)
                ->whereNotNull('slug')
                ->where('slug', '!=', '')
                ->get()
                ->map(function ($p) {
                return [
                    'id' => $p->id,
                    'name' => $p->name,
                    'slug' => $p->slug,
                    'brand' => $p->brand->name ?? null,
                    'primary_image_url'=> $p->primaryImage?->image_path ?? '/assets/images/logo.png',
                    'category_id' => $p->category_id,
                    'status_id' => $p->status_id,
                ];
            })->toArray(),

            'variants' => ProductVariant::whereHas('product', function ($q) {
                $q->withoutGlobalScopes()->where('status_id', 2)->whereNotNull('slug')->where('slug', '!=', '');
            })
                ->select('id', 'product_id', 'sku', 'regular_price', 'selling_price', 'stock')
                ->get()
                ->toArray(),
            'categories' => Category::select('id', 'name', 'slug', 'parent_id')->get()->toArray(),

            'brands' => Brand::select('id', 'name')->get()->toArray(),
        ];

        // Save in file cache (cPanel compatible)
        Cache::put(self::CACHE_KEY, $data, self::CACHE_TTL);
    }

    /**
     * Partial refresh: update or insert a single brand
     */
    public static function refreshBrand(Brand $brand): void
    {
        $cache = self::get();

        // Ensure 'brands' exists and is an array
        $cache['brands'] = $cache['brands'] ?? [];

        // Remove old entry if exists
        $cache['brands'] = array_filter($cache['brands'], fn($b) => $b['id'] !== $brand->id);

        // Add updated brand
        $cache['brands'][] = ['id' => $brand->id, 'name' => $brand->name];

        // Update related products
        $brandProducts = Product::withoutGlobalScopes()->where('brand_id', $brand->id)->with(['brand:id,name','primaryImage:id,product_id,image_path,is_primary'])->get();
        foreach ($brandProducts as $p) {
            //get primary image url
            $cache['products'] = $cache['products'] ?? [];
            $key = array_search($p->id, array_column($cache['products'], 'id'));
            $productData = [
                'id' => $p->id,
                'name' => $p->name,
                'slug' => $p->slug,
                'brand' => $p->brand->name ?? null,
                'primary_image_url'=> $p->primaryImage?->image_path ?? '/assets/images/logo.png',
                'category_id' => $p->category_id,
                'status_id' => $p->status_id,
            ];
            if ($key !== false) {
                $cache['products'][$key] = $productData;
            } else {
                $cache['products'][] = $productData;
            }
        }

        Cache::put(self::CACHE_KEY, $cache, now()->addMonths(6));
    }


    /**
     * Partial refresh: update or insert a single category
     */
    public static function refreshCategory(Category $category): void
    {
        $cache = self::get();
        $cache['categories'] = $cache['categories'] ?? [];
        $cache['products']   = $cache['products'] ?? [];
        // Update or insert category
        $cache['categories'] = array_filter($cache['categories'], fn($c) => $c['id'] !== $category->id);
        $cache['categories'][] = [
            'id' => $category->id,
            'name' => $category->name,
            'slug' => $category->slug,
            'parent_id' => $category->parent_id,
        ];

        // Update products for this category
        $categoryProducts = Product::withoutGlobalScopes()->where('category_id', $category->id)->with(['brand:id,name','primaryImage:id,product_id,image_path,is_primary'])->get();
        foreach ($categoryProducts as $p) {
            $key = array_search($p->id, array_column($cache['products'], 'id'));
            $productData = [
                'id' => $p->id,
                'name' => $p->name,
                'slug' => $p->slug,
                'brand' => $p->brand->name ?? null,
                'primary_image_url'=> $p->primaryImage?->image_path ?? '/assets/images/logo.png',
                'category_id' => $p->category_id,
                'status_id' => $p->status_id,
            ];

            if ($key !== false) {
                $cache['products'][$key] = $productData;
            } else {
                $cache['products'][] = $productData;
            }
        }

        Cache::put(self::CACHE_KEY, $cache, self::CACHE_TTL);
    }

    /**
     * Partial refresh: update or insert a single product
     */
    public static function refreshProduct(Product $product): void
    {
        $cache = self::get();

        if (!$cache) {
            self::rebuild();
            $cache = self::get();
        }

        $cache['products'] = $cache['products'] ?? [];
        $cache['variants'] = $cache['variants'] ?? [];

        // Check if the product is active/published using withoutGlobalScopes to avoid
        // SellerProductScope filtering out products when called in a seller auth context.
        $isActive = Product::withoutGlobalScopes()
            ->where('id', $product->id)
            ->where('status_id', 2)
            ->whereNotNull('slug')
            ->where('slug', '!=', '')
            ->exists();

        // If not active, remove from cache
        if (!$isActive) {
            $cache['products'] = array_filter($cache['products'] ?? [], fn($p) => $p['id'] !== $product->id);
            $cache['variants'] = array_filter($cache['variants'] ?? [], fn($v) => $v['product_id'] !== $product->id);
            Cache::put(self::CACHE_KEY, $cache, self::CACHE_TTL);
            Log::info("Product {$product->id} removed from search cache (not active)");
            return;
        }

        // Load relationships if not already loaded
        if (!$product->relationLoaded('brand')) {
            $product->load('brand:id,name');
        }
        if (!$product->relationLoaded('primaryImage')) {
            $product->load('primaryImage:id,product_id,image_path,is_primary');
        }

        // Update or insert product
        $key = array_search($product->id, array_column($cache['products'], 'id'));
        $productData = [
            'id' => $product->id,
            'name' => $product->name,
            'slug' => $product->slug,
            'brand' => $product->brand->name ?? null,
            'primary_image_url'=> $product->primaryImage?->image_path ?? '/assets/images/logo.png',
            'category_id' => $product->category_id,
            'status_id' => $product->status_id,
        ];

        if ($key !== false) {
            $cache['products'][$key] = $productData;
        } else {
            $cache['products'][] = $productData;
        }

        // Update variants
        $productVariants = $product->productVariants()->get(['id', 'product_id', 'sku', 'regular_price', 'selling_price', 'stock']);
        foreach ($productVariants as $v) {
            $vKey = array_search($v->id, array_column($cache['variants'], 'id'));
            $variantData = $v->toArray();

            if ($vKey !== false) {
                $cache['variants'][$vKey] = $variantData;
            } else {
                $cache['variants'][] = $variantData;
            }
        }

        Cache::put(self::CACHE_KEY, $cache, self::CACHE_TTL);
        Log::info("Product {$product->id} refreshed in search cache");
    }


    /**
     * @throws InvalidArgumentException
     */
    public static function get(): ?array
    {
        return Cache::get(self::CACHE_KEY);
    }

    public static function forget(): void
    {
        Cache::forget(self::CACHE_KEY);
    }

    public static function refresh(): void
    {
        self::forget();
        self::rebuild();
    }
}
