<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class ShippingRate extends Model
{
    use HasFactory;

    protected $table = 'shipping_rates';

    protected $fillable = [
        'zone_id',
        'package_size',
        'base_price',
        'door_price',
        'door_fallback_price',
        'door_fallback_min_km',
        'door_extra_km_cost',
        'cod_max_amount',
        'free_shipping_min_amount',
        'max_shipping_fee',
    ];

    protected $casts = [
        'base_price' => 'float',
        'door_price' => 'float',
        'door_fallback_price' => 'float',
        'door_fallback_min_km' => 'float',
        'door_extra_km_cost' => 'float',
        'cod_max_amount' => 'float',
        'free_shipping_min_amount' => 'float',
        'max_shipping_fee' => 'float',
    ];

    public function zone()
    {
        return $this->belongsTo(Zone::class);
    }

    /** Defaults when DB values are null */
    private const DEFAULT_FALLBACK_PRICE = 250;
    private const DEFAULT_FALLBACK_MIN_KM = 10;
    private const DEFAULT_EXTRA_KM_COST = 20;

    // --- Scopes ---
    public function scopePackageSize($query, string $size)
    {
        return $query->where('package_size', $size);
    }

    // --- Standard / Pickup ---
    public function costForTier(int $tier): float
    {
        $increment = (float) env('SHIPPING_INCREMENT_PER_TIER', 50);
        return $this->base_price + (($tier - 1) * $increment);
    }

    public function daysForTier(int $tier): int
    {
        $baseDays = (int) env('SHIPPING_DEFAULT_DAYS', 2);
        $increment = (int) env('SHIPPING_DAYS_INCREMENT', 1);
        return $baseDays + (($tier - 1) * $increment);
    }

    /**
     * Door Delivery/KM: calculate cost by distance.
     * - Distance <= min_km: cost = fallback (flat)
     * - Distance > min_km: cost = fallback + (distance - min_km) * extra_km_cost
     * Fallback only applies when calculated < fallback (floor).
     */
    public function calculateDoorCostByDistance(float $distanceKm): float
    {
        $fallback = (float) ($this->door_fallback_price ?? self::DEFAULT_FALLBACK_PRICE);
        $minKm = (float) ($this->door_fallback_min_km ?? self::DEFAULT_FALLBACK_MIN_KM);
        $extraPerKm = (float) ($this->door_extra_km_cost ?? self::DEFAULT_EXTRA_KM_COST);

        if ($distanceKm <= 0) {
            return $fallback;
        }

        $calculated = $distanceKm <= $minKm
            ? $fallback
            : $fallback + ($distanceKm - $minKm) * $extraPerKm;

        return max($fallback, $calculated);
    }

    /** Minimum door delivery cost (fallback price) - used when distance unknown */
    public function doorMinimumCost(): float
    {
        return (float) ($this->door_fallback_price ?? self::DEFAULT_FALLBACK_PRICE);
    }

    /** @deprecated Use calculateDoorCostByDistance or doorMinimumCost */
    public function doorCostForTier(int $tier): float
    {
        return $this->doorMinimumCost();
    }

    public function doorDaysForTier(int $tier): int
    {
        $baseDays = (int) env('DOOR_DELIVERY_DEFAULT_DAYS', 2);
        $increment = (int) env('DOOR_DELIVERY_DAYS_INCREMENT', 1);
        return $baseDays + (($tier - 1) * $increment);
    }

    /**
     * Apply the max_shipping_fee cap to a computed cost.
     * Returns the cost unchanged when no cap is configured.
     */
    public function applyCap(float $cost): float
    {
        if ($this->max_shipping_fee !== null && $this->max_shipping_fee > 0) {
            return min($cost, $this->max_shipping_fee);
        }
        return $cost;
    }

    /**
     * Apply the max_shipping_fee cap for home delivery.
     *
     * The effective cap is max(max_shipping_fee, door_fallback_price) so the
     * configured cap can never undercut the minimum fallback amount for door
     * delivery. If the calculated cost exceeds the cap it falls back to the
     * cap value; if the cap itself is below the fallback, the fallback is used.
     */
    public function applyHomeDeliveryCap(float $cost): float
    {
        if ($this->max_shipping_fee !== null && $this->max_shipping_fee > 0) {
            $effectiveCap = max((float) $this->max_shipping_fee, $this->doorMinimumCost());
            return min($cost, $effectiveCap);
        }
        return $cost;
    }
}
