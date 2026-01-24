<?php

use App\Models\Cart\CartReservation;
use App\Models\Products\ProductVariant;
use Illuminate\Foundation\Inspiring;
use Illuminate\Support\Facades\Artisan;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schedule;

Artisan::command('inspire', function () {
    $this->comment(Inspiring::quote());
})->purpose('Display an inspiring quote');

Schedule::call(function () {
    DB::transaction(function () {
        $expiredReservations = CartReservation::where('expires_at', '<=', now())->get();
        foreach ($expiredReservations as $reservation) {
            ProductVariant::where('id', $reservation->product_variant_id)
                ->increment('stock', $reservation->quantity);
            $reservation->delete();
        }
    });
})->everyMinute();
