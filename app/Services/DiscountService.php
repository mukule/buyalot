<?php

namespace App\Services;

use App\Models\ProductVariant;
use Illuminate\Support\Facades\Log;

class DiscountService
{
    public function calculateDiscounts(array $variantIds): array
    {
        // Log::info('DiscountService::calculateDiscounts called', [
        //     'variant_ids' => $variantIds,
        // ]);

    
        $variants = ProductVariant::with(['discounts', 'product.discounts'])
            ->whereIn('id', $variantIds)
            ->get();

        // Log::info('Fetched variants with related discounts', [
        //     'count' => $variants->count(),
        //     'variants' => $variants->map(fn($v) => [
        //         'id' => $v->id,
        //         'marked_price' => $v->marked_price,
        //         'variant_discounts' => $v->discounts->map(fn($d) => [
        //             'id' => $d->id,
        //             'name' => $d->name,
        //             'type' => $d->type,
        //             'value' => $d->value,
        //             'is_active' => $d->is_active,
        //         ]),
        //         'product_discounts' => optional($v->product)->discounts?->map(fn($d) => [
        //             'id' => $d->id,
        //             'name' => $d->name,
        //             'type' => $d->type,
        //             'value' => $d->value,
        //             'is_active' => $d->is_active,
        //         ]),
        //     ]),
        // ]);

        $results = [];

        foreach ($variants as $variant) {
            $markedPrice = $variant->marked_price ?? 0;
            $totalDiscount = 0;
            $discountDetails = [];

            
            $discounts = $variant->discounts->merge($variant->product->discounts ?? collect());

            if ($discounts->isEmpty()) {
                Log::debug('No discounts found for variant', ['variant_id' => $variant->id]);
            }

            foreach ($discounts as $discount) {
                // Skip inactive or expired discounts
                if (!$discount->is_active) {
                    Log::debug('Skipping inactive discount', [
                        'variant_id' => $variant->id,
                        'discount_id' => $discount->id,
                        'discount_name' => $discount->name,
                    ]);
                    continue;
                }

                if ($discount->starts_at && now()->lt($discount->starts_at)) {
                    // Log::debug('Skipping not-yet-started discount', [
                    //     'variant_id' => $variant->id,
                    //     'discount_id' => $discount->id,
                    // ]);
                    continue;
                }

                if ($discount->expires_at && now()->gt($discount->expires_at)) {
                    // Log::debug('Skipping expired discount', [
                    //     'variant_id' => $variant->id,
                    //     'discount_id' => $discount->id,
                    // ]);
                    continue;
                }

                // Calculate discount amount
                $discountAmount = match ($discount->type) {
                    'percentage' => $markedPrice * ($discount->value / 100),
                    'fixed' => $discount->value,
                    default => 0,
                };

                $discountAmount = min($discountAmount, $markedPrice);
                $totalDiscount += $discountAmount;

                $discountDetails[] = [
                    'discount_name'   => $discount->name,
                    'discount_type'   => $discount->type,
                    'discount_value'  => $discount->value,
                    'discount_amount' => round($discountAmount, 2),
                ];

                // Log::debug('Processed discount', [
                //     'variant_id' => $variant->id,
                //     'discount_id' => $discount->id,
                //     'discount_name' => $discount->name,
                //     'discount_amount' => $discountAmount,
                // ]);
            }

            
            $finalPrice = max($markedPrice - $totalDiscount, 0);
            $hasDiscount = $totalDiscount > 0;

            $variantResult = [
                'product_variant_id' => $variant->id,
                'marked_price'       => round($markedPrice, 2),
                'discounts'          => $discountDetails,
                'total_discount'     => round($totalDiscount, 2),
                'final_price'        => round($finalPrice, 2),
                'has_discount'       => $hasDiscount,
            ];

            // Log::info('Final discount calculation for variant', $variantResult);
            $results[] = $variantResult;
        }

        // Log::info('DiscountService::calculateDiscounts completed', [
        //     'results_count' => count($results),
        // ]);

        return $results;
    }
}
