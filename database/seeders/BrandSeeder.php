<?php

namespace Database\Seeders;

use Illuminate\Database\Console\Seeds\WithoutModelEvents;
use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\DB;

class BrandSeeder extends Seeder
{
    /**
     * Run the database seeds.
     */
    public function run(): void
    {
        $brands = [
            // Technology Brands
            [
                'name' => 'Apple',
                'slug' => 'apple',
                'description' => 'Innovative technology products',
                'logo' => 'brands/apple.png',
                'website' => 'https://apple.com',
                'active' => true,
                'country' => 'United States'
            ],
            [
                'name' => 'Samsung',
                'slug' => 'samsung',
                'description' => 'Electronics and mobile devices',
                'logo' => 'brands/samsung.png',
                'website' => 'https://samsung.com',
                'active' => true,
                'country' => 'South Korea'
            ],
            [
                'name' => 'Huawei',
                'slug' => 'huawei',
                'description' => 'Telecommunications and consumer electronics',
                'logo' => 'brands/huawei.png',
                'website' => 'https://huawei.com',
                'active' => true,
                'country' => 'China'
            ],
            [
                'name' => 'Xiaomi',
                'slug' => 'xiaomi',
                'description' => 'Smart devices and electronics',
                'logo' => 'brands/xiaomi.png',
                'website' => 'https://mi.com',
                'active' => true,
                'country' => 'China'
            ],
            [
                'name' => 'Dell',
                'slug' => 'dell',
                'description' => 'Computer technology solutions',
                'logo' => 'brands/dell.png',
                'website' => 'https://dell.com',
                'active' => true,
                'country' => 'United States'
            ],
            [
                'name' => 'HP',
                'slug' => 'hp',
                'description' => 'Computing and printing solutions',
                'logo' => 'brands/hp.png',
                'website' => 'https://hp.com',
                'active' => true,
                'country' => 'United States'
            ],
            [
                'name' => 'Lenovo',
                'slug' => 'lenovo',
                'description' => 'Personal computers and mobile devices',
                'logo' => 'brands/lenovo.png',
                'website' => 'https://lenovo.com',
                'active' => true,
                'country' => 'China'
            ],
            [
                'name' => 'Sony',
                'slug' => 'sony',
                'description' => 'Electronics and entertainment',
                'logo' => 'brands/sony.png',
                'website' => 'https://sony.com',
                'active' => true,
                'country' => 'Japan'
            ],

            // Fashion Brands
            [
                'name' => 'Nike',
                'slug' => 'nike',
                'description' => 'Athletic footwear and apparel',
                'logo' => 'brands/nike.png',
                'website' => 'https://nike.com',
                'active' => true,
                'country' => 'United States'
            ],
            [
                'name' => 'Adidas',
                'slug' => 'adidas',
                'description' => 'Sports clothing and accessories',
                'logo' => 'brands/adidas.png',
                'website' => 'https://adidas.com',
                'active' => true,
                'country' => 'Germany'
            ],
            [
                'name' => 'Puma',
                'slug' => 'puma',
                'description' => 'Athletic and casual footwear',
                'logo' => 'brands/puma.png',
                'website' => 'https://puma.com',
                'active' => true,
                'country' => 'Germany'
            ],
            [
                'name' => 'Zara',
                'slug' => 'zara',
                'description' => 'Fashion retail clothing',
                'logo' => 'brands/zara.png',
                'website' => 'https://zara.com',
                'active' => true,
                'country' => 'Spain'
            ],
            [
                'name' => 'H&M',
                'slug' => 'hm',
                'description' => 'Fast fashion clothing',
                'logo' => 'brands/hm.png',
                'website' => 'https://hm.com',
                'active' => true,
                'country' => 'Sweden'
            ],

            // Beauty Brands
            [
                'name' => 'L\'Oréal',
                'slug' => 'loreal',
                'description' => 'Cosmetics and beauty products',
                'logo' => 'brands/loreal.png',
                'website' => 'https://loreal.com',
                'active' => true,
                'country' => 'France'
            ],
            [
                'name' => 'Nivea',
                'slug' => 'nivea',
                'description' => 'Skincare and personal care',
                'logo' => 'brands/nivea.png',
                'website' => 'https://nivea.com',
                'active' => true,
                'country' => 'Germany'
            ],

            // Home & Furniture Brands
            [
                'name' => 'IKEA',
                'slug' => 'ikea',
                'description' => 'Furniture and home accessories',
                'logo' => 'brands/ikea.png',
                'website' => 'https://ikea.com',
                'active' => true,
                'country' => 'Sweden'
            ],

            // Food Brands
            [
                'name' => 'Coca-Cola',
                'slug' => 'coca-cola',
                'description' => 'Beverages and soft drinks',
                'logo' => 'brands/coca-cola.png',
                'website' => 'https://coca-cola.com',
                'active' => true,
                'country' => 'United States'
            ],
            [
                'name' => 'Nestlé',
                'slug' => 'nestle',
                'description' => 'Food and beverage products',
                'logo' => 'brands/nestle.png',
                'website' => 'https://nestle.com',
                'active' => true,
                'country' => 'Switzerland'
            ],

            // Kenyan/African Brands
            [
                'name' => 'Safaricom',
                'slug' => 'safaricom',
                'description' => 'Telecommunications and mobile services',
                'logo' => 'brands/safaricom.png',
                'active' => true,
                'country' => 'Kenya'
            ],
            [
                'name' => 'Equity Bank',
                'slug' => 'equity-bank',
                'description' => 'Banking and financial services',
                'logo' => 'brands/equity.png',
                'active' => true,
                'country' => 'Kenya'
            ],
            [
                'name' => 'Tusker',
                'slug' => 'tusker',
                'description' => 'Premium beer brand',
                'logo' => 'brands/tusker.png',
                'active' => true,
                'country' => 'Kenya'
            ],

            // Generic/Store Brands
            [
                'name' => 'Generic',
                'slug' => 'generic',
                'description' => 'Generic brand products',
                'logo' => null,
                'active' => true,
                'country' => 'Global'
            ],
            [
                'name' => 'No Brand',
                'slug' => 'no-brand',
                'description' => 'Unbranded products',
                'logo' => null,
                'active' => true,
                'country' => 'Global'
            ],
            [
                'name' => 'Store Brand',
                'slug' => 'store-brand',
                'description' => 'Store private label products',
                'logo' => null,
                'active' => true,
                'country' => 'Global'
            ],

            // Additional Popular Brands
            [
                'name' => 'Canon',
                'slug' => 'canon',
                'description' => 'Imaging and optical products',
                'logo' => 'brands/canon.png',
                'active' => true,
                'country' => 'Japan'
            ],
            [
                'name' => 'Nikon',
                'slug' => 'nikon',
                'description' => 'Cameras and optical equipment',
                'logo' => 'brands/nikon.png',
                'active' => true,
                'country' => 'Japan'
            ],
            [
                'name' => 'JBL',
                'slug' => 'jbl',
                'description' => 'Audio equipment and speakers',
                'logo' => 'brands/jbl.png',
                'active' => true,
                'country' => 'United States'
            ],
            [
                'name' => 'Philips',
                'slug' => 'philips',
                'description' => 'Health technology and consumer electronics',
                'logo' => 'brands/philips.png',
                'active' => true,
                'country' => 'Netherlands'
            ],
            [
                'name' => 'LG',
                'slug' => 'lg',
                'description' => 'Home appliances and electronics',
                'logo' => 'brands/lg.png',
                'active' => true,
                'country' => 'South Korea'
            ]
        ];

        foreach ($brands as $brand) {
            DB::table('brands')->insertOrIgnore([
                'name' => $brand['name'],
                'slug' => $brand['slug'],
                'description' => $brand['description'],
                'logo_path' => $brand['logo'],
                'active' => $brand['active'],
                'created_at' => now(),
                'updated_at' => now(),
            ]);

            $this->command->info("Created brand: {$brand['name']}");
        }

        $totalBrands = count($brands);
        $this->command->info("Total brands created: {$totalBrands}");
    }
}
