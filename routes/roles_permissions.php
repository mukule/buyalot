<?php

use App\Http\Controllers\Admin\PermissionController;
use App\Http\Controllers\Admin\RoleController;
use App\Http\Controllers\Admin\UserController;
use Illuminate\Support\Facades\Route;


Route::middleware(['auth', 'verified'])->group(function () {
    // Users
    Route::middleware(['check_permission:view-users'])->group(function () {
        Route::get('/users', [UserController::class, 'index'])->name('users.index');
        Route::get('/users/{user}', [UserController::class, 'show'])->name('users.show');
    });
    Route::middleware(['check_permission:create-users'])->group(function () {
//        Route::get('/users/create', [UserController::class, 'create'])->name('users.create');
        Route::post('/users', [UserController::class, 'store'])->name('users.store');
    });
    Route::middleware(['check_permission:edit-users'])->group(function () {
        Route::get('/users/{user}/edit', [UserController::class, 'edit'])->name('users.edit');
        Route::put('/users/{user}', [UserController::class, 'update'])->name('users.update');
        Route::post('/users/{user}/update-status', [UserController::class, 'updateStatus'])->name('users.update-status');
    });
    Route::middleware(['check_permission:delete-users'])->group(function () {
        Route::delete('/users/{user}', [UserController::class, 'destroy'])->name('users.destroy');
    });
    // Roles
    Route::middleware(['check_permission:view-roles'])->group(function () {
        Route::get('/roles', [RoleController::class, 'index'])->name('roles.index');
        Route::get('/roles/{role}', [RoleController::class, 'show'])->name('roles.show');
    });
    Route::middleware(['check_permission:create-roles'])->group(function () {
        Route::get('/roles/create', [RoleController::class, 'create'])->name('roles.create');
        Route::post('/roles', [RoleController::class, 'store'])->name('roles.store');
    });
    Route::middleware(['check_permission:edit-roles'])->group(function () {
        Route::get('/roles/{role}/edit', [RoleController::class, 'edit'])->name('roles.edit');
        Route::put('/roles/{role}', [RoleController::class, 'update'])->name('roles.update');
    });
    Route::middleware(['check_permission:delete-roles'])->group(function () {
        Route::delete('/roles/{role}', [RoleController::class, 'destroy'])->name('roles.destroy');
    });

    // Permissions
    Route::middleware(['check_permission:view-permissions'])->group(function () {
        Route::get('/permissions', [PermissionController::class, 'index'])->name('permissions.index');
        Route::get('/permissions/{permission}', [PermissionController::class, 'show'])->name('permissions.show');
    });
    Route::middleware(['check_permission:create-permissions'])->group(function () {
        Route::get('/permissions/create', [PermissionController::class, 'create'])->name('permissions.create');
        Route::post('/permissions', [PermissionController::class, 'store'])->name('permissions.store');
    });
    Route::middleware(['check_permission:edit-permissions'])->group(function () {
        Route::get('/permissions/{permission}/edit', [PermissionController::class, 'edit'])->name('permissions.edit');
        Route::put('/permissions/{permission}', [PermissionController::class, 'update'])->name('permissions.update');
    });
    Route::middleware(['check_permission:delete-permissions'])->group(function () {
        Route::delete('/permissions/{permission}', [PermissionController::class, 'destroy'])->name('permissions.destroy');
        Route::post('/permissions/bulk-delete', [PermissionController::class, 'bulkDelete'])->name('permissions.bulk-delete');
    });

    // User Roles
    Route::middleware(['check_permission:manage-user-roles'])->group(function () {
        Route::get('/user-roles', [UserController::class, 'index'])->name('user-roles.index');
        Route::put('/user-roles/{user}', [UserController::class, 'update'])->name('user-roles.update');
        Route::put('/users/{user}/roles', [UserController::class, 'updateRoles'])->name('user.roles.update');
    });

    // API routes
//    Route::prefix('api')->name('api.')->group(function () {
//        // Permission API routes
//        Route::get('permissions', [PermissionController::class, 'index']);
//        Route::post('permissions', [PermissionController::class, 'store']);
//        Route::get('permissions/{permission}', [PermissionController::class, 'show']);
//        Route::put('permissions/{permission}', [PermissionController::class, 'update']);
//        Route::delete('permissions/{permission}', [PermissionController::class, 'destroy']);
//        Route::post('permissions/bulk-delete', [PermissionController::class, 'bulkDelete']);
//
//        // Role API routes
//        Route::get('roles', [RoleController::class, 'index']);
//        Route::post('roles', [RoleController::class, 'store']);
//        Route::get('roles/{role}', [RoleController::class, 'show']);
//        Route::put('roles/{role}', [RoleController::class, 'update']);
//        Route::delete('roles/{role}', [RoleController::class, 'destroy']);
//        Route::post('roles/{role}/assign-permissions', [RoleController::class, 'assignPermissions']);
//
//        // User API routes
//        Route::get('users', [UserController::class, 'allUsers']);
//        Route::get('users/{user}', [UserController::class, 'show']);
//        Route::post('users/{user}/assign-roles', [UserController::class, 'assignRoles']);
//        Route::delete('users/{user}/remove-role', [UserController::class, 'removeRole']);
//        Route::post('users/{user}/update-status', [UserController::class, 'updateStatus']);
//
//        Route::get('user-roles', [UserController::class, 'index']);
//        Route::put('user-roles/{user}', [UserController::class, 'update']);
//    });
});
