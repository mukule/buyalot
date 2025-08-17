<?php

use App\Http\Controllers\Payments\PaymentController;
use Illuminate\Support\Facades\Route;

Route::post('/payments/{order}', [PaymentController::class, 'process'])
    ->name('payments.process');

Route::post('/payments/callback/{gateway}', [PaymentController::class, 'callback'])
    ->name('payments.callback');
