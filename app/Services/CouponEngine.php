<?php

namespace App\Services;

use App\Models\Payment\Discount;

/**
 * CouponEngine centralizes coupon validation, calculation, and usage tracking.
 * It wraps the Discount model logic to provide a stable API for controllers/services.
 */
class CouponEngine
{
    /**
     * Resolve an active coupon by code.
     */
    public function resolve(string $code): ?Discount
    {
        return Discount::query()->active()->byCode($code)->first();
    }

    /**
     * Check if a coupon can be used by a specific customer.
     */
    public function canUse(?Discount $coupon, ?int $customerId): bool
    {
        if (!$coupon) return false;
        if (!$customerId) return $coupon->canBeUsed();
        return $coupon->canBeUsedByCustomer($customerId);
    }

    /**
     * Calculate the discount amounts for a coupon.
     * Returns an array with keys: amount, coupon_amount, shipping_savings, free_shipping
     *
     * @param Discount $coupon
     * @param float $orderSubtotal
     * @param array $items Each item: product_id?, product_variant_id, quantity, unit_price, brand_id?, category_ids?, seller_id?
     * @param array $context Context can include: customer_id, shipping_amount, etc.
     */
    public function calculate(Discount $coupon, float $orderSubtotal, array $items, array $context = []): array
    {
        $couponAmount = (float) $coupon->calculateDiscount($orderSubtotal, $items, [
            'order_subtotal' => $orderSubtotal,
            'customer_id' => $context['customer_id'] ?? null,
        ]);

        $conditions = $coupon->conditions ?? [];
        $appliesTo = $conditions['applies_to'] ?? null;
        $shippingAmount = (float)($context['shipping_amount'] ?? 0);
        $freeShipping = ($coupon->type === 'free_shipping' || $appliesTo === 'shipping');
        $shippingSavings = $freeShipping ? $shippingAmount : 0.0;

        return [
            'amount' => round($couponAmount + $shippingSavings, 2),
            'coupon_amount' => round($couponAmount, 2),
            'shipping_savings' => round($shippingSavings, 2),
            'free_shipping' => $freeShipping,
        ];
    }

    /**
     * High-level validate method used by API endpoints. Returns [success, message, data].
     */
    public function validateCode(string $code, array $items, array $context = []): array
    {
        $coupon = $this->resolve($code);
        if (!$coupon) {
            return [
                'success' => false,
                'message' => 'Coupon not found or inactive',
                'data' => ['amount' => 0, 'free_shipping' => false],
                'status' => 404,
            ];
        }

        $customerId = isset($context['customer_id']) ? (int)$context['customer_id'] : null;
        if ($customerId && !$this->canUse($coupon, $customerId)) {
            return [
                'success' => false,
                'message' => 'Coupon usage limit reached for this customer',
                'data' => ['amount' => 0, 'free_shipping' => false],
                'status' => 422,
            ];
        }

        $subtotal = isset($context['subtotal'])
            ? (float)$context['subtotal']
            : collect($items)->sum(fn($it) => (float)$it['unit_price'] * (int)$it['quantity']);

        $amounts = $this->calculate($coupon, $subtotal, $items, [
            'customer_id' => $customerId,
            'shipping_amount' => (float)($context['shipping_amount'] ?? 0),
        ]);

        return [
            'success' => true,
            'message' => 'Coupon evaluated',
            'data' => array_merge($amounts, [
                'code' => $coupon->code,
                'type' => $coupon->type,
                'name' => $coupon->name,
            ]),
            'status' => 200,
        ];
    }

    /**
     * Increment coupon usage counter safely.
     */
    public function incrementUsage(?Discount $coupon): void
    {
        if (!$coupon) return;
        try {
            $coupon->incrementUsage();
        } catch (\Throwable $e) {
            logger('Failed to increment discount usage: ' . $e->getMessage());
        }
    }

    /**
     * Roll back usage counter if necessary.
     */
    public function rollbackUsage(?Discount $coupon): void
    {
        if (!$coupon) return;
        try {
            $coupon->decrementUsage();
        } catch (\Throwable $e) {
            logger('Failed to decrement discount usage: ' . $e->getMessage());
        }
    }
}
