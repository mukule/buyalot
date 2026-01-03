<?php

namespace App\Services;

use App\Models\Product;
use App\Models\ProductVariant;
use App\Models\Category;
use App\Models\Brand;
use Illuminate\Support\Facades\Cache;

class SearchCacheService
{
    const CACHE_KEY = 'search:data:v1';

    public static function rebuild(): void
    {
        $data = [
            'products' => Product::select('id', 'name', 'slug', 'brand_id', 'category_id', 'status_id')->with('brand:id,name')->get()->map(function ($p) {
                return [
                    'id' => $p->id,
                    'name' => $p->name,
                    'slug' => $p->slug,
                    'brand' => $p->brand->name ?? null,
                    'category_id' => $p->category_id,
                    'status_id' => $p->status_id,
                ];
            })->toArray(),

            'variants' => ProductVariant::select('id', 'product_id', 'sku', 'regular_price', 'selling_price', 'stock')->get()->toArray(),

            'categories' => Category::select('id', 'name', 'slug', 'parent_id')->get()->toArray(),

            'brands' => Brand::select('id', 'name')->get()->toArray(),
        ];

        Cache::put(self::CACHE_KEY, $data, now()->addHours(6));
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
        $brandProducts = Product::where('brand_id', $brand->id)->with('brand:id,name')->get();
        foreach ($brandProducts as $p) {
            $cache['products'] = $cache['products'] ?? [];
            $key = array_search($p->id, array_column($cache['products'], 'id'));
            $productData = [
                'id' => $p->id,
                'name' => $p->name,
                'slug' => $p->slug,
                'brand' => $p->brand->name ?? null,
                'category_id' => $p->category_id,
                'status_id' => $p->status_id,
            ];
            if ($key !== false) {
                $cache['products'][$key] = $productData;
            } else {
                $cache['products'][] = $productData;
            }
        }

        Cache::put(self::CACHE_KEY, $cache, now()->addHours(6));
    }


    /**
     * Partial refresh: update or insert a single category
     */
    public static function refreshCategory(Category $category): void
    {
        $cache = self::get();

        // Update or insert category
        $cache['categories'] = array_filter($cache['categories'], fn($c) => $c['id'] !== $category->id);
        $cache['categories'][] = [
            'id' => $category->id,
            'name' => $category->name,
            'slug' => $category->slug,
            'parent_id' => $category->parent_id,
        ];

        // Update products for this category
        $categoryProducts = Product::where('category_id', $category->id)->with('brand:id,name')->get();
        foreach ($categoryProducts as $p) {
            $key = array_search($p->id, array_column($cache['products'], 'id'));
            $productData = [
                'id' => $p->id,
                'name' => $p->name,
                'slug' => $p->slug,
                'brand' => $p->brand->name ?? null,
                'category_id' => $p->category_id,
                'status_id' => $p->status_id,
            ];

            if ($key !== false) {
                $cache['products'][$key] = $productData;
            } else {
                $cache['products'][] = $productData;
            }
        }

        Cache::put(self::CACHE_KEY, $cache, now()->addHours(6));
    }

    /**
     * Partial refresh: update or insert a single product
     */
    public static function refreshProduct(Product $product): void
    {
        $cache = self::get();

        // Update or insert product
        $key = array_search($product->id, array_column($cache['products'], 'id'));
        $productData = [
            'id' => $product->id,
            'name' => $product->name,
            'slug' => $product->slug,
            'brand' => $product->brand->name ?? null,
            'category_id' => $product->category_id,
            'status_id' => $product->status_id,
        ];

        if ($key !== false) {
            $cache['products'][$key] = $productData;
        } else {
            $cache['products'][] = $productData;
        }

        // Update variants
        $productVariants = $product->variants()->get(['id', 'product_id', 'sku', 'regular_price', 'selling_price', 'stock']);
        foreach ($productVariants as $v) {
            $vKey = array_search($v->id, array_column($cache['variants'], 'id'));
            $variantData = $v->toArray();

            if ($vKey !== false) {
                $cache['variants'][$vKey] = $variantData;
            } else {
                $cache['variants'][] = $variantData;
            }
        }

        Cache::put(self::CACHE_KEY, $cache, now()->addHours(6));
    }


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
