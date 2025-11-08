<?php

namespace App\Services;

use App\Models\Region;
use App\Models\ShippingRate;

class ShippingService
{
   
    public function getOptionsByRegion(int $regionId): array
    {
        $region = Region::with('zone')->findOrFail($regionId);
        $tier = $region->zone->tier ?? 1;

        
        $rate = ShippingRate::firstOrFail();

        return [
            'pickup' => [
                'cost' => $rate->costForTier($tier),
                'days' => $rate->daysForTier($tier),
            ],
            'door' => [
                'cost' => $rate->doorCostForTier($tier),
                'days' => $rate->doorDaysForTier($tier),
            ],
            // 'express' => [
            //     'cost' => $rate->expressCostForTier($tier),
            //     'hours' => $rate->expressHoursForTier($tier),
            // ],
        ];
    }
}
