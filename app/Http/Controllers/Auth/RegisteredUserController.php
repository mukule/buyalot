<?php

namespace App\Http\Controllers\Auth;

use App\Http\Controllers\Controller;
use App\Models\Customer\Customer;
use App\Models\User;
use Illuminate\Auth\Events\Registered;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Hash;
use Illuminate\Validation\Rules;
use Inertia\Inertia;
use Inertia\Response;

class RegisteredUserController extends Controller
{
    /**
     * Show the registration page.
     */
    public function create(): Response
    {
        return Inertia::render('auth/Register');
    }

    /**
     * Handle an incoming registration request.
     *
     * @throws \Illuminate\Validation\ValidationException
     */
    public function store(Request $request): RedirectResponse
{
    $request->validate([
        'name' => 'required|string|max:255',
        'email' => 'required|string|lowercase|email|max:255|unique:' . User::class,
        'password' => ['required', 'confirmed', Rules\Password::defaults()],
    ]);

    $user = User::create([
        'name' => $request->name,
        'email' => $request->email,
        'password' => Hash::make($request->password),
        'google_id' => null,
        'provider'   => 'register',
        'provider_verified_at' => now(),
        'email_verified_at' => now(),
        'last_login_at' => now(),
        'status' => true,
        'user_type' => 'customer',
    ]);

    $nameParts = explode(' ', trim($request->name), 2);
    $firstName = $nameParts[0] ?? '';
    $lastName = $nameParts[1] ?? '';

    Customer::create([
        'customer_code' => uniqid('CUS-'),
        'first_name' => $firstName,
        'last_name'  => $lastName,
        'email'      => $user->email,
        'avatar'     => null,
        'customer_type' => 'individual',
        'status' => 'active',
        'user_id' => $user->id,
    ]);
    $user->assignRole('user');

    event(new Registered($user));

    Auth::login($user);

    return redirect('/')
        ->with('success', 'Registration successful! Welcome aboard.');
}

}
