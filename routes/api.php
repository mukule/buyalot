<?php


use App\Http\Controllers\Admin\DiscountController;
use App\Http\Controllers\Api\ApiAuthController;
use App\Http\Controllers\Orders\OrderController;
use App\Http\Controllers\Billing\InvoiceController as BillingInvoiceController;
use App\Http\Controllers\Billing\ReceiptController as BillingReceiptController;
use Illuminate\Support\Facades\Route;


Route::post('login', [ApiAuthController::class, 'login']);

//Route::prefix('payments')->name('payments.')->group(function () {
//    Route::get('providers', [PaymentTransactionController::class, 'providers'])->name('providers');
//    Route::post('initiate', [PaymentTransactionController::class, 'initiate'])->name('initiate');
//    Route::get('{payment}/status', [PaymentTransactionController::class, 'status'])->name('status');
//    Route::post('callback/{provider}', [PaymentTransactionController::class, 'callback'])->name('callback');
//});

Route::prefix('v1')->middleware(['auth:sanctum'])->group(function () {
//Route::prefix('v1')->group(function () {
//    Route::post('commissions/calculate', [CommissionController::class, 'calculate']);
//    Route::get('commissions/summary', [CommissionController::class, 'summary']);
//    require __DIR__ .'/payment.php';

    Route::post('/discounts/calculate', [DiscountController::class, 'calculateDiscounts'])
        ->name('discounts.calculate');

    Route::apiResource('orders', OrderController::class)->names([
        'index'   => 'api.orders.index',
        'store'   => 'api.orders.store',
        'show'    => 'api.orders.show',
        'update'  => 'api.orders.update',
        'destroy' => 'api.orders.destroy',
    ]);
    Route::post('orders/bulk-update', [OrderController::class, 'bulkUpdate']);
    Route::post('orders/create', [OrderController::class, 'store'])->name('orders.custom_store');

    // Billing: invoices & payments
    Route::get('invoices', [BillingInvoiceController::class, 'index'])->name('api.invoices.index');
    Route::post('invoices', [BillingInvoiceController::class, 'store'])->name('api.invoices.store');
    Route::get('invoices/{invoice}', [BillingInvoiceController::class, 'show'])->name('api.invoices.show');
    Route::post('invoices/{invoice}/issue', [BillingInvoiceController::class, 'issue'])->name('api.invoices.issue');
    Route::post('invoices/{invoice}/convert-to-invoice', [BillingInvoiceController::class, 'convertToInvoice'])->name('api.invoices.convert_to_invoice');
    Route::post('invoices/{invoice}/allocate', [BillingInvoiceController::class, 'allocate'])->name('api.invoices.allocate');
    Route::post('invoices/{invoice}/allocate-payment', [BillingInvoiceController::class, 'allocatePayment'])->name('api.invoices.allocate_payment');

    // Receipts
    Route::post('receipts', [BillingReceiptController::class, 'store'])->name('api.receipts.store');
    Route::post('receipts/{receipt}/allocate', [BillingReceiptController::class, 'allocate'])->name('api.receipts.allocate');

});
