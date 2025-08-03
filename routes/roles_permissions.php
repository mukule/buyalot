<?php

use App\Http\Controllers\Admin\PermissionController;
use App\Http\Controllers\Admin\RoleController;
use App\Http\Controllers\Admin\UserRoleController;
use Illuminate\Support\Facades\Route;



// Roles
Route::get('/roles', [RoleController::class, 'index'])->name('roles.index');
Route::get('/roles/create', [RoleController::class, 'create'])->name('roles.create');
Route::post('/roles', [RoleController::class, 'store'])->name('roles.store');
Route::get('/roles/{role}/edit', [RoleController::class, 'edit'])->name('roles.edit');
Route::put('/roles/{role}', [RoleController::class, 'update'])->name('roles.update');
Route::delete('/roles/{role}', [RoleController::class, 'destroy'])->name('roles.destroy');
Route::get('/roles/{role}', [RoleController::class, 'show'])->name('roles.show');

// Permissions
Route::get('/permissions', [PermissionController::class, 'index'])->name('permissions.index');
Route::get('/permissions/create', [PermissionController::class, 'create'])->name('permissions.create');
Route::post('/permissions', [PermissionController::class, 'store'])->name('permissions.store');
Route::get('/permissions/{permission}/edit', [PermissionController::class, 'edit'])->name('permissions.edit');
Route::put('/permissions/{permission}', [PermissionController::class, 'update'])->name('permissions.update');
Route::delete('/permissions/{permission}', [PermissionController::class, 'destroy'])->name('permissions.destroy');


Route::get('/user-roles', [UserRoleController::class, 'index'])->name('admin.user-roles.index');
Route::put('/user-roles/{user}', [UserRoleController::class, 'update'])->name('admin.user-roles.update');
