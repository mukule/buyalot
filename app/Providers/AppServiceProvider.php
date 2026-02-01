<?php

namespace App\Providers;

use App\Models\Product;
use App\Observers\ProductObserver;
use Inertia\Inertia;
use Illuminate\Support\ServiceProvider;
use Illuminate\Support\Facades\URL;

class AppServiceProvider extends ServiceProvider
{
    /**
     * Register any application services.
     */
    public function register(): void
    {
        //
    }

    /**
     * Bootstrap any application services.
     */
    public function boot(): void
    {
        // Register the Product Observer to handle Meilisearch and Redis Cache
        Product::observe(ProductObserver::class);

        // if (config('app.url')) {
        // URL::forceRootUrl(config('app.url'));
        // }

        Inertia::share([
            'appName' => config('app.name'),
        ]);
    }
}