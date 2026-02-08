<?php

namespace App\Http\Controllers\Auth;

use App\Http\Controllers\Controller;
use App\Http\Requests\Auth\LoginRequest;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Route;
use Inertia\Inertia;
use Inertia\Response;
use App\Services\WishlistService;

class AuthenticatedSessionController extends Controller
{


    public function create(Request $request): Response
    {
        return Inertia::render('auth/Login', [
            'canResetPassword' => Route::has('password.request'),
            'status' => $request->session()->get('status'),
        ]);
    }

    public function createVendorLogin(Request $request): Response
    {
        return Inertia::render('auth/SellerLogin', [
            'canResetPassword' => Route::has('password.request'),
            'status' => $request->session()->get('status'),
        ]);
    }

    public function createAdminLogin(Request $request): Response
    {
        return Inertia::render('auth/AdminLogin', [
            'canResetPassword' => Route::has('password.request'),
            'status' => $request->session()->get('status'),
        ]);
    }

    /**
     * Handle an incoming authentication request.
     */

public function store(
    LoginRequest $request,
    WishlistService $wishlistService,
    \App\Services\CartReservationService $cartService
): RedirectResponse {

    // Attempt authentication
    $request->authenticate();

    if (!Auth::check()) {
        logger('Authentication failed');
        return back()->withErrors([
            'email' => 'These credentials do not match our records.',
        ]);
    }

    // Regenerate session to prevent fixation
    $request->session()->regenerate();

    $user = Auth::user();

    // Merge wishlist & cart
    try {
        $wishlistService->mergeGuestWishlist($request, $user);
        $cartService->getCart($request);
    } catch (\Throwable $e) {
        \Log::error('Merge failed during login', [
            'error'   => $e->getMessage(),
            'user_id' => $user->id ?? null,
        ]);
    }




//    if (in_array($user->user_type, ['user'])) {
//        $user->update(['last_login_at' => now()]);
//        return redirect()->intended(route('admin.dashboard'))
//            ->with('success', 'Welcome back, ' . $user->name . '!');
//    }


    if ($user->user_type === 'customer' || $user->secondary_role=="customer") {
        $customer = \App\Models\Customer\Customer::where('user_id', $user->id)->first();

        if (!$customer) {
            Auth::logout();
            return back()->withErrors([
                'email' => 'Customer account not found.',
            ]);
        }

        session(['customer_id' => $customer->id]);
        $user->update(['last_login_at' => now()]);
        return redirect()->intended(
            route('customers.dashboard', ['customer' => $customer->id])
        )->with('success', 'Welcome back, ' . $user->name . '!');
    } else{
        Auth::logout();
        $request->session()->invalidate();
        return redirect()->back()->withErrors(["password"=>"You don't have an active customer account to login. Please create an account or contact admin for assistance"]);
    }


    //as the last option
//
//    if (in_array($user->user_type, ['vendor', 'seller'])) {
//        $user->update(['last_login_at' => now()]);
//        return redirect()->intended(route('admin.dashboard'))
//            ->with('success', 'Welcome back, ' . $user->name . '!');
//    }


    return redirect()->intended(route('home'))
        ->with('success', 'Welcome back, ' . $user->name . '!');
}


    public function destroy(Request $request): RedirectResponse
    {
        Auth::guard('web')->logout();

        $request->session()->invalidate();
        $request->session()->regenerateToken();

        return redirect('/');
    }

    public function vendorStore(LoginRequest $request): RedirectResponse {

        // Attempt authentication
        $request->authenticate();

        if (!Auth::check()) {
            logger('Authentication failed');
            return back()->withErrors([
                'email' => 'These credentials do not match our records.',
            ]);
        }

        // Regenerate session to prevent fixation
        $request->session()->regenerate();
        $user = Auth::user();

        if (in_array($user->user_type, ['vendor', 'seller']) || $user->secondary_role == 'seller') {
            $user->update(['last_login_at' => now()]);
            return redirect()->intended(route('admin.dashboard'))
                ->with('success', 'Welcome back, ' . $user->name . '!');
        }else{
            Auth::logout();
            $request->session()->invalidate();
            return redirect()->back()->withErrors(["password"=>"You don't have permission to access this page. Please create an account or contact admin for assistance"]);
        }
    }


    public function adminStore(LoginRequest $request): RedirectResponse {

        // Attempt authentication
        $request->authenticate();

        if (!Auth::check()) {
            logger('Authentication failed');
            return back()->withErrors([
                'email' => 'These credentials do not match our records.',
            ]);
        }

        // Regenerate session to prevent fixation
        $request->session()->regenerate();
        $user = Auth::user();

        if ($user->user_type == "user") {
            $user->update(['last_login_at' => now()]);
            return redirect()->intended(route('admin.dashboard'))
                ->with('success', 'Welcome back, ' . $user->name . '!');
        }else{
            Auth::logout();
            $request->session()->invalidate();
            return redirect()->back()->withErrors(["password"=>"You don't have permission to access this page. Please create an account or contact admin for assistance"]);
        }
    }

    public function createDeliveryLogin(Request $request): Response
    {
        return Inertia::render('auth/DeliveryLogin', [
            'canResetPassword' => Route::has('password.request'),
            'status' => $request->session()->get('status'),
        ]);
    }

    public function deliveryStore(LoginRequest $request): RedirectResponse
    {
        $request->authenticate();

        if (!Auth::check()) {
            return back()->withErrors([
                'email' => 'These credentials do not match our records.',
            ]);
        }

        $request->session()->regenerate();
        $user = Auth::user();

        $isDelivery = $user->hasRole('delivery');
        $isActive = in_array($user->status, ['active', 1, true], true);

        if ($isDelivery && $isActive) {
            $user->update(['last_login_at' => now()]);
            return redirect()->intended(route('delivery.dashboard'))
                ->with('success', 'Welcome back, ' . $user->name . '!');
        }

        Auth::logout();
        $request->session()->invalidate();
        return redirect()->back()->withErrors([
            'password' => 'You do not have an active delivery account. Please contact admin.',
        ]);
    }
}
