<?php

namespace App\Services;
use Illuminate\Http\Request;
use App\Models\CartReservation;
use App\Models\ProductVariant;
use Illuminate\Support\Facades\Cookie;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Auth;
use App\Models\Cart;
use Illuminate\Support\Str;


class CartReservationService
{

    public function getCart(Request $request): Cart
    {
        if (Auth::check()) {
            // Logged-in user cart
            $cart = Cart::firstOrCreate(['user_id' => Auth::id()]);

            // Check if guest cart exists via cookie
            if ($guestToken = $request->cookie('cart_token')) {
                $guestCart = Cart::where('cart_token', $guestToken)->first();

                if ($guestCart && $guestCart->id !== $cart->id) {
                    DB::transaction(function () use ($cart, $guestCart) {
                        foreach ($guestCart->items as $item) {
                            $cart->items()->updateOrCreate(
                                ['product_variant_id' => $item->product_variant_id],
                                [
                                    'quantity'        => DB::raw('quantity + ' . $item->quantity),
                                    'unit_price'      => $item->unit_price,
                                    'discount_amount' => $item->discount_amount,
                                    'total_price'     => ($item->unit_price - $item->discount_amount) * $item->quantity,
                                    'product_id'      => $item->product_id,
                                    'seller_id'       => $item->seller_id,
                                ]
                            );
                        }

                        // Delete guest cart and its items after merging
                        $guestCart->items()->delete();
                        $guestCart->delete();
                    });

                    // Remove guest token after merge
                    Cookie::queue(Cookie::forget('cart_token'));
                }
            }
        } else {
            // Handle guest cart
            $token = $request->cookie('cart_token') ?? Str::uuid()->toString();
            $cart  = Cart::firstOrCreate(['cart_token' => $token]);

            if (!$request->cookie('cart_token')) {
                Cookie::queue(
                    Cookie::make(
                        'cart_token',
                        $token,
                        60 * 24 * 30, // 30 days
                        '/',
                        null,
                        false,
                        false,
                        false
                    )
                );
            }
        }

        return $cart;
    }



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
