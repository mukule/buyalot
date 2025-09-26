<?php

namespace Database\Seeders;

use App\Jobs\CalculateCommissionJob;
use Illuminate\Database\Seeder;

class DatabaseSeeder extends Seeder
{
    public function run(): void
    {
        $this->call([
//            AdminSeeder::class,
            PermissionSeeder::class,
            VariantCategorySeeder::class,
            VariantSeeder::class,
            BrandSeeder::class,
            CategorySeeder::class,
            SubcategorySeeder::class,
            // Optionally create more seeders here, e.g.
            // TestUserSeeder::class,
        ]);
    }
}
