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
