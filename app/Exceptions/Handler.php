<?php

namespace App\Exceptions;

use Illuminate\Foundation\Exceptions\Handler as ExceptionHandler;
use Inertia\Inertia;
use Spatie\Permission\Exceptions\UnauthorizedException;
use Symfony\Component\HttpFoundation\Response;
use Throwable;

class Handler extends ExceptionHandler
{
    /**
     * A list of exception types with their corresponding custom log levels.
     *
     * @var array<class-string<\Throwable>, \Psr\Log\LogLevel::*>
     */
    protected $levels = [
        //
    ];

    /**
     * A list of the exception types that are not reported.
     *
     * @var array<int, class-string<\Throwable>>
     */
    protected $dontReport = [
        //
    ];

    /**
     * A list of the inputs that are never flashed to the session on validation exceptions.
     *
     * @var array<int, string>
     */
    protected $dontFlash = [
        'current_password',
        'password',
        'password_confirmation',
    ];

    public function register(): void
    {
        $this->reportable(function (Throwable $e) {
            //
        });
        $this->renderable(function (UnauthorizedException $e, $request) {
            if ($request->expectsJson()) {
                return response()->json([
                    'message' => 'You do not have the required authorization.',
                    'required_permission' => $this->extractRequiredPermission($e->getMessage()),
                    'error_code' => 'INSUFFICIENT_PERMISSIONS'
                ], 403);
            }
            if ($request->header('X-Inertia')) {
                return Inertia::render('Errors/NoPermission', [
                    'message' => $this->getCustomMessage($e->getMessage()),
                    'requiredPermission' => $this->extractRequiredPermission($e->getMessage()),
                    'suggestedActions' => $this->getSuggestedActions($request),
                ])->toResponse($request)->setStatusCode(403);
            }
            return response()->view('errors.403', [
                'message' => $this->getCustomMessage($e->getMessage()),
                'requiredPermission' => $this->extractRequiredPermission($e->getMessage()),
            ], 403);
        });

        $this->renderable(function (\Illuminate\Auth\Access\AuthorizationException $e, $request) {
            if ($request->expectsJson()) {
                return response()->json([
                    'message' => 'You are not authorized to perform this action.',
                    'error_code' => 'AUTHORIZATION_FAILED'
                ], 403);
            }

            if ($request->header('X-Inertia')) {
                return Inertia::render('Errors/NoPermission', [
                    'message' => 'You are not authorized to perform this action.',
                    'requiredPermission' => null,
                    'suggestedActions' => $this->getSuggestedActions($request),
                ])->toResponse($request)->setStatusCode(403);
            }

            return response()->view('errors.403', [
                'message' => 'You are not authorized to perform this action.',
            ], 403);
        });
    }
    private function extractRequiredPermission(string $message): ?string
    {
        // Spatie permission messages usually contain the permission name
        if (preg_match('/permission `([^`]+)`/', $message, $matches)) {
            return $matches[1];
        }

        if (preg_match('/role `([^`]+)`/', $message, $matches)) {
            return $matches[1] . ' (role)';
        }

        return null;
    }
    private function getCustomMessage(string $originalMessage): string
    {
        $permission = $this->extractRequiredPermission($originalMessage);

        if ($permission) {
            return "You don't have the required '{$permission}' permission to access this resource.";
        }

        return "You don't have permission to access this resource.";
    }
    private function getSuggestedActions($request): array
    {
        $currentPath = $request->path();
        $suggestedActions = [
            [
                'title' => 'Go to Dashboard',
                'href' => '/admin/dashboard',
                'description' => 'Return to the main dashboard'
            ]
        ];
        if (str_contains($currentPath, 'users')) {
            $suggestedActions[] = [
                'title' => 'View Profile',
                'href' => '/profile',
                'description' => 'Manage your own profile'
            ];
        } elseif (str_contains($currentPath, 'products')) {
            $suggestedActions[] = [
                'title' => 'Browse Products',
                'href' => '/products',
                'description' => 'View available products'
            ];
        } elseif (str_contains($currentPath, 'orders')) {
            $suggestedActions[] = [
                'title' => 'My Orders',
                'href' => '/orders',
                'description' => 'View your orders'
            ];
        }

        return $suggestedActions;
    }
}
