<?php

namespace Database\Seeders;

use DB;
use Illuminate\Database\Console\Seeds\WithoutModelEvents;
use Illuminate\Database\Seeder;

class DiscountTypeSeeder extends Seeder
{
    /**
     * Run the database seeds.
     */
    public function run(): void
    {
        $types = [
            ['name' => 'Percentage Discount', 'code' => 'percentage', 'description' => 'Applies a percentage reduction to the product price.'],
            ['name' => 'Fixed Amount Discount', 'code' => 'fixed_amount', 'description' => 'Applies a fixed amount reduction to the product price.'],
            ['name' => 'Tiered Discount', 'code' => 'tiered', 'description' => 'Discount varies based on spending or quantity tiers.'],
            ['name' => 'Buy X Get Y', 'code' => 'bogo', 'description' => 'Buy one get one free or similar offer.'],
            ['name' => 'Volume Discount', 'code' => 'volume', 'description' => 'Discount increases with larger quantities.'],
            ['name' => 'Flash Sale', 'code' => 'flash_sale', 'description' => 'Short-term discount valid for specific hours or dates.'],
            ['name' => 'Seasonal Sale', 'code' => 'seasonal', 'description' => 'Discount applied during specific seasons or events.'],
            ['name' => 'Product Bundle', 'code' => 'bundle', 'description' => 'Discount for buying related products together.'],
            ['name' => 'Cross-Sell Offer', 'code' => 'cross_sell', 'description' => 'Discount for complementary products.'],
            ['name' => 'Coupon Code', 'code' => 'coupon_code', 'description' => 'Discount applied using a code.'],
            ['name' => 'First Purchase Discount', 'code' => 'first_purchase', 'description' => 'Discount for new customers only.'],
            ['name' => 'Referral Discount', 'code' => 'referral', 'description' => 'Discount for referring or being referred.'],
            ['name' => 'Free Shipping', 'code' => 'free_shipping', 'description' => 'Free delivery when certain conditions are met.'],
            ['name' => 'Discounted Shipping', 'code' => 'discounted_shipping', 'description' => 'Reduced shipping cost based on criteria.'],
            ['name' => 'Cashback Offer', 'code' => 'cashback', 'description' => 'Customer earns cashback after purchase.'],
            ['name' => 'Reward Points', 'code' => 'reward_points', 'description' => 'Buyer earns points redeemable later.'],
            ['name' => 'Membership Discount', 'code' => 'membership', 'description' => 'Exclusive discount for subscribed members.'],
            ['name' => 'Region-Based Offer', 'code' => 'region_based', 'description' => 'Discount applies to specific buyer locations.'],
            ['name' => 'Category Discount', 'code' => 'category_based', 'description' => 'Discount applies to products in certain categories.'],
            ['name' => 'New Seller Offer', 'code' => 'new_seller', 'description' => 'Special offer to attract first-time customers of a seller.'],
        ];

        // Use upsert to avoid duplicate key errors when seeding multiple times
        DB::table('discount_types')->upsert($types, ['code'], ['name', 'description']);
    }
}
