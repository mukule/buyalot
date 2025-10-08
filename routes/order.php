<?php


use App\Http\Controllers\Orders\OrderController;
use Illuminate\Support\Facades\Route;


Route::middleware(['web'])->prefix('orders')->name('orders.')->group(function () {
    Route::get('/my-orders', [OrderController::class, 'myOrders'])->name('my-orders');
    Route::resource('/', OrderController::class)->parameters(['' => 'orders']);
});

