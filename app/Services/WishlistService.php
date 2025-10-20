<?php

namespace App\Services;

use App\Models\Wishlist;
use App\Models\WishlistItem;
use App\Models\User;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Cookie;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Str;

class WishlistService
{
    // Cache the wishlist for the current request
    protected ?Wishlist $wishlist = null;

  
    public function getWishlist(Request $request): Wishlist
{
    if ($this->wishlist) {
        return $this->wishlist;
    }

    // --- For authenticated users ---
    if (Auth::check()) {
        $this->wishlist = Wishlist::firstOrCreate(['user_id' => Auth::id()]);
        Log::info('Wishlist retrieved or created for authenticated user.', [
            'user_id' => Auth::id(),
            'wishlist_id' => $this->wishlist->id,
        ]);
        return $this->wishlist;
    }

    // --- For guests ---
    $token = $request->cookie('wishlist_token');

    if ($token) {
        Log::info('Found existing wishlist token in cookie.', ['token' => $token]);
    } else {
        $token = Str::uuid()->toString();

        Cookie::queue(
            Cookie::make(
                'wishlist_token',
                $token,
                60 * 24 * 30, // 30 days
                '/',
                null,
                false,
                false,
                false 
            )
        );

        Log::info('Created new wishlist token for guest.', ['token' => $token]);
    }

    $this->wishlist = Wishlist::firstOrCreate(['wishlist_token' => $token]);

    Log::info('Wishlist retrieved or created for guest user.', [
        'token' => $token,
        'wishlist_id' => $this->wishlist->id,
    ]);

    return $this->wishlist;
}

public function findWishlist(Request $request): ?Wishlist
{
    if (Auth::check()) {
        return Wishlist::where('user_id', Auth::id())->first();
    }

    $token = $request->cookie('wishlist_token');

    if (!$token) {
        Log::info('❌ No wishlist token found in cookie.');
        return null;
    }

    $wishlist = Wishlist::where('wishlist_token', $token)->first();

    Log::info('✅ Wishlist retrieved via token (read-only).', [
        'token' => $token,
        'wishlist_id' => $wishlist?->id,
    ]);

    return $wishlist;
}



public function toggle(Request $request, int $variantId): bool
{
    $wishlist = $this->getWishlist($request);

    if (!$wishlist) {
        return false;
    }

    $item = $wishlist->items()->where('product_variant_id', $variantId)->first();

    if ($item) {
        $item->delete();
        return false; 
    }

    $wishlist->items()->create([
        'product_variant_id' => $variantId,
    ]);

    return true; 
}



    public function getWishlistVariantIds(Request $request): array
    {
        $wishlist = $this->getWishlist($request);

        return WishlistItem::where('wishlist_id', $wishlist->id)
            ->pluck('product_variant_id')
            ->toArray();
    }

   
    public function getWishlistCount(Request $request): int
    {
        $wishlist = $this->getWishlist($request);

        return WishlistItem::where('wishlist_id', $wishlist->id)->count();
    }

   
    public function mergeGuestWishlist(Request $request, User $user): void
    {
        $guestToken = $request->cookie('wishlist_token');
        if (!$guestToken) return;

        $guestWishlist = Wishlist::where('wishlist_token', $guestToken)->first();
        if (!$guestWishlist) return;

        $userWishlist = Wishlist::firstOrCreate(['user_id' => $user->id]);

        $guestItems = WishlistItem::where('wishlist_id', $guestWishlist->id)->get();
        foreach ($guestItems as $item) {
            WishlistItem::firstOrCreate([
                'wishlist_id' => $userWishlist->id,
                'product_variant_id' => $item->product_variant_id,
            ]);
        }

       
        $guestWishlist->delete();
        Cookie::queue(Cookie::forget('wishlist_token'));
    }
}
