<?php

namespace App\Providers;

use App\Models\Product;
use App\Models\Policy\Policy;
use App\Observers\ProductObserver;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\ServiceProvider;
use Inertia\Inertia;

class AppServiceProvider extends ServiceProvider
{
    public function register(): void
    {
        //
    }

    
    public function boot(): void
{
    Product::observe(ProductObserver::class);

    Inertia::share([
        'appName' => config('app.name'),

        'customerPolicies' => function () {
            $policies = Cache::tags(['policies'])->rememberForever('customer_policies', function () {
                return Policy::active()
                    ->customer()
                    ->orderBy('title')
                    ->get(['title', 'slug']);
            });

            // Log the result to storage/logs/laravel.log
            \Log::info('Customer policies loaded', ['policies' => $policies->toArray()]);

            return $policies;
        },
    ]);
}

}
