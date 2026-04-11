<?php

namespace App\Services;

use App\Models\Region;
use App\Models\ShippingRate;
use App\Models\Warehouse\Warehouse;
use Illuminate\Support\Facades\Http;

class ShippingService
{

    /** Get the shipping rate for home delivery config (default = first rate with no zone) */
    private function getHomeDeliveryRate(): ?ShippingRate
    {
        return ShippingRate::whereNull('zone_id')->orderBy('id')->first()
            ?? ShippingRate::orderBy('id')->first();
    }

    /**
     * Get shipping rate for a zone. Uses zone's shipping rates; falls back to default when zone has none.
     * Flow: Region → Zone → Shipping Rate
     */
    public function getRateForZone(?int $zoneId): ?ShippingRate
    {
        if ($zoneId) {
            $zone = \App\Models\Zone::with('shippingRates')->find($zoneId);
            if ($zone && $zone->shippingRates->isNotEmpty()) {
                return $zone->shippingRates->sortBy('id')->first();
            }
        }
        return $this->getHomeDeliveryRate();
    }

    /**
     * Get shipping rate for a region via its zone.
     * Region → Zone → Shipping Rate
     */
    public function getRateForRegion(Region $region): ?ShippingRate
    {
        $region->loadMissing('zone.shippingRates');
        if ($region->zone && $region->zone->shippingRates->isNotEmpty()) {
            return $region->zone->shippingRates->sortBy('id')->first();
        }
        return $this->getHomeDeliveryRate();
    }

    /**
     * Get home delivery config for frontend (Door Delivery/KM settings).
     */
    public function getHomeDeliveryConfig(): array
    {
        $rate = $this->getHomeDeliveryRate();
        if (!$rate) {
            return [
                'fallback_price' => 0,
                'fallback_min_km' => 10,
                'extra_km_cost' => 20,
                'min_shipping' => 0,
            ];
        }

        return [
            'fallback_price'  => (float) ($rate->door_fallback_price ?? 0),
            'fallback_min_km' => (float) ($rate->door_fallback_min_km ?? 10),
            'extra_km_cost'   => (float) ($rate->door_extra_km_cost ?? 20),
            'min_shipping'    => (int) round($rate->doorMinimumCost()),
            // Effective cap is max(max_shipping_fee, fallback) — cap never undercuts the minimum.
            'max_shipping_fee' => $rate->max_shipping_fee !== null
                ? (int) round(max((float) $rate->max_shipping_fee, $rate->doorMinimumCost()))
                : null,
        ];
    }

    /**
     * Get the center point (centroid) of all warehouses that support pickup.
     * Used as "main warehouse" for distance-based home delivery cost.
     */
    public function getMainWarehouseCenter(): ?array
    {
        $warehouses = Warehouse::withoutGlobalScopes()
            ->where(function ($q) {
                $q->where('supports_pickup', true)->orWhereIn('type', ['pickup_point', 'dispatch_center', 'general']);
            })
            ->whereNotNull('latitude')
            ->whereNotNull('longitude')
            ->where('latitude', '!=', '')
            ->where('longitude', '!=', '')
            ->get(['latitude', 'longitude']);

        if ($warehouses->isEmpty()) {
            return null;
        }

        $sumLat = 0;
        $sumLng = 0;
        $count = 0;
        foreach ($warehouses as $w) {
            $lat = (float) $w->latitude;
            $lng = (float) $w->longitude;
            if ($lat !== 0.0 || $lng !== 0.0) {
                $sumLat += $lat;
                $sumLng += $lng;
                $count++;
            }
        }

        if ($count === 0) {
            return null;
        }

        return [
            'lat' => $sumLat / $count,
            'lng' => $sumLng / $count,
        ];
    }

    /**
     * Calculate home delivery cost from Shipping Rates (Door Delivery/KM).
     * Uses door_fallback_price, door_fallback_min_km, door_extra_km_cost.
     * Rounded to whole number. Minimum is the rate's configured door minimum cost.
     * When a Region is supplied the zone-specific rate is used; otherwise falls
     * back to the default (no-zone) rate.
     */
    public function calculateHomeDeliveryCost(float $distanceKm, ?Region $region = null): float
    {
        $rate = $region ? $this->getRateForRegion($region) : $this->getHomeDeliveryRate();
        if (!$rate) {
            return 0.0;
        }

        $cost    = $rate->calculateDoorCostByDistance($distanceKm);
        $minCost = (int) round($rate->doorMinimumCost());
        $computed = (float) max($minCost, (int) round($cost));

        // applyHomeDeliveryCap ensures the cap never falls below the fallback price.
        return $rate->applyHomeDeliveryCap($computed);
    }

    /**
     * Haversine distance in km between two points.
     */
    public function distanceKm(float $lat1, float $lng1, float $lat2, float $lng2): float
    {
        $earthRadius = 6371; // km
        $dLat = deg2rad($lat2 - $lat1);
        $dLng = deg2rad($lng2 - $lng1);
        $a = sin($dLat / 2) ** 2 + cos(deg2rad($lat1)) * cos(deg2rad($lat2)) * sin($dLng / 2) ** 2;
        $c = 2 * atan2(sqrt($a), sqrt(1 - $a));

        return round($earthRadius * $c, 2);
    }

    /**
     * Reverse geocode lat/lng via Google Geocoding API and match to a Region.
     * Returns the Region if it matches a "pickup region", null otherwise.
     */
    public function reverseGeocodeAndMatchRegion(float $lat, float $lng): ?Region
    {
        $apiKey = config('services.google.maps_api_key');
        if (empty($apiKey)) {
            return null;
        }

        try {
            $response = Http::get('https://maps.googleapis.com/maps/api/geocode/json', [
                'latlng' => "{$lat},{$lng}",
                'key' => $apiKey,
            ]);

            if (! $response->successful()) {
                return null;
            }

            $data = $response->json();
            $results = $data['results'] ?? [];
            if (empty($results)) {
                return null;
            }

            $searchTerms = [];
            foreach ($results[0]['address_components'] ?? [] as $component) {
                $types = $component['types'] ?? [];
                if (array_intersect($types, ['locality', 'administrative_area_level_1', 'administrative_area_level_2', 'subadministrative_area', 'neighborhood', 'sublocality'])) {
                    $searchTerms[] = $component['long_name'] ?? null;
                    $searchTerms[] = $component['short_name'] ?? null;
                }
            }
            $searchTerms = array_filter(array_unique($searchTerms));

            $pickupRegionIds = $this->getRegionsWithPickup()->pluck('id')->toArray();
            $regions = Region::whereIn('id', $pickupRegionIds)->get();

            foreach ($regions as $region) {
                $name = strtolower($region->name);
                foreach ($searchTerms as $term) {
                    if (empty($term)) {
                        continue;
                    }
                    $term = strtolower((string) $term);
                    if (str_contains($term, $name) || str_contains($name, $term)) {
                        return $region;
                    }
                }
            }

            return null;
        } catch (\Throwable $e) {
            return null;
        }
    }

    /**
     * Regions that have at least one pickup point or warehouse with pickup support.
     */
    public function getRegionsWithPickup()
    {
        return Region::active()
            ->level('region')
            ->where(function ($q) {
                $q->whereHas('pickupPoints')
                    ->orWhereHas('warehouses', fn ($w) => $w->withoutGlobalScopes()->where('active', true));
            })
            ->orderBy('name')
            ->get();
    }

    /**
     * Get shipping options for a region via its zone.
     * Flow: Region → Zone → Shipping Rate.
     * Applies to both pickup point and home delivery – same rate and policy for both.
     * Free shipping when rate has free_shipping_min_amount and order total >= that amount.
     *
     * @param  int  $regionId
     * @param  float|null  $orderTotalBeforeShipping  Order total (items minus coupons) before shipping.
     */
    public function getOptionsByRegion(int $regionId, ?float $orderTotalBeforeShipping = null): array
    {
        $region = Region::with('zone.shippingRates')->findOrFail($regionId);
        $tier = $region->zone?->tier ?? 1;

        $rate = $this->getRateForRegion($region);

        if (!$rate) {
            return [
                'pickup' => ['cost' => 0, 'days' => 0],
                'door'   => ['cost' => 0, 'days' => 0],
            ];
        }

        $pickupCost = (int) round($rate->applyCap($rate->costForTier($tier)));
        // Home delivery cap must not undercut the fallback price.
        $doorCost   = (int) round($rate->applyHomeDeliveryCap($rate->doorMinimumCost()));

        // Free shipping when rate has free_shipping_min_amount and order total >= that amount
        $freeShippingMin = $rate->free_shipping_min_amount !== null ? (float) $rate->free_shipping_min_amount : null;
        if ($freeShippingMin !== null && $orderTotalBeforeShipping !== null && $orderTotalBeforeShipping >= $freeShippingMin) {
            $pickupCost = 0;
            $doorCost = 0;
        }

        return [
            'pickup' => [
                'cost' => $pickupCost,
                'days' => $rate->daysForTier($tier),
            ],
            'door' => [
                'cost' => $doorCost,
                'days' => $rate->doorDaysForTier($tier),
            ],
        ];
    }
}
