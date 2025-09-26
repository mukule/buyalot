<?php

namespace App\Http\Controllers\Api;

use App\Services\CommissionCalculatorService;
use App\Http\Resources\CommissionCalculationResource;
use Illuminate\Http\Request;
use Illuminate\Http\JsonResponse;


class CommissionController
{
    public function __construct(
        private CommissionCalculatorService $calculatorService
    ) {}

    /**
     * Calculate commission for a sale
     */
    public function calculate(Request $request): JsonResponse
    {
        $request->validate([
            'seller_id' => 'required|exists:users,id',
            'amount' => 'required|numeric|min:0',
            'calculable_type' => 'required|string',
            'calculable_id' => 'required|integer',
            'item_count' => 'sometimes|integer|min:1',
            'payment_method' => 'sometimes|string',
            'product_category' => 'sometimes|string',
            'customer_type' => 'sometimes|string',
        ]);

        $seller = \App\Models\User::find($request->seller_id);

        if (!$seller->hasActiveSubscription()) {
            return response()->json([
                'error' => 'Seller does not have an active subscription'
            ], 400);
        }

        $calculation = $this->calculatorService->calculateForSeller(
            $seller,
            $request->all()
        );

        if (!$calculation) {
            return response()->json([
                'message' => 'No applicable commission rules found',
                'commission_amount' => 0
            ]);
        }

        return response()->json([
            'message' => 'Commission calculated successfully',
            'data' => new CommissionCalculationResource($calculation)
        ]);
    }

    /**
     * Get seller's commission summary
     */
    public function summary(Request $request): \Illuminate\Http\JsonResponse
    {
        $request->validate([
            'seller_id' => 'required|exists:users,id',
            'period' => 'sometimes|in:today,week,month,quarter,year',
        ]);

        $seller = \App\Models\User::find($request->seller_id);
        $period = $request->period ?? 'month';

        $dateRange = match($period) {
            'today' => [now()->startOfDay(), now()->endOfDay()],
            'week' => [now()->startOfWeek(), now()->endOfWeek()],
            'month' => [now()->startOfMonth(), now()->endOfMonth()],
            'quarter' => [now()->startOfQuarter(), now()->endOfQuarter()],
            'year' => [now()->startOfYear(), now()->endOfYear()],
        };

        $calculations = $seller->commissionCalculations()
            ->whereBetween('calculated_at', $dateRange)
            ->get();

        $summary = [
            'period' => $period,
            'total_sales' => $calculations->sum('sale_amount'),
            'total_commission' => $calculations->sum('commission_amount'),
            'average_rate' => $calculations->avg('commission_rate'),
            'transaction_count' => $calculations->count(),
            'pending_amount' => $calculations->where('status', 'pending')->sum('commission_amount'),
            'confirmed_amount' => $calculations->where('status', 'confirmed')->sum('commission_amount'),
        ];

        return response()->json([
            'message' => 'Commission summary retrieved successfully',
            'data' => $summary
        ]);
    }
}
