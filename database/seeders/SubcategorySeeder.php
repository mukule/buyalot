<?php

namespace Database\Seeders;

use App\Models\Category;
use App\Models\Subcategory;
use Illuminate\Database\Console\Seeds\WithoutModelEvents;
use Illuminate\Database\Seeder;
use Illuminate\Support\Str;

class SubcategorySeeder extends Seeder
{
    /**
     * Run the database seeds.
     */
    public function run(): void
    {
        $subcategories = [
            'Electronics' => [
                'Mobile Phones',
                'Laptops',
                'Tablets',
                'Cameras',
                'Headphones',
                'Smartwatches',
                'Televisions',
                'Gaming Consoles',
            ],
            'Fashion' => [
                'Men Clothing',
                'Women Clothing',
                'Shoes',
                'Bags',
                'Accessories',
                'Eyewear',
            ],
            'Home & Kitchen' => [
                'Furniture',
                'Appliances',
                'Cookware',
                'Bedding',
                'Decor',
                'Lighting',
            ],
            'Beauty & Personal Care' => [
                'Makeup',
                'Skincare',
                'Haircare',
                'Fragrances',
                'Personal Hygiene',
            ],
            'Sports & Outdoors' => [
                'Fitness Equipment',
                'Cycling',
                'Camping & Hiking',
                'Team Sports',
                'Water Sports',
            ],
            'Toys & Games' => [
                'Educational Toys',
                'Action Figures',
                'Board Games',
                'Puzzles',
                'Dolls',
                'Outdoor Toys',
            ],
            'Books & Stationery' => [
                'Fiction',
                'Non-fiction',
                'Children’s Books',
                'School Supplies',
                'Office Supplies',
            ],
            'Automotive' => [
                'Car Electronics',
                'Car Care',
                'Spare Parts',
                'Motorcycle Accessories',
            ],
            'Health & Wellness' => [
                'Supplements',
                'Medical Devices',
                'Fitness Gear',
                'Personal Safety',
            ],
            'Groceries' => [
                'Beverages',
                'Snacks',
                'Fresh Produce',
                'Bakery',
                'Dairy',
                'Household Essentials',
            ],
            'Jewelry & Watches' => [
                'Necklaces',
                'Rings',
                'Bracelets',
                'Watches',
                'Earrings',
            ],
            'Baby & Kids' => [
                'Baby Clothing',
                'Diapers',
                'Feeding',
                'Toys',
                'Nursery Furniture',
            ],
            'Pet Supplies' => [
                'Pet Food',
                'Pet Toys',
                'Pet Grooming',
                'Pet Health',
            ],
        ];

        foreach ($subcategories as $categoryName => $subs) {
            $category = Category::where('slug', Str::slug($categoryName))->first();

            if ($category) {
                foreach ($subs as $sub) {
                    Subcategory::updateOrCreate(
                        ['slug' => Str::slug($sub)],
                        [
                            'category_id' => $category->id,
                            'name' => $sub,
                            'active' => true,
                        ]
                    );
                }
            }
        }
    }
}
