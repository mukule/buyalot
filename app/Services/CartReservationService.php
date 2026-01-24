<?php

namespace App\Services;
use App\Models\Cart\Cart;
use App\Models\Cart\CartReservation;
use App\Models\Products\ProductVariant;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Cookie;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Str;


class CartReservationService
{




public function getCart(Request $request): Cart
{
    if (Auth::check()) {

        $cart = Cart::firstOrCreate(['user_id' => Auth::id()]);

        if ($guestToken = $request->cookie('cart_token')) {

            $guestCart = Cart::where('cart_token', $guestToken)->first();

            if ($guestCart && $guestCart->id !== $cart->id) {

                DB::transaction(function () use ($cart, $guestCart) {

                    foreach ($guestCart->items as $item) {

                        // Prevent duplicate variant merge
                        $existing = $cart->items()
                            ->where('product_variant_id', $item->product_variant_id)
                            ->first();

                        if ($existing) {
                            continue;
                        }

                        // 🟢 Preserve the same pricing semantics as add-to-cart
                        $cart->items()->create([
                            'product_variant_id'  => $item->product_variant_id,
                            'quantity'            => $item->quantity,

                            'marked_price'        => $item->marked_price,
                            'unit_price'          => $item->unit_price,
                            'discount_amount'     => $item->discount_amount,
                            'discount_percentage' => $item->discount_percentage,

                            // total = final payable price × qty
                            'total_price'         => $item->unit_price * $item->quantity,

                            'product_id'          => $item->product_id,
                            'seller_id'           => $item->seller_id,
                        ]);
                    }

                    // Remove guest cart after merge
                    $guestCart->items()->delete();
                    $guestCart->delete();
                });

                Cookie::queue(Cookie::forget('cart_token'));
            }
        }

    } else {

        // Guest cart handling stays unchanged
        $token = $request->cookie('cart_token') ?? Str::uuid()->toString();

        // Log::info('Guest cart access', [
        //     'token' => $token,
        //     'has_cookie' => (bool) $request->cookie('cart_token'),
        // ]);

        $cart = Cart::firstOrCreate(['cart_token' => $token]);

        // Log::info('Using guest cart', [
        //     'cart_id'    => $cart->id,
        //     'cart_token' => $token,
        // ]);

        if (!$request->cookie('cart_token')) {
            Cookie::queue(Cookie::make(
                'cart_token',
                $token,
                60 * 24 * 30,
                '/',
                null,
                false,
                false,
                false
            ));
        }
    }

    return $cart;
}




    public function availableForCart(int $productVariantId, ?int $exceptCartId = null): int
    {
        /** @var ProductVariant|null $variant */
        $variant = ProductVariant::find($productVariantId);
        if (!$variant) return 0;

        // Since we now decrement stock immediately upon reservation,
        // $variant->stock already reflects available stock (excluding all reservations).
        return (int)$variant->stock;
    }

    public function reserve(int $cartId, int $productVariantId, int $quantity, int $ttlSeconds = 60): CartReservation
    {
        $expiresAt = now()->addSeconds($ttlSeconds);

        return DB::transaction(function () use ($cartId, $productVariantId, $quantity, $expiresAt) {
            $variant = ProductVariant::lockForUpdate()->findOrFail($productVariantId);
            $reservation = CartReservation::query()
                ->where('cart_id', $cartId)
                ->where('product_variant_id', $productVariantId)
                ->first();

            if ($quantity <= 0) {
                if ($reservation) {
                    $variant->increment('stock', $reservation->quantity);
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
                $diff = $quantity - $reservation->quantity;
                if ($diff > 0) {
                    $variant->decrement('stock', $diff);
                } elseif ($diff < 0) {
                    $variant->increment('stock', abs($diff));
                }

                $reservation->update([
                    'quantity' => $quantity,
                    'expires_at' => $expiresAt,
                ]);
                return $reservation;
            }

            $variant->decrement('stock', $quantity);

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
        DB::transaction(function () use ($cartId, $productVariantId) {
            $reservation = CartReservation::query()
                ->where('cart_id', $cartId)
                ->where('product_variant_id', $productVariantId)
                ->first();

            if ($reservation) {
                ProductVariant::where('id', $productVariantId)->increment('stock', $reservation->quantity);
                $reservation->delete();
            }
        });
    }

    public function releaseAllForCart(int $cartId, bool $returnStock = true): void
    {
        DB::transaction(function () use ($cartId, $returnStock) {
            $reservations = CartReservation::where('cart_id', $cartId)->get();
            foreach ($reservations as $res) {
                if ($returnStock) {
                    ProductVariant::where('id', $res->product_variant_id)->increment('stock', $res->quantity);
                }
                $res->delete();
            }
        });
    }
}
