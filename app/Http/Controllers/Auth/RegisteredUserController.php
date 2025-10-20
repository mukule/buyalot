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
        // Normalize email to lowercase before validation for consistency
        if ($request->has('email')) {
            $request->merge(['email' => strtolower((string) $request->input('email'))]);
        }

        // Validate core user fields
        $validated = $request->validate([
            'email' => 'required|string|lowercase|email|max:255|unique:' . User::class,
            'password' => ['required', 'confirmed', Rules\Password::defaults()],
            'phone' => 'required|string|max:255',

            // Customer profile
            'first_name' => 'required|string|max:255',
            'last_name' => 'required|string|max:255',

            // Address
            'address.first_name' => 'nullable|string|max:255',
            'address.last_name' => 'nullable|string|max:255',
            'address.label' => 'nullable|string|max:255',
            'address.type' => 'nullable|string|in:shipping,billing,both',
            'address.address_line_1' => 'required|string|max:255',
            'address.address_line_2' => 'nullable|string|max:255',
            'address.city' => 'required|string|max:255',
            'address.state_province' => 'required|string|max:255',
            'address.postal_code' => 'required|string|max:255',
            'address.country_code' => 'required|string|size:2',
            'address.country_name' => 'required|string|max:255',
            'address.phone' => 'nullable|string|max:255',
            'address.delivery_instructions' => 'nullable|string',
            'address.latitude' => 'nullable|numeric|between:-90,90',
            'address.longitude' => 'nullable|numeric|between:-180,180',
        ]);

        // Build display name if not explicitly provided
        $name = trim(($request->string('name') ?? '') . '');
        if ($name === '') {
            $name = trim($validated['first_name'] . ' ' . $validated['last_name']);
        }

        // Normalize phone (remove leading 0 if present, prefix country code if provided separately on frontend)
        $phone = (string) $validated['phone'];

        \DB::transaction(function () use ($validated, $name, $phone, &$user) {
            $user = User::create([
                'name' => $name,
                'email' => $validated['email'],
                'password' => Hash::make($validated['password']),
                'google_id' => null,
                'provider'   => 'register',
                'provider_verified_at' => now(),
                'email_verified_at' => now(),
                'last_login_at' => null,
                'status' => true,
                'user_type' => 'customer',
            ]);

            $customer = Customer::create([
                'first_name' => $validated['first_name'],
                'last_name'  => $validated['last_name'],
                'email'      => $validated['email'],
                'phone'      => $phone,
                'avatar'     => null,
                'customer_type' => $validated['customer_type'] ?? 'individual',
                'status' => 'active',
                'user_id' => $user->id,
            ]);

            // Prepare address data
            $addr = $validated['address'] ?? [];
            $addressData = [
                'customer_id' => $customer->id,
                'type' => $addr['type'] ?? 'shipping',
                'label' => $addr['label'] ?? 'Primary',
                'first_name' => $addr['first_name'] ?? $validated['first_name'],
                'last_name' => $addr['last_name'] ?? $validated['last_name'],
                'company' => $addr['company'] ?? null,
                'address_line_1' => $addr['address_line_1'] ?? null,
                'address_line_2' => $addr['address_line_2'] ?? null,
                'city' => $addr['city'] ?? null,
                'state_province' => $addr['state_province'] ?? null,
                'postal_code' => $addr['postal_code'] ?? null,
                'country_code' => strtoupper($addr['country_code'] ?? ''),
                'country_name' => $addr['country_name'] ?? null,
                'phone' => $addr['phone'] ?? $phone,
                'delivery_instructions' => $addr['delivery_instructions'] ?? null,
                'latitude' => $addr['latitude'] ?? null,
                'longitude' => $addr['longitude'] ?? null,
                'is_default' => true,
            ];

            \App\Models\Customer\CustomerAddress::create($addressData);

            $user->assignRole('customer');
        });

        event(new Registered($user));
        Auth::login($user);

        return redirect('/')
            ->with('success', 'Registration successful! Welcome aboard.');
    }

}
