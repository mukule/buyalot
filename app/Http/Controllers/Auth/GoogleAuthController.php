<?php

namespace App\Http\Controllers\Auth;

use App\Http\Controllers\Controller;
use App\Models\Customer\Customer;
use App\Models\User;
use DB;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Laravel\Socialite\Facades\Socialite;

class GoogleAuthController extends Controller
{
    public function redirect()
    {
        return Socialite::driver('google')->redirect();
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
            $googleUser = Socialite::driver('google')->user();

            if (!$googleUser->email) {
                return redirect()->route('login')->with('error', 'Google account must have an email.');
            }

            DB::beginTransaction();

            // Try find existing customer by google_id or email
            $customer = User::where('google_id', $googleUser->id)
                ->orWhere('email', $googleUser->email)
                ->first();

            if ($customer) {
                $user = User::where('user_id', $customer->user_id)->first();
                $user->update([
                    'google_id' => $googleUser->id,
                    'avatar' => $googleUser->avatar,
                    'provider' => 'google',
                    'provider_id' => $googleUser->id,
                    'provider_verified_at' => now(),
                    'email_verified_at' => $customer->email_verified_at ?? now(),
                    'last_login_at' => now(),
                ]);

                DB::commit();
                Auth::guard('web')->login($user);
                request()->session()->regenerate();
                return redirect()->intended(route('home'))
                    ->with('success', 'Welcome back, ' . $customer->first_name . '!');
            }

            // Otherwise create a new customer
            $nameParts = $this->parseGoogleName($googleUser->name);

            $newUser= User::create([
                'name' => $nameParts['first_name']. ' ' .$nameParts['last_name'],
                'email' => $googleUser->email,
                'password' => bcrypt(str()->random(16)),
                'google_id' => $googleUser->id,
                'provider'   => 'google',
                'provider_verified_at' => now(),
                'email_verified_at' => now(),
                'last_login_at' => now(),
                'status' => true,
                'user_type' => 'customer',
                'avatar' => $googleUser->avatar,

            ]);

            $newCustomer = Customer::create([
                'customer_code' => uniqid('CUS-'),
                'first_name' => $nameParts['first_name'],
                'last_name'  => $nameParts['last_name'],
                'email'      => $googleUser->email,
                'avatar'     => $googleUser->avatar,
                'customer_type' => 'individual',
                'status' => 'active',
                'user_id' => $newUser->id,
            ]);

            DB::commit();

            Auth::guard('web')->login($newUser);
            request()->session()->regenerate();

            return redirect()->route('home')->with('success', 'Welcome to our shop, ' . $newCustomer->first_name . '!');

        } catch (\Exception $e) {
            DB::rollBack();
            \Log::error('Google OAuth error: '.$e->getMessage());

            return redirect()->route('login')->with('error', 'Google login failed, please try again.');
        }
    }

    private function parseGoogleName(string $fullName): array
    {
        $parts = explode(' ', trim($fullName), 2);

        return [
            'first_name' => $parts[0] ?? 'Customer',
            'last_name'  => $parts[1] ?? '',
        ];
    }
}
