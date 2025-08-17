<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use Inertia\Inertia;
use Symfony\Component\HttpFoundation\Response;

class ShareUserPermissions
{
    public function handle(Request $request, Closure $next): Response
    {
        if ($request->user()) {
            Inertia::share([
                'auth.permissions' => $request->user()->getAllPermissions()->pluck('name')->toArray(),
                'auth.roles' => $request->user()->getRoleNames()->toArray(),
            ]);
        }

        return $next($request);
    }
}
