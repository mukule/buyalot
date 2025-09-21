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
        return Socialite::driver('google')->redirect();
    }

    public function callback()
    {
        try {
            $googleUser = Socialite::driver('google')->user();

            // Validate required data from Google
            if (!$googleUser->email) {
                return redirect()->route('login')->withErrors([
                    'google' => 'Email is required from Google account.'
                ]);
            }

            DB::beginTransaction();

            // Check for existing customer with Google ID
            $customer = Customer::where('google_id', $googleUser->id)->first();

            if ($customer) {
                $this->updateExistingCustomerFromGoogle($customer, $googleUser);
                DB::commit();

                Auth::guard('customer')->login($customer);
                return $this->successRedirect($customer, 'Welcome back');
            }

            // Check for existing customer with email
            $existingCustomer = Customer::where('email', $googleUser->email)->first();

            if ($existingCustomer) {
                $this->linkGoogleToExistingCustomer($existingCustomer, $googleUser);
                DB::commit();

                Auth::guard('customer')->login($existingCustomer);
                return $this->successRedirect($existingCustomer, 'Google account linked successfully');
            }

            // Create new customer
            $newCustomer = $this->createCustomerFromGoogle($googleUser);
            DB::commit();

            Auth::guard('customer')->login($newCustomer);
            return $this->successRedirect($newCustomer, 'Account created successfully');

        } catch (\Exception $e) {
            DB::rollBack();

            \Log::error('Google OAuth Error', [
                'message' => $e->getMessage(),
                'trace' => $e->getTraceAsString(),
            ]);

            return redirect()->route('login')->withErrors([
                'google' => 'Authentication failed. Please try again.'
            ]);
        }
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
        $nameParts = $this->parseGoogleName($googleUser->name);

        $customer= Customer::create([
            'first_name' => $nameParts['first_name'],
            'last_name' => $nameParts['last_name'],
            'email' => $googleUser->email,
            'google_id' => $googleUser->id,
            'avatar' => $googleUser->avatar,
            'provider' => 'google',
            'provider_verified_at' => now(),
            'email_verified_at' => now(),
            'customer_type' => 'individual',
            'status' => 'active',
            'acquisition_source' => 'google_oauth',
            'last_login_at' => now(),
        ]);
        $customer->assignRole('customer');
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
