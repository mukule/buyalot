<?php

namespace Database\Seeders;

use App\Models\POS\PosRegister;
use App\Models\Warehouse\Warehouse;
use Illuminate\Database\Seeder;

class PosSeeder extends Seeder
{
    /**
     * Run the database seeds.
     */
    public function run(): void
    {
        $warehouse = Warehouse::first();

        $registers = [
            ['name' => 'Main Register', 'warehouse_id' => $warehouse?->id, 'status' => 'active'],
            ['name' => 'Back Office Register', 'warehouse_id' => $warehouse?->id, 'status' => 'active'],
            ['name' => 'Quick Checkout 1', 'warehouse_id' => $warehouse?->id, 'status' => 'active'],
        ];

        foreach ($registers as $register) {
            PosRegister::create($register);
        }
    }
}
