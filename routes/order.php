<?php


use App\Http\Controllers\Orders\OrderController;
use Illuminate\Support\Facades\Route;


Route::middleware(['web','auth'])->prefix('orders')->name('orders.')->group(function () {
    Route::get('/my-orders', [OrderController::class, 'myOrders'])->name('my-orders');
    // Use empty string for resource path within the prefixed group to avoid double slashes
    Route::resource('', OrderController::class)->parameters(['' => 'orders']);
});

