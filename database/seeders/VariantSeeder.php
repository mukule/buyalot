<?php

namespace Database\Seeders;

use Illuminate\Database\Console\Seeds\WithoutModelEvents;
use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\DB;

class VariantSeeder extends Seeder
{
    /**
     * Run the database seeds.
     */
    public function run(): void
    {
        // Get category IDs
        $colorId = DB::table('variant_categories')->where('name', 'Color')->first()?->id;
        $sizeId = DB::table('variant_categories')->where('name', 'Size')->first()?->id;
        $materialId = DB::table('variant_categories')->where('name', 'Material')->first()?->id;
        $styleId = DB::table('variant_categories')->where('name', 'Style')->first()?->id;
        $patternId = DB::table('variant_categories')->where('name', 'Pattern')->first()?->id;
        $finishId = DB::table('variant_categories')->where('name', 'Finish')->first()?->id;
        $capacityId = DB::table('variant_categories')->where('name', 'Capacity')->first()?->id;
        $weightId = DB::table('variant_categories')->where('name', 'Weight')->first()?->id;
        $brandId = DB::table('variant_categories')->where('name', 'Brand')->first()?->id;
        $modelId = DB::table('variant_categories')->where('name', 'Model')->first()?->id;
        $configurationId = DB::table('variant_categories')->where('name', 'Configuration')->first()?->id;
        $versionId = DB::table('variant_categories')->where('name', 'Version')->first()?->id;

        // Define variants for each category
        $variants = [];

        // Colors
        if ($colorId) {
            $colors = [
                'Red', 'Blue', 'Green', 'Yellow', 'Orange', 'Purple', 'Pink',
                'Black', 'White', 'Gray', 'Grey', 'Brown', 'Beige', 'Navy',
                'Maroon', 'Teal', 'Turquoise', 'Lime', 'Olive', 'Silver',
                'Gold', 'Rose Gold', 'Bronze', 'Copper', 'Cream', 'Ivory',
                'Charcoal', 'Slate', 'Mint', 'Coral', 'Burgundy', 'Khaki'
            ];

            foreach ($colors as $color) {
                $variants[] = [
                    'variant_category_id' => $colorId,
                    'value' => $color,
                    'created_at' => now(),
                    'updated_at' => now(),
                ];
            }
        }

        // Sizes
        if ($sizeId) {
            $sizes = [
                // Clothing sizes
                'XXS', 'XS', 'S', 'M', 'L', 'XL', 'XXL', 'XXXL',
                // Shoe sizes
                '36', '37', '38', '39', '40', '41', '42', '43', '44', '45', '46', '47',
                // US sizes
                '6', '7', '8', '9', '10', '11', '12', '13', '14',
                // Generic sizes
                'Small', 'Medium', 'Large', 'Extra Large',
                // Specific measurements
                '28"', '30"', '32"', '34"', '36"', '38"', '40"', '42"',
                'One Size', 'Free Size', 'Adjustable'
            ];

            foreach ($sizes as $size) {
                $variants[] = [
                    'variant_category_id' => $sizeId,
                    'value' => $size,
                    'created_at' => now(),
                    'updated_at' => now(),
                ];
            }
        }

        // Materials
        if ($materialId) {
            $materials = [
                'Cotton', 'Polyester', 'Leather', 'Silk', 'Wool', 'Denim',
                'Linen', 'Nylon', 'Spandex', 'Rayon', 'Bamboo', 'Hemp',
                'Plastic', 'Metal', 'Wood', 'Glass', 'Ceramic', 'Steel',
                'Aluminum', 'Stainless Steel', 'Titanium', 'Carbon Fiber',
                'Rubber', 'Silicone', 'Canvas', 'Suede', 'Velvet', 'Satin',
                'Fleece', 'Cashmere', 'Tweed', 'Corduroy'
            ];

            foreach ($materials as $material) {
                $variants[] = [
                    'variant_category_id' => $materialId,
                    'value' => $material,
                    'created_at' => now(),
                    'updated_at' => now(),
                ];
            }
        }

        // Styles
        if ($styleId) {
            $styles = [
                'Classic', 'Modern', 'Vintage', 'Contemporary', 'Traditional',
                'Minimalist', 'Bohemian', 'Industrial', 'Rustic', 'Elegant',
                'Casual', 'Formal', 'Sporty', 'Trendy', 'Retro', 'Urban',
                'Chic', 'Sophisticated', 'Edgy', 'Romantic', 'Professional',
                'Relaxed', 'Bold', 'Subtle', 'Statement'
            ];

            foreach ($styles as $style) {
                $variants[] = [
                    'variant_category_id' => $styleId,
                    'value' => $style,
                    'created_at' => now(),
                    'updated_at' => now(),
                ];
            }
        }

        // Patterns
        if ($patternId) {
            $patterns = [
                'Solid', 'Striped', 'Polka Dot', 'Floral', 'Geometric',
                'Abstract', 'Plaid', 'Checkered', 'Paisley', 'Animal Print',
                'Leopard', 'Zebra', 'Camouflage', 'Tribal', 'Mandala',
                'Chevron', 'Herringbone', 'Damask', 'Ikat', 'Ombre',
                'Gradient', 'Marble', 'Watercolor', 'Digital Print'
            ];

            foreach ($patterns as $pattern) {
                $variants[] = [
                    'variant_category_id' => $patternId,
                    'value' => $pattern,
                    'created_at' => now(),
                    'updated_at' => now(),
                ];
            }
        }

        // Finishes
        if ($finishId) {
            $finishes = [
                'Matte', 'Glossy', 'Satin', 'Metallic', 'Brushed',
                'Polished', 'Textured', 'Smooth', 'Rough', 'Antique',
                'Distressed', 'Natural', 'Painted', 'Stained', 'Lacquered',
                'Chrome', 'Nickel', 'Brass', 'Bronze', 'Copper'
            ];

            foreach ($finishes as $finish) {
                $variants[] = [
                    'variant_category_id' => $finishId,
                    'value' => $finish,
                    'created_at' => now(),
                    'updated_at' => now(),
                ];
            }
        }

        // Capacities
        if ($capacityId) {
            $capacities = [
                '100ml', '250ml', '500ml', '750ml', '1L', '1.5L', '2L', '5L',
                '50g', '100g', '250g', '500g', '1kg', '2kg', '5kg', '10kg',
                '16GB', '32GB', '64GB', '128GB', '256GB', '512GB', '1TB',
                '10 pieces', '20 pieces', '50 pieces', '100 pieces',
                'Single', 'Double', 'Triple', 'Family Size', 'Economy Size'
            ];

            foreach ($capacities as $capacity) {
                $variants[] = [
                    'variant_category_id' => $capacityId,
                    'value' => $capacity,
                    'created_at' => now(),
                    'updated_at' => now(),
                ];
            }
        }

        // Weights
        if ($weightId) {
            $weights = [
                'Light', 'Medium', 'Heavy', 'Ultra Light', 'Extra Heavy',
                '100g', '250g', '500g', '1kg', '2kg', '5kg', '10kg',
                '1lb', '2lb', '5lb', '10lb', '20lb', '50lb'
            ];

            foreach ($weights as $weight) {
                $variants[] = [
                    'variant_category_id' => $weightId,
                    'value' => $weight,
                    'created_at' => now(),
                    'updated_at' => now(),
                ];
            }
        }

        // Brands (Popular Kenyan/International brands)
        if ($brandId) {
            $brands = [
                'Samsung', 'Apple', 'Huawei', 'Xiaomi', 'Oppo', 'Vivo',
                'Nike', 'Adidas', 'Puma', 'Reebok', 'Converse',
                'Zara', 'H&M', 'Uniqlo', 'Forever 21',
                'Dell', 'HP', 'Lenovo', 'Asus', 'Acer',
                'Sony', 'LG', 'Panasonic', 'Philips', 'Canon',
                'Generic', 'No Brand', 'OEM', 'Private Label'
            ];

            foreach ($brands as $brand) {
                $variants[] = [
                    'variant_category_id' => $brandId,
                    'value' => $brand,
                    'created_at' => now(),
                    'updated_at' => now(),
                ];
            }
        }

        // Models
        if ($modelId) {
            $models = [
                'Standard', 'Premium', 'Deluxe', 'Professional', 'Enterprise',
                'Basic', 'Advanced', 'Pro', 'Elite', 'Ultimate',
                'Series 1', 'Series 2', 'Series 3', 'Generation 1', 'Generation 2',
                'Classic', 'Modern', 'Sport', 'Casual', 'Formal'
            ];

            foreach ($models as $model) {
                $variants[] = [
                    'variant_category_id' => $modelId,
                    'value' => $model,
                    'created_at' => now(),
                    'updated_at' => now(),
                ];
            }
        }

        // Configurations
        if ($configurationId) {
            $configurations = [
                'Single', 'Double', 'Triple', 'Quad',
                '2-piece', '3-piece', '4-piece', '5-piece',
                'Left', 'Right', 'Center', 'Corner',
                'Wired', 'Wireless', 'Bluetooth', 'USB',
                'Manual', 'Automatic', 'Semi-automatic',
                'Indoor', 'Outdoor', 'All-weather'
            ];

            foreach ($configurations as $configuration) {
                $variants[] = [
                    'variant_category_id' => $configurationId,
                    'value' => $configuration,
                    'created_at' => now(),
                    'updated_at' => now(),
                ];
            }
        }

        // Versions
        if ($versionId) {
            $versions = [
                'V1.0', 'V2.0', 'V3.0', 'V4.0', 'V5.0',
                '2023', '2024', '2025',
                'Original', 'Updated', 'Latest', 'New',
                'International', 'Local', 'Global',
                'Standard', 'Enhanced', 'Premium'
            ];

            foreach ($versions as $version) {
                $variants[] = [
                    'variant_category_id' => $versionId,
                    'value' => $version,
                    'created_at' => now(),
                    'updated_at' => now(),
                ];
            }
        }

        // Insert all variants in chunks for better performance
        $chunks = array_chunk($variants, 100);
        foreach ($chunks as $chunk) {
            DB::table('variants')->insertOrIgnore($chunk);
        }

        $totalVariants = count($variants);
        $this->command->info("Created {$totalVariants} variants across all categories");
        $this->command->info("Variant seeding completed!");
    }
}
