<?php

namespace App\Http\Controllers\Auth;

use App\Http\Controllers\Controller;
use App\Models\Customer\Customer;
use App\Models\User;
use App\Notifications\CustomerRegistrationPendingApproval;
use Illuminate\Auth\Events\Registered;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\URL;
use Illuminate\Validation\Rule;
use Illuminate\Validation\Rules;
use Inertia\Inertia;
use Inertia\Response;

class RegisteredUserController extends Controller
{


    public function create(): Response
    {
        return Inertia::render('auth/Register');
    }


    /**
     * @throws \Throwable
     */
    public function store(Request $request): RedirectResponse
    {

        if ($request->has('email')) {
            $request->merge(['email' => strtolower((string) $request->input('email'))]);
        }


        $normalizeName = function ($s) {
            $s = trim((string) $s);
            if ($s === '') return $s;
            $lower = mb_strtolower($s, 'UTF-8');
            $first = mb_strtoupper(mb_substr($lower, 0, 1, 'UTF-8'), 'UTF-8');
            $rest = mb_substr($lower, 1, null, 'UTF-8');
            return $first . $rest;
        };

        if ($request->has('first_name')) {
            $request->merge(['first_name' => $normalizeName($request->input('first_name'))]);
        }
        if ($request->has('last_name')) {
            $request->merge(['last_name' => $normalizeName($request->input('last_name'))]);
        }



        $validated = $request->validate([
            'email' => [
                'required',
                'string',
                'lowercase',
                'email',
                'max:255',
                Rule::unique('users', 'email')->where(fn($q) => $q->whereNotIn('user_type', ['seller', 'vendor'])),
            ],
            'password' => ['required', 'confirmed', Rules\Password::defaults()],
            'phone' => 'required|string|max:255',


            'first_name' => 'required|string|max:255',
            'last_name' => 'required|string|max:255',

            // Address
            'address.first_name' => 'nullable|string|max:255',
            'address.last_name' => 'nullable|string|max:255',
            'address.label' => 'nullable|string|max:255',
            'address.type' => 'nullable|string|in:shipping,billing,both',
            'address.address_line_1' => 'nullable|string|max:255',
            'address.address_line_2' => 'nullable|string|max:255',
            'address.city' => 'nullable|string|max:255',
            'address.state_province' => 'nullable|string|max:255',
            'address.postal_code' => 'nullable|string|max:255',
            'address.country_code' => 'nullable|string|size:2',
            'address.country_name' => 'nullable|string|max:255',
            'address.phone' => 'nullable|string|max:255',
            'address.delivery_instructions' => 'nullable|string',
            'address.latitude' => 'nullable|numeric|between:-90,90',
            'address.longitude' => 'nullable|numeric|between:-180,180',
        ]);


        $name = trim(($request->string('name') ?? '') . '');
        if ($name === '') {
            $name = trim($validated['first_name'] . ' ' . $validated['last_name']);
        }
        $phone = (string) $validated['phone'];
        $existingSeller = User::where('email', $validated['email'])
            ->whereIn('user_type', ['seller', 'vendor'])
            ->first();

        if ($existingSeller) {
            if ($existingSeller->customer) {
                return redirect()->route('login')
                    ->with('info', 'You already have a customer profile linked to this account.');
            }

            \DB::transaction(function () use ($validated, $existingSeller, $phone) {
                $customer = Customer::create([
                    'first_name'     => $validated['first_name'],
                    'last_name'      => $validated['last_name'],
                    'email'          => $validated['email'],
                    'phone'          => $phone,
                    'avatar'         => null,
                    'customer_type'  => $validated['customer_type'] ?? 'individual',
                    'status'         => 'inactive', // wait for approval
                    'user_id'        => $existingSeller->id,
                ]);

                $activationUrl = URL::temporarySignedRoute(
                    'customer.activate',
                    now()->addDays(7),
                    ['customer' => $customer->getRouteKey()]
                );
                $existingSeller->notify(new CustomerRegistrationPendingApproval($customer, $activationUrl));

            });

            return redirect()->route('login')
                ->with('success', 'Thanks! We emailed you an activation link to enable your customer account.');
        }

        \DB::transaction(function () use ($validated, $name, $phone, &$user) {
            $user = User::create([
                'name' => $name,
                'email' => $validated['email'],
                'password' => bcrypt($validated['password']),
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


            $addr = $validated['address'] ?? [];
            $hasAddress = ($addr['address_line_1'] ?? null) || ($addr['city'] ?? null) || ($addr['country_code'] ?? null);
            if ($hasAddress) {
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
            }

            $user->assignRole('customer');
        });

        event(new Registered($user));
        Auth::login($user);

        return redirect('/')
            ->with('success', 'Registration successful! Welcome aboard.');
    }

    public function activateCustomer(Request $request, Customer $customer): RedirectResponse
    {
        if (! $request->hasValidSignature()) {
            abort(401);
        }

        $user = $customer->user;
        if (! $user || ! in_array($user->user_type, ['seller', 'vendor'])) {
            abort(403);
        }

        if ($customer->status === 'active') {
            return redirect()->route('login')
                ->with('info', 'Your customer account is already active.');
        }

        //add the secondary role
        if (!$user->secondary_role) {
            $user->update(['secondary_role' => 'customer']);
        }

        \DB::transaction(function () use ($customer, $user) {
            $customer->update(['status' => 'active']);
            if (! $user->hasRole('customer')) {
                $user->assignRole('customer');
            }
        });

        return redirect()->route('login')
            ->with('success', 'Customer account activated. You can now switch to the customer portal.');
    }

}
