<?php

namespace App\Services;

use App\Models\Cart;
use App\Models\User;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Str;

class CartService
{
    /**
     * Get or create the current cart (for guest or logged-in user).
     */
    public function getCart(Request $request): Cart
    {
        if (Auth::check()) {
            return Cart::firstOrCreate(['user_id' => Auth::id()]);
        }

        $token = $request->cookie('cart_token') ?? Str::uuid()->toString();

        $cart = Cart::firstOrCreate(['cart_token' => $token]);

        if (!$request->cookie('cart_token')) {
            cookie()->queue('cart_token', $token, 60 * 24 * 30); // 30 days
        }

        return $cart;
    }

    /**
     * Merge guest cart into the authenticated user's cart after login.
     */
    public function mergeGuestCart(Request $request, User $user): void
    {
        $guestToken = $request->cookie('cart_token');

        if (!$guestToken) {
            return;
        }

        $guestCart = Cart::where('cart_token', $guestToken)->with('items')->first();
        if (!$guestCart || $guestCart->items->isEmpty()) {
            return;
        }

        $userCart = Cart::firstOrCreate(['user_id' => $user->id]);

        foreach ($guestCart->items as $item) {
            $existing = $userCart->items()
                ->where('product_variant_id', $item->product_variant_id)
                ->first();

            if ($existing) {
                // If item exists, increase quantity
                $existing->increment('quantity', $item->quantity);
            } else {
                // Otherwise, move the item
                $userCart->items()->create($item->only([
                    'product_variant_id',
                    'quantity',
                    'regular_price',
                    'selling_price',
                    'discount',
                ]));
            }
        }

        // Delete guest cart after merging
        $guestCart->delete();

        // Remove guest cart cookie
        cookie()->queue(cookie()->forget('cart_token'));
    }
}
