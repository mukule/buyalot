<?php

namespace Database\Seeders;

use DB;
use Illuminate\Database\Console\Seeds\WithoutModelEvents;
use Illuminate\Database\Seeder;

class UnitsTableSeeder extends Seeder
{
    /**
     * Run the database seeds.
     */
    public function run(): void
    {
        $typeIds = DB::table('unit_types')->pluck('id', 'slug');
        // ['weight' => 2, 'volume' => 3, ...]

        $units = [
            // Quantity
            ['name' => 'Piece', 'symbol' => 'pcs', 'unit_type_id' => $typeIds['quantity']],
            ['name' => 'Dozen', 'symbol' => 'dz', 'unit_type_id' => $typeIds['quantity']],

            // Weight
            ['name' => 'Gram', 'symbol' => 'g', 'unit_type_id' => $typeIds['weight']],
            ['name' => 'Kilogram', 'symbol' => 'kg', 'unit_type_id' => $typeIds['weight']],
            ['name' => 'Pound', 'symbol' => 'lb', 'unit_type_id' => $typeIds['weight']],

            // Volume
            ['name' => 'Milliliter', 'symbol' => 'ml', 'unit_type_id' => $typeIds['volume']],
            ['name' => 'Liter', 'symbol' => 'L', 'unit_type_id' => $typeIds['volume']],
            ['name' => 'Gallon', 'symbol' => 'gal', 'unit_type_id' => $typeIds['volume']],

            // Length
            ['name' => 'Centimeter', 'symbol' => 'cm', 'unit_type_id' => $typeIds['length']],
            ['name' => 'Meter', 'symbol' => 'm', 'unit_type_id' => $typeIds['length']],
            ['name' => 'Inch', 'symbol' => 'in', 'unit_type_id' => $typeIds['length']],

            // Area
            ['name' => 'Square Meter', 'symbol' => 'm²', 'unit_type_id' => $typeIds['area']],
            ['name' => 'Hectare', 'symbol' => 'ha', 'unit_type_id' => $typeIds['area']],

            // Time
            ['name' => 'Day', 'symbol' => 'd', 'unit_type_id' => $typeIds['time']],
            ['name' => 'Month', 'symbol' => 'mo', 'unit_type_id' => $typeIds['time']],
            ['name' => 'Year', 'symbol' => 'yr', 'unit_type_id' => $typeIds['time']],

            // Digital
            ['name' => 'Megabyte', 'symbol' => 'MB', 'unit_type_id' => $typeIds['digital']],
            ['name' => 'Gigabyte', 'symbol' => 'GB', 'unit_type_id' => $typeIds['digital']],

            // Special
            ['name' => 'Carat', 'symbol' => 'ct', 'unit_type_id' => $typeIds['special']],
            ['name' => 'Bottle', 'symbol' => 'bottle', 'unit_type_id' => $typeIds['special']],
        ];

        DB::table('units')->insert($units);
    }
}
