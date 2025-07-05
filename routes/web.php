<?php

use Illuminate\Support\Facades\Route;
use Inertia\Inertia;
use App\Http\Controllers\Admin\RoleController;
use App\Http\Controllers\Admin\PermissionController;
use App\Http\Controllers\SellController;


Route::get('/', function () {
    return Inertia::render('Home', [
        'title' => 'Online Shopping Store',
    ]);
})->name('home');



Route::get('/sell', [SellController::class, 'index'])->name('sell.index');



Route::middleware(['auth', 'admin'])->group(function () {
    Route::get('dashboard', function () {
        return Inertia::render('Dashboard');
    })->name('dashboard');

  
Route::prefix('admin')->name('admin.')->group(function () {
    Route::get('/roles', [RoleController::class, 'index'])->name('roles.index');
    Route::get('/roles/create', [RoleController::class, 'create'])->name('roles.create'); 
    Route::post('/roles', [RoleController::class, 'store'])->name('roles.store');
    
    Route::get('/roles/{role}/edit', [RoleController::class, 'edit'])->name('roles.edit');
    
    Route::put('/roles/{role}', [RoleController::class, 'update'])->name('roles.update');
    Route::delete('/roles/{role}', [RoleController::class, 'destroy'])->name('roles.destroy');
    Route::get('/roles/{role}', [RoleController::class, 'show'])->name('roles.show');
});


Route::prefix('admin')->name('admin.')->group(function () {
    Route::get('/permissions', [PermissionController::class, 'index'])->name('permissions.index');
    Route::get('/permissions/create', [PermissionController::class, 'create'])->name('permissions.create');
    Route::post('/permissions', [PermissionController::class, 'store'])->name('permissions.store');
    Route::get('/permissions/{permission}/edit', [PermissionController::class, 'edit'])->name('permissions.edit');
    Route::put('/permissions/{permission}', [PermissionController::class, 'update'])->name('permissions.update');
    Route::delete('/permissions/{permission}', [PermissionController::class, 'destroy'])->name('permissions.destroy');
});



});

require __DIR__.'/settings.php';
require __DIR__.'/auth.php';
