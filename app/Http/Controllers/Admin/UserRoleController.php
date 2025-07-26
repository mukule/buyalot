<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\User;
use Illuminate\Http\Request;
use Inertia\Inertia;
use Spatie\Permission\Models\Role;

class UserRoleController extends Controller
{
  
    

public function index()
{
    $users = User::with('roles')
        ->whereHas('roles', function ($query) {
            $query->where('name', '<>', 'user');
        })
        ->get()
        ->filter(function ($user) {
            return !($user->roles->count() === 1 && $user->roles->first()->name === 'user');
        })
        ->values();

    $roles = Role::pluck('name');

    return Inertia::render('Admin/Roles/ManageRoles', [
        'users' => $users->map(fn($user) => [
            'id' => $user->id,
            'name' => $user->name,
            'roles' => $user->roles->pluck('name'),
        ]),
        'roles' => $roles,
    ]);
}


   
public function update(Request $request, User $user)
{
    $request->validate([
        'roles' => 'required|array',
        'roles.*' => 'string|exists:roles,name',
    ]);

    $rolesToKeep = $user->hasRole('user') ? ['user'] : [];

    $user->syncRoles(array_merge($rolesToKeep, $request->roles));

    return redirect()->back()->with('success', 'User roles updated successfully.');
}


}
