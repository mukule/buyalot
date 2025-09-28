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
    public function redirect1()
    {
        info("started google auth redirect....");
        try {
            $redirectUri = route('google.callback');
            \Log::info('Google OAuth Redirect URI: ' . $redirectUri);

            return Socialite::driver('google')
                ->redirectUrl($redirectUri)
                ->redirect();
        } catch (\Exception $e) {
            \Log::error('Google OAuth Redirect Error', [
                'message' => $e->getMessage(),
                'trace' => $e->getTraceAsString()
            ]);

            return redirect()->route('login')
                ->with('error', 'Failed to connect to Google. Please try again.');
        }
    }

    public function redirect()
    {
        return Socialite::driver('google')
            ->redirectUrl(route('google.callback'))
            ->redirect();
    }

    public function register()
    {
        info("started google auth register....");
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
                return redirect()->route('login')->with('error','Email is required from Google account.');
            }

            DB::beginTransaction();

            $authIntent = session('google_auth_intent', 'login');
            session()->forget('google_auth_intent');

            $user = User::where('google_id', $googleUser->id)->first();

            if ($user) {
                $customer = Customer::where('user_id', $user->id)->first();

                if ($customer) {
                    if ($authIntent === 'register') {
                        DB::rollBack();
                        return redirect()->route('login')->with('error',
                            'An account with this Google account already exists. Please log in instead.');
                    }

                    $this->updateExistingCustomerFromGoogle($customer, $googleUser);
                    DB::commit();
                    Auth::login($customer);
                    request()->session()->regenerate();
                    return redirect()->route('home')->with('success', 'Welcome back!');
                }
            }

            $existingCustomer = Customer::where('email', $googleUser->email)->first();

            if ($existingCustomer) {
                if ($authIntent === 'register') {
                    DB::rollBack();
                    return redirect()->route('login')->with('error',
                        'An account with this email already exists. Please log in instead.');
                }
                $user = User::where('email', $googleUser->email)->first();
                $this->linkGoogleToExistingCustomer($existingCustomer, $googleUser);
                DB::commit();
                    if ($user) {
                        Auth::login($existingCustomer);
                        request()->session()->regenerate();
                    }else{
                        return redirect()->route('login')->with('error','Sory, could not veryfy user account');
                    }
                return redirect()->route('home')->with('success', 'Google account linked successfully.');
            }

            $newCustomer = $this->createCustomerFromGoogle($googleUser);
            DB::commit();
            Auth::login($newCustomer);
            request()->session()->regenerate();

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

            return redirect()->route($redirectRoute)->with(
                'error','Authentication failed. Please try again.');
        } catch (\Throwable $e) {
            log($e->getMessage());
            DB::rollBack();
            return $this->redirect()->route("/")->with("error","Authentication failed. Please try again.");
        }
    }

    private function updateExistingCustomerFromGoogle(Customer $customer, $googleUser): void
    {
        $customer->update([
            'avatar' => $googleUser->avatar,
        ]);

        if ($customer->user) {
            $customer->user->update([
                'name' => $customer->first_name . ' ' . $customer->last_name,
                'email' => $customer->email,
                'last_login_at' => now(),
            ]);
        }
    }

    private function linkGoogleToExistingCustomer(Customer $customer, $googleUser): void
    {
        $customer->update([
            'avatar' => $googleUser->avatar,
        ]);

        if ($customer->user) {
            $customer->user->update([
                'name' => $customer->first_name . ' ' . $customer->last_name,
                'email' => $customer->email,
                'provider' => 'google',
                'provider_verified_at' => now(),
                'email_verified_at' => $customer->email_verified_at ?? now(),
                'last_login_at' => now(),
                'google_id' => $googleUser->id,
            ]);
        }
    }

    private function createCustomerFromGoogle($googleUser): Customer
    {
        info('Creating new customer from Google');
        $nameParts = $this->parseGoogleName($googleUser->name);
//        $user = User::create([
//            'name' => $googleUser->name,
//            'email' => $googleUser->email,
//            'password' => bcrypt(str()->random(16)), // random password
//            'email_verified_at' => now(),
//            'google_id' => $googleUser->id,
//            'phone' => $googleUser->phone_number,
//            'provider' => 'google',
//            'provider_verified_at' => now(),
//            'last_login_at' => now(),
//        ]);
//        $user->assignRole('customer');
        $customer = Customer::create([
//            'user_id' => $user->id,
            'first_name' => $nameParts['first_name'],
            'last_name' => $nameParts['last_name'],
            'email' => $googleUser->email,
            'phone' => $googleUser->phone_number,
            'avatar' => $googleUser->avatar,
            'customer_type' => 'individual',
            'status' => 'active',
            'acquisition_source' => 'google_oauth',
        ]);

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

    private function successRedirect(Customer $customer, string $message)
    {
        return redirect()->intended(route('home'))
            ->with('success', $message . ', ' . $customer->first_name . '!');
    }
}
