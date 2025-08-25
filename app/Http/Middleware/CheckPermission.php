<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Inertia\Inertia;
use Symfony\Component\HttpFoundation\Response;

class CheckPermission
{
    /**
     * Handle an incoming request.
     */
    public function handle(Request $request, Closure $next, ...$permissions): Response
    {
        if (!Auth::check()) {
            return redirect()->route('login');
        }

        $user = Auth::user();

        // Super admin has all permissions
        if ($user->hasRole('super-admin')) {
            return $next($request);
        }

        // Check if user has any of the required permissions
        $hasPermission = false;
        foreach ($permissions as $permission) {
            if ($user->can($permission)) {
                $hasPermission = true;
                break;
            }
        }

        if (!$hasPermission) {
            // For AJAX/API requests
            if ($request->expectsJson()) {
                return response()->json([
                    'message' => 'You do not have the required authorization.',
                    'required_permissions' => $permissions,
                    'error_code' => 'INSUFFICIENT_PERMISSIONS'
                ], 403);
            }

            // For Inertia requests
            if ($request->header('X-Inertia')) {
                return Inertia::render('Errors/NoPermission', [
                    'message' => $this->buildPermissionMessage($permissions),
                    'requiredPermission' => implode(', ', $permissions),
                    'suggestedActions' => $this->getSuggestedActions($request),
                ])->toResponse($request)->setStatusCode(403);
            }

            // For regular requests
            abort(403, 'You do not have permission to access this resource.');
        }

        return $next($request);
    }

    /**
     * Build a user-friendly permission message
     */
    private function buildPermissionMessage(array $permissions): string
    {
        if (count($permissions) === 1) {
            return "You need the '{$permissions[0]}' permission to access this resource.";
        }

        $permissionsList = implode("', '", array_slice($permissions, 0, -1));
        $lastPermission = end($permissions);

        return "You need one of the following permissions to access this resource: '{$permissionsList}' or '{$lastPermission}'.";
    }

    /**
     * Get suggested actions based on the current route
     */
    private function getSuggestedActions(Request $request): array
    {
        $currentPath = $request->path();
        $suggestedActions = [
            [
                'title' => 'Go to Dashboard',
                'href' => '/admin/dashboard',
                'description' => 'Return to the main dashboard'
            ]
        ];

        // Add role-specific suggestions
        $user = Auth::user();

        if ($user->hasRole('seller')) {
            $suggestedActions[] = [
                'title' => 'Seller Dashboard',
                'href' => '/seller/dashboard',
                'description' => 'Access your seller panel'
            ];
            $suggestedActions[] = [
                'title' => 'My Products',
                'href' => '/seller/products',
                'description' => 'Manage your products'
            ];
        }

        if ($user->hasAnyPermission(['view-orders', 'process-orders'])) {
            $suggestedActions[] = [
                'title' => 'Orders',
                'href' => '/admin/orders',
                'description' => 'View and manage orders'
            ];
        }

        return $suggestedActions;
    }
}
