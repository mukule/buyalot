<?php

namespace App\Http\Controllers\Auth;

use App\Http\Controllers\Controller;
use App\Support\Portal;
use Illuminate\Auth\Events\PasswordReset;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Password;
use Illuminate\Support\Str;
use Illuminate\Validation\Rules;
use Illuminate\Validation\ValidationException;
use Inertia\Inertia;
use Inertia\Response;

class NewPasswordController extends Controller
{
    /**
     * Show the password reset page (storefront or a portal: /admin/reset-password/{token}, …).
     */
    public function create(Request $request, ?string $portal = null): Response
    {
        $portal = Portal::normalize($portal);

        return Inertia::render('auth/ResetPassword', [
            'email'     => $request->email,
            'token'     => $request->route('token'),
            'portal'    => $portal,
            'submitUrl' => Portal::resetPasswordSubmitUrl($portal),
            'loginUrl'  => Portal::loginUrl($portal),
        ]);
    }

    /**
     * Handle an incoming new password request.
     *
     * @throws \Illuminate\Validation\ValidationException
     */
    public function store(Request $request, ?string $portal = null): RedirectResponse
    {
        $portal = Portal::normalize($portal);

        $request->validate([
            'token' => 'required',
            'email' => 'required|email',
            'password' => ['required', 'confirmed', Rules\Password::defaults()],
        ]);

        // Here we will attempt to reset the user's password. If it is successful we
        // will update the password on an actual user model and persist it to the
        // database. Otherwise we will parse the error and return the response.
        $status = Password::reset(
            $request->only('email', 'password', 'password_confirmation', 'token'),
            function ($user) use ($request) {
                $user->forceFill([
                    'password' => bcrypt($request->password),
                    'remember_token' => Str::random(60),
                ])->save();

                event(new PasswordReset($user));
            }
        );

        // Back to the login page of the same portal (e.g. /admin/login).
        if ($status == Password::PasswordReset) {
            return redirect(Portal::loginUrl($portal))->with('status', __($status));
        }

        throw ValidationException::withMessages([
            'email' => [__($status)],
        ]);
    }
}
