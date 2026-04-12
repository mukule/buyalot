<?php

namespace App\Observers;

use App\Models\Products\Product;
use App\Services\FrontendProductService;
use App\Services\SearchCacheService;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\Log;

class ProductObserver
{
    public function saved(Product $product): void
    {
        Log::info("--- Product Observer: Saved ---", [
            'id' => $product->id,
            'name' => $product->name,
            'status_id' => $product->status_id,
            'was_recently_created' => $product->wasRecentlyCreated,
        ]);

        $this->handleInvalidation($product);
    }

    public function deleted(Product $product): void
    {
        Log::warning("--- Product Observer: Deleted ---", [
            'id' => $product->id,
            'name' => $product->name
        ]);

        $product->unsearchable();
        $this->handleInvalidation($product);
    }

    protected function handleInvalidation(Product $product): void
    {
        // 1. Update Meilisearch
        try {
            $product->searchable();
            Log::info("Meilisearch sync triggered for Product ID: {$product->id}");
        } catch (\Exception $e) {
            Log::error("Meilisearch sync FAILED for Product ID: {$product->id}", ['error' => $e->getMessage()]);
        }

        // 2. Cache Invalidation
        if (Cache::supportsTags()) {
            Cache::tags(['frontend_products', 'homepage'])->flush();
            Log::info("Cache tags ['frontend_products', 'homepage'] FLUSHED.");
        } else {
            // Bump the version key — forces FrontendProductService to build a fresh
            // cache entry on the next request. Works on any driver without tag support.
            $version = Cache::get(FrontendProductService::HOMEPAGE_VERSION_KEY, 1);
            Cache::put(FrontendProductService::HOMEPAGE_VERSION_KEY, $version + 1, now()->addDays(30));
            Log::info("Homepage cache version bumped to " . ($version + 1) . " (Tags not supported).");
        }

        // 3. Update SearchCacheService (for cPanel compatibility)
        try {
            SearchCacheService::refreshProduct($product);
            Log::info("SearchCacheService refreshed for Product ID: {$product->id}");
        } catch (\Exception $e) {
            Log::error("SearchCacheService refresh FAILED for Product ID: {$product->id}", ['error' => $e->getMessage()]);
        }
    }


}
