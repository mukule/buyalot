<?php

namespace App\Http\Controllers\POS;

use App\Http\Controllers\Controller;
use App\Models\POS\PosSession;
use App\Models\POS\PosVoidedSale;
use App\Services\SellerContext;
use Carbon\Carbon;
use Illuminate\Http\Request;

class PosVoidedSaleController extends Controller
{
    public function index(Request $request)
    {
        $request->validate([
            'pos_session_id' => 'required|exists:pos_sessions,id',
        ]);

        $user = $request->user() ?? auth()->user();
        $session = PosSession::forUser($user)->findOrFail($request->pos_session_id);

        $voidedSales = PosVoidedSale::where('pos_session_id', $session->id)
            ->where('recalled', false)
            ->with(['customer.user', 'user'])
            ->latest()
            ->get();

        return response()->json($voidedSales);
    }

    public function store(Request $request)
    {
        $request->validate([
            'pos_session_id' => 'required|exists:pos_sessions,id',
            'cart_data' => 'required|array',
            'total_amount' => 'required|numeric',
            'reason' => 'nullable|string',
        ]);

        $user = $request->user() ?? auth()->user();
        $session = PosSession::forUser($user)->findOrFail($request->pos_session_id);

        $voidedSale = PosVoidedSale::create([
            'pos_session_id' => $session->id,
            'user_id' => $user->id,
            'customer_id' => $request->customer_id,
            'cart_data' => $request->cart_data,
            'total_amount' => $request->total_amount,
            'reason' => $request->reason,
            'voided_at' => Carbon::now(),
        ]);

        return response()->json([
            'message' => 'Sale voided and saved.',
            'voided_sale' => $voidedSale,
        ]);
    }

    public function recall(Request $request, PosVoidedSale $voidedSale)
    {
        $user = $request->user() ?? auth()->user();
        if (! SellerContext::canAccessSession($user, $voidedSale->session)) {
            abort(403, 'You cannot access this voided sale.');
        }

        if ($voidedSale->recalled) {
            return response()->json(['message' => 'This sale has already been recalled.'], 422);
        }

        $voidedSale->update([
            'recalled' => true,
            'recalled_at' => Carbon::now(),
        ]);

        return response()->json([
            'message' => 'Sale recalled successfully.',
            'voided_sale' => $voidedSale,
        ]);
    }
}
