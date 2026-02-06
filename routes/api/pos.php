<?php

use App\Domains\POS\Controllers\PosAuthController;
use App\Domains\POS\Controllers\PosSaleController;
use App\Domains\POS\Controllers\PosSessionController;
use App\Http\Controllers\POS\PosCustomerController;
use App\Http\Controllers\POS\PosProductController;
use App\Http\Controllers\POS\PosUnallocatedPaymentController;
use App\Http\Controllers\POS\PosVoidedSaleController;
use Illuminate\Support\Facades\Route;

/*
|--------------------------------------------------------------------------
| POS API Routes (stateless, JSON only)
|--------------------------------------------------------------------------
| All routes require POS token (auth:sanctum) after login.
| Login accepts username (email or phone) + 4-digit PIN + optional terminal_id.
| Base path: /api/pos (when loaded from api.php context).
*/

Route::prefix('pos')->name('api.pos.')->group(function () {
    Route::post('login', [PosAuthController::class, 'login'])->name('login');

    Route::middleware(['auth:sanctum'])->group(function () {
    Route::post('logout', [PosAuthController::class, 'logout'])->name('logout');

    // Session (current, open, close — JSON only)
    Route::get('session/current', [PosSessionController::class, 'current'])->name('session.current');
    Route::post('sessions/open', [PosSessionController::class, 'open'])->name('sessions.open');
    Route::post('sessions/{session}/close', [PosSessionController::class, 'close'])->name('sessions.close');

    // Products & categories (read-only for selling)
    Route::get('products', [PosProductController::class, 'index'])->name('products.index');
    Route::get('categories', [PosProductController::class, 'categories'])->name('categories.index');

    // Customers (search + quick create)
    Route::get('customers', [PosCustomerController::class, 'index'])->name('customers.index');
    Route::post('customers', [PosCustomerController::class, 'store'])->name('customers.store');

    // Sales (domain SaleService)
    Route::post('sales', [PosSaleController::class, 'store'])->name('sales.store');

    // Unsettled (unallocated) payments
    Route::get('unallocated-payments', [PosUnallocatedPaymentController::class, 'index'])->name('unallocated-payments.index');
    Route::post('unallocated-payments', [PosUnallocatedPaymentController::class, 'store'])->name('unallocated-payments.store');

    // Voided sales
    Route::get('voided-sales', [PosVoidedSaleController::class, 'index'])->name('voided-sales.index');
    Route::post('voided-sales', [PosVoidedSaleController::class, 'store'])->name('voided-sales.store');
    Route::post('voided-sales/{voidedSale}/recall', [PosVoidedSaleController::class, 'recall'])->name('voided-sales.recall');
    });
});
