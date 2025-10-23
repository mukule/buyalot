<?php

namespace App\Services;

use App\Models\Customer\Customer;
use App\Models\Customer\CustomerLoyaltyPoint;
use Carbon\Carbon;

class LoyaltyPointService
{
    public function awardPoints(
        Customer $customer,
        int $points,
        string $type,
        string $description,
        ?Carbon $expiresAt = null
    ): CustomerLoyaltyPoint {
        return CustomerLoyaltyPoint::create([
            'customer_id' => $customer->id,
            'points' => $points,
            'type' => $type,
            'description' => $description,
            'status' => 'active',
            'expires_at' => $expiresAt,
            'awarded_at' => now(),
        ]);
    }

    public function redeemPoints(Customer $customer, int $points, string $description): CustomerLoyaltyPoint
    {
        return CustomerLoyaltyPoint::create([
            'customer_id' => $customer->id,
            'points' => -$points, // Negative for redemption
            'type' => 'redemption',
            'description' => $description,
            'status' => 'active',
            'awarded_at' => now(),
            'redeemed_at' => now(),
        ]);
    }

    public function calculatePointsForPurchase(float $amount): int
    {
        return (int) floor($amount);
    }

    public function expirePoints(): int
    {
        $expiredCount = CustomerLoyaltyPoint::where('expires_at', '<', now())
            ->where('status', 'active')
            ->update(['status' => 'expired']);

        return $expiredCount;
    }

    public function getPointsExpiringInDays(Customer $customer, int $days): int
    {
        return $customer->loyaltyPoints()
            ->active()
            ->where('expires_at', '<=', now()->addDays($days))
            ->where('expires_at', '>', now())
            ->sum('points');
    }
}
