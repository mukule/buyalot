<?php

namespace Database\Seeders;

use Illuminate\Database\Console\Seeds\WithoutModelEvents;
use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\DB;

class VariantCategorySeeder extends Seeder
{
    /**
     * Run the database seeds.
     */
    public function run(): void
    {
        $categories = [
            'Color',
            'Size',
            'Material',
            'Style',
            'Pattern',
            'Finish',
            'Capacity',
            'Weight',
            'Brand',
            'Model',
            'Configuration',
            'Version'
        ];

        foreach ($categories as $category) {
            DB::table('variant_categories')->insertOrIgnore([
                'name' => $category,
                'created_at' => now(),
                'updated_at' => now(),
            ]);

            $this->command->info("Created variant category: {$category}");
        }

        $this->command->info("Variant categories seeding completed!");
    }
}
