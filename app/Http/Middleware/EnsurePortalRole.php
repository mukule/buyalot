<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Symfony\Component\HttpFoundation\Response;

class EnsurePortalRole
{
    /**
     * Ensure the authenticated user has the given portal role (customer, seller, distributor).
     * Sets active_role so the rest of the app treats them as that role.
     */
    public function handle(Request $request, Closure $next, string ...$roles): Response
    {
        if (! Auth::check()) {
            return redirect()->route('login');
        }

        $user = Auth::user();
        $allowed = false;
        $targetRole = null;
        foreach ($roles as $role) {
            if ($user->hasPortalRole($role)) {
                $allowed = true;
                $targetRole = $role === 'vendor' ? 'seller' : $role;
                break;
            }
        }

        if (! $allowed) {
            abort(403, 'You do not have access to this portal.');
        }

        session(['active_role' => $targetRole]);

        return $next($request);
    }
}
