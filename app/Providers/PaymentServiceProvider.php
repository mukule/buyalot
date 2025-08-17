<?php

namespace App\Providers;

use App\Gateways\CashOnDeliveryGateway;
use App\Interfaces\PaymentGatewayInterface;
use Illuminate\Support\ServiceProvider;
use MpesaGateway;

class PaymentServiceProvider extends ServiceProvider
{
    /**
     * Register services.
     */
    public function register(): void
    {
        $this->app->bind(PaymentGatewayInterface::class, function ($app) {
            $gateway = request()->get('gateway', 'mpesa');

            return match ($gateway) {
                'mpesa' => new MpesaGateway(),
                'cash_on_delivery' => new CashOnDeliveryGateway(),
                default => throw new \Exception('Invalid payment gateway'),
            };
        });
    }

    /**
     * Bootstrap services.
     */
    public function boot(): void
    {
        //
    }
}
