<?php

namespace App\Observers;

use App\Models\Products\Product;
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

        // 2. Cache Invalidation with specific logs
        if (Cache::supportsTags()) {
            Cache::tags(['frontend_products', 'homepage'])->flush();
            Log::info("Cache tags ['frontend_products', 'homepage'] FLUSHED.");
        } else {
            // For file/database cache without tag support - clear all homepage cache keys
            $keys = Cache::get('homepage_cache_keys', []);
            foreach ($keys as $key) {
                Cache::forget($key);
            }
            Cache::forget('homepage_products');
            Cache::forget('homepage_cache_keys');

            // Also clear any home_grouped_v* cache keys by pattern
            $this->clearCacheByPattern('home_grouped_v');

            Log::info("Homepage cache keys CLEARED (Tags not supported).");
        }

        // 3. Update SearchCacheService (for cPanel compatibility)
        try {
            SearchCacheService::refreshProduct($product);
            Log::info("SearchCacheService refreshed for Product ID: {$product->id}");
        } catch (\Exception $e) {
            Log::error("SearchCacheService refresh FAILED for Product ID: {$product->id}", ['error' => $e->getMessage()]);
        }
    }

    /**
     * Clear cache keys matching a pattern (for file/database cache)
     */
    protected function clearCacheByPattern(string $pattern): void
    {
        try {
            // For database cache, query and delete matching keys
            if (config('cache.default') === 'database') {
                \DB::table(config('cache.stores.database.table', 'cache'))
                    ->where('key', 'like', config('cache.prefix') . $pattern . '%')
                    ->delete();
            }
            // For file cache, scan and delete files
            elseif (config('cache.default') === 'file') {
                $cachePath = storage_path('framework/cache/data');
                if (is_dir($cachePath)) {
                    $files = glob($cachePath . '/*/' . md5(config('cache.prefix') . $pattern) . '*');
                    foreach ($files as $file) {
                        @unlink($file);
                    }
                }
            }
        } catch (\Exception $e) {
            Log::error("Cache pattern clear FAILED for pattern: {$pattern}", ['error' => $e->getMessage()]);
        }
    }
}
