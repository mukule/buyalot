<?php

namespace App\Http\Controllers\Auth;

use App\Http\Controllers\Controller;
use App\Support\Portal;
use Illuminate\Auth\Notifications\ResetPassword;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Password;
use Illuminate\Validation\ValidationException;
use Inertia\Inertia;
use Inertia\Response;

class PasswordResetLinkController extends Controller
{
    /**
     * Show the password reset link request page (storefront or a portal: /admin/forgot-password, …).
     */
    public function create(Request $request, ?string $portal = null): Response
    {
        $portal = Portal::normalize($portal);

        return Inertia::render('auth/ForgotPassword', [
            'status'    => $request->session()->get('status'),
            'portal'    => $portal,
            'submitUrl' => Portal::forgotPasswordSubmitUrl($portal),
            'loginUrl'  => Portal::loginUrl($portal),
        ]);
    }

    /**
     * Handle an incoming password reset link request.
     *
     * @throws \Illuminate\Validation\ValidationException
     */
    public function store(Request $request, ?string $portal = null): RedirectResponse
    {
        $portal = Portal::normalize($portal);

        $request->validate([
            'email' => 'required|email',
        ]);

        // The emailed link opens the reset page of the portal the request came from.
        ResetPassword::createUrlUsing(
            fn ($user, string $token) => Portal::resetPasswordUrl($portal, $token, $user->getEmailForPasswordReset())
        );

        try {
            $status = Password::sendResetLink($request->only('email'));
        } catch (\Throwable $e) {
            Log::error('Password reset email failed', ['email' => $request->input('email'), 'error' => $e->getMessage()]);

            throw ValidationException::withMessages([
                'email' => 'We could not send the reset email right now. Please try again in a few minutes or contact support.',
            ]);
        }

        if ($status === Password::RESET_THROTTLED) {
            throw ValidationException::withMessages(['email' => __($status)]);
        }

        // Same message whether or not the account exists, so emails can't be probed.
        return back()->with('status', __('If an account exists for that email, a reset link has been sent. Check your inbox (and spam folder).'));
    }
}
