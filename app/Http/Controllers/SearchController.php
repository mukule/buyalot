<?php

namespace App\Http\Controllers;

use App\Models\Products\Product;
use App\Services\FrontendProductService;
use Illuminate\Http\Request;
use Illuminate\Pagination\LengthAwarePaginator;
use Inertia\Inertia;

class SearchController extends Controller
{
    protected FrontendProductService $productService;

    public function __construct(FrontendProductService $productService)
    {
        $this->productService = $productService;
    }

    /**
     * Search products with Meilisearch and return normalized results
     */
    public function search(Request $request)
    {
        $q = trim($request->input('q', ''));
        $ajax = $request->boolean('ajax', false);
        $perPage = (int) $request->input('per_page', 15);
        $page = (int) $request->input('page', 1);

        if (strlen($q) < 2) {
            $emptyResults = [
                'data' => [],
                'total' => 0,
                'per_page' => $perPage,
                'current_page' => 1,
                'last_page' => 1
            ];

            if ($ajax) {
                return response()->json(['results' => $emptyResults]);
            }

            return Inertia::render('Frontend/SearchResults', [
                'q' => $q,
                'results' => $emptyResults,
                'message' => 'Please enter at least 2 characters to search.',
            ]);
        }

        // Search products via Meilisearch
        $query = Product::search($q);

        $allResults = $query->get();
        $total = $allResults->count();

        // Map results using FrontendProductService
        $mapped = $allResults->map(function (Product $product) {
            $variant = $product->productVariants()->first();

            // Use service to get normalized variant with proper image
            if ($variant) {
                $normalized = $this->productService->normalizeVariant($variant);
            } else {
                // Fallback if no variant exists
                $normalized = [
                    'id' => $product->id,
                    'name' => $product->name,
                    'product_slug' => $product->slug,
                    'sku' => null,
                    'brand' => $product->brand?->name,
                    'category' => $product->category?->name,
                    'primary_image_url' => $product->primaryImageUrl ?? '/assets/brands/no-brand.png',
                ];
            }

            return $normalized;
        });

        // AJAX suggestions
        if ($ajax) {
            return response()->json([
                'results' => [
                    'data' => $mapped->take($perPage)->values()
                ]
            ]);
        }

        // Inertia paginated results
        $paginated = new LengthAwarePaginator(
            $mapped->forPage($page, $perPage),
            $total,
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
