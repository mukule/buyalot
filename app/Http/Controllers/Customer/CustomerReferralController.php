<?php

namespace App\Http\Controllers\Customer;

use App\Http\Controllers\Controller;
use App\Models\Customer\Customer;
use App\Models\Customer\CustomerReferral;
use Illuminate\Http\Request;

class CustomerReferralController extends Controller
{
    public function __construct(
        private ReferralService $referralService
    ) {}

    public function index(Customer $customer)
    {
        $referrals = $customer->referrals()
            ->with('referred')
            ->latest()
            ->paginate(20);

        $stats = [
            'total_referrals' => $customer->referrals()->count(),
            'successful_referrals' => $customer->referrals()->successful()->count(),
            'pending_referrals' => $customer->referrals()->pending()->count(),
            'total_rewards_earned' => $customer->referrals()->successful()->sum('reward_amount'),
        ];

        return view('customers.referrals.index', compact('customer', 'referrals', 'stats'));
    }

    public function create(Customer $customer)
    {
        $referralCode = $this->referralService->generateReferralCode($customer);
        return view('customers.referrals.create', compact('customer', 'referralCode'));
    }

    public function store(Request $request, Customer $customer)
    {
        $validated = $request->validate([
            'referred_email' => 'required|email',
            'campaign_id' => 'nullable|exists:referral_campaigns,id',
            'source' => 'nullable|string',
        ]);

        $referral = $this->referralService->createReferral(
            $customer,
            $validated['referred_email'],
            $validated['campaign_id'] ?? null,
            $validated['source'] ?? 'manual'
        );

        return redirect()->route('customers.referrals.index', $customer)
            ->with('success', 'Referral invitation sent successfully.');
    }

    public function show(Customer $customer, CustomerReferral $referral)
    {
        return view('customers.referrals.show', compact('customer', 'referral'));
    }
}
