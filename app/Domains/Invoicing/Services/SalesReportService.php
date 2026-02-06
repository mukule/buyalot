<?php

namespace App\Domains\Invoicing\Services;

use App\Models\Orders\Order;
use App\Models\Orders\OrderItem;
use App\Models\POS\PosVoidedSale;
use App\Models\Payment\Payment;
use Carbon\Carbon;
use Illuminate\Support\Facades\DB;

final class SalesReportService
{
    /**
     * Get sales report for a date range. Filter by period: daily, weekly, monthly.
     */
    public function report(Carbon $startDate, Carbon $endDate, ?string $period = null): array
    {
        $query = Order::query()
            ->whereBetween('created_at', [$startDate, $endDate])
            ->whereIn('status', ['confirmed', 'delivered']);

        $summary = (clone $query)->select(
            DB::raw('COUNT(*) as total_orders'),
            DB::raw('COALESCE(SUM(total_amount), 0) as total_sales'),
            DB::raw('COALESCE(SUM(tax_amount), 0) as total_vat'),
            DB::raw('COALESCE(SUM(discount_amount), 0) as total_discount')
        )->first();

        $bySource = (clone $query)->select('source', DB::raw('SUM(total_amount) as total'))
            ->groupBy('source')
            ->get();

        $paymentsReceived = Payment::query()
            ->where('payable_type', Order::class)
            ->where('status', 'completed')
            ->whereBetween('completed_at', [$startDate, $endDate])
            ->sum('amount');

        return [
            'period' => $period ?? 'custom',
            'start_date' => $startDate->toDateString(),
            'end_date' => $endDate->toDateString(),
            'summary' => [
                'total_orders' => (int) $summary->total_orders,
                'total_sales' => (float) $summary->total_sales,
                'total_vat' => (float) $summary->total_vat,
                'total_discount' => (float) $summary->total_discount,
                'payments_received' => (float) $paymentsReceived,
            ],
            'by_source' => $bySource->map(fn ($r) => [
                'source' => $r->source ?? 'unknown',
                'total' => (float) $r->total,
            ]),
        ];
    }

    /**
     * Daily aggregation (group by day). DATE() works on MySQL; SQLite uses date().
     */
    public function dailyReport(Carbon $startDate, Carbon $endDate): array
    {
        $driver = DB::connection()->getDriverName();
        $dateExpr = $driver === 'mysql' ? 'DATE(created_at)' : 'date(created_at)';

        $rows = Order::query()
            ->whereBetween('created_at', [$startDate, $endDate])
            ->whereIn('status', ['confirmed', 'delivered'])
            ->select(
                DB::raw("{$dateExpr} as date"),
                DB::raw('COUNT(*) as order_count'),
                DB::raw('SUM(total_amount) as total_sales'),
                DB::raw('SUM(tax_amount) as total_vat')
            )
            ->groupBy(DB::raw($dateExpr))
            ->orderBy('date')
            ->get();

        return $rows->map(fn ($r) => [
            'date' => $r->date,
            'order_count' => (int) $r->order_count,
            'total_sales' => (float) $r->total_sales,
            'total_vat' => (float) $r->total_vat,
        ])->all();
    }

    /**
     * Weekly aggregation (group by week). Uses driver-agnostic date grouping.
     */
    public function weeklyReport(Carbon $startDate, Carbon $endDate): array
    {
        $driver = DB::connection()->getDriverName();
        if ($driver === 'mysql') {
            $rows = Order::query()
                ->whereBetween('created_at', [$startDate, $endDate])
                ->whereIn('status', ['confirmed', 'delivered'])
                ->select(
                    DB::raw('YEARWEEK(created_at) as week'),
                    DB::raw('MIN(DATE(created_at)) as week_start'),
                    DB::raw('COUNT(*) as order_count'),
                    DB::raw('SUM(total_amount) as total_sales'),
                    DB::raw('SUM(tax_amount) as total_vat')
                )
                ->groupBy(DB::raw('YEARWEEK(created_at)'))
                ->orderBy('week')
                ->get();
        } else {
            $rows = Order::query()
                ->whereBetween('created_at', [$startDate, $endDate])
                ->whereIn('status', ['confirmed', 'delivered'])
                ->select(
                    DB::raw("strftime('%Y-%W', created_at) as week"),
                    DB::raw("date(min(created_at)) as week_start"),
                    DB::raw('COUNT(*) as order_count'),
                    DB::raw('SUM(total_amount) as total_sales'),
                    DB::raw('SUM(tax_amount) as total_vat')
                )
                ->groupBy(DB::raw("strftime('%Y-%W', created_at)"))
                ->orderBy('week')
                ->get();
        }

        return $rows->map(fn ($r) => [
            'week' => $r->week,
            'week_start' => $r->week_start,
            'order_count' => (int) $r->order_count,
            'total_sales' => (float) $r->total_sales,
            'total_vat' => (float) $r->total_vat,
        ])->all();
    }

    /**
     * Monthly aggregation (group by month). Uses driver-agnostic date grouping.
     */
    public function monthlyReport(Carbon $startDate, Carbon $endDate): array
    {
        $driver = DB::connection()->getDriverName();
        if ($driver === 'mysql') {
            $expr = 'DATE_FORMAT(created_at, "%Y-%m")';
        } else {
            $expr = "strftime('%Y-%m', created_at)";
        }

        $rows = Order::query()
            ->whereBetween('created_at', [$startDate, $endDate])
            ->whereIn('status', ['confirmed', 'delivered'])
            ->select(
                DB::raw("{$expr} as month"),
                DB::raw('COUNT(*) as order_count'),
                DB::raw('SUM(total_amount) as total_sales'),
                DB::raw('SUM(tax_amount) as total_vat')
            )
            ->groupBy(DB::raw($expr))
            ->orderBy('month')
            ->get();

        return $rows->map(fn ($r) => [
            'month' => $r->month,
            'order_count' => (int) $r->order_count,
            'total_sales' => (float) $r->total_sales,
            'total_vat' => (float) $r->total_vat,
        ])->all();
    }

    /**
     * List voided sales with reference, date/time, user/cashier, reason.
     */
    public function voidedSales(Carbon $startDate, Carbon $endDate): array
    {
        $rows = PosVoidedSale::query()
            ->where('recalled', false)
            ->whereBetween('voided_at', [$startDate, $endDate])
            ->with(['user:id,name,email', 'customer.user:id,name,email'])
            ->orderByDesc('voided_at')
            ->get();

        return $rows->map(fn ($v) => [
            'id' => $v->id,
            'sale_reference' => 'voided-' . $v->id,
            'voided_at' => $v->voided_at?->toIso8601String(),
            'total_amount' => (float) $v->total_amount,
            'cashier' => $v->user ? [
                'id' => $v->user->id,
                'name' => $v->user->name,
                'email' => $v->user->email,
            ] : null,
            'reason' => $v->reason,
            'customer' => $v->customer ? [
                'id' => $v->customer->id,
                'name' => $v->customer->user->name ?? null,
            ] : null,
        ])->all();
    }
}
