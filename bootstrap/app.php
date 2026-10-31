<?php

use App\Http\Middleware\CheckPermission;
use App\Http\Middleware\HandleAppearance;
use App\Http\Middleware\HandleInertiaRequests;
use Illuminate\Foundation\Application;
use Illuminate\Foundation\Configuration\Exceptions;
use Illuminate\Foundation\Configuration\Middleware;
use Illuminate\Http\Middleware\AddLinkHeadersForPreloadedAssets;
use App\Http\Middleware\Admin;
use Spatie\Permission\Middleware\PermissionMiddleware;
use Spatie\Permission\Middleware\RoleMiddleware;
use App\Http\Middleware\EnsurePortalRole;
use App\Http\Middleware\ShareSellerVerificationStatus;
use Spatie\Permission\Middleware\RoleOrPermissionMiddleware;


return Application::configure(basePath: dirname(__DIR__))
    ->withRouting(
        web: __DIR__.'/../routes/web.php',
        commands: __DIR__.'/../routes/console.php',
        health: '/up',
        api: __DIR__.'/../routes/api.php',
        then: function () {
            require base_path('routes/order.php');
        },
    )
    ->withMiddleware(function (Middleware $middleware) {
        //$middleware->encryptCookies(except: ['appearance', 'sidebar_state']);
        $middleware->encryptCookies(except: ['appearance', 'sidebar_state', 'wishlist_token']);

        $middleware->web(append: [
            HandleAppearance::class,
            HandleInertiaRequests::class,
            AddLinkHeadersForPreloadedAssets::class,
            ShareSellerVerificationStatus::class,
        ]);

        // Signed-out visitors go to the login page of the portal they were trying to reach
        // (e.g. /admin/* → /admin/login), not the storefront login.
        $middleware->redirectGuestsTo(fn (\Illuminate\Http\Request $request) => \App\Support\Portal::loginUrlForRequest($request));

        // Signed-in users opening a login/forgot-password page go to their own dashboard.
        $middleware->redirectUsersTo(function (\Illuminate\Http\Request $request) {
            $portal = $request->session()->get('login_portal')
                ?? \App\Support\Portal::forUser($request->user(), $request->session()->get('active_role'));

            return match ($portal) {
                \App\Support\Portal::ADMIN, \App\Support\Portal::VENDOR => route('admin.dashboard'),
                \App\Support\Portal::DELIVERY    => route('delivery.dashboard'),
                \App\Support\Portal::DISTRIBUTOR => route('distributor.dashboard'),
                default                          => '/',
            };
        });

         $middleware->alias([
            'admin' => Admin::class,
            'role' => RoleMiddleware::class,
            'verified' => ShareSellerVerificationStatus::class,
             'permission' => PermissionMiddleware::class,
             'role_or_permission' => RoleOrPermissionMiddleware::class,
             'check_permission' => CheckPermission::class,
             'ensure.portal.role' => EnsurePortalRole::class,
        ]);
    })
    ->withExceptions(function (Exceptions $exceptions) {

    })->create();
