<?php

namespace App\Http\Middleware;

use App\Models\Wishlist;
use App\Services\CartService;
use Illuminate\Foundation\Inspiring;
use Illuminate\Http\Request;
use Inertia\Middleware;
use Tighten\Ziggy\Ziggy;

class HandleInertiaRequests extends Middleware
{
    /**
     * The root template that's loaded on the first page visit.
     *
     * @see https://inertiajs.com/server-side-setup#root-template
     *
     * @var string
     */
    protected $rootView = 'app';

    /**
     * Determines the current asset version.
     *
     * @see https://inertiajs.com/asset-versioning
     */
    public function version(Request $request): ?string
    {
        return parent::version($request);
    }

    /**
     * Define the props that are shared by default.
     *
     * @see https://inertiajs.com/shared-data
     *
     * @return array<string, mixed>
     */
    public function share(Request $request): array
    {
        [$message, $author] = str(Inspiring::quotes()->random())->explode('-');

        $user  = $request->user();
        $roles = $user ? $user->getRoleNames() : collect();
//        $permissions => $user ? $user->getAllPermissions()->pluck('name') : [];

        // ------------------------
        // Cart (works for auth + guests)
        // ------------------------
        $cartService = app(CartService::class);
        $cart = $cartService
            ->getCart($request)
            ->load('items.productVariant.product'); // eager load product + variant

        // ------------------------
        // Wishlist (works for auth + guests)
        // ------------------------
        $wishlistQuery = Wishlist::query();
        if ($user) {
            $wishlistQuery->forOwner($user->id, null);
        } else {
            $token = $request->cookie('wishlist_token');
            $wishlistQuery->forOwner(null, $token);
        }
        $wishlistItems = $wishlistQuery->pluck('product_variant_id');

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

            'auth' => [
                'user' => $user ? [
                    'id'    => $user->id,
                    'name'  => $user->name,
                    'email' => $user->email,
                ] : null,
                'roles' => $user ? $roles : [],
                'permissions' => $user ? $user->getAllPermissions()->pluck('name') : [],
                'counts' => [
                    'wishlist' => $wishlistItems->count(),
                    'cart'     => $cart->items->count(),
                ],
                'wishlistVariantIds' => $wishlistItems,
                'cartItems' => $cart->items->map(fn($item) => [
                    'product_variant_id' => $item->product_variant_id,
                    'quantity'           => $item->quantity,
                ]),
            ],

            // 🔑 Full cart shared globally
            'cart' => $cart,

            'flash' => [
                'success'    => (string) $request->session()->get('success'),
                'error'      => (string) $request->session()->get('error'),
                'info'       => (string) $request->session()->get('info'),
                'step'       => $request->session()->get('step'),
                'product_id' => $request->session()->get('product_id'),
            ],

            'ziggy' => [
                ...(new Ziggy)->toArray(),
//                'location' => $request->url(),
                'location' => (string) $request->url(),
            ],

            'sidebarOpen' => ! $request->hasCookie('sidebar_state') || $request->cookie('sidebar_state') === 'true',
        ];
    }
}
