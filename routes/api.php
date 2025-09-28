<?php


use App\Http\Controllers\Commission\CommissionController;
use App\Http\Controllers\Orders\OrderController;
use App\Http\Controllers\Payments\PaymentTransactionController;
use Illuminate\Support\Facades\Route;


Route::prefix('payments')->name('payments.')->group(function () {
    Route::get('providers', [PaymentTransactionController::class, 'providers'])->name('providers');
    Route::post('initiate', [PaymentTransactionController::class, 'initiate'])->name('initiate');
    Route::get('{payment}/status', [PaymentTransactionController::class, 'status'])->name('status');

    // Callbacks
    Route::post('callback/{provider}', [PaymentTransactionController::class, 'callback'])->name('callback');
});

//Route::prefix('v1')->middleware(['auth:api'])->group(function () {
Route::prefix('v1')->group(function () {
//    Route::post('commissions/calculate', [CommissionController::class, 'calculate']);
//    Route::get('commissions/summary', [CommissionController::class, 'summary']);
//    require __DIR__ .'/payment.php';
//    Route::apiResource('orders', OrderController::class)->names([
//        'index'   => 'api.orders.index',
//        'store'   => 'api.orders.store',
//        'show'    => 'api.orders.show',
//        'update'  => 'api.orders.update',
//        'destroy' => 'api.orders.destroy',
//    ]);
//        Route::post('orders/bulk-update', [OrderController::class, 'bulkUpdate']);
//        Route::post('orders/create', [OrderController::class, 'store'])->name('orders.custom_store');

});
