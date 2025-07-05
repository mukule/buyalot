<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;

class DatabaseSeeder extends Seeder
{
    public function run(): void
    {
        $this->call([
            AdminSeeder::class,
            // Optionally create more seeders here, e.g.
            // TestUserSeeder::class,
        ]);
    }
}
