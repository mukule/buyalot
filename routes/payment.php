<?php

use App\Http\Controllers\Payments\MpesaPaymentController;
use App\Http\Controllers\Payments\PaymentController;
use Illuminate\Foundation\Http\Middleware\VerifyCsrfToken as VerifyCsrfTokenMiddleware;
use Illuminate\Support\Facades\Route;

Route::prefix('api/v1')->name('api.v1.')->group(function () {
    Route::get('/payments/providers', [PaymentController::class, 'providers'])->name('payments.providers');

    // Verify a payment status
    Route::get('/payments/{payment}/verify', [PaymentController::class, 'verify'])->name('payments.verify');
    Route::post('/payments/mpesa/callback', [MpesaPaymentController::class, 'mpesaCallback']);

    Route::post('/payments/callback/{provider}', [PaymentController::class, 'callback'])->withoutMiddleware([VerifyCsrfTokenMiddleware::class])->name('payments.callback');
});
