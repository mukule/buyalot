<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use Inertia\Inertia;
use Inertia\Response;

class NoPermissionController extends Controller
{
    /**
     * Show the no permission page
     */
    public function show(Request $request): Response
    {
        $message = $request->get('message', "You don't have permission to access this resource.");
        $requiredPermission = $request->get('permission');
        $fromUrl = $request->get('from', url()->previous());

        $suggestedActions = $this->getSuggestedActions($request, $fromUrl);

        return Inertia::render('Errors/NoPermission', [
            'message' => $message,
            'requiredPermission' => $requiredPermission,
            'suggestedActions' => $suggestedActions,
        ]);
    }

    /**
     * Get suggested actions based on user permissions and context
     */
    private function getSuggestedActions(Request $request, string $fromUrl): array
    {
        $user = $request->user();
        $suggestedActions = [
            [
                'title' => 'Go to Dashboard',
                'href' => '/admin/dashboard',
                'description' => 'Return to the main dashboard'
            ]
        ];

        if (!$user) {
            return $suggestedActions;
        }
        if ($user->hasRole('seller')) {
            $suggestedActions = [
                [
                    'title' => 'Seller Dashboard',
                    'href' => '/seller/dashboard',
                    'description' => 'Access your seller panel'
                ],
                [
                    'title' => 'My Products',
                    'href' => '/seller/products',
                    'description' => 'Manage your products'
                ],
                [
                    'title' => 'Profile',
                    'href' => '/seller/profile',
                    'description' => 'Update your profile information'
                ],
            ];
        } else {
            if ($user->hasAnyPermission(['view-users', 'create-users'])) {
                $suggestedActions[] = [
                    'title' => 'Users',
                    'href' => '/admin/users',
                    'description' => 'Manage system users'
                ];
            }

            if ($user->hasAnyPermission(['view-products', 'create-products'])) {
                $suggestedActions[] = [
                    'title' => 'Products',
                    'href' => '/admin/products',
                    'description' => 'Manage products'
                ];
            }

            if ($user->hasAnyPermission(['view-orders', 'process-orders'])) {
                $suggestedActions[] = [
                    'title' => 'Orders',
                    'href' => '/admin/orders',
                    'description' => 'View and manage orders'
                ];
            }

            if ($user->hasAnyPermission(['view-sellers', 'approve-sellers'])) {
                $suggestedActions[] = [
                    'title' => 'Sellers',
                    'href' => '/admin/sellers',
                    'description' => 'Manage sellers'
                ];
            }
        }
        $suggestedActions[] = [
            'title' => 'My Profile',
            'href' => '/profile',
            'description' => 'View and edit your profile'
        ];

        return $suggestedActions;
    }
}
