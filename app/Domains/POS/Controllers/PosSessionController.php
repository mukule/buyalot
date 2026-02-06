<?php

namespace App\Domains\POS\Controllers;

use App\Http\Controllers\Controller;
use App\Models\POS\PosRegister;
use App\Models\POS\PosSession;
use App\Models\POS\PosSetting;
use Carbon\Carbon;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

class PosSessionController extends Controller
{
    /**
     * Get current user's open session and POS settings (read-only for UI).
     */
    public function current(Request $request): JsonResponse
    {
        $session = PosSession::where('user_id', $request->user()->id)
            ->where('status', 'open')
            ->with(['register.warehouse'])
            ->first();

        $settings = PosSetting::getSettings();

        return response()->json([
            'session' => $session,
            'settings' => $settings,
        ]);
    }

    /**
     * Open a POS session for a terminal (register). JSON only for API.
     */
    public function open(Request $request): JsonResponse
    {
        $request->validate([
            'pos_register_id' => 'required|exists:pos_registers,id',
            'opening_balance' => 'required|numeric|min:0',
        ]);

        $register = PosRegister::findOrFail($request->pos_register_id);

        if ($register->activeSession) {
            return response()->json(['message' => 'Register is already in use.'], 422);
        }

        $session = PosSession::create([
            'pos_register_id' => $register->id,
            'user_id' => $request->user()->id,
            'opened_at' => Carbon::now(),
            'opening_balance' => $request->opening_balance,
            'status' => 'open',
        ]);

        return response()->json([
            'message' => 'Session opened.',
            'session' => $session->load('register'),
        ]);
    }

    /**
     * Close a POS session. JSON only for API.
     */
    public function close(Request $request, PosSession $session): JsonResponse
    {
        $request->validate([
            'closing_balance' => 'required|numeric|min:0',
            'notes' => 'nullable|string',
        ]);

        if ($session->user_id !== $request->user()->id) {
            return response()->json(['message' => 'You can only close your own session.'], 403);
        }

        if ($session->status !== 'open') {
            return response()->json(['message' => 'Session is already closed.'], 422);
        }

        $session->update([
            'closed_at' => Carbon::now(),
            'closing_balance' => $request->closing_balance,
            'notes' => $request->notes,
            'status' => 'closed',
        ]);

        return response()->json([
            'message' => 'Session closed.',
            'session' => $session->fresh(),
        ]);
    }
}
