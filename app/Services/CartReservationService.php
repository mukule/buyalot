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
use Illuminate\Support\Facades\Log;



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
