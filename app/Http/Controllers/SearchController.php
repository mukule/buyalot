<?php

namespace App\Http\Controllers;

use App\Models\Product;
use App\Services\FrontendProductService;
use Illuminate\Http\Request;
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

        // 1. Validation for short queries
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

        /**
         * 2. Optimized Search Query
         * query() allows us to eager load relations on the Eloquent models 
         * returned by Scout. This prevents the N+1 problem in the map() below.
         */
        $paginator = Product::search($q)
            ->where('status_id', 2)
            ->query(fn($query) => $query->with([
                'productVariants', 
                'brand', 
                'category', 
                'primaryImage'
            ]))
            ->paginate($perPage);

        /**
         * 3. Map results using Service
         * Since we used with('productVariants'), $product->productVariants is already a loaded 
         * collection. Calling ->first() here does NOT trigger a new DB query.
         */
        $mappedData = collect($paginator->items())->map(function (Product $product) {
            $variant = $product->productVariants->first();

            if ($variant) {
                // Manually link the product to the variant to ensure the 
                // Normalizer doesn't re-query the product parent.
                $variant->setRelation('product', $product);
                return $this->productService->normalizeVariant($variant);
            }

            // Fallback for products without variants
            return [
                'id'                => $product->id,
                'name'              => $product->name,
                'product_slug'      => $product->slug,
                'sku'               => null,
                'brand'             => $product->brand?->name,
                'category_slug'     => $product->category?->slug ?? '',
                'primary_image_url' => $product->primaryImageUrl ?? asset('images/fallback-image.png'),
                'final_price'       => 0,
                'in_stock'          => false
            ];
        });

        // 4. Handle AJAX (Live Search Suggestions)
        if ($ajax) {
            return response()->json([
                'results' => [
                    'data' => $mappedData->values()
                ]
            ]);
        }

        // 5. Standard Search Page Response
        return Inertia::render('Frontend/SearchResults', [
            'q'       => $q,
            'results' => [
                'data'         => $mappedData,
                'total'        => $paginator->total(),
                'per_page'     => $paginator->perPage(),
                'current_page' => $paginator->currentPage(),
                'last_page'    => $paginator->lastPage(),
            ],
        ]);
    }
}