<?php


use App\Http\Controllers\Admin\DiscountController;
use App\Http\Controllers\Api\ApiAuthController;
use App\Http\Controllers\Orders\OrderController;
use App\Http\Controllers\Admin\SalesReportController;
use App\Http\Controllers\Billing\CreditNoteController;
use App\Http\Controllers\Billing\DeliveryNoteController;
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
    Route::post('invoices/{invoice}/etims-signed', [BillingInvoiceController::class, 'etimsSigned'])->name('api.invoices.etims_signed');
    Route::post('invoices/{invoice}/etims-failed', [BillingInvoiceController::class, 'etimsFailed'])->name('api.invoices.etims_failed');
    Route::post('invoices/{invoice}/allocate', [BillingInvoiceController::class, 'allocate'])->name('api.invoices.allocate');
    Route::post('invoices/{invoice}/allocate-payment', [BillingInvoiceController::class, 'allocatePayment'])->name('api.invoices.allocate_payment');

    // Receipts
    Route::post('receipts', [BillingReceiptController::class, 'store'])->name('api.receipts.store');
    Route::post('receipts/{receipt}/allocate', [BillingReceiptController::class, 'allocate'])->name('api.receipts.allocate');

    // Credit notes (linked to invoices)
    Route::get('credit-notes', [CreditNoteController::class, 'index'])->name('api.credit-notes.index');
    Route::post('credit-notes', [CreditNoteController::class, 'store'])->name('api.credit-notes.store');
    Route::get('credit-notes/{credit_note}', [CreditNoteController::class, 'show'])->name('api.credit-notes.show');

    // Delivery notes (linked to invoices)
    Route::get('delivery-notes', [DeliveryNoteController::class, 'index'])->name('api.delivery-notes.index');
    Route::post('delivery-notes', [DeliveryNoteController::class, 'store'])->name('api.delivery-notes.store');
    Route::get('delivery-notes/{delivery_note}', [DeliveryNoteController::class, 'show'])->name('api.delivery-notes.show');
    Route::patch('delivery-notes/{delivery_note}/status', [DeliveryNoteController::class, 'updateStatus'])->name('api.delivery-notes.update-status');

    // Sales reports (daily, weekly, monthly, by source, VAT, payments, voided sales)
    Route::get('reports/sales', [SalesReportController::class, 'report'])->name('api.reports.sales');
    Route::get('reports/sales/daily', [SalesReportController::class, 'daily'])->name('api.reports.sales.daily');
    Route::get('reports/sales/weekly', [SalesReportController::class, 'weekly'])->name('api.reports.sales.weekly');
    Route::get('reports/sales/monthly', [SalesReportController::class, 'monthly'])->name('api.reports.sales.monthly');
    Route::get('reports/sales/voided', [SalesReportController::class, 'voidedSales'])->name('api.reports.sales.voided');
});

// POS API (stateless, JSON; login with username + PIN + optional terminal_id)
require __DIR__ . '/api/pos.php';
