<?php

namespace App\Http\Controllers;

use App\Models\Payment\Discount;
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

        /** @var Discount|null $discount */
        $discount = Discount::query()->active()->byCode($data['coupon_code'])->first();
        if (!$discount) {
            return response()->json([
                'success' => false,
                'message' => 'Coupon not found or inactive',
                'data' => [ 'amount' => 0, 'free_shipping' => false ],
            ], 404);
        }

        // Optionally deny if customer exceeded usage limit
        $customerId = $data['customer_id'] ?? null;
        if ($customerId && !$discount->canBeUsedByCustomer((int)$customerId)) {
            return response()->json([
                'success' => false,
                'message' => 'Coupon usage limit reached for this customer',
                'data' => [ 'amount' => 0, 'free_shipping' => false ],
            ], 422);
        }

        $subtotal = isset($data['subtotal'])
            ? (float)$data['subtotal']
            : collect($data['items'])->sum(fn($it) => (float)$it['unit_price'] * (int)$it['quantity']);

        $amount = (float)$discount->calculateDiscount($subtotal, $data['items'], [
            'order_subtotal' => $subtotal,
            'customer_id' => $customerId,
        ]);

        $conditions = $discount->conditions ?? [];
        $appliesTo = $conditions['applies_to'] ?? null;
        $shippingAmount = (float)($data['shipping_amount'] ?? 0);
        $freeShipping = false;
        $shippingSavings = 0.0;
        if ($discount->type === 'free_shipping' || $appliesTo === 'shipping') {
            $freeShipping = true;
            $shippingSavings = $shippingAmount;
        }

        return response()->json([
            'success' => true,
            'message' => 'Coupon evaluated',
            'data' => [
                'amount' => round($amount + $shippingSavings, 2),
                'coupon_amount' => round($amount, 2),
                'shipping_savings' => round($shippingSavings, 2),
                'free_shipping' => $freeShipping,
                'code' => $discount->code,
                'type' => $discount->type,
                'name' => $discount->name,
            ],
        ]);
    }
}
