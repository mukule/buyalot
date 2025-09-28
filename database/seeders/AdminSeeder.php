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


        $user = User::factory()->create([
            'name' => 'Masibo',
            'email' => 'nelsonmasibo6@gmail.com',
            'phone' =>'0704122212',
            'password' => bcrypt('123456'),
            'email_verified_at' => now(),
            'status' => true,
        ]);
        $user->assignRole('admin');

        $user2 = User::factory()->create([
            'name' => 'Steven Maina',
            'email' => 'stevenmaina17@gmail.com',
            'phone' =>'0710767015',
            'password' => bcrypt('password'),
            'email_verified_at' => now(),
            'status' => true,
        ]);


        $user2->assignRole('admin');
    }
}
