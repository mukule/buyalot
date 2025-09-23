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
use App\Http\Controllers\Commission\CommissionCalculationController;
use App\Http\Controllers\Commission\CommissionInvoiceController;
use App\Http\Controllers\Commission\CommissionPlanController;
use App\Http\Controllers\Customer\CustomerAddressController;
use App\Http\Controllers\Customer\CustomerController;
use App\Http\Controllers\Customer\CustomerLoyaltyPointController;
use App\Http\Controllers\Customer\CustomerReferralController;
use App\Http\Controllers\Customer\CustomerSupportTicketController;
use App\Http\Controllers\Customer\CustomerWishlistsController;
use App\Http\Controllers\HomeController;
use App\Http\Controllers\Payments\PaymentController;
use App\Http\Controllers\SellController;
use App\Http\Controllers\SellerAccountController;
use Illuminate\Support\Facades\Route;
use Inertia\Inertia;
use App\Http\Controllers\Admin\VariantController;
use App\Http\Controllers\Admin\BrandCategoryController;
use App\Http\Controllers\Admin\ProductStatusController;
use App\Http\Controllers\Admin\WarrantyController;
use App\Http\Controllers\WishlistController;
use App\Http\Controllers\CartController;
use App\Http\Controllers\Admin\RegionController;


require __DIR__.'/settings.php';
require __DIR__.'/auth.php';
require __DIR__.'/payment.php';
require __DIR__.'/order.php';
require __DIR__.'/customer.php';


Route::get('/', [HomeController::class, 'index'])->name('home');

Route::middleware(['auth','role:admin|seller','check_permission:view-dashboard'])->prefix('admin')->name('admin.')->group(function () {
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

Route::middleware(['auth','role:admin'])->prefix('admin')->name('admin.')->group(function () {
    require __DIR__ . '/roles_permissions.php';

    Route::prefix('users')->name('users.')->group(function () {

        Route::middleware(['check_permission:view-users'])->group(function () {
            Route::get('/{user}', [UserController::class, 'show']);
            Route::get('/', [UserController::class, 'index'])->name('index');
        });
        Route::middleware(['check_permission:manage-user-roles'])->group(function () {
            Route::post('/{user}/assign-roles', [UserController::class, 'assignRoles']);
            Route::delete('/{user}/remove-role', [UserController::class, 'removeRole']);
        });
        Route::middleware(['check_permission:edit-users'])->group(function () {
            Route::get('/{user}/edit', [UserController::class, 'edit'])->name('users.edit');
            Route::put('/{user}', [UserController::class, 'update'])->name('users.update');
        });
        Route::middleware(['check_permission:delete-users'])->group(function () {
            Route::delete('/{user}', [UserController::class, 'destroy'])->name('users.destroy');
        });
        Route::middleware(['check_permission:activate-deactivate-users'])->group(function () {
        Route::post('/{user}/update-status', [UserController::class, 'updateStatus'])->name('users.update-status');
        });

        Route::middleware(['check_permission:view-seller-applications'])->group(function () {
            Route::get('/{sellerApplication}', [SellerApplicationController::class, 'show'])->name('show');
        });
        Route::middleware(['check_permission:delete-seller-applications'])->group(function () {
            Route::delete('/{sellerApplication}', [SellerApplicationController::class, 'destroy'])->name('destroy');
        });
        Route::middleware(['check_permission:approve-seller-applications'])->group(function () {
            Route::put('/{sellerApplication}/approve', [SellerApplicationController::class, 'approve'])->name('approve');
        });
        Route::middleware(['check_permission:reject-seller-applications'])->group(function () {
            Route::put('/{sellerApplication}/reject', [SellerApplicationController::class, 'reject'])->name('reject');
        });
    });

    // Seller Applications
    Route::prefix('applications')->name('applications.')->group(function () {
        Route::middleware(['check_permission:view-seller-applications'])->group(function () {
            Route::get('/', [SellerApplicationController::class, 'index'])->name('index');
            Route::get('/{sellerApplication}', [SellerApplicationController::class, 'show'])->name('show');
        });
        Route::middleware(['check_permission:delete-seller-applications'])->group(function () {
            Route::delete('/{sellerApplication}', [SellerApplicationController::class, 'destroy'])->name('destroy');
        });
        Route::middleware(['check_permission:approve-seller-applications'])->group(function () {
            Route::put('/{sellerApplication}/approve', [SellerApplicationController::class, 'approve'])->name('approve');
        });
        Route::middleware(['check_permission:reject-seller-applications'])->group(function () {
            Route::put('/{sellerApplication}/reject', [SellerApplicationController::class, 'reject'])->name('reject');
        });
    });
    Route::resource('document-types', DocumentTypeController::class);
    Route::middleware(['check_permission:view-verification-documents'])->group(function () {
        Route::get('/seller-verification/{sellerApplication}', [SellerVerificationController::class, 'show'])
            ->name('seller-verification.show');
        Route::put('/seller-documents/{sellerDocument}/review', [SellerVerificationController::class, 'review'])
            ->name('admin.seller-documents.review');
    });
    Route::middleware(['check_permission:approve-verification-documents'])->group(function () {
        Route::put('/seller-applications/{sellerApplication}/verify', [SellerVerificationController::class, 'verify'])
            ->name('seller-applications.verify');
    });

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
    Route::resource('regions', RegionController::class);


    Route::resource('payments', PaymentController::class);

    Route::resource('commission-plans', CommissionPlanController::class);
    Route::post('commission-plans/{plan}/toggle', [CommissionPlanController::class, 'toggle'])
        ->name('commission-plans.toggle');

    // Commission Calculations
    Route::resource('commission-calculations', CommissionCalculationController::class)
        ->only(['index', 'show']);
    Route::post('commission-calculations/{calculation}/confirm', [CommissionCalculationController::class, 'confirm'])
        ->name('commission-calculations.confirm');
    Route::post('commission-calculations/{calculation}/dispute', [CommissionCalculationController::class, 'dispute'])
        ->name('commission-calculations.dispute');
    Route::post('commission-calculations/{calculation}/recalculate', [CommissionCalculationController::class, 'recalculate'])
        ->name('commission-calculations.recalculate');

    // Commission Invoices
    Route::resource('commission-invoices', CommissionInvoiceController::class);
    Route::post('commission-invoices/{invoice}/mark-paid', [CommissionInvoiceController::class, 'markPaid'])
        ->name('commission-invoices.mark-paid');

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


Route::get('products/{slug}', [HomeController::class, 'productDetails'])->name('product.details');



Route::resource('wishlist', WishlistController::class)
    ->only(['index', 'store', 'destroy']);



Route::resource('customers', CustomerController::class);
Route::get('customers/{customer}/dashboard', [CustomerController::class, 'dashboard'])->name('customers.dashboard');

Route::resource('customers.addresses', CustomerAddressController::class)->except(['index']);
Route::get('customers/{customer}/addresses', [CustomerAddressController::class, 'index'])->name('customers.addresses.index');
Route::post('customers/{customer}/addresses/{address}/make-default', [CustomerAddressController::class, 'makeDefault'])->name('customers.addresses.make-default');

Route::resource('customers.loyalty-points', CustomerLoyaltyPointController::class)->only(['index']);
Route::post('customers/{customer}/loyalty-points/award', [CustomerLoyaltyPointController::class, 'award'])->name('customers.loyalty-points.award');
Route::post('customers/{customer}/loyalty-points/redeem', [CustomerLoyaltyPointController::class, 'redeem'])->name('customers.loyalty-points.redeem');

Route::resource('customers.referrals', CustomerReferralController::class)->except(['edit', 'update', 'destroy']);
Route::resource('customers.support-tickets', CustomerSupportTicketController::class)->except(['edit', 'destroy']);
Route::resource('customers.wishlist', CustomerWishlistsController::class)->only(['index', 'store', 'update', 'destroy']);



Route::prefix('cart')->name('cart.')->group(function () {

    Route::get('/', [CartController::class, 'index'])->name('index');


    Route::post('/', [CartController::class, 'store'])->name('store');
    Route::delete('', [CartController::class, 'clear'])->name('clear');
});




Route::get('/category/{slug}', [HomeController::class, 'category'])
    ->name('category.show');
