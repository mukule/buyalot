<?php

namespace App\Support;

use App\Models\User;
use Illuminate\Http\Request;

/**
 * The separate sign-in portals (customer storefront, admin, vendor, delivery,
 * distributor). Keeps login, logout, forgot/reset-password and welcome-email
 * links on the portal a user belongs to, e.g. admins always land on /admin/login.
 */
class Portal
{
    public const CUSTOMER    = 'customer';
    public const ADMIN       = 'admin';
    public const VENDOR      = 'vendor';
    public const DELIVERY    = 'delivery';
    public const DISTRIBUTOR = 'distributor';

    /** Portals with their own /{portal}/forgot-password and reset pages. */
    public const PREFIXED = [self::ADMIN, self::VENDOR, self::DELIVERY, self::DISTRIBUTOR];

    /** Remembers the last portal used, so expired sessions return to the right login. */
    public const COOKIE = 'login_portal';

    protected const LOGIN_ROUTES = [
        self::CUSTOMER    => 'login',
        self::ADMIN       => 'admin.login',
        self::VENDOR      => 'vendor.login',
        self::DELIVERY    => 'delivery.login',
        self::DISTRIBUTOR => 'distributor.login',
    ];

    public static function normalize(?string $portal): string
    {
        return array_key_exists((string) $portal, self::LOGIN_ROUTES) ? $portal : self::CUSTOMER;
    }

    public static function loginUrl(?string $portal): string
    {
        return route(self::LOGIN_ROUTES[self::normalize($portal)]);
    }

    public static function forgotPasswordUrl(?string $portal): string
    {
        $portal = self::normalize($portal);

        return in_array($portal, self::PREFIXED, true)
            ? route('portal.password.request', ['portal' => $portal])
            : route('password.request');
    }

    public static function forgotPasswordSubmitUrl(?string $portal): string
    {
        $portal = self::normalize($portal);

        return in_array($portal, self::PREFIXED, true)
            ? route('portal.password.email', ['portal' => $portal])
            : route('password.email');
    }

    public static function resetPasswordUrl(?string $portal, string $token, string $email): string
    {
        $portal = self::normalize($portal);

        return in_array($portal, self::PREFIXED, true)
            ? route('portal.password.reset', ['portal' => $portal, 'token' => $token, 'email' => $email])
            : route('password.reset', ['token' => $token, 'email' => $email]);
    }

    public static function resetPasswordSubmitUrl(?string $portal): string
    {
        $portal = self::normalize($portal);

        return in_array($portal, self::PREFIXED, true)
            ? route('portal.password.store', ['portal' => $portal])
            : route('password.store');
    }

    /**
     * The portal a user normally signs in through.
     */
    public static function forUser(?User $user, ?string $activeRole = null): string
    {
        if (! $user) {
            return self::CUSTOMER;
        }

        return match (true) {
            $activeRole === 'customer'                         => self::CUSTOMER,
            $activeRole === 'distributor'                      => self::DISTRIBUTOR,
            in_array($activeRole, ['seller', 'vendor'], true)  => self::VENDOR,
            $user->hasRole('delivery')                         => self::DELIVERY,
            in_array($user->user_type, ['seller', 'vendor'], true) => self::VENDOR,
            $user->user_type === 'user'                        => self::ADMIN,
            default                                            => self::CUSTOMER,
        };
    }

    /**
     * Where an unauthenticated request should be sent to sign in.
     */
    public static function loginUrlForRequest(Request $request): string
    {
        if ($request->is('admin/delivery', 'admin/delivery/*')) {
            return self::loginUrl(self::DELIVERY);
        }
        if ($request->is('distributor', 'distributor/*')) {
            return self::loginUrl(self::DISTRIBUTOR);
        }
        if ($request->is('admin', 'admin/*')) {
            // Sellers also work under /admin; send them back to the vendor login they used.
            $remembered = $request->cookie(self::COOKIE);

            return self::loginUrl($remembered === self::VENDOR ? self::VENDOR : self::ADMIN);
        }

        return self::loginUrl(self::CUSTOMER);
    }

    /**
     * Record the portal used to sign in (session for logout, cookie for expired sessions).
     */
    public static function remember(string $portal): void
    {
        $portal = self::normalize($portal);
        session(['login_portal' => $portal]);
        cookie()->queue(cookie()->forever(self::COOKIE, $portal));
    }
}
