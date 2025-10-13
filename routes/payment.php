<?php

use App\Http\Controllers\Payments\PaymentController;
use Illuminate\Foundation\Http\Middleware\VerifyCsrfToken as VerifyCsrfTokenMiddleware;
use Illuminate\Support\Facades\Route;

Route::prefix('api')->name('api.')->group(function () {
    Route::get('/payments/providers', [PaymentController::class, 'providers'])->name('payments.providers');

    // Verify a payment status
    Route::get('/payments/{payment}/verify', [PaymentController::class, 'verify'])->name('payments.verify');
    Route::post('/payments/mpesa/callback', [PaymentController::class, 'mpesaCallback'])
        ->withoutMiddleware([VerifyCsrfTokenMiddleware::class])
        ->name('payments.mpesa.callback');
    Route::post('/payments/callback/mpesa', [PaymentController::class, 'mpesaCallback'])
        ->withoutMiddleware([VerifyCsrfTokenMiddleware::class])
        ->name('payments.mpesa.callback.alt');
});
