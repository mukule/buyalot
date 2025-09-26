<?php

namespace App\Http\Controllers\Auth;

use App\Http\Controllers\Controller;
use App\Models\Customer\Customer;
use App\Models\User;
use DB;
use Illuminate\Support\Facades\Auth;
use Laravel\Socialite\Facades\Socialite;

class GoogleAuthController extends Controller
{
//    public function redirect()
//    {
//        $redirectUri = route('google.callback');
//        \Log::info('Google OAuth Redirect URI: ' . $redirectUri);
//
//        return Socialite::driver('google')
//            ->redirectUrl($redirectUri)
//            ->redirect();
//    }
    public function redirect()
    {
        return Socialite::driver('google')
            ->redirectUrl(route('google.callback'))
            ->redirect();
    }
    public function register()
    {
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

            if (!$googleUser->email) {
                return redirect()->route('login')->with('error', 'Email is required from Google account.');
            }

            $authIntent = session('google_auth_intent', 'login');
            session()->forget('google_auth_intent');

            DB::beginTransaction();

            // Check if user already exists with this Google ID
            $existingUser = User::where('google_id', $googleUser->id)->first();

            if ($existingUser) {
                return $this->handleExistingGoogleUser($existingUser, $googleUser, $authIntent);
            }

            // Check if user exists with this email
            $emailUser = User::where('email', $googleUser->email)->first();

            if ($emailUser) {
                return $this->handleExistingEmailUser($emailUser, $googleUser, $authIntent);
            }

            // Create new user and customer
            return $this->createNewUser($googleUser, $authIntent);

        } catch (\Exception $e) {
            DB::rollBack();

            \Log::error('Google OAuth Error', [
                'message' => $e->getMessage(),
                'trace' => $e->getTraceAsString(),
            ]);

            $redirectRoute = session('google_auth_intent') === 'register' ? 'register' : 'login';

            return redirect()->route($redirectRoute)->with(
                'error', 'Authentication failed. Please try again.'
            );
        }
    }

    private function handleExistingGoogleUser(User $user, $googleUser, string $authIntent)
    {
        if ($authIntent === 'register') {
            DB::rollBack();
            return redirect()->route('login')->with('error',
                'An account with this Google account already exists. Please log in instead.');
        }

        // Update user info
        $user->update([
            'name' => $googleUser->name,
            'email' => $googleUser->email,
            'last_login_at' => now(),
        ]);

        // Update or create customer
        $customer = Customer::where('user_id', $user->id)->first();

        if (!$customer) {
            $customer = $this->createCustomerForUser($user, $googleUser);
        } else {
            $this->updateCustomerFromGoogle($customer, $googleUser);
        }

        DB::commit();
        Auth::guard('web')->login($user);
        request()->session()->regenerate();

        return redirect()->route('home')->with('success', 'Welcome back!');
    }

    private function handleExistingEmailUser(User $user, $googleUser, string $authIntent)
    {
        if ($authIntent === 'register') {
            DB::rollBack();
            return redirect()->route('login')->with('error',
                'An account with this email already exists. Please log in instead.');
        }

        // Link Google account to existing user
        $user->update([
            'google_id' => $googleUser->id,
            'provider' => 'google',
            'provider_verified_at' => now(),
            'email_verified_at' => $user->email_verified_at ?? now(),
            'last_login_at' => now(),
        ]);

        // Update or create customer
        $customer = Customer::where('user_id', $user->id)->first();

        if (!$customer) {
            $customer = $this->createCustomerForUser($user, $googleUser);
        } else {
            $this->updateCustomerFromGoogle($customer, $googleUser);
        }

        DB::commit();
        Auth::guard('web')->login($user);
        request()->session()->regenerate();

        return redirect()->route('home')->with('success', 'Google account linked successfully.');
    }

    private function createNewUser($googleUser, string $authIntent)
    {
        $nameParts = $this->parseGoogleName($googleUser->name);

        $user = User::create([
            'name' => $googleUser->name,
            'email' => $googleUser->email,
            'password' => bcrypt(str()->random(16)),
            'email_verified_at' => now(),
            'google_id' => $googleUser->id,
            'phone' => null,
            'provider' => 'google',
            'provider_verified_at' => now(),
            'last_login_at' => now(),
        ]);

        $user->assignRole('customer');

        $customer = Customer::create([
            'user_id' => $user->id,
            'first_name' => $nameParts['first_name'],
            'last_name' => $nameParts['last_name'],
            'email' => $googleUser->email,
            'phone' => null,
            'avatar' => $googleUser->avatar,
            'customer_type' => 'individual',
            'status' => 'active',
            'acquisition_source' => 'google_oauth',
        ]);

        DB::commit();
        Auth::guard('web')->login($user);
        request()->session()->regenerate();

        $message = $authIntent === 'register'
            ? 'Account created successfully'
            : 'Account created successfully';

        return $this->successRedirect($customer, $message);
    }

    private function createCustomerForUser(User $user, $googleUser): Customer
    {
        $nameParts = $this->parseGoogleName($googleUser->name);

        return Customer::create([
            'user_id' => $user->id,
            'first_name' => $nameParts['first_name'],
            'last_name' => $nameParts['last_name'],
            'email' => $googleUser->email,
            'phone' => null,
            'avatar' => $googleUser->avatar,
            'customer_type' => 'individual',
            'status' => 'active',
            'acquisition_source' => 'google_oauth',
        ]);
    }

    private function updateCustomerFromGoogle(Customer $customer, $googleUser): void
    {
        $customer->update([
            'avatar' => $googleUser->avatar,
        ]);
    }

    private function parseGoogleName(string $fullName): array
    {
        $parts = explode(' ', trim($fullName), 2);

        return [
            'first_name' => $parts[0] ?? 'Customer',
            'last_name' => $parts[1] ?? '',
        ];
    }

    private function successRedirect(Customer $customer, string $message)
    {
        return redirect()->intended(route('home'))
            ->with('success', $message . ', ' . $customer->first_name . '!');
    }
}
