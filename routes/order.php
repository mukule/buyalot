<?php


use App\Http\Controllers\Orders\OrderController;
use Illuminate\Support\Facades\Route;


Route::middleware(['web','auth'])->group(function () {
    Route::get('orders/my-orders', [OrderController::class, 'myOrders'])->name('orders.index');
    Route::get('orders/my-orders/{order}', [OrderController::class, 'show'])->name('orders.show');
    // Throttle order writes (create/update/destroy) per user to curb spam/abuse.
    Route::resource('orders/my-orders', OrderController::class)->only(['store', 'update', 'destroy', 'create', 'edit'])->middleware('throttle:20,1')->parameters(['my-orders' => 'order'])->names([
        'store' => 'orders.store',
        'update' => 'orders.update',
        'destroy' => 'orders.destroy',
        'create' => 'orders.create',
        'edit' => 'orders.edit',
    ]);
});

