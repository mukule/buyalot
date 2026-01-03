<?php

namespace App\Http\Controllers;

use App\Services\SearchCacheService;
use Illuminate\Http\Request;
use Illuminate\Support\Str;
use Illuminate\Pagination\LengthAwarePaginator;
use Inertia\Inertia;

class SearchController extends Controller
{
    /**
     * Main search entry point
     * Supports both Inertia full results and AJAX suggestions.
     */
    public function search1(Request $request)
    {
        $q = strtolower(trim($request->input('q', '')));
        $ajax = $request->boolean('ajax', false);
        $perPage = (int) $request->input('per_page', 15);
        $page = (int) $request->input('page', 1);

        if (strlen($q) < 2) {
            if ($ajax) {
                return response()->json(['results' => ['data' => []]]);
            }

            return Inertia::render('Frontend/SearchResults', [
                'q' => $q,
                'results' => [
                    'data' => [],
                    'total' => 0,
                    'per_page' => $perPage,
                    'current_page' => 1,
                    'last_page' => 1,
                ],
                'message' => 'Please enter at least 2 characters to search.',
            ]);
        }

        // 🔹 Load cached search data
        $cache = SearchCacheService::get();
        if (!$cache) {
            SearchCacheService::rebuild();
            $cache = SearchCacheService::get();
        }

        $products = collect($cache['products'] ?? []);
        $variants = collect($cache['variants'] ?? []);
        $brands = collect($cache['brands'] ?? []);
        $categories = collect($cache['categories'] ?? []);

        // 🔹 Match variants + products
        $matches = $variants->filter(function ($variant) use ($products, $q) {
            $product = $products->firstWhere('id', $variant['product_id']);
            if (!$product || ($product['status_id'] ?? null) != 2) {
                return false;
            }

            $brand = strtolower($product['brand'] ?? '');
            $name = strtolower($product['name'] ?? '');
            $sku = strtolower($variant['sku'] ?? '');

            return Str::contains($name, $q)
                || Str::contains($sku, $q)
                || Str::contains($brand, $q);
        })->map(function ($variant) use ($products, $brands, $categories) {
            $product = $products->firstWhere('id', $variant['product_id']);
            $brand = $brands->firstWhere('id', $product['brand_id'] ?? null);
            $category = $categories->firstWhere('id', $product['category_id'] ?? null);

            return [
                'id' => $variant['id'],
                'sku' => $variant['sku'],
                'name' => $product['name'],
                'product_slug' => $product['slug'],
//                'primary_image_url' => $product['primary_image_url'] ?? null,
                'primary_image_url' => $product['primary_image_url']
                    ?? $variant['primary_image_url']
                        ?? '/assets/images/logo.png',
                'brand' => $brand['name'] ?? null,
                'category' => $category['name'] ?? null,
            ];
        });

        // 🔹 Ranking
        $scored = $matches->map(function ($item) use ($q) {
            $nameScore = similar_text(strtolower($item['name']), $q);
            $skuScore = similar_text(strtolower($item['sku']), $q);
            $brandScore = similar_text(strtolower($item['brand'] ?? ''), $q);
            $item['score'] = $nameScore * 2 + $skuScore + $brandScore;
            return $item;
        })->sortByDesc('score')->values();

        // AJAX call (auto-suggest)
        if ($ajax) {
            $suggestions = $scored->take($perPage)->values();
            return response()->json(['results' => ['data' => $suggestions]]);
        }

        // Inertia paginated search results
        $paginated = new LengthAwarePaginator(
            $scored->forPage($page, $perPage),
            $scored->count(),
            $perPage,
            $page,
            ['path' => $request->url(), 'query' => $request->query()]
        );

        return Inertia::render('Frontend/SearchResults', [
            'q' => $q,
            'results' => [
                'data' => $paginated->items(),
                'total' => $paginated->total(),
                'per_page' => $paginated->perPage(),
                'current_page' => $paginated->currentPage(),
                'last_page' => $paginated->lastPage(),
            ],
        ]);
    }

    public function search(Request $request)
    {
        $q = strtolower(trim($request->input('q', '')));
        $ajax = $request->boolean('ajax', false);
        $perPage = (int) $request->input('per_page', 15);
        $page = (int) $request->input('page', 1);

        if (strlen($q) < 2) {
            $emptyResults = ['data' => [], 'total' => 0, 'per_page' => $perPage, 'current_page' => 1, 'last_page' => 1];
            if ($ajax) return response()->json(['results' => $emptyResults]);

            return Inertia::render('Frontend/SearchResults', [
                'q' => $q,
                'results' => $emptyResults,
                'message' => 'Please enter at least 2 characters to search.',
            ]);
        }

        // Load cached data
        $cache = SearchCacheService::get();
        $products = collect($cache['products'] ?? []);
        $variants = collect($cache['variants'] ?? []);
        $brands = collect($cache['brands'] ?? []);
        $categories = collect($cache['categories'] ?? []);

        $matches = collect();

        // 1️⃣ Search products directly
        $products->each(function ($product) use ($q, &$matches) {
            $name = strtolower($product['name'] ?? '');
            $brand = strtolower($product['brand'] ?? '');
            if (Str::contains($name, $q) || Str::contains($brand, $q)) {
                $matches->push($product);
            }
        });

        // 2️⃣ Search variants (return parent product)
        $variants->each(function ($variant) use ($q, $products, &$matches) {
            $sku = strtolower($variant['sku'] ?? '');
            if (Str::contains($sku, $q)) {
                $product = $products->firstWhere('id', $variant['product_id']);
                if ($product) $matches->push($product);
            }
        });

        // 3️⃣ Search brands (return all products under that brand)
        $brands->each(function ($brand) use ($q, $products, &$matches) {
            $brandName = strtolower($brand['name'] ?? '');
            if (Str::contains($brandName, $q)) {
                $brandProducts = $products->where('brand', $brand['name'])->all();
                foreach ($brandProducts as $p) $matches->push($p);
            }
        });

        // Remove duplicates by product ID
        $matches = $matches->unique('id');

        // Map products with variants, category, brand, primary image
        $results = $matches->map(function ($product) use ($variants, $brands, $categories) {
            $brand = collect($brands)->firstWhere('id', $product['brand_id'] ?? null);
            $category = collect($categories)->firstWhere('id', $product['category_id'] ?? null);
            $variant = collect($variants)->where('product_id', $product['id'])->first();

            return [
                'id' => $product['id'],
                'name' => $product['name'],
                'product_slug' => $product['slug'],
                'sku' => $variant['sku'] ?? null,
                'brand' => $brand['name'] ?? $product['brand'] ?? null,
                'category' => $category['name'] ?? null,
                'primary_image_url' => '/storage/'.$product['primary_image_url'] ?? '/storage/'.$variant['primary_image_url'] ?? '/assets/brands/no-brand.png',
            ];
        });

        // Ranking based on keyword matches
        $scored = $results->map(function ($item) use ($q) {
            $score = 0;
            $score += similar_text(strtolower($item['name']), $q) * 2; // name matches stronger
            $score += similar_text(strtolower($item['sku'] ?? ''), $q);
            $score += similar_text(strtolower($item['brand'] ?? ''), $q);
            $item['score'] = $score;
            return $item;
        })->sortByDesc('score')->values();

        // AJAX response
        if ($ajax) {
            return response()->json(['results' => ['data' => $scored->take($perPage)->values()]]);
        }

        // Inertia paginated response
        $paginated = new LengthAwarePaginator(
            $scored->forPage($page, $perPage),
            $scored->count(),
            $perPage,
            $page,
            ['path' => $request->url(), 'query' => $request->query()]
        );

        return Inertia::render('Frontend/SearchResults', [
            'q' => $q,
            'results' => [
                'data' => $paginated->items(),
                'total' => $paginated->total(),
                'per_page' => $paginated->perPage(),
                'current_page' => $paginated->currentPage(),
                'last_page' => $paginated->lastPage(),
            ],
        ]);
    }

}
