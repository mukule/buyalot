<?php

namespace App\Http\Controllers\POS;

use App\Http\Controllers\Controller;
use App\Models\POS\PosSession;
use App\Models\POS\PosUnallocatedPayment;
use Illuminate\Http\Request;

class PosUnallocatedPaymentController extends Controller
{
    public function index(Request $request)
    {
        $request->validate([
            'customer_id' => 'required|exists:customers,id',
            'pos_session_id' => 'nullable|exists:pos_sessions,id',
        ]);

        $user = $request->user() ?? auth()->user();

        $query = PosUnallocatedPayment::where('customer_id', $request->customer_id)
            ->where('status', 'active')
            ->whereColumn('used_amount', '<', 'amount');

        if ($request->pos_session_id) {
            PosSession::forUser($user)->findOrFail($request->pos_session_id);
        }

        $query->whereHas('session', function ($q) use ($user) {
            $q->forUser($user);
        });

        return response()->json($query->latest()->get());
    }

    public function store(Request $request)
    {
        $request->validate([
            'customer_id' => 'required|exists:customers,id',
            'pos_session_id' => 'required|exists:pos_sessions,id',
            'amount' => 'required|numeric|min:0.01',
            'payment_method' => 'required|string',
            'reference' => 'nullable|string',
            'notes' => 'nullable|string',
        ]);

        $user = $request->user() ?? auth()->user();
        $session = PosSession::forUser($user)->findOrFail($request->pos_session_id);

        $payment = PosUnallocatedPayment::create([
            'customer_id' => $request->customer_id,
            'pos_session_id' => $session->id,
            'user_id' => $user->id,
            'amount' => $request->amount,
            'payment_method' => $request->payment_method,
            'reference' => $request->reference,
            'notes' => $request->notes,
            'status' => 'active',
        ]);

        if ($request->payment_method === 'cash') {
            $session->increment('cash_sales_total', $request->amount);
        } elseif ($request->payment_method === 'mpesa') {
            $session->increment('mpesa_sales_total', $request->amount);
        } else {
            $session->increment('other_sales_total', $request->amount);
        }

        return response()->json([
            'message' => 'Unallocated payment recorded.',
            'payment' => $payment,
        ]);
    }
}
