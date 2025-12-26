<?php

namespace App\Services;

use App\Models\ProductVariant;
use Illuminate\Support\Facades\Log;

class DiscountService
{
    public function calculateDiscounts(array $variantIds): array
    {
        $variants = ProductVariant::with(['discounts', 'product.discounts'])
            ->whereIn('id', $variantIds)
            ->get();

        $results = [];

        foreach ($variants as $variant) {
            $markedPrice = $variant->marked_price ?? 0;
            $totalDiscount = 0;
            $discountDetails = [];

            $discounts = $variant->discounts->merge($variant->product->discounts ?? collect());

            // if ($discounts->isEmpty()) {
            //     Log::debug('No discounts found for variant', ['variant_id' => $variant->id]);
            // }

            foreach ($discounts as $discount) {
                if (!$discount->is_active) continue;
                if ($discount->starts_at && now()->lt($discount->starts_at)) continue;
                if ($discount->expires_at && now()->gt($discount->expires_at)) continue;

                $discountAmount = match ($discount->type) {
                    'percentage' => $markedPrice * ($discount->value / 100),
                    'fixed'      => $discount->value,
                    default      => 0,
                };

                $discountAmount = min($discountAmount, $markedPrice);
                $totalDiscount += $discountAmount;

                $discountDetails[] = [
                    'discount_name'   => $discount->name,
                    'discount_type'   => $discount->type,
                    'discount_value'  => $discount->value,
                    'discount_amount' => round($discountAmount, 2),
                ];
            }

            $finalPrice = max($markedPrice - $totalDiscount, 0);
            $hasDiscount = $totalDiscount > 0;

            
            $discountPercentage = $markedPrice > 0 ? (int) round(($totalDiscount / $markedPrice) * 100) : 0;

            $variantResult = [
                'product_variant_id'  => $variant->id,
                'marked_price'        => round($markedPrice, 2),
                'discounts'           => $discountDetails,
                'total_discount'      => round($totalDiscount, 2),
                'discount_percentage' => $discountPercentage,
                'final_price'         => round($finalPrice, 2),
                'has_discount'        => $hasDiscount,
            ];

            $results[] = $variantResult;
        }

        return $results;
    }
}
