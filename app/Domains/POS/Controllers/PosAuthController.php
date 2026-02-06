<?php

namespace App\Domains\POS\Controllers;

use App\Http\Controllers\Controller;
use App\Models\POS\PosRegister;
use App\Models\POS\PosSession;
use App\Models\User;
use Carbon\Carbon;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Validation\ValidationException;

class PosAuthController extends Controller
{
    /**
     * POS terminal login: username (email or phone) + 4-digit PIN.
     * Optional terminal_id (pos_register_id) opens or attaches to a session.
     * Returns user info + token for all POS API calls.
     */
    public function login(Request $request): JsonResponse
    {
        $request->validate([
            'username' => 'required|string',
            'pin' => 'required|string|size:4|regex:/^[0-9]+$/',
            'terminal_id' => 'nullable|exists:pos_registers,id',
            'opening_balance' => 'nullable|numeric|min:0',
        ]);

        $user = $this->findUserByUsername($request->username);

        if (! $user) {
            throw ValidationException::withMessages(['username' => ['The provided credentials are incorrect.']]);
        }

        if ($user->pos_pin !== $request->pin) {
            throw ValidationException::withMessages(['pin' => ['Invalid PIN.']]);
        }

        $session = null;
        if ($request->terminal_id) {
            $register = PosRegister::findOrFail($request->terminal_id);
            if ($register->activeSession) {
                $existing = $register->activeSession;
                if ((int) $existing->user_id === (int) $user->id) {
                    $session = $existing;
                } else {
                    throw ValidationException::withMessages(['terminal_id' => ['This terminal is already in use by another user.']]);
                }
            } else {
                $session = PosSession::create([
                    'pos_register_id' => $register->id,
                    'user_id' => $user->id,
                    'opened_at' => Carbon::now(),
                    'opening_balance' => $request->opening_balance ?? 0,
                    'status' => 'open',
                ]);
            }
        }

        $user->tokens()->where('name', 'pos-terminal')->delete();
        $token = $user->createToken('pos-terminal', ['pos'])->plainTextToken;

        return response()->json([
            'message' => 'Logged in successfully.',
            'user' => [
                'id' => $user->id,
                'name' => $user->name,
                'email' => $user->email,
            ],
            'token' => $token,
            'token_type' => 'Bearer',
            'terminal' => $session ? [
                'session_id' => $session->id,
                'register_id' => $session->pos_register_id,
                'register_name' => $session->register->name ?? null,
            ] : null,
        ]);
    }

    /**
     * Logout: revoke current POS token.
     */
    public function logout(Request $request): JsonResponse
    {
        $request->user()->currentAccessToken()->delete();

        return response()->json(['message' => 'Logged out successfully.']);
    }

    /**
     * Resolve user by username (email or phone).
     */
    protected function findUserByUsername(string $username): ?User
    {
        if (str_contains($username, '@')) {
            return User::where('email', $username)->first();
        }

        return User::where('phone', $username)->first();
    }
}
