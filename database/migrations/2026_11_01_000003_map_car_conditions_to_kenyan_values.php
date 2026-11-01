<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\DB;

/**
 * Cars & Motors now uses Kenyan market condition values:
 *   vehicles:    Brand New / Foreign Used / Locally Used
 *   spare parts: Brand New / Refurbished / Used
 * Map the old values on existing listings so filters and the edit form match.
 */
return new class extends Migration
{
    private const VEHICLE_MAP = ['New' => 'Brand New', 'Used' => 'Locally Used', 'Certified' => 'Foreign Used'];
    private const PART_MAP    = ['New' => 'Brand New'];

    public function up(): void
    {
        $this->remap(self::VEHICLE_MAP, self::PART_MAP);
    }

    public function down(): void
    {
        $this->remap(array_flip(self::VEHICLE_MAP), array_flip(self::PART_MAP));
    }

    private function remap(array $vehicleMap, array $partMap): void
    {
        DB::table('products')
            ->whereJsonContains('marketplaces', 'cars')
            ->select(['id', 'attributes'])
            ->orderBy('id')
            ->each(function ($row) use ($vehicleMap, $partMap) {
                $attributes = json_decode($row->attributes ?? '[]', true);
                if (! is_array($attributes) || ! isset($attributes['condition'])) {
                    return;
                }

                $map = ($attributes['listing_type'] ?? 'vehicle') === 'spare_part' ? $partMap : $vehicleMap;
                $new = $map[$attributes['condition']] ?? null;
                if ($new === null) {
                    return;
                }

                $attributes['condition'] = $new;
                DB::table('products')->where('id', $row->id)->update(['attributes' => json_encode($attributes)]);
            });
    }
};
