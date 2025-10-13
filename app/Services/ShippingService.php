<?php

namespace App\Services;

use App\Models\ProductVariant;

class ShippingService
{
    /**
     * Estimate shipping cost for a set of items and a distance (in KM).
     *
     * Each item should be an associative array with at least:
     * - quantity (int)
     * - product_variant_id OR unit_price (float)
     *
     * You may pass richer context but the calculation is intentionally simple
     * and configurable via config/shipping.php.
     */
    public function estimate(array $items, float $distanceKm): array
    {
        $distanceKm = max(0, (float)$distanceKm);
        $baseFee   = (float) config('shipping.base_fee', 50);
        $perKm     = (float) config('shipping.per_km', 12);
        $perItem   = (float) config('shipping.per_item', 10);
        $minTotal  = (float) config('shipping.min_total', 0);
        $maxTotal  = (float) config('shipping.max_total', 0); // 0 = no cap

        $totalQty = 0;
        $lines = [];

        foreach ($items as $row) {
            $qty = (int) ($row['quantity'] ?? 1);
            $totalQty += $qty;

            $variantId = $row['product_variant_id'] ?? null;
            $unitPrice = $row['unit_price'] ?? null;

            // Optional: pull some context for future advanced rules
            $variant = null;
            if ($variantId) {
                $variant = ProductVariant::with('product')->find($variantId);
            }

            $lines[] = [
                'product_variant_id' => $variant?->id,
                'product_id' => $variant?->product_id,
                'quantity' => $qty,
                'unit_price' => $unitPrice ?? (float)($variant?->selling_price ?? 0),
            ];
        }

        // Core formula
        $amount = $baseFee + ($perKm * $distanceKm) + ($perItem * $totalQty);

        if ($minTotal > 0) {
            $amount = max($amount, $minTotal);
        }
        if ($maxTotal > 0) {
            $amount = min($amount, $maxTotal);
        }

        $amount = round($amount, 2);

        return [
            'amount' => $amount,
            'breakdown' => [
                'base_fee' => $baseFee,
                'distance_km' => $distanceKm,
                'per_km_rate' => $perKm,
                'distance_component' => round($perKm * $distanceKm, 2),
                'total_items' => $totalQty,
                'per_item_rate' => $perItem,
                'items_component' => round($perItem * $totalQty, 2),
            ],
            'items' => $lines,
        ];
    }
}
