<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;

use App\Models\Orders\Order;
use App\Models\Orders\OrderItem;
use App\Models\POS\PosSession;
use App\Models\POS\PosRegister;
use App\Models\User;
use Illuminate\Support\Facades\DB;
use Inertia\Inertia;
use Carbon\Carbon;

class ReportController extends Controller
{
    public function index(Request $request)
    {
        $startDate = $request->start_date ? Carbon::parse($request->start_date)->startOfDay() : Carbon::now()->startOfMonth();
        $endDate = $request->end_date ? Carbon::parse($request->end_date)->endOfDay() : Carbon::now()->endOfDay();

        // Summary Stats
        $summary = Order::whereBetween('created_at', [$startDate, $endDate])
            ->whereIn('status', ['confirmed', 'delivered'])
            ->select(
                DB::raw('COUNT(*) as total_orders'),
                DB::raw('SUM(total_amount) as total_sales'),
                DB::raw('SUM(tax_amount) as total_tax'),
                DB::raw('SUM(discount_amount) as total_discount')
            )->first();

        // Sales by Source (Web vs POS)
        $bySource = Order::whereBetween('created_at', [$startDate, $endDate])
            ->whereIn('status', ['confirmed', 'delivered'])
            ->select('source', DB::raw('SUM(total_amount) as total'))
            ->groupBy('source')
            ->get();

        // Top Products
        $topProducts = OrderItem::whereHas('order', function($q) use ($startDate, $endDate) {
                $q->whereBetween('created_at', [$startDate, $endDate])
                  ->whereIn('status', ['confirmed', 'delivered']);
            })
            ->select('product_snapshot->name as name', DB::raw('SUM(quantity) as total_quantity'), DB::raw('SUM(total_price) as total_sales'))
            ->groupBy('name')
            ->orderBy('total_sales', 'desc')
            ->limit(10)
            ->get();

        // Sales by Register
        $byRegister = Order::whereBetween('created_at', [$startDate, $endDate])
            ->whereIn('status', ['confirmed', 'delivered'])
            ->whereNotNull('pos_session_id')
            ->join('pos_sessions', 'orders.pos_session_id', '=', 'pos_sessions.id')
            ->join('pos_registers', 'pos_sessions.pos_register_id', '=', 'pos_registers.id')
            ->select('pos_registers.name', DB::raw('SUM(total_amount) as total'))
            ->groupBy('pos_registers.name')
            ->get();

        // Sales by Attendant
        $byAttendant = Order::whereBetween('created_at', [$startDate, $endDate])
            ->whereIn('status', ['confirmed', 'delivered'])
            ->whereNotNull('pos_session_id')
            ->join('pos_sessions', 'orders.pos_session_id', '=', 'pos_sessions.id')
            ->join('users', 'pos_sessions.user_id', '=', 'users.id')
            ->select('users.name', DB::raw('SUM(total_amount) as total'))
            ->groupBy('users.name')
            ->get();

        return Inertia::render('Admin/Reports/Sales/Index', [
            'summary' => $summary,
            'bySource' => $bySource,
            'topProducts' => $topProducts,
            'byRegister' => $byRegister,
            'byAttendant' => $byAttendant,
            'filters' => [
                'start_date' => $startDate->toDateString(),
                'end_date' => $endDate->toDateString(),
            ]
        ]);
    }
}
