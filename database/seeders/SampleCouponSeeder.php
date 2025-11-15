<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;
use Illuminate\Support\Carbon;
use Illuminate\Support\Str;
use App\Models\Payment\Discount;

class SampleCouponSeeder extends Seeder
{
    /**
     * Run the database seeds.
     */
    public function run(): void
    {
        $now = Carbon::now();
        $inOneYear = Carbon::now()->addYear();

        // Helper to upsert by code so running the seeder multiple times is safe
        $upsert = function (array $attrs) use ($now, $inOneYear) {
            $code = strtoupper($attrs['code']);
            $defaults = [
                'name' => $attrs['name'] ?? $code,
                'slug' => Str::slug($attrs['name'] ?? $code),
                'description' => $attrs['description'] ?? null,
                'type' => $attrs['type'],
                'value' => $attrs['value'] ?? 0,
                'minimum_amount' => $attrs['minimum_amount'] ?? 0,
                'maximum_discount' => $attrs['maximum_discount'] ?? null,
                'usage_limit' => $attrs['usage_limit'] ?? null,
                'usage_limit_per_customer' => $attrs['usage_limit_per_customer'] ?? null,
                'used_count' => 0,
                'is_active' => true,
                'starts_at' => $attrs['starts_at'] ?? $now,
                'expires_at' => $attrs['expires_at'] ?? $inOneYear,
                'applicable_to' => $attrs['applicable_to'] ?? 'order',
                'conditions' => $attrs['conditions'] ?? null,
                'metadata' => $attrs['metadata'] ?? null,
                'created_by' => $attrs['created_by'] ?? null,
                'no_time_limit' => $attrs['no_time_limit'] ?? false,
            ];

            /** @var Discount $discount */
            $discount = Discount::query()->firstOrNew(['code' => $code]);
            $discount->fill(array_merge(['code' => $code], $defaults));
            $discount->save();
        };

        // 1) 10% off any order
        $upsert([
            'code' => 'TEST10',
            'name' => 'Test 10% OFF',
            'type' => 'percentage',
            'value' => 10, // percent
            'description' => 'Sample coupon: 10% off entire order',
        ]);

        // 2) KSh 500 off orders over KSh 2,000
        $upsert([
            'code' => 'TEST500',
            'name' => 'Test KSh 500 OFF',
            'type' => 'fixed',
            'value' => 500, // fixed amount in KSh
            'minimum_amount' => 2000,
            'description' => 'Sample coupon: KSh 500 off orders over KSh 2,000',
        ]);

        // 3) Free shipping coupon
        $upsert([
            'code' => 'FREESHIP',
            'name' => 'Test Free Shipping',
            'type' => 'free_shipping',
            'value' => 0,
            'description' => 'Sample coupon: free shipping on the order',
            'conditions' => [
                'applies_to' => 'shipping',
            ],
        ]);
    }
}
