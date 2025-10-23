<?php

namespace Database\Seeders;

use App\Models\Category;
use Illuminate\Database\Console\Seeds\WithoutModelEvents;
use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Str;

class CategorySeeder extends Seeder
{
    /**
     * Run the database seeds.
     */
    public function run(): void
    {
        $categories = [
            'Electronics' => 'Devices, gadgets, and accessories',
            'Fashion' => 'Clothing, footwear, and accessories',
            'Home & Kitchen' => 'Household and kitchen essentials',
            'Beauty & Personal Care' => 'Cosmetics and personal products',
            'Sports & Outdoors' => 'Sports equipment and outdoor gear',
            'Toys & Games' => 'Toys, puzzles, and games for all ages',
            'Books & Stationery' => 'Books, office, and school supplies',
            'Automotive' => 'Car parts and accessories',
            'Health & Wellness' => 'Medical supplies and fitness',
            'Groceries' => 'Food, beverages, and daily essentials',
            'Jewelry & Watches' => 'Luxury items and accessories',
            'Baby & Kids' => 'Baby care and kids’ essentials',
            'Pet Supplies' => 'Products for pets and animals',
        ];


        foreach ($categories as $name => $desc) {
            Category::updateOrCreate(
                ['slug' => Str::slug($name)],
                [
                    'name' => $name,
                    'description' => $desc,
                    'active' => true,
                ]
            );
        }
    }
}
