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
        ]);

        
        $user->assignRole('admin');
    }
}
