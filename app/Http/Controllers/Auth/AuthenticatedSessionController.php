<?php

namespace App\Http\Controllers\Auth;

use App\Http\Controllers\Controller;
use App\Http\Requests\Auth\LoginRequest;
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

    if ($user->hasAnyRole(['admin', 'superadmin', 'seller'])) {
        return redirect()->intended(route('admin.dashboard'))
            ->with('success', 'Welcome back, ' . $user->name . '!');
    }

    return redirect()->intended(route('home'))
        ->with('success', 'Welcome back, ' . $user->name . '!');
}
    public function store(LoginRequest $request): RedirectResponse
    {
        $request->authenticate();

        $request->session()->regenerate();

        // Check which guard was used
        if (Auth::guard('web')->check()) {
            $user = Auth::guard('web')->user();

            if ($user->hasAnyRole(['admin', 'superadmin', 'seller'])) {
                return redirect()->intended(route('admin.dashboard'))
                    ->with('success', 'Welcome back, ' . $user->name . '!');
            }

            return redirect()->intended(route('home'))
                ->with('success', 'Welcome back, ' . $user->name . '!');
        }

        if (Auth::guard('customers')->check()) {
            $customer = Auth::guard('customers')->user();

            return redirect()->intended(route('home', $customer->id))
                ->with('success', 'Welcome back, ' . $customer->first_name . '!');
        }

        return redirect()->route('home');
    }

    public function authenticate(): void
    {
        $this->ensureIsNotRateLimited();

        if (Auth::guard('web')->attempt($this->only('email', 'password'), $this->boolean('remember'))) {
            return;
        }

        if (Auth::guard('customers')->attempt($this->only('email', 'password'), $this->boolean('remember'))) {
            return;
        }

        RateLimiter::hit($this->throttleKey());

        throw ValidationException::withMessages([
            'email' => __('auth.failed'),
        ]);
    }

    /**
     * Destroy an authenticated session.
     */
    public function destroy1(Request $request): RedirectResponse
    {
        Auth::guard('web')->logout();

        $request->session()->invalidate();
        $request->session()->regenerateToken();

        return redirect('/');
    }
    public function destroy(Request $request): RedirectResponse
    {
        if (Auth::guard('web')->check()) {
            Auth::guard('web')->logout();
        }

        if (Auth::guard('customers')->check()) {
            Auth::guard('customers')->logout();
        }

        $request->session()->invalidate();
        $request->session()->regenerateToken();

        return redirect('/');
    }

}
