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

    public function store(LoginRequest $request): RedirectResponse
    {
        $request->authenticate();

        if (! Auth::check()) {
            logger('Authentication failed');
            return back()->withErrors([
                'email' => 'These credentials do not match our records.',
            ]);
        }
        $request->session()->regenerate();

        $user = Auth::user();
        if (in_array($user->user_type, ['admin', 'superadmin', 'seller'])) {
            logger('User is an admin');
            return redirect()->intended(route('admin.dashboard'))
                ->with('success', 'Welcome back, ' . $user->name . '!');
        }

        // Handle customer login
        if ($user->user_type === 'customer') {
            $customer = Customer::where('user_id', $user->id)->first();

            if (!$customer) {
                logger('No customer record found for user: ' . $user->id);
                Auth::logout();
                return back()->withErrors([
                    'email' => 'Customer account not found.',
                ]);
            }

            // Store customer_id in session
            session(['customer_id' => $customer->id]);
            return redirect()->route('customers.dashboard', ['customer' => $customer->id])
                ->with('success', 'Welcome back, ' . $user->name . '!');
        }

        logger('User is a customer');
        return redirect()->intended(route('home'))
            ->with('success', 'Welcome back, ' . $user->name . '!');
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

}
