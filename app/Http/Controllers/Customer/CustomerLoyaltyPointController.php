<?php

namespace App\Http\Controllers\Customer;

use App\Http\Controllers\Controller;
use App\Models\Customer\Customer;
use Illuminate\Http\Request;
use Inertia\Inertia;

class CustomerLoyaltyPointController extends Controller
{
    public function __construct(
        private LoyaltyPointService $loyaltyService
    ) {}

    public function index(Customer $customer)
    {
        $points = $customer->loyaltyPoints()
            ->with('reference')
            ->latest()
            ->paginate(20);

        $summary = [
            'total_points' => $customer->getTotalLoyaltyPoints(),
            'total_earned' => $customer->loyaltyPoints()->earned()->sum('points'),
            'total_redeemed' => abs($customer->loyaltyPoints()->redeemed()->sum('points')),
            'expired_points' => $customer->loyaltyPoints()->expired()->sum('points')
        ];

        return Inertia::render('customers.loyalty-points.index', compact('customer', 'points', 'summary'));
    }

    public function award(Request $request, Customer $customer)
    {
        $request->validate([
            'points' => 'required|integer|min:1',
            'description' => 'required|string|max:255',
            'type' => 'required|in:bonus,purchase,referral,review,birthday',
            'expires_at' => 'nullable|date|after:today'
        ]);

        $loyaltyPoint = $this->loyaltyService->awardPoints(
            $customer,
            $request->points,
            $request->type,
            $request->description,
            $request->expires_at
        );

        return back()->with('success', "Awarded {$request->points} points to {$customer->full_name}.");
    }

    public function redeem(Request $request, Customer $customer)
    {
        $request->validate([
            'points' => 'required|integer|min:1',
            'description' => 'required|string|max:255'
        ]);

        if ($customer->getTotalLoyaltyPoints() < $request->points) {
            return back()->with('error', 'Customer does not have enough points.');
        }

        $loyaltyPoint = $this->loyaltyService->redeemPoints(
            $customer,
            $request->points,
            $request->description
        );

        return back()->with('success', "Redeemed {$request->points} points for {$customer->full_name}.");
    }

    public function history(Customer $customer)
    {
        $history = $customer->loyaltyPoints()
            ->with('reference')
            ->orderBy('created_at', 'desc')
            ->get()
            ->groupBy(function($item) {
                return $item->created_at->format('Y-m');
            });

        return view('customers.loyalty-points.history', compact('customer', 'history'));
    }
}
