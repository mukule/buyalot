<?php

namespace App\Services;

use App\Models\Customer\Customer;
use App\Models\Customer\CustomerReferral;
use Illuminate\Support\Str;

class ReferralService
{
    public function generateReferralCode(Customer $customer): string
    {
        return strtoupper($customer->first_name . $customer->id . Str::random(4));
    }

    public function createReferral(
        Customer $referrer,
        string $referredEmail,
        ?int $campaignId = null,
        string $source = 'manual'
    ): CustomerReferral {
        return CustomerReferral::create([
            'referrer_customer_id' => $referrer->id,
            'referral_code' => $this->generateReferralCode($referrer),
            'status' => 'pending',
            'referred_at' => now(),
            'reward_type' => 'credit',
            'reward_amount' => 10.00, // Default reward
            'reward_currency' => 'USD',
            'campaign_id' => $campaignId,
            'source' => $source,
            'metadata' => [
                'referred_email' => $referredEmail,
                'invitation_sent_at' => now(),
            ]
        ]);
    }

    public function processReferralConversion(Customer $newCustomer, string $referralCode): ?CustomerReferral
    {
        $referral = CustomerReferral::where('referral_code', $referralCode)
            ->where('status', 'pending')
            ->first();

        if (!$referral) {
            return null;
        }

        $referral->update([
            'referred_customer_id' => $newCustomer->id,
            'status' => 'converted',
            'converted_at' => now(),
        ]);

        // Award rewards
        $this->awardReferralRewards($referral);

        return $referral;
    }

    private function awardReferralRewards(CustomerReferral $referral): void
    {
        $loyaltyService = app(LoyaltyPointService::class);

        // Award points to referrer
        $loyaltyService->awardPoints(
            $referral->referrer,
            100, // 100 points for successful referral
            'referral',
            'Referral bonus for ' . $referral->referred->full_name
        );

        $loyaltyService->awardPoints(
            $referral->referred,
            50,
            'referral',
            'Welcome bonus for joining via referral'
        );

        $referral->update([
            'referrer_reward_given' => true,
            'referred_reward_given' => true,
        ]);
    }
}
