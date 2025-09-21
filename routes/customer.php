<?php
use App\Http\Controllers\Customer\CustomerAddressController;
use App\Http\Controllers\Customer\CustomerController;
use App\Http\Controllers\Customer\CustomerLoyaltyPointController;
use App\Http\Controllers\Customer\CustomerReferralController;
use App\Http\Controllers\Customer\CustomerSupportTicketController;
use App\Http\Controllers\Customer\CustomerWishlistsController;
use App\Http\Controllers\Orders\OrderController;


Route::middleware(['auth'])->group(function () {
Route::resource('orders', OrderController::class);

Route::resource('customers', CustomerController::class);
Route::get('customers/{customer}/dashboard', [CustomerController::class, 'dashboard'])->name('customers.dashboard');
Route::get('customer/profile', [CustomerController::class, 'profile'])->name('customer.profile.show');
Route::get('customer/welcome', [CustomerController::class, 'welcome'])->name('emails.customer.welcome');

Route::resource('customers.addresses', CustomerAddressController::class)->except(['index']);
Route::get('customers/{customer}/addresses', [CustomerAddressController::class, 'index'])->name('customers.addresses.index');
Route::post('customers/{customer}/addresses/{address}/make-default', [CustomerAddressController::class, 'makeDefault'])->name('customers.addresses.make-default');

Route::resource('customers.loyalty-points', CustomerLoyaltyPointController::class)->only(['index']);
Route::post('customers/{customer}/loyalty-points/award', [CustomerLoyaltyPointController::class, 'award'])->name('customers.loyalty-points.award');
Route::post('customers/{customer}/loyalty-points/redeem', [CustomerLoyaltyPointController::class, 'redeem'])->name('customers.loyalty-points.redeem');

Route::resource('customers.referrals', CustomerReferralController::class)->except(['edit', 'update', 'destroy']);
Route::resource('customers.support-tickets', CustomerSupportTicketController::class)->except(['edit', 'destroy']);
Route::resource('customers.wishlist', CustomerWishlistsController::class)->only(['index', 'store', 'update', 'destroy']);
});
