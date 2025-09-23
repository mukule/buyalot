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
use App\Services\CartService;

class AuthenticatedSessionController extends Controller
{
    /**
     * Show the login page.
     */
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

    public function store(LoginRequest $request): RedirectResponse
{
    info("login button store function");
    $request->authenticate();
    $request->session()->regenerate();

    $user = $request->user();

    // 🔥 Merge guest cart into user cart
    app(\App\Services\CartService::class)->mergeGuestCart($request, $user);

    // 🔥 Merge guest wishlist into user wishlist
    $this->mergeGuestWishlist($request, $user);

    if ($user->hasAnyRole(['admin', 'superadmin', 'seller'])) {
        $user->update("last_login_at", now());
        return redirect()->intended(route('admin.dashboard'))
            ->with('success', 'Welcome back, ' . $user->name . '!');
    }

    return redirect()->intended(route('home'))
        ->with('success', 'Welcome back, ' . $user->name . '!');
}

/**
 * Merge guest wishlist items into authenticated user's wishlist after login.
 */
protected function mergeGuestWishlist(Request $request, $user): void
{
    $guestToken = $request->cookie('wishlist_token');

    if (!$guestToken) {
        return; // no guest wishlist to merge
    }

    $guestItems = \App\Models\Wishlist::where('wishlist_token', $guestToken)->get();

    foreach ($guestItems as $item) {
        // Avoid duplicates
        \App\Models\Wishlist::firstOrCreate([
            'user_id' => $user->id,
            'product_variant_id' => $item->product_variant_id,
        ]);

        // Delete the guest item
        $item->delete();
    }

    // Remove guest wishlist cookie
    cookie()->queue(cookie()->forget('wishlist_token'));
}

    /**
     * Destroy an authenticated session.
     */
    public function destroy(Request $request): RedirectResponse
    {
        Auth::guard('web')->logout();

        $request->session()->invalidate();
        $request->session()->regenerateToken();

        return redirect('/');
    }
    public function customerLogin(Request $request)
    {
        $credentials = $request->validate([
            'email' => 'required|email',
            'password' => 'required',
        ]);

        if (Auth::guard('customer')->attempt($credentials, $request->filled('remember'))) {
            $request->session()->regenerate();

            // Update last login
            Auth::guard('customer')->user()->update([
                'last_login_at' => now(),
            ]);

            return redirect()->intended(route('home'))
                ->with('success', 'Welcome back, ' . Auth::guard('customer')->user()->name . '!');
        }

        return back()->withErrors([
            'email' => 'The provided credentials do not match our records.',
        ]);
    }

    public function logoutCustomer(Request $request)
    {
        Auth::guard('customer')->logout();
        $request->session()->invalidate();
        $request->session()->regenerateToken();

        return redirect('/');
    }
}
