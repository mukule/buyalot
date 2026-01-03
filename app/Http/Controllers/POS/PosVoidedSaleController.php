<?php

namespace App\Http\Controllers\POS;

use App\Http\Controllers\Controller;
use App\Models\POS\PosVoidedSale;
use App\Models\POS\PosSession;
use Illuminate\Http\Request;
use Carbon\Carbon;

class PosVoidedSaleController extends Controller
{
    public function index(Request $request)
    {
        $request->validate([
            'pos_session_id' => 'required|exists:pos_sessions,id',
        ]);

        $voidedSales = PosVoidedSale::where('pos_session_id', $request->pos_session_id)
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

        $session = PosSession::findOrFail($request->pos_session_id);

        $voidedSale = PosVoidedSale::create([
            'pos_session_id' => $session->id,
            'user_id' => auth()->id(),
            'customer_id' => $request->customer_id,
            'cart_data' => $request->cart_data,
            'total_amount' => $request->total_amount,
            'reason' => $request->reason,
            'voided_at' => Carbon::now(),
        ]);

        return response()->json([
            'message' => 'Sale voided and saved.',
            'voided_sale' => $voidedSale
        ]);
    }

    public function recall(PosVoidedSale $voidedSale)
    {
        if ($voidedSale->recalled) {
            return response()->json(['message' => 'This sale has already been recalled.'], 422);
        }

        $voidedSale->update([
            'recalled' => true,
            'recalled_at' => Carbon::now(),
        ]);

        return response()->json([
            'message' => 'Sale recalled successfully.',
            'voided_sale' => $voidedSale
        ]);
    }
}
