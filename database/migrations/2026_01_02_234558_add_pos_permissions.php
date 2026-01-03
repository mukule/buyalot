<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Run the migrations.
     */
    public function up(): void
    {
        $permissions = [
            'access-pos',
            'manage-pos-registers',
            'view-pos-reports',
        ];

        foreach ($permissions as $permission) {
            \Spatie\Permission\Models\Permission::firstOrCreate([
                'name' => $permission,
                'guard_name' => 'web',
                'module' => 'pos'
            ]);
        }

        // Assign to admin by default
        $admin = \Spatie\Permission\Models\Role::where('name', 'admin')->first();
        if ($admin) {
            $admin->givePermissionTo($permissions);
        }

        $superAdmin = \Spatie\Permission\Models\Role::where('name', 'super-admin')->first();
        if ($superAdmin) {
            $superAdmin->givePermissionTo($permissions);
        }
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        $permissions = [
            'access-pos',
            'manage-pos-registers',
            'view-pos-reports',
        ];

        foreach ($permissions as $permission) {
            \Spatie\Permission\Models\Permission::where('name', $permission)->delete();
        }
    }
};
