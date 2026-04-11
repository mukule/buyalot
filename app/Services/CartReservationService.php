<?php

namespace App\Services;
use App\Models\Cart\Cart;
use App\Models\Cart\CartReservation;
use Carbon\Carbon;
use App\Models\Products\ProductVariant;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Cookie;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Str;


class CartReservationService
{
    /** First payment attempt: 3 minutes */
    public const TTL_INITIAL = 180;

    /** Retry after a failed/expired attempt: 10 minutes */
    public const TTL_RETRY = 600;




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




    /**
     * Returns true when the cart already has at least one active (not-yet-expired) reservation.
     * Used to detect a payment retry so we can extend the TTL.
     */
    public function hasActiveReservations(int $cartId): bool
    {
        return CartReservation::where('cart_id', $cartId)
            ->where('expires_at', '>', now())
            ->exists();
    }

    public function availableForCart(int $productVariantId, ?int $exceptCartId = null): int
    {
        /** @var ProductVariant|null $variant */
        $variant = ProductVariant::find($productVariantId);
        if (!$variant) return 0;

        // Stock is decremented immediately when a reservation is created.
        // $variant->stock therefore already excludes ALL reservations.
        //
        // When checking availability for a specific cart we must add back
        // whatever that cart itself has reserved — both active reservations
        // (stock "owned" by this cart) and expired-but-not-yet-cleaned ones
        // (scheduler hasn't restored the stock yet).
        // Without this, a cart that already holds reservations would always
        // appear to have exceeded available stock when the customer tries to pay.
        $available = (int) $variant->stock;

        if ($exceptCartId) {
            $ownReservedQty = CartReservation::where('cart_id', $exceptCartId)
                ->where('product_variant_id', $productVariantId)
                ->sum('quantity');   // active + expired, no date filter

            $available += (int) $ownReservedQty;
        }

        return $available;
    }

    /**
     * Ensure every item in the cart has an active reservation.
     *
     * - If all items already have live reservations, returns the expiry unchanged.
     * - If any reservation is missing or has expired AND stock is still available,
     *   it (re-)creates reservations with TTL_INITIAL (3 minutes).
     * - Returns ['ok' => true, 'expires_at' => Carbon] on success.
     * - Returns ['ok' => false, 'message' => string] when stock is gone.
     */
    public function ensureReservedForCheckout(Cart $cart): array
    {
        $cart->loadMissing('items');

        if ($cart->items->isEmpty()) {
            return ['ok' => false, 'message' => 'Your cart is empty.'];
        }

        // Gather live reservations indexed by product_variant_id
        $liveReservations = CartReservation::where('cart_id', $cart->id)
            ->where('expires_at', '>', now())
            ->get()
            ->keyBy('product_variant_id');

        $allLive = $cart->items->every(
            fn($item) => isset($liveReservations[$item->product_variant_id])
        );

        if ($allLive) {
            // Everything is reserved — return the earliest expiry so the
            // frontend timer is accurate.
            $expiresAt = $liveReservations->min('expires_at');
            return ['ok' => true, 'expires_at' => $expiresAt];
        }

        // Some reservations are missing or expired. Verify stock is still
        // available before re-reserving (uses the corrected availableForCart
        // which adds back own expired qty).
        foreach ($cart->items as $item) {
            $available = $this->availableForCart($item->product_variant_id, $cart->id);
            if ($item->quantity > $available) {
                $productName = $item->productVariant?->product?->name ?? 'An item';
                return [
                    'ok'      => false,
                    'message' => "{$productName} no longer has enough stock. Please update your cart.",
                ];
            }
        }

        // Stock is available — re-reserve every item at the fresh initial TTL.
        $earliestExpiry = null;
        foreach ($cart->items as $item) {
            $reservation = $this->reserve(
                $cart->id,
                $item->product_variant_id,
                $item->quantity,
                self::TTL_INITIAL
            );
            if ($earliestExpiry === null || $reservation->expires_at < $earliestExpiry) {
                $earliestExpiry = $reservation->expires_at;
            }
        }

        return ['ok' => true, 'expires_at' => $earliestExpiry, 'refreshed' => true];
    }

    public function reserve(int $cartId, int $productVariantId, int $quantity, int $ttlSeconds = self::TTL_INITIAL): CartReservation
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
