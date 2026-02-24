<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\DB;
use Spatie\Permission\Models\Permission;
use Spatie\Permission\Models\Role;

return new class extends Migration
{
    public function up(): void
    {
        app()[\Spatie\Permission\PermissionRegistrar::class]->forgetCachedPermissions();

        $permissions = [
            ['name' => 'restock-product-items', 'module' => 'products'],
            ['name' => 'restock-product-history', 'module' => 'products'],
        ];

        foreach ($permissions as $p) {
            Permission::firstOrCreate(
                ['name' => $p['name'], 'guard_name' => 'web'],
                ['module' => $p['module']]
            );
        }

        $rolesToUpdate = ['manager', 'staff', 'seller', 'admin'];
        foreach ($rolesToUpdate as $roleName) {
            $role = Role::where('name', $roleName)->where('guard_name', 'web')->first();
            if ($role) {
                $role->givePermissionTo(['restock-product-items', 'restock-product-history']);
            }
        }
    }

    public function down(): void
    {
        app()[\Spatie\Permission\PermissionRegistrar::class]->forgetCachedPermissions();

        $rolesToUpdate = ['manager', 'staff', 'seller'];
        foreach ($rolesToUpdate as $roleName) {
            $role = Role::where('name', $roleName)->where('guard_name', 'web')->first();
            if ($role) {
                $role->revokePermissionTo(['restock-product-items', 'restock-product-history']);
            }
        }

        Permission::whereIn('name', ['restock-product-items', 'restock-product-history'])
            ->where('guard_name', 'web')
            ->delete();
    }
};
