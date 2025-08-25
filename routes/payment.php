<?php

use App\Http\Controllers\Payments\PaymentController;
use Illuminate\Support\Facades\Route;

Route::prefix('payments')->name('payments.')->group(function () {
    Route::get('providers', [PaymentController::class, 'providers'])->name('providers');
    Route::post('initiate', [PaymentController::class, 'initiate'])->name('initiate');
    Route::get('{payment}/status', [PaymentController::class, 'status'])->name('status');

    // Callbacks
    Route::post('callback/{provider}', [PaymentController::class, 'callback'])->name('callback');
});
