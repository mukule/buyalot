<?php

use App\Http\Controllers\Admin\DiscountController as AdminDiscountController;
use App\Http\Controllers\Admin\DiscountTypeController;
use App\Http\Controllers\Admin\DocumentTypeController;
use App\Http\Controllers\Admin\ProductController;
use App\Http\Controllers\Admin\ProductStatusController;
use App\Http\Controllers\Admin\RegionController;
use App\Http\Controllers\Admin\SellerApplicationController;
use App\Http\Controllers\Admin\SellerVerificationController;
use App\Http\Controllers\Admin\UnitController;
use App\Http\Controllers\Admin\UnitTypeController;
use App\Http\Controllers\Admin\UserController;
use App\Http\Controllers\Admin\VariantCategoryController;
use App\Http\Controllers\Admin\WarrantyController;
use App\Http\Controllers\Admin\PickupPointController;
use App\Http\Controllers\Admin\ZoneController;
use App\Http\Controllers\Admin\ShippingRateController;
use App\Http\Controllers\CartController;
use App\Http\Controllers\Commission\CommissionCalculationController;
use App\Http\Controllers\Commission\CommissionInvoiceController;
use App\Http\Controllers\Commission\CommissionPlanController;
use App\Http\Controllers\CouponController;
use App\Http\Controllers\Customer\CustomerController;
use App\Http\Controllers\HomeController;
use App\Http\Controllers\Payments\MpesaPaymentController;
use App\Http\Controllers\Payments\MpesaRequestController;
use App\Http\Controllers\Payments\PaymentController;
use App\Http\Controllers\RoleSwitchController;
use App\Http\Controllers\SearchController;
use App\Http\Controllers\SellController;
use App\Http\Controllers\SellerAccountController;
use App\Http\Controllers\Seller\UserManagementController as SellerUserManagementController;
use App\Http\Controllers\Admin\PolicyController;
use App\Http\Controllers\Warehouse\WarehouseController;
use Illuminate\Foundation\Http\Middleware\VerifyCsrfToken as VerifyCsrfTokenMiddleware;
use Illuminate\Support\Facades\Route;
use Inertia\Inertia;


require __DIR__.'/settings.php';
require __DIR__.'/auth.php';
require __DIR__.'/payment.php';
require __DIR__.'/customer.php';


Route::get('/', [HomeController::class, 'index'])->name('home');

// Product search
Route::get('/refresh/cache', [\App\Services\SearchCacheService::class, 'refresh'])->name('refresh.cache');
Route::get('/forget/cache', [\App\Services\SearchCacheService::class, 'forget'])->name('forget.cache');
Route::get('/search', [SearchController::class, 'search'])->name('search');

// POS Direct Login
Route::middleware('guest')->group(function () {
    Route::get('/pos/login', [\App\Http\Controllers\POS\PosLoginController::class, 'showLoginForm'])->name('pos.login');
    Route::post('/pos/login', [\App\Http\Controllers\POS\PosLoginController::class, 'login'])->name('pos.login.submit');
});

Route::middleware(['auth','role:admin|seller','check_permission:view-dashboard'])->prefix('admin')->name('admin.')->group(function () {
Route::middleware(['auth','role:admin|seller|vendor|super-admin','check_permission:view-dashboard'])->prefix('admin')->name('admin.')->group(function () {

    // Invoices management (Admin)
    Route::get('/invoices', [\App\Http\Controllers\Admin\InvoiceController::class, 'index'])->name('invoices.index')
        ->middleware('check_permission:view-invoices');
    Route::get('/dashboard',[HomeController::class,'dashboard'])->name('dashboard');
//        function () {
//        return Inertia::render('Dashboard');
//    })->name('dashboard');

    // Seller user management (API JSON endpoints)
    Route::get('/seller-users', [SellerUserManagementController::class, 'index'])->name('seller-users.index');
    Route::post('/seller-users', [SellerUserManagementController::class, 'store'])->name('seller-users.store');
    Route::patch('/seller-users/{user}', [SellerUserManagementController::class, 'update'])->name('seller-users.update');
    Route::delete('/seller-users/{user}', [SellerUserManagementController::class, 'destroy'])->name('seller-users.destroy');

    Route::delete('products/destroy-all', [ProductController::class, 'destroyAll'])
        ->name('products.destroyAll');

    Route::resource('products', ProductController::class);
    Route::post('/products/{product}/restock-variants', [ProductController::class, 'restockVariants'])
        ->name('products.restock.variants');


    // Delete a single product image
    Route::delete('products/{product}/images/{imageId}', [ProductController::class, 'destroyImage'])
        ->name('products.images.destroy');
    Route::resource('products.warranties', WarrantyController::class)->except(['index']);

    // Update product status
    Route::patch('products/{product}/status', [ProductController::class, 'updateStatus'])
        ->name('products.updateStatus');

    Route::patch('warranties/{warranty}/toggle-active', [WarrantyController::class, 'toggleActive'])
        ->name('warranties.toggleActive');

    // Warehouses (admins and sellers)
    // NOTE: Define specific helper endpoints BEFORE the resource route to avoid being shadowed by warehouses.show
    Route::get('warehouses/assignable-users', [WarehouseController::class, 'getAssignableUsers'])
        ->name('warehouses.assignable-users');
    Route::get('warehouses/assignable-roles', [WarehouseController::class, 'getAssignableRoles'])
        ->name('warehouses.assignable-roles');

    Route::resource('warehouses', WarehouseController::class);
    Route::patch('warehouses/{warehouse}/toggle-status', [WarehouseController::class, 'toggleStatus'])
        ->name('warehouses.toggle-status');
    Route::post('warehouses/{warehouse}/assign-managers', [WarehouseController::class, 'assignManagers'])
        ->name('warehouses.assign-managers');

    // Inventory & stock routes
    Route::get('{warehouse}/inventory', [WarehouseController::class, 'inventory'])->name('inventory');
    Route::post('{warehouse}/inventory/update', [WarehouseController::class, 'updateInventory'])->name('inventory.update');
    Route::post('{warehouse}/inventory/adjust', [WarehouseController::class, 'adjustStock'])->name('inventory.adjust');
    Route::post('{warehouse}/inventory/add', [WarehouseController::class, 'addInventory'])->name('inventory.add');
    Route::post('{warehouse}/transfer', [WarehouseController::class, 'transferStock'])->name('inventory.transfer');

    // Receivables & Dispatches
    Route::get('{warehouse}/receivables', [WarehouseController::class, 'receivables'])->name('receivables.index');
    Route::get('{warehouse}/receivables/rejected', [WarehouseController::class, 'rejectedReceivables'])->name('receivables.rejected');
    Route::post('{warehouse}/receivables/create', [WarehouseController::class, 'createReceivable'])->name('receivables.create');
    Route::post('{warehouse}/receivables/accept', [WarehouseController::class, 'acceptReceivable'])->name('receivables.accept');
    Route::post('{warehouse}/receivables/reject', [WarehouseController::class, 'rejectReceivable'])->name('receivables.reject');
    Route::get('{warehouse}/dispatches', [WarehouseController::class, 'dispatches'])->name('dispatches.index');
    Route::post('{warehouse}/dispatches/create', [WarehouseController::class, 'createDispatch'])->name('dispatches.create');

    // Publish product variant from warehouse inventory
    Route::post('{warehouse}/inventory/{inventory}/publish', [WarehouseController::class, 'publishInventory'])->name('inventory.publish');

    // Product variant search for adding to inventory via modal
    Route::get('{warehouse}/inventory/variants', [WarehouseController::class, 'searchVariants'])->name('inventory.variants');

    // Cascading selects for Add Product modal
    Route::get('{warehouse}/inventory/categories', [WarehouseController::class, 'categories'])->name('inventory.categories');
    Route::get('{warehouse}/inventory/products', [WarehouseController::class, 'productsByCategory'])->name('inventory.products');
    Route::get('{warehouse}/inventory/variants-by-product', [WarehouseController::class, 'variantsByProduct'])->name('inventory.variants-by-product');

});

// Allow non-admin users with specific permissions to access listing pages
Route::middleware(['auth','role_or_permission:admin|view-orders'])->prefix('admin')->name('admin.')->group(function () {
    Route::get('/orders', [\App\Http\Controllers\Orders\OrderController::class, 'index'])->name('orders.index');
    Route::get('/orders/{order}', [\App\Http\Controllers\Admin\OrderController::class, 'show'])
        ->name('orders.show');
});

Route::middleware(['auth', 'role_or_permission:admin|super-admin|view-categories'])->prefix('admin')->name('admin.')
    ->group(function () {

        Route::get('/categories', [\App\Http\Controllers\Admin\CategoryController::class, 'index'])->name('categories.index');
        Route::get('/categories/create', [\App\Http\Controllers\Admin\CategoryController::class, 'create'])->name('categories.create');
        Route::post('/categories', [\App\Http\Controllers\Admin\CategoryController::class, 'store'])->name('categories.store');
        Route::get('/categories/{category}/edit', [\App\Http\Controllers\Admin\CategoryController::class, 'edit'])->name('categories.edit');
        Route::put('/categories/{category}', [\App\Http\Controllers\Admin\CategoryController::class, 'update'])->name('categories.update');
        Route::delete('/categories/{category}', [\App\Http\Controllers\Admin\CategoryController::class, 'destroy'])->name('categories.destroy');
        Route::post('/categories/{category}/restore', [\App\Http\Controllers\Admin\CategoryController::class, 'restore'])->name('categories.restore');
        Route::get('/categories/{category}', [\App\Http\Controllers\Admin\CategoryController::class, 'show'])->name('categories.show');
        Route::delete('/categories/{category}/force',[\App\Http\Controllers\Admin\CategoryController::class, 'forceDestroy'])->name('categories.force-destroy');

    });


Route::middleware(['auth','role_or_permission:admin|super-admin|view-brands'])->prefix('admin')->name('admin.')->group(function () {
    Route::get('/brands', [\App\Http\Controllers\Admin\BrandController::class, 'index'])->name('brands.index');
});


Route::middleware(['auth', 'role_or_permission:admin|view-policies'])
    ->prefix('admin')
    ->name('admin.')
    ->group(function () {

        Route::get('/policies', [PolicyController::class, 'index'])->name('policies.index');
        Route::get('/policies/create', [PolicyController::class, 'create'])->name('policies.create');
        Route::post('/policies', [PolicyController::class, 'store'])->name('policies.store');
        Route::get('/policies/{policy}/edit', [PolicyController::class, 'edit'])->name('policies.edit');
        Route::put('/policies/{policy}', [PolicyController::class, 'update'])->name('policies.update');
        Route::delete('/policies/{policy}', [PolicyController::class, 'destroy'])->name('policies.destroy');

        Route::get('/policies/{policy}/versions', [\App\Http\Controllers\Admin\PolicyVersionController::class, 'index'])->name('policies.versions.index');
        Route::get('/policies/{policy}/versions/create', [\App\Http\Controllers\Admin\PolicyVersionController::class, 'create'])->name('policies.versions.create');
        Route::post('/policies/{policy}/versions', [\App\Http\Controllers\Admin\PolicyVersionController::class, 'store'])->name('policies.versions.store');
        Route::get('/policies/{policy}/versions/{version}/edit', [\App\Http\Controllers\Admin\PolicyVersionController::class, 'edit'])->name('policies.versions.edit');
        Route::put('/policies/{policy}/versions/{version}', [\App\Http\Controllers\Admin\PolicyVersionController::class, 'update'])->name('policies.versions.update');
        Route::delete('/policies/{policy}/versions/{version}', [\App\Http\Controllers\Admin\PolicyVersionController::class, 'destroy'])->name('policies.versions.destroy');
});


Route::middleware(['auth', 'check_permission:access-pos'])->prefix('admin')->name('admin.')->group(function () {
    // POS Routes
    Route::prefix('pos')->name('pos.')->group(function () {
        Route::get('/', [\App\Http\Controllers\POS\PosController::class, 'index'])->name('index');
        Route::post('/verify-pin', [\App\Http\Controllers\POS\PosController::class, 'verifyPin'])->name('verify-pin');
        Route::post('/lock', [\App\Http\Controllers\POS\PosController::class, 'lockTerminal'])->name('lock');
        Route::post('/sessions/open', [\App\Http\Controllers\POS\PosController::class, 'openSession'])->name('sessions.open');
        Route::get('/terminal', [\App\Http\Controllers\POS\PosController::class, 'show'])->name('show');
        Route::post('/sessions/{session}/close', [\App\Http\Controllers\POS\PosController::class, 'closeSession'])->name('sessions.close');
        Route::post('/update-pin', [\App\Http\Controllers\POS\PosController::class, 'updatePin'])->name('update-pin');
        Route::post('/verify-admin-pin', [\App\Http\Controllers\POS\PosController::class, 'verifyAdminPin'])->name('verify-admin-pin');

        // API-like endpoints for POS
        Route::get('/products', [\App\Http\Controllers\POS\PosProductController::class, 'index'])->name('products.index');
        Route::get('/categories', [\App\Http\Controllers\POS\PosProductController::class, 'categories'])->name('categories.index');
        Route::get('/customers', [\App\Http\Controllers\POS\PosCustomerController::class, 'index'])->name('customers.index');
        Route::post('/customers', [\App\Http\Controllers\POS\PosCustomerController::class, 'store'])->name('customers.store');
        Route::post('/orders', [\App\Http\Controllers\POS\PosOrderController::class, 'store'])->name('orders.store');

        // Voided Sales
        Route::get('/voided-sales', [\App\Http\Controllers\POS\PosVoidedSaleController::class, 'index'])->name('voided-sales.index');
        Route::post('/voided-sales', [\App\Http\Controllers\POS\PosVoidedSaleController::class, 'store'])->name('voided-sales.store');
        Route::post('/voided-sales/{voidedSale}/recall', [\App\Http\Controllers\POS\PosVoidedSaleController::class, 'recall'])->name('voided-sales.recall');

        // Unallocated Payments
        Route::get('/unallocated-payments', [\App\Http\Controllers\POS\PosUnallocatedPaymentController::class, 'index'])->name('unallocated-payments.index');
        Route::post('/unallocated-payments', [\App\Http\Controllers\POS\PosUnallocatedPaymentController::class, 'store'])->name('unallocated-payments.store');

        // POS Settings & Register Management
        Route::middleware('check_permission:manage-pos-settings')->group(function () {
            Route::get('/settings', [\App\Http\Controllers\POS\PosSettingsController::class, 'index'])->name('settings.index');
            Route::post('/settings/global', [\App\Http\Controllers\POS\PosSettingsController::class, 'updateGlobal'])->name('settings.global.update');
            Route::post('/registers', [\App\Http\Controllers\POS\PosSettingsController::class, 'storeRegister'])->name('registers.store');
            Route::put('/registers/{register}', [\App\Http\Controllers\POS\PosSettingsController::class, 'updateRegister'])->name('registers.update');
            Route::delete('/registers/{register}', [\App\Http\Controllers\POS\PosSettingsController::class, 'destroyRegister'])->name('registers.destroy');
        });
    });
});

Route::middleware(['auth'])->prefix('admin')->name('admin.')->group(function () {
    require __DIR__ . '/roles_permissions.php';
    Route::resource('customers', CustomerController::class);

    Route::prefix('users')->group(function () {

        Route::middleware(['role_or_permission:admin|view-users'])->group(function () {
            Route::get('/{user}', [UserController::class, 'show'])->name('users.show');
            Route::get('/', [UserController::class, 'index'])->name('users.index');
        });
        Route::post('/', [UserController::class, 'store'])->name('users.store');
        Route::middleware(['check_permission:manage-user-roles'])->group(function () {
            Route::post('/{user}/assign-roles', [UserController::class, 'assignRoles'])->name('users.assign-roles');
            Route::delete('/{user}/remove-role', [UserController::class, 'removeRole'])->name('users.remove-role');
        });
        Route::middleware(['role_or_permission:admin|edit-users'])->group(function () {
            Route::get('/{user}/edit', [UserController::class, 'edit'])->name('users.edit');
            Route::put('/{user}', [UserController::class, 'update'])->name('users.update');
        });
        Route::middleware(['check_permission:delete-users'])->group(function () {
            Route::delete('/{user}', [UserController::class, 'destroy'])->name('users.destroy');
        });
        Route::middleware(['check_permission:activate-deactivate-users'])->group(function () {
            Route::post('/{user}/update-status', [UserController::class, 'updateStatus'])->name('users.update-status');
        });
        Route::put('/{user}/roles', [UserController::class, 'updateRoles'])->name('user.roles.update');

        Route::middleware(['check_permission:view-seller-applications'])->group(function () {
            Route::get('/{sellerApplication}', [SellerApplicationController::class, 'show'])->name('seller-applications.show');
        });
        Route::middleware(['check_permission:delete-seller-applications'])->group(function () {
            Route::delete('/{sellerApplication}', [SellerApplicationController::class, 'destroy'])->name('seller-applications.destroy');
        });
        Route::middleware(['check_permission:approve-seller-applications'])->group(function () {
            Route::put('/{sellerApplication}/approve', [SellerApplicationController::class, 'approve'])->name('seller-applications.approve');
        });
        Route::middleware(['check_permission:reject-seller-applications'])->group(function () {
            Route::put('/{sellerApplication}/reject', [SellerApplicationController::class, 'reject'])->name('seller-applications.reject');
        });
    });

    // Vendor Applications
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



    Route::resource('product-statuses', ProductStatusController::class);
    Route::resource('units', UnitController::class);
    Route::resource('unit-types', UnitTypeController::class);
    Route::resource('unit-types.units', UnitController::class)->except(['index', 'show']);
    Route::resource('variant-categories', VariantCategoryController::class);
    Route::resource('regions', RegionController::class);
    Route::resource('zones', ZoneController::class);
    Route::resource('shipping-rates', ShippingRateController::class);
    Route::resource('regions.pickup-points', PickupPointController::class)->except(['index']);
    Route::resource('subregions', RegionController::class);
    Route::resource('areas', RegionController::class);
    Route::resource('routes', RegionController::class);

    // Brands management (create/edit/delete)
    // Note: index is defined under a separate middleware group for users with view-brands permission
    Route::resource('brands', \App\Http\Controllers\Admin\BrandController::class)->except(['index']);


    Route::resource('payments', PaymentController::class);

    // Sales Reports
    Route::middleware('check_permission:view-sales-reports')->prefix('reports/sales')->name('reports.sales.')->group(function () {
        Route::get('/', [\App\Http\Controllers\Admin\ReportController::class, 'index'])->name('index');
    });

    // Discounts management
    Route::resource('discounts', AdminDiscountController::class);

    Route::resource('discount-types', DiscountTypeController::class);
    Route::patch('discount-types/{discountType}/toggle', [DiscountTypeController::class, 'toggleStatus'])
        ->name('discount-types.toggle');

    Route::post('/discounts/calculate', [AdminDiscountController::class, 'calculateDiscounts'])
        ->name('discounts.calculate');

    // Sellers management
    Route::middleware(['check_permission:view-sellers'])->group(function () {
        Route::resource('sellers', \App\Http\Controllers\Admin\SellerController::class)->only(['index','show']);
    });

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
    Route::get('/get-progress', [SellController::class, 'getProgress'])->name('sell.getProgress');

    Route::post('/save-progress', [SellController::class, 'saveProgress'])->name('sell.saveProgress');
    Route::post('/clear-progress', [SellController::class, 'clearProgress'])->name('sell.clearProgress');
    Route::post('/upload-image', [SellController::class, 'uploadImage'])->name('sell.uploadImage');
    Route::post('/apply', [SellController::class, 'submit'])->name('sell.apply');
});

Route::prefix('seller')->middleware(['auth', 'role:seller'])->name('seller.')->group(function () {
    Route::get('/profile', [SellerAccountController::class, 'profile'])->name('profile');
    Route::post('/documents', [SellerAccountController::class, 'submitDocument']);
});

Route::get('products/{slug}', [HomeController::class, 'productDetails'])->name('product.details');




Route::prefix('cart')->name('cart.')->group(function () {

    Route::get('/', [CartController::class, 'index'])->name('index');
    Route::post('/', [CartController::class, 'store'])->name('store');
    Route::delete('', [CartController::class, 'clear'])->name('clear');
});

// Checkout Summary (similar to Jumia)
// Route::get('/checkout/summary', [CartController::class, 'checkout'])
//     ->name('checkout.summary');
Route::get('/checkout/summary', [CartController::class, 'checkout'])
    ->middleware('auth')
    ->name('checkout.summary');


// Checkout Payment page
Route::get('/checkout/payment', [CartController::class, 'payment'])
    ->name('checkout.payment');

// Terms & Conditions
Route::get('/terms', function () {
    return Inertia::render('Frontend/Legal/Terms');
})->name('terms');

// Shipping estimate endpoint
Route::post('/shipping/estimate', [CartController::class, 'estimateShipping'])
    ->name('shipping.estimate');

// Coupon validation endpoint
Route::post('/coupons/validate', [CouponController::class, 'validateCode'])
    ->name('coupons.validate');

Route::get('/{slug}', [HomeController::class, 'category'])->name('category.show');

Route::middleware(['auth'])->group(function () {
    Route::post('/switch-role', [RoleSwitchController::class, 'switchRole'])->name('role.switch');
});

Route::prefix('payments')->name('payments.')->group(function () {
    Route::get('providers', [PaymentController::class, 'providers'])->name('providers');
    Route::post('initiate', [MpesaRequestController::class, 'initiate'])->withoutMiddleware([VerifyCsrfTokenMiddleware::class])->name('initiate');
    // Flexible status endpoint: accepts Payment ULID/numeric ID or MpesaRequest id/reference
    Route::get('{id}/status', [PaymentController::class, 'statusFlexible'])->name('status');
    // Poll status of an M-Pesa request by CheckoutRequestID/reference (frontend-friendly)
    Route::get('requests/{checkout_request_id}/status', [MpesaRequestController::class, 'statusByCheckoutId'])
        ->name('requests.status');
    Route::post('callback/{provider}', [PaymentController::class, 'callback'])->withoutMiddleware([VerifyCsrfTokenMiddleware::class])->name('callback');
});
});


