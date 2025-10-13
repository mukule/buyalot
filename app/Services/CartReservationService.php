<?php

namespace App\Services;

use App\Models\CartReservation;
use App\Models\ProductVariant;
use Illuminate\Support\Facades\DB;

class CartReservationService
{
    public function availableForCart(int $productVariantId, ?int $exceptCartId = null): int
    {
        /** @var ProductVariant|null $variant */
        $variant = ProductVariant::find($productVariantId);
        if (!$variant) return 0;

        $reservedByOthers = CartReservation::query()
            ->active()
            ->where('product_variant_id', $productVariantId)
            ->when($exceptCartId, fn($q) => $q->where('cart_id', '!=', $exceptCartId))
            ->sum('quantity');

        $available = max(0, (int)$variant->stock - (int)$reservedByOthers);
        return $available;
    }

    public function reserve(int $cartId, int $productVariantId, int $quantity, int $ttlMinutes = 20): CartReservation
    {
        $expiresAt = now()->addMinutes($ttlMinutes);

        return DB::transaction(function () use ($cartId, $productVariantId, $quantity, $expiresAt) {
            $reservation = CartReservation::query()
                ->where('cart_id', $cartId)
                ->where('product_variant_id', $productVariantId)
                ->first();

            if ($quantity <= 0) {
                if ($reservation) {
                    $reservation->delete();
                }
                // Create a fresh zero-quantity reservation is unnecessary; simply return a new instance
                return new CartReservation([
                    'cart_id' => $cartId,
                    'product_variant_id' => $productVariantId,
                    'quantity' => 0,
                    'expires_at' => now(),
                ]);
            }

            if ($reservation) {
                $reservation->update([
                    'quantity' => $quantity,
                    'expires_at' => $expiresAt,
                ]);
                return $reservation;
            }

            return CartReservation::create([
                'cart_id' => $cartId,
                'product_variant_id' => $productVariantId,
                'quantity' => $quantity,
                'expires_at' => $expiresAt,
            ]);
        });
    }

    public function release(int $cartId, int $productVariantId): void
    {
        CartReservation::query()
            ->where('cart_id', $cartId)
            ->where('product_variant_id', $productVariantId)
            ->delete();
    }

    public function releaseAllForCart(int $cartId): void
    {
        CartReservation::query()->where('cart_id', $cartId)->delete();
    }
}
