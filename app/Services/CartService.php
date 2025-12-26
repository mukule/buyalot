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

   

    public function mergeGuestCart(Request $request, User $user, DiscountService $discountService): void
{
    $guestToken = $request->cookie('cart_token');
    if (!$guestToken) return;

    $guestCart = Cart::where('cart_token', $guestToken)
        ->with('items.productVariant')
        ->first();

    if (!$guestCart || $guestCart->items->isEmpty()) return;

    // Get or create the authenticated user's cart
    $userCart = Cart::firstOrCreate(['user_id' => $user->id]);
    $userCart->load('items'); // preload items for comparison

    // Index existing user cart items by variant ID
    $existingItems = $userCart->items->keyBy('product_variant_id');

    // Get discount and price data for all guest cart variants
    $variantIds = $guestCart->items->pluck('product_variant_id')->all();
    $discountData = $discountService->calculateDiscounts($variantIds);
    $priceData = collect($discountData)->keyBy('product_variant_id');

    foreach ($guestCart->items as $item) {
        $variantPriceInfo = $priceData[$item->product_variant_id] ?? [];

        $unitPrice = $variantPriceInfo['final_price'] ?? $item->unit_price ?? 0;
        $discountAmount = $variantPriceInfo['total_discount'] ?? 0;
        $markedPrice = $variantPriceInfo['marked_price'] ?? $item->marked_price ?? 0;
        $quantity = $item->quantity;

        if (isset($existingItems[$item->product_variant_id])) {
            // Merge quantities correctly
            $existing = $existingItems[$item->product_variant_id];
            $newQuantity = $existing->quantity + $quantity;

            $existing->update([
                'quantity'        => $newQuantity,
                'unit_price'      => $unitPrice,
                'marked_price'    => $markedPrice,
                'discount_amount' => $discountAmount,
                'total_price'     => $unitPrice * $newQuantity,
            ]);
        } else {
            // Add new item to user cart
            $userCart->items()->create([
                'product_variant_id' => $item->product_variant_id,
                'quantity'           => $quantity,
                'unit_price'         => $unitPrice,
                'marked_price'       => $markedPrice,
                'discount_amount'    => $discountAmount,
                'total_price'        => $unitPrice * $quantity,
            ]);
        }
    }

    // Delete guest cart and clear cookie
    $guestCart->delete();
    cookie()->queue(cookie()->forget('cart_token'));
}

}
