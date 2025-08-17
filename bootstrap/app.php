<?php

use App\Http\Middleware\HandleAppearance;
use App\Http\Middleware\HandleInertiaRequests;
use App\Http\Middleware\ShareUserPermissions;
use Illuminate\Foundation\Application;
use Illuminate\Foundation\Configuration\Exceptions;
use Illuminate\Foundation\Configuration\Middleware;
use Illuminate\Http\Middleware\AddLinkHeadersForPreloadedAssets;
use App\Http\Middleware\Admin;
use Spatie\Permission\Middleware\PermissionMiddleware;
use Spatie\Permission\Middleware\RoleMiddleware;
use App\Http\Middleware\ShareSellerVerificationStatus;
use Spatie\Permission\Middleware\RoleOrPermissionMiddleware;


return Application::configure(basePath: dirname(__DIR__))
    ->withRouting(
        web: __DIR__.'/../routes/web.php',
        commands: __DIR__.'/../routes/console.php',
        health: '/up',
    )
    ->withMiddleware(function (Middleware $middleware) {
        $middleware->encryptCookies(except: ['appearance', 'sidebar_state']);
        $middleware->web(append: [
            HandleAppearance::class,
            HandleInertiaRequests::class,
            AddLinkHeadersForPreloadedAssets::class,
            ShareSellerVerificationStatus::class,
            ShareUserPermissions::class,
            \App\Http\Middleware\HandleInertiaRequests::class,
            \App\Http\Middleware\ShareUserPermissions::class,
        ]);

         $middleware->alias([
            'admin' => Admin::class,
            'role' => RoleMiddleware::class,
            'verified' => ShareSellerVerificationStatus::class,
             'permission' => PermissionMiddleware::class,
             'role_or_permission' => RoleOrPermissionMiddleware::class,
             'check_permission' => \App\Http\Middleware\CheckPermission::class,
         ]);

    })
    ->withExceptions(function (Exceptions $exceptions) {

    })->create();
