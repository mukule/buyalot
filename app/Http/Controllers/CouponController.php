<?php

namespace App\Http\Controllers;

use App\Services\CouponEngine;
use Illuminate\Http\Request;

class CouponController extends Controller
{
    /**
     * Validate/apply a coupon code against a set of items and optional context.
     * Request expects:
     * - coupon_code: string
     * - items: [{ product_id?, product_variant_id, quantity, unit_price }]
     * - subtotal: optional float (if not provided, computed from items)
     * - customer_id: optional int (for per-customer usage and targeting)
     * - shipping_amount: optional float (to report free-shipping savings)
     */
    public function validateCode(Request $request)
    {
        $data = $request->validate([
            'coupon_code' => 'required|string|max:50',
            'items' => 'required|array|min:1',
            'items.*.product_variant_id' => 'nullable|integer',
            'items.*.product_id' => 'nullable|integer',
            'items.*.quantity' => 'required|integer|min:1',
            'items.*.unit_price' => 'required|numeric|min:0',
            'subtotal' => 'sometimes|numeric|min:0',
            'customer_id' => 'sometimes|integer|min:1',
            'shipping_amount' => 'sometimes|numeric|min:0',
        ]);

        /** @var CouponEngine $engine */
        $engine = app(CouponEngine::class);

        $result = $engine->validateCode($data['coupon_code'], $data['items'], [
            'subtotal' => $data['subtotal'] ?? null,
            'customer_id' => $data['customer_id'] ?? null,
            'shipping_amount' => $data['shipping_amount'] ?? null,
        ]);

        return response()->json([
            'success' => $result['success'],
            'message' => $result['message'],
            'data' => $result['data'],
        ], $result['status']);
    }
}
