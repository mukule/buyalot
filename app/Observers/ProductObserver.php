<?php

namespace App\Observers;

use App\Models\Products\Product;
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
            // This is likely what is running if your Redis isn't configured for tags
            Cache::forget('homepage_products');
            Log::info("Standard Cache key 'homepage_products' FORGOTTEN (Tags not supported).");
        }
    }
}
