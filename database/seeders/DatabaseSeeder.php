<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;

class DatabaseSeeder extends Seeder
{
    public function run(): void
    {
        $this->call([
            PermissionSeeder::class,
            DiscountTypeSeeder::class,
            VariantCategorySeeder::class,
            VariantSeeder::class,
            BrandSeeder::class,
            CategorySeeder::class,
            SubcategorySeeder::class,
//            AdminSeeder::class,
//            CommissionPlanSeeder::class,
            CountrySeeder::class,
            CustomerSeeder::class,
            // Optionally create more seeders here, e.g.
            // TestUserSeeder::class,
        ]);
    }
}
