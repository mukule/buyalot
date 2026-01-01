<?php
use App\Http\Controllers\Customer\CustomerAddressController;
use App\Http\Controllers\Customer\CustomerController;
use App\Http\Controllers\Customer\CustomerLoyaltyPointController;
use App\Http\Controllers\Customer\CustomerReferralController;
use App\Http\Controllers\Customer\CustomerSupportTicketController;
use App\Http\Controllers\Customer\CustomerWishlistsController;
use App\Http\Controllers\Orders\OrderController;
use App\Http\Controllers\WishlistController;

Route::middleware(['auth'])->group(function () {

    // Customer dashboard and profile
    Route::get('customers/dashboard', [CustomerController::class, 'dashboard'])->name('customers.dashboard');
    Route::get('customer/profile', [CustomerController::class, 'profile'])->name('customer.profile.show');
    Route::put('customer/profile', [CustomerController::class, 'updateProfile'])->name('customer.profile.update');
    Route::put('customer/password', [CustomerController::class, 'updatePassword'])->name('customer.password.update');
    Route::delete('customer/account', [CustomerController::class, 'deleteAccount'])->name('customer.account.delete');
    Route::get('customer/welcome', [CustomerController::class, 'welcome'])->name('customer.welcome');

    // Checkout addresses (web + Inertia)
   // List
Route::get('checkout/addresses', [CustomerAddressController::class, 'index'])
    ->name('checkout.addresses.index');

// Create
Route::get('checkout/addresses/create', [CustomerAddressController::class, 'form'])
    ->name('checkout.addresses.create');

// Edit
Route::get('checkout/addresses/{address}/edit', [CustomerAddressController::class, 'form'])
    ->name('checkout.addresses.edit');

// Store
Route::post('checkout/addresses', [CustomerAddressController::class, 'store'])
    ->name('checkout.addresses.store');

// Update
Route::put('checkout/addresses/{address}', [CustomerAddressController::class, 'update'])
    ->name('checkout.addresses.update');

// Delete
Route::delete('checkout/addresses/{address}', [CustomerAddressController::class, 'destroy'])
    ->name('checkout.addresses.destroy');

// Make default
Route::post('checkout/addresses/{address}/make-default', [CustomerAddressController::class, 'makeDefault'])
    ->name('checkout.addresses.make-default');


    // JSON API endpoints for addresses (optional)
    Route::get('me/addresses', [CustomerAddressController::class, 'apiList'])->name('me.addresses.index');
    Route::post('me/addresses', [CustomerAddressController::class, 'apiStore'])->name('me.addresses.store');
    Route::post('me/addresses/{address}/make-default', [CustomerAddressController::class, 'apiMakeDefault'])
        ->name('me.addresses.make-default');

    // Loyalty points
//    Route::resource('customers/loyalty-points', CustomerLoyaltyPointController::class)->only(['index']);
    Route::get('customers/loyalty-points/index', [CustomerLoyaltyPointController::class, 'index'])->name('customers.loyalty-points.index');
    Route::post('customers/loyalty-points/award', [CustomerLoyaltyPointController::class, 'award'])->name('customers.loyalty-points.award');
    Route::post('customers/loyalty-points/redeem', [CustomerLoyaltyPointController::class, 'redeem'])->name('customers.loyalty-points.redeem');

    // Referrals
    Route::resource('customers/referrals', CustomerReferralController::class)->except(['edit', 'update', 'destroy']);

    // Support tickets
    Route::resource('customers/support-tickets', CustomerSupportTicketController::class)->except(['edit', 'destroy']);

    // Wishlist
    Route::resource('wishlist', WishlistController::class)->only(['index', 'store','destroy']);
});
