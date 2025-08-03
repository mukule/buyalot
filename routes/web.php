<?php

use App\Http\Controllers\Admin\BrandController;
use App\Http\Controllers\Admin\CategoryController;
use App\Http\Controllers\Admin\DocumentTypeController;
use App\Http\Controllers\Admin\ProductController;
use App\Http\Controllers\Admin\SellerApplicationController;
use App\Http\Controllers\Admin\SellerVerificationController;
use App\Http\Controllers\Admin\SubcategoryController;
use App\Http\Controllers\Admin\UnitController;
use App\Http\Controllers\Admin\UnitTypeController;
use App\Http\Controllers\Admin\UserController;
use App\Http\Controllers\Admin\VariantCategoryController;
use App\Http\Controllers\Admin\WarehouseController;
use App\Http\Controllers\SellController;
use App\Http\Controllers\SellerAccountController;
use Illuminate\Support\Facades\Route;
use Inertia\Inertia;


Route::get('/', function () {
    return Inertia::render('Home', [
        'title' => 'Online Shopping Store',
    ]);
})->name('home');

Route::prefix('sell')->name('sell.')->controller(SellController::class)->group(function () {
    Route::get('/', 'index')->name('index');
    Route::get('/apply', 'applyForm')->name('apply');
});

Route::middleware(['auth', 'role:admin|seller'])->prefix('admin')->name('admin.')->group(function () {
    Route::get('/dashboard', function () {
        return Inertia::render('Dashboard');
    })->name('dashboard');
    Route::resource('products', ProductController::class);
});


Route::middleware(['auth', 'role:admin'])->prefix('admin')->name('admin.')->group(function () {

    require __DIR__ . '/roles_permissions.php';

    Route::prefix('users')->name('users.')->group(function () {
        Route::get('/', [UserController::class, 'index'])->name('index');
        Route::get('/{user}', [UserController::class, 'show']);
        Route::post('/{user}/assign-roles', [UserController::class, 'assignRoles']);
        Route::delete('/{user}/remove-role', [UserController::class, 'removeRole']);

        Route::get('/{user}/edit', [UserController::class, 'edit'])->name('users.edit');
        Route::put('/{user}', [UserController::class, 'update'])->name('users.update');
        Route::delete('/{user}', [UserController::class, 'destroy'])->name('users.destroy');
        Route::post('/{user}/update-status', [UserController::class, 'updateStatus'])->name('users.update-status');


        Route::get('/{sellerApplication}', [SellerApplicationController::class, 'show'])->name('show');
        Route::delete('/{sellerApplication}', [SellerApplicationController::class, 'destroy'])->name('destroy');
        Route::put('/{sellerApplication}/approve', [SellerApplicationController::class, 'approve'])->name('approve');
        Route::put('/{sellerApplication}/reject', [SellerApplicationController::class, 'reject'])->name('reject');
    });

    // Seller Applications
    Route::prefix('applications')->name('applications.')->group(function () {
        Route::get('/', [SellerApplicationController::class, 'index'])->name('index');
        Route::get('/{sellerApplication}', [SellerApplicationController::class, 'show'])->name('show');
        Route::delete('/{sellerApplication}', [SellerApplicationController::class, 'destroy'])->name('destroy');
        Route::put('/{sellerApplication}/approve', [SellerApplicationController::class, 'approve'])->name('approve');
        Route::put('/{sellerApplication}/reject', [SellerApplicationController::class, 'reject'])->name('reject');
    });
    Route::resource('document-types', DocumentTypeController::class);

    Route::get('/seller-verification/{sellerApplication}', [SellerVerificationController::class, 'show'])
    ->name('seller-verification.show');
    Route::put('/seller-documents/{sellerDocument}/review', [SellerVerificationController::class, 'review'])
    ->name('admin.seller-documents.review');


   Route::put('/seller-applications/{sellerApplication}/verify', [SellerVerificationController::class, 'verify'])
        ->name('seller-applications.verify');

    Route::resource('warehouses', WarehouseController::class);
    Route::resource('categories', CategoryController::class);
    Route::resource('categories.subcategories', SubcategoryController::class)->except(['index']);
    Route::resource('brands', BrandController::class);
    Route::resource('units', UnitController::class);
    Route::resource('unit-types', UnitTypeController::class);
    Route::resource('unit-types.units', UnitController::class)->except(['index', 'show']);
    Route::resource('variant-categories', VariantCategoryController::class);


});


Route::prefix('sell')->group(function () {
    Route::get('/', [SellController::class, 'index'])->name('sell.index');
    Route::get('/apply', [SellController::class, 'applyForm'])->name('sell.applyForm');

    Route::post('/save-progress', [SellController::class, 'saveProgress'])->name('sell.saveProgress');
    Route::post('/clear-progress', [SellController::class, 'clearProgress'])->name('sell.clearProgress');
    Route::post('/upload-image', [SellController::class, 'uploadImage'])->name('sell.uploadImage');
    Route::post('/apply', [SellController::class, 'submit'])->name('sell.apply');
});

Route::prefix('seller')->middleware(['auth', 'role:seller'])->name('seller.')->group(function () {
    Route::get('/profile', [SellerAccountController::class, 'profile'])->name('profile');
    Route::post('/documents', [SellerAccountController::class, 'submitDocument']);



});


require __DIR__.'/settings.php';
require __DIR__.'/auth.php';
