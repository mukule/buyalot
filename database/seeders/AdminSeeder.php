<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;
use App\Models\User;
use Spatie\Permission\Models\Role;

class AdminSeeder extends Seeder
{
    public function run()
    {
        // Ensure the admin role exists
        $adminRole = Role::firstOrCreate(['name' => 'admin']);

        // Create or update the admin user without sending emails
        $admin = User::updateOrCreate(
            ['email' => 'nelsonmasibo6@gmail.com'], // Find by email
            [
                'name' => 'Masibo',
                'password' => Hash::make('NewSecurePassword123'), // Change to your desired password
                'email_verified_at' => now(),
            ]
        );

        // Assign admin role if not already assigned
        if (!$admin->hasRole('admin')) {
            $admin->assignRole($adminRole);
        }

        // No email sending here — prevents SSL or SMTP errors
    }
}
