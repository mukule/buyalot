<?php

namespace App\Http\Controllers\POS;

use App\Http\Controllers\Controller;
use App\Models\User;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Inertia\Inertia;

class PosLoginController extends Controller
{
    public function showLoginForm()
    {
        return Inertia::render('Admin/POS/Login');
    }

    public function login(Request $request)
    {
        $request->validate([
            'email' => 'required|email',
            'pin' => 'required|string|size:4',
        ]);

        $user = User::where('email', $request->email)->first();

        if (!$user) {
            return back()->withErrors([
                'email' => 'The provided email was not found.',
            ]);
        }

        if ($user->pos_pin !== $request->pin) {
            return back()->withErrors([
                'pin' => 'Invalid PIN.',
            ]);
        }

        Auth::login($user);
        $request->session()->regenerate();

        // Auto-verify POS session since we logged in with PIN
        $request->session()->put('pos_verified', true);
        $request->session()->put('pos_verified_at', now());

        return redirect()->route('admin.pos.index');
    }
}
