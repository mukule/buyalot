<?php

namespace App\Http\Middleware;

use Illuminate\Foundation\Inspiring;
use Illuminate\Http\Request;
use Inertia\Middleware;
use Tighten\Ziggy\Ziggy;

class HandleInertiaRequests extends Middleware
{
    /**
     * The root template that's loaded on the first page visit.
     *
     * @var string
     */
    protected $rootView = 'app';

    /**
     * Determines the current asset version.
     */
    public function version(Request $request): ?string
    {
        return parent::version($request);
    }

    /**
     * Define the props that are shared by default.
     *
     * @return array<string, mixed>
     */
    public function share(Request $request): array
    {
        [$message, $author] = str(Inspiring::quotes()->random())->explode('-');

        $user  = $request->user();
        $roles = $user ? $user->getRoleNames() : collect();

        return [
            ...parent::share($request),

            'app' => [
                'name' => config('app.name'),
                'url'  => config('app.url'),
            ],

            'quote' => [
                'message' => trim($message),
                'author'  => trim($author),
            ],

            'auth' => $user ? [
                'user' => [
                    'id'    => $user->id,
                    'name'  => $user->name,
                    'email' => $user->email,
                ],
                'roles' => $roles,
                'counts' => [
                    'wishlist' => $user->wishlists()->count(),
                    'cart'     => 1, 
                ],
                'wishlistProductIds' => $user->wishlists()->pluck('product_id'),
            ] : [
                'user' => null,
                'roles' => [],
                'counts' => [
                    'wishlist' => 0,
                    'cart'     => 0,
                ],
                'wishlistProductIds' => [],
            ],

            'flash' => [
                'success'    => (string) $request->session()->get('success'),
                'error'      => (string) $request->session()->get('error'),
                'info'       => (string) $request->session()->get('info'),
                'step'       => $request->session()->get('step'),
                'product_id' => $request->session()->get('product_id'),
            ],

            'ziggy' => [
                ...(new Ziggy)->toArray(),
                'location' => $request->url(),
            ],

            'sidebarOpen' => ! $request->hasCookie('sidebar_state') || $request->cookie('sidebar_state') === 'true',
        ];
    }
}
