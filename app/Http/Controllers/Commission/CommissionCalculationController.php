<?php

namespace App\Http\Controllers\Commission;

use App\Http\Controllers\Controller;
use App\Models\Commission\CommissionCalculation;
use App\Services\CommissionCalculatorService;
use Illuminate\Support\Facades\Request;

class CommissionCalculationController extends Controller
{
    public function __construct(
        private CommissionCalculatorService $calculatorService
    ) {}

    public function index(Request $request)
    {
        $calculations = CommissionCalculation::with(['seller', 'rule'])
            ->when($request->seller_id, fn($q) => $q->where('seller_id', $request->seller_id))
            ->when($request->status, fn($q) => $q->where('status', $request->status))
            ->latest()
            ->paginate(20);

        return view('admin.commission-calculations.index', compact('calculations'));
    }

    public function show(CommissionCalculation $calculation)
    {
        $calculation->load(['seller', 'rule', 'calculable']);
        return view('admin.commission-calculations.show', compact('calculation'));
    }

    public function confirm(CommissionCalculation $calculation)
    {
        $calculation->update(['status' => 'confirmed']);
        return back()->with('success', 'Commission calculation confirmed.');
    }

    public function dispute(CommissionCalculation $calculation, Request $request)
    {
        $calculation->update([
            'status' => 'disputed',
            'calculation_details' => array_merge(
                $calculation->calculation_details ?? [],
                ['dispute_reason' => $request->reason]
            )
        ]);
        return back()->with('success', 'Commission calculation disputed.');
    }

    public function recalculate(CommissionCalculation $calculation)
    {
        // Get original sale data and recalculate
        $saleData = [
            'amount' => $calculation->sale_amount,
            'calculable_type' => $calculation->calculable_type,
            'calculable_id' => $calculation->calculable_id,
        ];

        $newCalculation = $this->calculatorService->calculateForSeller(
            $calculation->seller,
            $saleData
        );

        if ($newCalculation) {
            $calculation->delete();
            return redirect()->route('admin.commission-calculations.show', $newCalculation)
                ->with('success', 'Commission recalculated successfully.');
        }

        return back()->with('error', 'Failed to recalculate commission.');
    }
}
