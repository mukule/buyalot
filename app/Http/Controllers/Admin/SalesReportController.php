<?php

namespace App\Http\Controllers\Admin;

use App\Domains\Invoicing\Services\SalesReportService;
use App\Http\Controllers\Controller;
use Carbon\Carbon;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

class SalesReportController extends Controller
{
    public function __construct(
        private SalesReportService $salesReportService
    ) {}

    /**
     * Sales report for date range with optional period aggregation.
     * Query: start_date, end_date, period (daily|weekly|monthly|null)
     */
    public function report(Request $request): JsonResponse
    {
        $startDate = $request->start_date
            ? Carbon::parse($request->start_date)->startOfDay()
            : Carbon::now()->startOfMonth();
        $endDate = $request->end_date
            ? Carbon::parse($request->end_date)->endOfDay()
            : Carbon::now()->endOfDay();

        $period = $request->string('period')->toString() ?: null;
        $report = $this->salesReportService->report($startDate, $endDate, $period);

        if ($period === 'daily') {
            $report['daily'] = $this->salesReportService->dailyReport($startDate, $endDate);
        } elseif ($period === 'weekly') {
            $report['weekly'] = $this->salesReportService->weeklyReport($startDate, $endDate);
        } elseif ($period === 'monthly') {
            $report['monthly'] = $this->salesReportService->monthlyReport($startDate, $endDate);
        }

        return response()->json($report);
    }

    /**
     * Daily sales aggregation.
     */
    public function daily(Request $request): JsonResponse
    {
        $startDate = Carbon::parse($request->input('start_date', now()->startOfMonth()))->startOfDay();
        $endDate = Carbon::parse($request->input('end_date', now()))->endOfDay();

        return response()->json([
            'data' => $this->salesReportService->dailyReport($startDate, $endDate),
            'start_date' => $startDate->toDateString(),
            'end_date' => $endDate->toDateString(),
        ]);
    }

    /**
     * Weekly sales aggregation.
     */
    public function weekly(Request $request): JsonResponse
    {
        $startDate = Carbon::parse($request->input('start_date', now()->startOfMonth()))->startOfDay();
        $endDate = Carbon::parse($request->input('end_date', now()))->endOfDay();

        return response()->json([
            'data' => $this->salesReportService->weeklyReport($startDate, $endDate),
            'start_date' => $startDate->toDateString(),
            'end_date' => $endDate->toDateString(),
        ]);
    }

    /**
     * Monthly sales aggregation.
     */
    public function monthly(Request $request): JsonResponse
    {
        $startDate = Carbon::parse($request->input('start_date', now()->startOfYear()))->startOfDay();
        $endDate = Carbon::parse($request->input('end_date', now()))->endOfDay();

        return response()->json([
            'data' => $this->salesReportService->monthlyReport($startDate, $endDate),
            'start_date' => $startDate->toDateString(),
            'end_date' => $endDate->toDateString(),
        ]);
    }

    /**
     * Voided sales list: sale reference, date/time, user/cashier, reason.
     */
    public function voidedSales(Request $request): JsonResponse
    {
        $startDate = Carbon::parse($request->input('start_date', now()->startOfMonth()))->startOfDay();
        $endDate = Carbon::parse($request->input('end_date', now()))->endOfDay();

        return response()->json([
            'data' => $this->salesReportService->voidedSales($startDate, $endDate),
            'start_date' => $startDate->toDateString(),
            'end_date' => $endDate->toDateString(),
        ]);
    }
}
