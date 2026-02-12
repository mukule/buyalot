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
        $user = $request->user();

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

            'quote' => function () {
                [$message, $author] = str(Inspiring::quotes()->random())->explode('-');
                return [
                    'message' => trim($message),
                    'author'  => trim($author),
                ];
            },

            'auth' => [
                'user' => $user ? [
                    'id'             => $user->id,
                    'name'           => $user->name,
                    'email'          => $user->email,
                    'secondary_role' => $user->secondary_role,
                    'user_type'      => $user->user_type,
                ] : null,
                'active_role' => session('active_role', $user?->user_type),
                'switchable_roles' => $user ? $user->getPortalRoles() : [],
                'customer_id' => function () use ($user) {
                    $activeRole = session('active_role', $user?->user_type);
                    if ($user && $activeRole === 'customer') {
                        $customerId = session('customer_id');
                        if (! $customerId) {
                            $customer = Customer::where('user_id', $user->id)->first();
                            if ($customer) {
                                session(['customer_id' => $customer->id]);
                                return $customer->id;
                            }
                        }
                        return $customerId;
                    }
                    return null;
                },
                'roles'       => fn () => $user ? $user->getRoleNames() : [],
                'permissions' => fn () => $user ? $user->getAllPermissions()->pluck('name') : [],
                
                // Lazy loaded Wishlist and Cart counts
                'counts' => [
                    'wishlist' => fn () => (new WishlistService($request))->getWishlistCount($request),
                    'cart'     => fn () => app(CartService::class)->getCart($request)->items->count(),
                ],
                'wishlistVariantIds' => fn () => (new WishlistService($request))->getWishlistVariantIds($request),
                'cartItems' => fn () => app(CartService::class)->getCart($request)->items->map(fn($item) => [
                    'product_variant_id' => $item->product_variant_id,
                    'quantity'           => $item->quantity,
                ]),
            ],

            'appName' => config('app.name'),

            // 🔑 Full cart shared globally - but only evaluated if used
            'cart' => fn () => app(CartService::class)
                ->getCart($request)
                ->load('items.productVariant.product'),

            'flash' => [
                'success'    => fn () => $request->session()->get('success'),
                'error'      => fn () => $request->session()->get('error'),
                'info'       => fn () => $request->session()->get('info'),
                'step'       => fn () => $request->session()->get('step'),
                'product_id' => fn () => $request->session()->get('product_id'),
            ],

            'ziggy' => fn () => [
                ...(new Ziggy)->toArray(),
                'location' => $request->url(),
            ],

            'sidebarOpen' => ! $request->hasCookie('sidebar_state') || $request->cookie('sidebar_state') === 'true',
        ];
    }
}