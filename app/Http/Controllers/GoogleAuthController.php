<?php

namespace App\Http\Controllers;

use App\Models\User;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Str;
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

            // Check if user already exists with this Google ID
            $user = User::where('google_id', $googleUser->id)->first();

            if ($user) {
                Auth::login($user);
                return redirect()->intended('dashboard');
            }

            // Check if user exists with this email
            $existingUser = User::where('email', $googleUser->email)->first();

            if ($existingUser) {
                // Link Google account to existing user
                $existingUser->update([
                    'google_id' => $googleUser->id,
                    'avatar' => $googleUser->avatar,
                ]);

                Auth::login($existingUser);
                return redirect()->intended('dashboard');
            }

//            // Create new user
//            $newUser = User::create([
//                'name' => $googleUser->name,
//                'email' => $googleUser->email,
//                'google_id' => $googleUser->id,
//                'avatar' => $googleUser->avatar,
//                'password' => Hash::make(Str::random(24)),
//                'email_verified_at' => now(),
//            ]);

//            Auth::login($newUser);
//            return redirect()->intended('dashboard');
            return redirect()->route('login')->withErrors([
                'google' => 'No account found with this Google account. Please create an account first or use a different login method.'
            ]);

        } catch (\Exception $e) {
            return redirect()->route('login')->withErrors([
                'google' => 'Something went wrong with Google authentication. Please try again.'
            ]);
        }
    }
}
