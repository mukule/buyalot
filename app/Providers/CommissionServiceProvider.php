<?php

namespace App\Providers;

use App\Models\Orders\Order;
use App\Observers\OrderObserver;
use Illuminate\Support\ServiceProvider;

class CommissionServiceProvider extends ServiceProvider
{
    /**
     * Register services.
     */
    public function register(): void
    {
        $this->app->singleton(\App\Services\CommissionCalculatorService::class);
        $this->app->singleton(\App\Services\CommissionInvoiceService::class);
    }


    /**
     * Bootstrap services.
     */
    public function boot(): void
    {
        Order::observe(OrderObserver::class);
    }
}
