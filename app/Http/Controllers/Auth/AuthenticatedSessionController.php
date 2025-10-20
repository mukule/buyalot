<?php

namespace App\Http\Controllers\Auth;

use App\Http\Controllers\Controller;
use App\Http\Requests\Auth\LoginRequest;
use App\Models\Customer\Customer;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\RateLimiter;
use Illuminate\Support\Facades\Route;
use Illuminate\Validation\ValidationException;
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

    /**
     * Handle an incoming authentication request.
     */

     public function store1(LoginRequest $request): RedirectResponse
{
    $request->authenticate();

    $request->session()->regenerate();

    $user = $request->user();
    if ($user->user_type == 'user' || $user->user_type == 'seller' || $user->user_type == 'vendor') {
//    if ($user->hasAnyRole(['admin', 'superadmin', 'seller'])) {
        return redirect()->intended(route('admin.dashboard'))
            ->with('success', 'Welcome back, ' . $user->name . '!');
    }
    if ($user->user_type == 'customer') {
        $customer = Customer::where('user_id', $user->id)->first();
        if ($customer) {
            session(['customer_id' => $customer->id]);
        }
    }
    return redirect()->intended(route('home'))
        ->with('success', 'Welcome back, ' . $user->name . '!');
}

  

public function store(
    LoginRequest $request,
    WishlistService $wishlistService,
    \App\Services\CartReservationService $cartService
): RedirectResponse {
    $request->authenticate();

    if (!Auth::check()) {
        logger('Authentication failed');
        return back()->withErrors([
            'email' => 'These credentials do not match our records.',
        ]);
    }

    $request->session()->regenerate();

    $user = Auth::user();

   
    try {
        
        $wishlistService->mergeGuestWishlist($request, $user);

       
        $cartService->getCart($request);
    } catch (\Throwable $e) {
        \Log::error('Merge failed during login', [
            'error'   => $e->getMessage(),
            'user_id' => $user->id ?? null,
        ]);
    }

   
    if (in_array($user->user_type, ['user', 'vendor', 'seller'])) {
        return redirect()->intended(route('admin.dashboard'))
            ->with('success', 'Welcome back, ' . $user->name . '!');
    }

    if ($user->user_type === 'customer') {
        $customer = \App\Models\Customer\Customer::where('user_id', $user->id)->first();

        if (!$customer) {
            Auth::logout();
            return back()->withErrors([
                'email' => 'Customer account not found.',
            ]);
        }

        session(['customer_id' => $customer->id]);

        return redirect()
            ->route('customers.dashboard', ['customer' => $customer->id])
            ->with('success', 'Welcome back, ' . $user->name . '!');
    }

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

}
