<?php

use Illuminate\Support\Facades\Route;
use Inertia\Inertia;
use App\Http\Controllers\Admin\RoleController;
use App\Http\Controllers\Admin\PermissionController;
use App\Http\Controllers\SellController;
use App\Http\Controllers\Admin\SellerApplicationController;
use App\Http\Controllers\Admin\DocumentTypeController;
use App\Http\Controllers\SellerAccountController;
use App\Http\Controllers\Admin\UserRoleController;
use App\Http\Controllers\Admin\SellerVerificationController;
use App\Models\SellerDocument;
use Illuminate\Validation\Rule;
use App\Http\Controllers\Admin\WarehouseController;
use App\Http\Controllers\Admin\CategoryController;
use App\Http\Controllers\Admin\SubcategoryController;
use App\Http\Controllers\Admin\BrandController;
use App\Http\Controllers\Admin\UnitController;
use App\Http\Controllers\Admin\UnitTypeController;
use App\Http\Controllers\Admin\VariantCategoryController;
use App\Http\Controllers\Admin\VariantController;
use App\Http\Controllers\Admin\ProductController;
use App\Http\Controllers\HomeController;
use App\Http\Controllers\Admin\BrandCategoryController;
use App\Http\Controllers\Admin\ProductStatusController;
use App\Http\Controllers\Admin\WarrantyController;
use App\Http\Controllers\WishlistController;


require __DIR__.'/settings.php';
require __DIR__.'/auth.php';


Route::get('/', [HomeController::class, 'index'])->name('home');


Route::middleware(['auth', 'role:admin|seller'])->prefix('admin')->name('admin.')->group(function () {
    Route::get('/dashboard', function () {
        return Inertia::render('Dashboard');
    })->name('dashboard');

    Route::delete('products/destroy-all', [ProductController::class, 'destroyAll'])
        ->name('products.destroyAll');
        
    Route::resource('products', ProductController::class);

    // Delete a single product image
    Route::delete('products/{product}/images/{imageId}', [ProductController::class, 'destroyImage'])
        ->name('products.images.destroy');
    Route::resource('products.warranties', WarrantyController::class)->except(['index']);

    // Update product status
    Route::patch('products/{product}/status', [ProductController::class, 'updateStatus'])
        ->name('products.updateStatus');

    Route::patch('warranties/{warranty}/toggle-active', [WarrantyController::class, 'toggleActive'])
    ->name('warranties.toggleActive');

});



Route::middleware(['auth', 'role:admin'])->prefix('admin')->name('admin.')->group(function () {
    // Roles
    Route::get('/roles', [RoleController::class, 'index'])->name('roles.index');
    Route::get('/roles/create', [RoleController::class, 'create'])->name('roles.create');
    Route::post('/roles', [RoleController::class, 'store'])->name('roles.store');
    Route::get('/roles/{role}/edit', [RoleController::class, 'edit'])->name('roles.edit');
    Route::put('/roles/{role}', [RoleController::class, 'update'])->name('roles.update');
    Route::delete('/roles/{role}', [RoleController::class, 'destroy'])->name('roles.destroy');
    Route::get('/roles/{role}', [RoleController::class, 'show'])->name('roles.show');

    // Permissions
    Route::get('/permissions', [PermissionController::class, 'index'])->name('permissions.index');
    Route::get('/permissions/create', [PermissionController::class, 'create'])->name('permissions.create');
    Route::post('/permissions', [PermissionController::class, 'store'])->name('permissions.store');
    Route::get('/permissions/{permission}/edit', [PermissionController::class, 'edit'])->name('permissions.edit');
    Route::put('/permissions/{permission}', [PermissionController::class, 'update'])->name('permissions.update');
    Route::delete('/permissions/{permission}', [PermissionController::class, 'destroy'])->name('permissions.destroy');

    // Seller Applications
    Route::prefix('applications')->name('applications.')->group(function () {
        Route::get('/', [SellerApplicationController::class, 'index'])->name('index');
        Route::get('/{sellerApplication}', [SellerApplicationController::class, 'show'])->name('show');
        Route::delete('/{sellerApplication}', [SellerApplicationController::class, 'destroy'])->name('destroy');
        Route::put('/{sellerApplication}/approve', [SellerApplicationController::class, 'approve'])->name('approve');
        Route::put('/{sellerApplication}/reject', [SellerApplicationController::class, 'reject'])->name('reject');
    });
    Route::resource('document-types', DocumentTypeController::class);

    Route::get('/user-roles', [UserRoleController::class, 'index'])->name('admin.user-roles.index');
    Route::put('/user-roles/{user}', [UserRoleController::class, 'update'])->name('admin.user-roles.update');

    Route::get('/seller-verification/{sellerApplication}', [SellerVerificationController::class, 'show'])
    ->name('seller-verification.show');
    Route::put('/seller-documents/{sellerDocument}/review', [SellerVerificationController::class, 'review'])
    ->name('admin.seller-documents.review');
   

   Route::put('/seller-applications/{sellerApplication}/verify', [SellerVerificationController::class, 'verify'])
        ->name('seller-applications.verify');

    Route::resource('warehouses', WarehouseController::class);
    Route::resource('product-statuses', ProductStatusController::class);
    Route::resource('categories', CategoryController::class);
    Route::get('/categories/{category}/children', [CategoryController::class, 'children'])
        ->name('categories.children');
    Route::resource('categories.subcategories', SubcategoryController::class)->except(['index']);
    Route::resource('brands', BrandController::class);
    Route::resource('brand-categories', BrandCategoryController::class);
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


Route::get('/{slug}', [HomeController::class, 'productDetails'])->name('product.details');

Route::middleware(['auth', 'role:user'])->group(function () {
    Route::resource('wishlist', WishlistController::class)
        ->only(['index', 'store', 'destroy']);
});
