<?php

use App\Http\Controllers\Payments\PaymentController;
use App\Http\Controllers\Payments\PaymentTransactionController;
use Illuminate\Support\Facades\Route;

Route::prefix('api')->name('api.')->group(function () {
    Route::prefix('payments')->name('payments.')->group(function () {
        Route::get('providers', [PaymentTransactionController::class, 'providers'])->name('providers');
        Route::post('initiate', [PaymentTransactionController::class, 'initiate'])->name('initiate');
        Route::get('{payment}/status', [PaymentTransactionController::class, 'status'])->name('status');

        // Callbacks
        Route::post('callback/{provider}', [PaymentTransactionController::class, 'callback'])->name('callback');
    });
});
