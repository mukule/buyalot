<?php

namespace App\Traits;

use Inertia\Inertia;
use Symfony\Component\HttpFoundation\Response as HttpResponse;

trait HasPermissionCheck
{
    protected function checkPermissionOrFail(string|array $permissions, string $customMessage = null): ?HttpResponse
    {
        $user = auth()->user();
        if (!$user) {
            return redirect()->route('login');
        }
        if ($user->hasRole('super-admin')) {
            return null;
        }
        $permissions = is_array($permissions) ? $permissions : [$permissions];
        $hasPermission = false;
        foreach ($permissions as $permission) {
            if ($user->can($permission)) {
                $hasPermission = true;
                break;
            }
        }
        if (!$hasPermission) {
            return $this->noPermissionResponse($permissions, $customMessage);
        }

        return null;
    }

    protected function noPermissionResponse(array $permissions, string $customMessage = null): HttpResponse
    {
        $message = $customMessage ?? $this->buildPermissionMessage($permissions);
        if (request()->expectsJson()) {
            return response()->json([
                'message' => $message,
                'required_permissions' => $permissions,
                'error_code' => 'INSUFFICIENT_PERMISSIONS'
            ], 403);
        }
        if (request()->header('X-Inertia')) {
            return Inertia::render('Errors/NoPermission', [
                'message' => $message,
                'requiredPermission' => implode(', ', $permissions),
                'suggestedActions' => $this->getSuggestedActions(),
            ])->toResponse(request())->setStatusCode(403);
        }
        abort(403, $message);
    }
    protected function redirectIfNoPermission(string|array $permissions, string $customMessage = null): ?\Illuminate\Http\RedirectResponse
    {
        $response = $this->checkPermissionOrFail($permissions, $customMessage);
        if ($response && !request()->header('X-Inertia') && !request()->expectsJson()) {
            $permissions = is_array($permissions) ? $permissions : [$permissions];
            return redirect()->route('no-permission', [
                'message' => $customMessage ?? $this->buildPermissionMessage($permissions),
                'permission' => implode(', ', $permissions),
                'from' => request()->url()
            ]);
        }

        return $response instanceof \Illuminate\Http\RedirectResponse ? $response : null;
    }

    protected function authorizeWithMessage(string|array $abilities, mixed $arguments = [], string $customMessage = null): void
    {
        try {
            if (is_array($abilities)) {
                // Check multiple abilities (OR logic)
                $hasPermission = false;
                foreach ($abilities as $ability) {
                    if (auth()->user()->can($ability, $arguments)) {
                        $hasPermission = true;
                        break;
                    }
                }
                if (!$hasPermission) {
                    throw new \Illuminate\Auth\Access\AuthorizationException($customMessage ?? 'Unauthorized.');
                }
            } else {
                // Single ability check
                $this->authorize($abilities, $arguments);
            }
        } catch (\Illuminate\Auth\Access\AuthorizationException $e) {
            if (request()->header('X-Inertia')) {
                $abilities = is_array($abilities) ? $abilities : [$abilities];
                throw new \Illuminate\Auth\Access\AuthorizationException(
                    $customMessage ?? $this->buildPermissionMessage($abilities)
                );
            }
            throw $e;
        }
    }
    private function buildPermissionMessage(array $permissions): string
    {
        if (count($permissions) === 1) {
            return "You need the '{$permissions[0]}' permission to access this resource.";
        }

        $permissionsList = implode("', '", array_slice($permissions, 0, -1));
        $lastPermission = end($permissions);

        return "You need one of the following permissions: '{$permissionsList}' or '{$lastPermission}'.";
    }

    private function getSuggestedActions(): array
    {
        $user = auth()->user();
        $actions = [
            [
                'title' => 'Go to Dashboard',
                'href' => '/admin/dashboard',
                'description' => 'Return to the main dashboard'
            ]
        ];

        if ($user && $user->hasRole('seller')) {
            return [
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
            ];
        }

        return $actions;
    }
}
