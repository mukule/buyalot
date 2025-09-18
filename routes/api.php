<?php


use App\Http\Controllers\Commission\CommissionController;
use Illuminate\Support\Facades\Route;

Route::prefix('v1')->middleware(['auth:api'])->group(function () {
    Route::post('commissions/calculate', [CommissionController::class, 'calculate']);
    Route::get('commissions/summary', [CommissionController::class, 'summary']);
//    require __DIR__ .'/payment.php';
});
