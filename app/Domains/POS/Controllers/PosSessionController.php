<?php

namespace App\Domains\POS\Controllers;

use App\Http\Controllers\Controller;
use App\Models\POS\PosRegister;
use App\Models\POS\PosSession;
use App\Models\POS\PosSetting;
use App\Services\SellerContext;
use Carbon\Carbon;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

class PosSessionController extends Controller
{
    public function registers(Request $request): JsonResponse
    {
        $registers = PosRegister::forUser($request->user())
            ->with('activeSession')
            ->get();

        return response()->json(['registers' => $registers]);
    }

    public function current(Request $request): JsonResponse
    {
        $session = PosSession::forUser($request->user())
            ->where('user_id', $request->user()->id)
            ->where('status', 'open')
            ->with(['register.warehouse'])
            ->first();

        $sellerId = $session?->register?->seller_id;
        $settings = PosSetting::resolveForSeller($sellerId);

        return response()->json([
            'session' => $session,
            'settings' => $settings,
            'settings_version' => $settings->settings_version,
        ]);
    }

    /**
     * Lightweight endpoint — returns only the version number so the
     * POS frontend can decide whether to refetch full settings.
     */
    public function settingsVersion(Request $request): JsonResponse
    {
        $user = $request->user();

        $session = PosSession::forUser($user)
            ->where('user_id', $user->id)
            ->where('status', 'open')
            ->with('register')
            ->first();

        $sellerId = $session?->register?->seller_id;
        $settings = PosSetting::resolveForSeller($sellerId);

        return response()->json([
            'settings_version' => $settings->settings_version,
            'seller_id' => $sellerId,
        ]);
    }

    /**
     * Full settings payload for the POS frontend to cache.
     */
    public function settingsFull(Request $request): JsonResponse
    {
        $user = $request->user();

        $session = PosSession::forUser($user)
            ->where('user_id', $user->id)
            ->where('status', 'open')
            ->with('register')
            ->first();

        $sellerId = $session?->register?->seller_id;
        $settings = PosSetting::resolveForSeller($sellerId);

        return response()->json([
            'settings' => $settings,
            'settings_version' => $settings->settings_version,
            'seller_id' => $sellerId,
        ]);
    }

    public function open(Request $request): JsonResponse
    {
        $request->validate([
            'pos_register_id' => 'required|exists:pos_registers,id',
            'opening_balance' => 'required|numeric|min:0',
        ]);

        $register = PosRegister::forUser($request->user())
            ->findOrFail($request->pos_register_id);

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

    public function close(Request $request, PosSession $session): JsonResponse
    {
        $request->validate([
            'closing_balance' => 'required|numeric|min:0',
            'notes' => 'nullable|string',
        ]);

        $user = $request->user();
        if (! SellerContext::canAccessSession($user, $session)) {
            return response()->json(['message' => 'You do not have access to this session.'], 403);
        }

        if ((int) $session->user_id !== (int) $user->id) {
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
