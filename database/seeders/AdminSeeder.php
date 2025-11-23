<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;
use App\Models\User;
use Spatie\Permission\Models\Role;

class AdminSeeder extends Seeder
{
    public function run()
    {
        $adminRole = Role::firstOrCreate(['name' => 'admin']);

        $user = User::updateOrCreate(
            ['email' => 'nelsonmasibo6@gmail.com'], // match on email
            [
                'name' => 'Masibo',
                'phone' =>'0704122212',
                'password' => bcrypt('123456'),
                'email_verified_at' => now(),
                'status' => true,
                'user_type' => 'user',
            ]
        );
        $user->assignRole('admin');

        $user2 = User::updateOrCreate(
            ['email' => 'stevenmaina17@gmail.com'],
            [
                'name' => 'Steven Maina',
                'phone' =>'0710767015',
                'password' => bcrypt('password'),
                'email_verified_at' => now(),
                'status' => true,
                'user_type' => 'user',
            ]
        );
        $user2->assignRole('admin');
    }

}
