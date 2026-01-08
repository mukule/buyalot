<?php

namespace App\Http\Middleware;

use App\Models\Customer\Customer;
use App\Services\CartService;
use App\Services\WishlistService;
use Illuminate\Foundation\Inspiring;
use Illuminate\Http\Request;
use Inertia\Middleware;
use Tighten\Ziggy\Ziggy;

class HandleInertiaRequests extends Middleware
{
    protected $rootView = 'app';

    public function version(Request $request): ?string
    {
        return parent::version($request);
    }

    public function share(Request $request): array
    {
        [$message, $author] = str(Inspiring::quotes()->random())->explode('-');

        $user  = $request->user();
        $roles = $user ? $user->getRoleNames() : collect();

        // ------------------------
        // Cart (works for auth + guests)
        // ------------------------
        $cartService = app(CartService::class);
        $cart = $cartService
            ->getCart($request)
            ->load('items.productVariant.product');

        // ------------------------
        // Wishlist (using service)
        // ------------------------
        $wishlistService = new WishlistService($request);

        $wishlistItems = $wishlistService->getWishlistVariantIds($request);
        $wishlistCount = $wishlistService->getWishlistCount($request);

        // ------------------------
        // Customer session
        // ------------------------
        $customerId = null;
        if ($user && $user->user_type === 'customer') {
            $customerId = session('customer_id');
            if (!$customerId) {
                $customer = Customer::where('user_id', $user->id)->first();
                if ($customer) {
                    $customerId = $customer->id;
                    session(['customer_id' => $customer->id]);
                }
            }
        }

        // ------------------------
        // Shared data
        // ------------------------
        return [
            ...parent::share($request),

            'app' => [
                'name' => config('app.name'),
                'url'  => config('app.url'),
                'appName' => config('app.name'),
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
                    'secondary_role' => $user->secondary_role,
                    'user_type' => $user->user_type,
                ] : null,
                'active_role' => session('active_role', $request->user()?->user_type),
                'customer_id' => $customerId,
                'roles' => $user ? $roles : [],
                'permissions' => $user ? $user->getAllPermissions()->pluck('name') : [],
                'counts' => [
                    'wishlist' => $wishlistCount,
                    'cart'     => $cart->items->count(),
                ],
                'wishlistVariantIds' => $wishlistItems,
                'cartItems' => $cart->items->map(fn($item) => [
                    'product_variant_id' => $item->product_variant_id,
                    'quantity'           => $item->quantity,
                ]),
            ],

            'appName' => config('app.name'),

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
                'location' => (string) $request->url(),
            ],

            'sidebarOpen' => ! $request->hasCookie('sidebar_state') || $request->cookie('sidebar_state') === 'true',
        ];
    }
}
