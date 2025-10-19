<?php

namespace App\Providers;
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
	    if (config('app.url')) {
        URL::forceRootUrl(config('app.url'));
    		}

        Inertia::share([
            'appName' => config('app.name'),
        ]);

    }
}
