<?php

namespace App\Http\Controllers\Auth;

use App\Http\Controllers\Controller;
use App\Models\Customer\Customer;
use DB;
use Illuminate\Support\Facades\Auth;
use Laravel\Socialite\Facades\Socialite;

class GoogleAuthController extends Controller
{
    public function redirect()
    {
        // Debug: Check what redirect URI is being used
        $redirectUri = route('google.callback');
        \Log::info('Google OAuth Redirect URI: ' . $redirectUri);
        \Log::info('APP_URL: ' . config('app.url'));

        return Socialite::driver('google')
            ->redirectUrl($redirectUri)
            ->redirect();
    }

    public function register()
    {
        // Store a session flag to indicate this is a registration flow
        session(['google_auth_intent' => 'register']);

        $redirectUri = route('google.callback');
        \Log::info('Google OAuth Register Redirect URI: ' . $redirectUri);

        return Socialite::driver('google')
            ->redirectUrl($redirectUri)
            ->redirect();
    }

    public function callback()
    {
        try {
            $redirectUri = route('google.callback');
            \Log::info('Google OAuth Callback URI: ' . $redirectUri);

            $googleUser = Socialite::driver('google')
                ->redirectUrl($redirectUri)
                ->user();

            // Validate required data from Google
            if (!$googleUser->email) {
                return redirect()->route('login')->withErrors([
                    'google' => 'Email is required from Google account.'
                ]);
            }

            DB::beginTransaction();

            // Check the auth intent from session
            $authIntent = session('google_auth_intent', 'login');
            session()->forget('google_auth_intent'); // Clean up session

            // Check for existing customer with Google ID
            $customer = Customer::where('google_id', $googleUser->id)->first();

            if ($customer) {
                if ($authIntent === 'register') {
                    // User tried to register but already has an account
                    DB::rollBack();
                    return redirect()->route('login')->withErrors([
                        'google' => 'An account with this Google account already exists. Please log in instead.'
                    ]);
                }

                $this->updateExistingCustomerFromGoogle($customer, $googleUser);
                DB::commit();

                Auth::guard('customer')->login($customer);
                return $this->successRedirect($customer, 'Welcome back');
            }

            // Check for existing customer with email
            $existingCustomer = Customer::where('email', $googleUser->email)->first();

            if ($existingCustomer) {
                if ($authIntent === 'register') {
                    // User tried to register but email already exists
                    DB::rollBack();
                    return redirect()->route('login')->withErrors([
                        'google' => 'An account with this email already exists. Please log in instead.'
                    ]);
                }

                $this->linkGoogleToExistingCustomer($existingCustomer, $googleUser);
                DB::commit();

                Auth::guard('customer')->login($existingCustomer);
                return $this->successRedirect($existingCustomer, 'Google account linked successfully');
            }

            // Create new customer (works for both login and register flows)
            $newCustomer = $this->createCustomerFromGoogle($googleUser);
            DB::commit();

            Auth::guard('customer')->login($newCustomer);

            $message = $authIntent === 'register'
                ? 'Account created successfully'
                : 'Account created successfully';

            return $this->successRedirect($newCustomer, $message);

        } catch (\Exception $e) {
            DB::rollBack();

            \Log::error('Google OAuth Error', [
                'message' => $e->getMessage(),
                'trace' => $e->getTraceAsString(),
            ]);

            $redirectRoute = session('google_auth_intent') === 'register' ? 'register' : 'login';

            return redirect()->route($redirectRoute)->withErrors([
                'google' => 'Authentication failed. Please try again.'
            ]);
        }
    }

    //check current configuration
    public function debugConfig()
    {
        if (!app()->environment('local')) {
            abort(403, 'Debug endpoint only available in local environment');
        }

        $config = [
            'APP_URL' => config('app.url'),
            'Google Client ID' => config('services.google.client_id'),
            'Google Redirect' => config('services.google.redirect'),
            'Route Callback' => route('google.callback'),
            'Full URL' => url()->full(),
            'Current Domain' => request()->getHost(),
        ];

        return response()->json($config);
    }

    private function updateExistingCustomerFromGoogle(Customer $customer, $googleUser): void
    {
        $customer->update([
            'avatar' => $googleUser->avatar,
            'last_login_at' => now(),
        ]);
    }

    private function linkGoogleToExistingCustomer(Customer $customer, $googleUser): void
    {
        $customer->update([
            'google_id' => $googleUser->id,
            'avatar' => $googleUser->avatar,
            'provider' => 'google',
            'provider_verified_at' => now(),
            'email_verified_at' => $customer->email_verified_at ?? now(),
            'last_login_at' => now(),
        ]);
    }

    private function createCustomerFromGoogle($googleUser): Customer
    {
        info('Creating new customer from Google');
        $nameParts = $this->parseGoogleName($googleUser->name);
        $customer = Customer::create([
            'first_name' => $nameParts['first_name'],
            'last_name' => $nameParts['last_name'],
            'email' => $googleUser->email,
            'google_id' => $googleUser->id,
            'phone' => $googleUser->phone_number,
            'avatar' => $googleUser->avatar,
            'provider' => 'google',
            'provider_verified_at' => now(),
            'email_verified_at' => now(),
            'customer_type' => 'individual',
            'status' => 'active',
            'acquisition_source' => 'google_oauth',
            'last_login_at' => now(),
        ]);
//        $customer->syncRoles('customer');
        return $customer;
    }

    private function parseGoogleName(string $fullName): array
    {
        $parts = explode(' ', trim($fullName), 2);

        return [
            'first_name' => $parts[0] ?? 'Customer',
            'last_name' => $parts[1] ?? '',
        ];
    }

    private function successRedirect(Customer $customer, string $message): \Illuminate\Http\RedirectResponse
    {
        return redirect()->intended(route('home'))
            ->with('success', $message . ', ' . $customer->first_name . '!');
    }
}
