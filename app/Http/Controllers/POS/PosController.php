<?php

namespace App\Http\Controllers\POS;

use App\Http\Controllers\Controller;
use App\Models\POS\PosRegister;
use App\Models\POS\PosSession;
use App\Models\POS\PosSetting;
use Illuminate\Http\Request;
use Inertia\Inertia;
use Carbon\Carbon;

class PosController extends Controller
{
    public function index()
    {
        $user = auth()->user();
        $registers = PosRegister::forUser($user)->with('activeSession')->get();
        $activeSession = PosSession::forUser($user)
            ->where('user_id', $user->id)
            ->where('status', 'open')
            ->with('register')
            ->first();

        return Inertia::render('Admin/POS/Index', [
            'registers' => $registers,
            'activeSession' => $activeSession,
            'canManageSettings' => auth()->user()->can('manage-pos-settings'),
        ]);
    }

    public function openSession(Request $request)
    {
        $request->validate([
            'pos_register_id' => 'required|exists:pos_registers,id',
            'opening_balance' => 'required|numeric|min:0',
        ]);

        $register = PosRegister::forUser(auth()->user())->findOrFail($request->pos_register_id);

        if ($register->activeSession) {
            return back()->with('error', 'Register is already in use.');
        }

        $session = PosSession::create([
            'pos_register_id' => $register->id,
            'user_id' => auth()->id(),
            'opened_at' => Carbon::now(),
            'opening_balance' => $request->opening_balance,
            'status' => 'open',
        ]);

        return redirect()->route('admin.pos.show')->with('success', 'POS session opened.');
    }

    public function show(Request $request)
    {
        $user = auth()->user();
        $activeSession = PosSession::forUser($user)
            ->where('user_id', $user->id)
            ->where('status', 'open')
            ->with(['register.warehouse', 'user'])
            ->first();

        if (!$activeSession) {
            return redirect()->route('admin.pos.index')->with('error', 'No active POS session found.');
        }

        // Check if PIN was recently verified (session based)
        if (!$request->session()->get('pos_verified')) {
            return redirect()->route('admin.pos.index')->with('error', 'Please login with your PIN.');
        }

        return Inertia::render('Admin/POS/Terminal', [
            'session' => $activeSession,
            'settings' => PosSetting::getSettings(),
        ]);
    }

    public function verifyPin(Request $request)
    {
        $request->validate([
            'pin' => 'required|string|size:4',
        ]);

        $user = auth()->user();

        if ($user->pos_pin === $request->pin) {
            $request->session()->put('pos_verified', true);
            $request->session()->put('pos_verified_at', now());
            return response()->json(['success' => true]);
        }

        return response()->json(['success' => false, 'message' => 'Invalid PIN.'], 422);
    }

    public function lockTerminal(Request $request)
    {
        $request->session()->forget('pos_verified');
        $request->session()->forget('pos_verified_at');

        return redirect()->route('admin.pos.index')->with('success', 'Terminal locked.');
    }

    public function closeSession(Request $request, PosSession $session)
    {
        $request->validate([
            'closing_balance' => 'required|numeric|min:0',
            'notes' => 'nullable|string',
        ]);

        $user = auth()->user();
        if (! \App\Services\SellerContext::canAccessSession($user, $session)) {
            return back()->with('error', 'You do not have access to this session.');
        }

        $session->update([
            'closed_at' => Carbon::now(),
            'closing_balance' => $request->closing_balance,
            'notes' => $request->notes,
            'status' => 'closed',
        ]);

        return redirect()->route('admin.pos.index')->with('success', 'POS session closed.');
    }

    public function updatePin(Request $request)
    {
        $request->validate([
            'pin' => 'required|string|size:4|regex:/^[0-9]+$/',
        ]);

        $user = auth()->user();
        $user->pos_pin = $request->pin;
        $user->save();

        return back()->with('success', 'POS PIN updated successfully.');
    }

    public function verifyAdminPin(Request $request)
    {
        $request->validate([
            'pin' => 'required|string|size:4',
        ]);

        $currentUser = $request->user() ?? auth()->user();

        $admin = \App\Models\User::role(['admin', 'super-admin'])
            ->where('pos_pin', $request->pin)
            ->first();

        if ($admin) {
            return response()->json(['success' => true]);
        }

        if ($currentUser && \App\Services\SellerContext::isSeller($currentUser)) {
            $sellerIds = \App\Services\SellerContext::sellerIds($currentUser);
            $owner = \App\Models\User::where('pos_pin', $request->pin)
                ->whereHas('sellers', fn ($q) => $q->whereIn('seller_applications.id', $sellerIds)
                    ->where('seller_user.is_owner', true))
                ->first();

            if ($owner) {
                return response()->json(['success' => true]);
            }
        }

        return response()->json(['success' => false, 'message' => 'Invalid Admin PIN.'], 422);
    }
}
