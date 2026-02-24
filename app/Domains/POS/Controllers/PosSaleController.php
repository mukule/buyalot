<?php

namespace App\Domains\POS\Controllers;

use App\Http\Controllers\Controller;
use App\Models\POS\PosSession;
use App\Domains\POS\Services\SaleService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

class PosSaleController extends Controller
{
    public function __construct(
        protected SaleService $saleService
    ) {}

    /**
     * Create a completed sale (order). Thin controller — logic in SaleService.
     */
    public function store(Request $request): JsonResponse
    {
        $validated = $request->validate([
            'pos_session_id' => 'required|exists:pos_sessions,id',
            'customer_id' => 'required|exists:customers,id',
            'items' => 'required|array|min:1',
            'items.*.product_variant_id' => 'required|exists:product_variants,id',
            'items.*.quantity' => 'required|integer|min:1',
            'payment_method' => 'required|string',
            'amount_paid' => 'required|numeric|min:0',
            'allocated_payment_ids' => 'nullable|array',
            'allocated_payment_ids.*' => 'exists:pos_unallocated_payments,id',
        ]);

        $user = $request->user();
        $session = PosSession::forUser($user)->findOrFail($validated['pos_session_id']);

        if ($session->status !== 'open') {
            return response()->json(['message' => 'POS session is closed.'], 422);
        }

        $order = $this->saleService->createSale(
            $session,
            (int) $validated['customer_id'],
            $validated['items'],
            $validated['payment_method'],
            (float) $validated['amount_paid'],
            $validated['allocated_payment_ids'] ?? []
        );

        $settings = \App\Models\POS\PosSetting::getSettings();

        return response()->json([
            'message' => 'Order created successfully',
            'order' => $order,
            'settings' => $settings,
            'register' => $session->register,
        ]);
    }
}
