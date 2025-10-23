<?php
namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use Inertia\Inertia;

class ShareSellerVerificationStatus
{
    public function handle(Request $request, Closure $next)
    {
        if ($request->user()) {
            $sellerApp = $request->user()->sellerApplication;
            $isVerified = $sellerApp?->verified ?? false;

            Inertia::share('auth.isVerifiedSeller', $isVerified);
        }
        return $next($request);
    }
}
