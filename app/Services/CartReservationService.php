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

        // Log::info('Cart check: authenticated user', [
        //     'user_id' => Auth::id()
        // ]);

        // Logged-in user cart
        $cart = Cart::firstOrCreate(['user_id' => Auth::id()]);

        // Log::info('Using authenticated cart', [
        //     'cart_id' => $cart->id,
        //     'user_id' => $cart->user_id
        // ]);

        // Check if guest cart exists via cookie
        if ($guestToken = $request->cookie('cart_token')) {

            $guestCart = Cart::where('cart_token', $guestToken)->first();

            // Log::info('Guest cart detected for logged-in user', [
            //     'guest_token'   => $guestToken,
            //     'guest_cart_id' => optional($guestCart)->id
            // ]);

            if ($guestCart && $guestCart->id !== $cart->id) {

                // Log::info('Merging guest cart into user cart', [
                //     'user_cart_id'  => $cart->id,
                //     'guest_cart_id' => $guestCart->id,
                // ]);

                DB::transaction(function () use ($cart, $guestCart) {

                    foreach ($guestCart->items as $item) {

                        // Check if this item was already merged (prevents duplication)
                        $existing = $cart->items()
                            ->where('product_variant_id', $item->product_variant_id)
                            ->first();

                        if ($existing) {

                            // Log::warning('Skipping duplicate merge of item (already exists)', [
                            //     'variant_id' => $item->product_variant_id,
                            //     'existing_qty' => $existing->quantity,
                            //     'guest_qty' => $item->quantity,
                            // ]);

                            // If you ever want to increment instead, change logic here.
                            continue;
                        }

                        // Log::info('Creating new cart item during merge', [
                        //     'variant_id' => $item->product_variant_id,
                        //     'guest_qty'  => $item->quantity,
                        //     'user_cart_id' => $cart->id,
                        // ]);

                        $cart->items()->create([
                            'product_variant_id' => $item->product_variant_id,
                            'quantity'           => $item->quantity,
                            'unit_price'         => $item->unit_price,
                            'discount_amount'    => $item->discount_amount,
                            'total_price'        => ($item->unit_price - $item->discount_amount) * $item->quantity,
                            'product_id'         => $item->product_id,
                            'seller_id'          => $item->seller_id,
                        ]);

                        // Log::info('Item created in user cart after merge', [
                        //     'variant_id' => $item->product_variant_id,
                        //     'final_qty'  => $item->quantity,
                        // ]);
                    }

                    // Log::info('Merge complete — deleting guest cart', [
                    //     'guest_cart_id' => $guestCart->id,
                    // ]);

                    $guestCart->items()->delete();
                    $guestCart->delete();
                });

                Cookie::queue(Cookie::forget('cart_token'));

                // Log::info('Guest cart cookie removed after merge');
            }
        }

    } else {
        // Handle guest cart
        $token = $request->cookie('cart_token') ?? Str::uuid()->toString();

        Log::info('Guest cart access', [
            'token' => $token,
            'has_cookie' => (bool) $request->cookie('cart_token'),
        ]);

        $cart = Cart::firstOrCreate(['cart_token' => $token]);

        Log::info('Using guest cart', [
            'cart_id'    => $cart->id,
            'cart_token' => $token,
        ]);

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

            Log::info('Guest cart cookie created', [
                'token' => $token,
            ]);
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
