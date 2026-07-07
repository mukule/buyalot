<?php

namespace Database\Seeders;

use App\Models\Category;
use App\Models\Products\Product;
use App\Models\Products\ProductVariant;
use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Str;

/**
 * Demo data for the "All Marketplace" verticals feature.
 *
 * Seeds the Cars & Motors and Building & Construction top-level categories
 * (+ a few subcategories) and a set of demo products carrying vertical-specific
 * `attributes` JSON, each with a single public variant, so the automotive and
 * construction UIs render with real, filterable content. Idempotent.
 */
class MarketplaceDemoSeeder extends Seeder
{
    public function run(): void
    {
        // The storefront treats status_id = 2 as the public/approved status.
        $publicStatusId = $this->ensurePublicStatus();

        // ---- Categories (top-level verticals + subcategories) ----------------
        $cars = $this->category('Cars & Motors', 'Buy & sell cars, bikes and vehicles');
        $carSubs = [
            'cars'       => $this->category('Cars', null, $cars->id),
            'motorbikes' => $this->category('Motorbikes', null, $cars->id),
            'trucks'     => $this->category('Trucks & Pickups', null, $cars->id),
        ];

        $construction = $this->category('Building & Construction', 'Materials, tools and equipment for building');
        $conSubs = [
            'cement'  => $this->category('Cement & Aggregates', null, $construction->id),
            'steel'   => $this->category('Steel & Metal', null, $construction->id),
            'roofing' => $this->category('Roofing', null, $construction->id),
            'timber'  => $this->category('Timber & Wood', null, $construction->id),
            'tiles'   => $this->category('Tiles & Finishes', null, $construction->id),
        ];

        // ---- Cars ------------------------------------------------------------
        // Tuple: [name, subKey, selling, marked, attributes, reserved]
        $carListings = [
            ['Toyota Corolla', 'cars', 1_850_000, 1_980_000, ['make' => 'Toyota', 'model' => 'Corolla', 'year' => 2018, 'mileage' => 65000, 'fuel' => 'Petrol', 'transmission' => 'Automatic', 'body_type' => 'Sedan', 'drive_type' => '2WD', 'steering' => 'Right', 'color' => 'Silver', 'engine_cc' => 1800, 'condition' => 'Used', 'location' => 'Nairobi'], false],
            ['Mazda Demio', 'cars', 950_000, null, ['make' => 'Mazda', 'model' => 'Demio', 'year' => 2016, 'mileage' => 89000, 'fuel' => 'Petrol', 'transmission' => 'Automatic', 'body_type' => 'Hatchback', 'drive_type' => '2WD', 'steering' => 'Right', 'color' => 'White', 'engine_cc' => 1300, 'condition' => 'Used', 'location' => 'Nairobi'], false],
            ['Nissan X-Trail', 'cars', 3_200_000, 3_450_000, ['make' => 'Nissan', 'model' => 'X-Trail', 'year' => 2019, 'mileage' => 42000, 'fuel' => 'Petrol', 'transmission' => 'Automatic', 'body_type' => 'SUV', 'drive_type' => 'AWD', 'steering' => 'Right', 'color' => 'Black', 'engine_cc' => 2000, 'condition' => 'Used', 'location' => 'Mombasa'], false],
            ['Subaru Forester', 'cars', 2_650_000, null, ['make' => 'Subaru', 'model' => 'Forester', 'year' => 2017, 'mileage' => 78000, 'fuel' => 'Petrol', 'transmission' => 'Automatic', 'body_type' => 'SUV', 'drive_type' => 'AWD', 'steering' => 'Right', 'color' => 'Blue', 'engine_cc' => 2000, 'condition' => 'Used', 'location' => 'Nakuru'], true],
            ['Honda Fit', 'cars', 780_000, 860_000, ['make' => 'Honda', 'model' => 'Fit', 'year' => 2015, 'mileage' => 110000, 'fuel' => 'Petrol', 'transmission' => 'Automatic', 'body_type' => 'Hatchback', 'drive_type' => '2WD', 'steering' => 'Right', 'color' => 'Red', 'engine_cc' => 1300, 'condition' => 'Used', 'location' => 'Nairobi'], false],
            ['Toyota Aqua Hybrid', 'cars', 1_550_000, 1_700_000, ['make' => 'Toyota', 'model' => 'Aqua', 'year' => 2019, 'mileage' => 48000, 'fuel' => 'Hybrid', 'transmission' => 'Automatic', 'body_type' => 'Hatchback', 'drive_type' => '2WD', 'steering' => 'Right', 'color' => 'Silver', 'engine_cc' => 1500, 'condition' => 'Used', 'location' => 'Nairobi'], false],
            ['Toyota Hilux', 'trucks', 4_500_000, null, ['make' => 'Toyota', 'model' => 'Hilux', 'year' => 2020, 'mileage' => 55000, 'fuel' => 'Diesel', 'transmission' => 'Manual', 'body_type' => 'Pickup', 'drive_type' => '4WD', 'steering' => 'Right', 'color' => 'White', 'engine_cc' => 2400, 'condition' => 'Used', 'location' => 'Eldoret'], false],
            ['Mercedes-Benz C200', 'cars', 3_100_000, 3_400_000, ['make' => 'Mercedes-Benz', 'model' => 'C200', 'year' => 2016, 'mileage' => 60000, 'fuel' => 'Petrol', 'transmission' => 'Automatic', 'body_type' => 'Sedan', 'drive_type' => '2WD', 'steering' => 'Left', 'color' => 'Black', 'engine_cc' => 2000, 'condition' => 'Used', 'location' => 'Nairobi'], true],
            ['Toyota Land Cruiser V8', 'cars', 12_000_000, null, ['make' => 'Toyota', 'model' => 'Land Cruiser', 'year' => 2023, 'mileage' => 5000, 'fuel' => 'Diesel', 'transmission' => 'Automatic', 'body_type' => 'SUV', 'drive_type' => '4WD', 'steering' => 'Right', 'color' => 'White', 'engine_cc' => 4500, 'condition' => 'New', 'location' => 'Nairobi'], false],
            ['Boxer 150cc Motorbike', 'motorbikes', 165_000, 185_000, ['make' => 'Bajaj', 'model' => 'Boxer', 'year' => 2022, 'mileage' => 12000, 'fuel' => 'Petrol', 'transmission' => 'Manual', 'body_type' => 'Motorbike', 'drive_type' => '2WD', 'steering' => 'Right', 'color' => 'Red', 'engine_cc' => 150, 'condition' => 'Used', 'location' => 'Thika'], false],
        ];

        // Skip Scout indexing while seeding so it works without a running search engine.
        Product::withoutSyncingToSearch(function () use ($carListings, $carSubs, $publicStatusId) {
            foreach ($carListings as $i => [$name, $subKey, $selling, $marked, $attrs, $reserved]) {
                // Demo contact number so the card's "call seller" button is live.
                $attrs['phone'] = '07' . str_pad((string) (10_000_000 + $i * 111_111), 8, '0', STR_PAD_LEFT);
                $this->product("$name " . $attrs['year'], $carSubs[$subKey]->id, $publicStatusId, $attrs, $selling, $marked, "CAR-" . str_pad((string) ($i + 1), 3, '0', STR_PAD_LEFT), 1, $reserved, ['cars']);
            }
        });

        // ---- Construction ----------------------------------------------------
        $conListings = [
            ['Bamburi Portland Cement 32.5R', 'cement', 780, 820, ['material' => 'Cement', 'type' => 'Cement', 'brand' => 'Bamburi', 'grade' => '32.5R', 'size' => '50 kg', 'unit' => 'bag', 'certification' => 'KEBS', 'min_order' => 10, 'condition' => 'New', 'location' => 'Nairobi']],
            ['Simba Cement 42.5N', 'cement', 810, null, ['material' => 'Cement', 'type' => 'Cement', 'brand' => 'Simba', 'grade' => '42.5N', 'size' => '50 kg', 'unit' => 'bag', 'certification' => 'KEBS', 'min_order' => 10, 'condition' => 'New', 'location' => 'Athi River']],
            ['Ballast ¾ inch', 'cement', 2_600, null, ['material' => 'Aggregate', 'type' => 'Ballast', 'unit' => 'tonne', 'min_order' => 1, 'condition' => 'New', 'location' => 'Machakos']],
            ['River Sand', 'cement', 1_800, 2_000, ['material' => 'Sand', 'type' => 'Sand', 'unit' => 'tonne', 'min_order' => 1, 'condition' => 'New', 'location' => 'Kajiado']],
            ['Steel Reinforcement Bar D12', 'steel', 1_150, null, ['material' => 'Steel', 'type' => 'Rebar', 'brand' => 'Devki', 'grade' => 'D12', 'size' => '12mm x 12m', 'unit' => 'piece', 'certification' => 'KEBS', 'min_order' => 20, 'condition' => 'New', 'location' => 'Nairobi']],
            ['Square Tube 2x2 Steel', 'steel', 1_950, 2_150, ['material' => 'Steel', 'type' => 'Tube', 'brand' => 'MRM', 'size' => '50 x 50mm', 'unit' => 'piece', 'min_order' => 10, 'condition' => 'New', 'location' => 'Nairobi']],
            ['Mabati Roofing Sheet 30G', 'roofing', 850, null, ['material' => 'Iron Sheet', 'type' => 'Roofing', 'brand' => 'MRM', 'grade' => '30G', 'size' => '2m', 'unit' => 'sheet', 'coverage' => 1.6, 'coverage_unit' => 'sq metre', 'certification' => 'KEBS', 'min_order' => 10, 'condition' => 'New', 'location' => 'Ruiru']],
            ['Clay Roofing Tiles', 'roofing', 95, 110, ['material' => 'Clay', 'type' => 'Roofing', 'unit' => 'piece', 'coverage' => 0.09, 'coverage_unit' => 'sq metre', 'min_order' => 50, 'condition' => 'New', 'location' => 'Nairobi']],
            ['Hardwood Timber 4x2', 'timber', 900, null, ['material' => 'Timber', 'type' => 'Timber', 'size' => '4x2 inch x 12ft', 'unit' => 'piece', 'min_order' => 10, 'condition' => 'New', 'location' => 'Nyeri']],
            ['Plywood Board 18mm', 'timber', 2_400, 2_650, ['material' => 'Plywood', 'type' => 'Board', 'size' => '2440 x 1220mm', 'unit' => 'sheet', 'coverage' => 2.97, 'coverage_unit' => 'sq metre', 'min_order' => 5, 'condition' => 'New', 'location' => 'Nairobi']],
            ['Ceramic Floor Tiles 60x60', 'tiles', 1_450, 1_650, ['material' => 'Ceramic', 'type' => 'Tile', 'brand' => 'Goodwill', 'size' => '600 x 600mm', 'unit' => 'box', 'coverage' => 1.44, 'coverage_unit' => 'sq metre', 'certification' => 'KEBS', 'min_order' => 5, 'condition' => 'New', 'location' => 'Nairobi']],
        ];

        Product::withoutSyncingToSearch(function () use ($conListings, $conSubs, $publicStatusId) {
            foreach ($conListings as $i => [$name, $subKey, $selling, $marked, $attrs]) {
                $this->product($name, $conSubs[$subKey]->id, $publicStatusId, $attrs, $selling, $marked, "CON-" . str_pad((string) ($i + 1), 3, '0', STR_PAD_LEFT), 1, false, ['construction']);
            }
        });
    }

    /** Ensure product_statuses has the public status the storefront filters on (id = 2). */
    protected function ensurePublicStatus(): int
    {
        if (! DB::table('product_statuses')->where('id', 2)->exists()) {
            DB::table('product_statuses')->updateOrInsert(
                ['id' => 1],
                ['name' => 'pending', 'label' => 'Pending Review', 'created_at' => now(), 'updated_at' => now()],
            );
            DB::table('product_statuses')->updateOrInsert(
                ['id' => 2],
                ['name' => 'approved', 'label' => 'Public', 'color_class' => 'green', 'created_at' => now(), 'updated_at' => now()],
            );
        }

        return 2;
    }

    protected function category(string $name, ?string $description = null, ?int $parentId = null): Category
    {
        return Category::updateOrCreate(
            ['slug' => Str::slug($name)],
            ['name' => $name, 'description' => $description, 'active' => true, 'parent_id' => $parentId],
        );
    }

    protected function product(
        string $name,
        int $categoryId,
        int $statusId,
        array $attributes,
        float $sellingPrice,
        ?float $markedPrice,
        string $code,
        ?int $stock = 1,
        bool $reserved = false,
        array $marketplaces = [],
    ): void {
        $marked = $markedPrice ?? $sellingPrice;
        $discount = max(0, $marked - $sellingPrice);

        $product = Product::updateOrCreate(
            ['product_code' => $code],
            [
                'name'         => $name,
                'description'  => $name,
                'attributes'   => $attributes,
                'marketplaces' => $marketplaces,
                'reserved_at'  => $reserved ? now() : null,
                'owner_type'   => 'admin',
                'owner_id'     => null,
                'category_id'  => $categoryId,
                'status'       => 2,
                'status_id'    => $statusId,
            ],
        );

        ProductVariant::updateOrCreate(
            ['sku' => $code . '-V1'],
            [
                'product_id'    => $product->id,
                'regular_price' => $marked,
                'marked_price'  => $marked,
                'selling_price' => $sellingPrice,
                'buying_price'  => round($sellingPrice * 0.85, 2),
                'discount'      => $discount,
                'stock'         => $stock ?? 1,
                'is_active'     => true,
            ],
        );
    }
}
