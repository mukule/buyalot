<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use App\Models\Wishlist;
use Illuminate\Support\Facades\Auth;
use Vinkla\Hashids\Facades\Hashids;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Str;

class WishlistController extends Controller
{
    /**
     * Display the wishlist (user or guest).
     */
    public function index(Request $request)
    {
        $userId = Auth::id();
        $token  = $request->cookie('wishlist_token');

        $wishlist = Wishlist::with('productVariant.product')
            ->forOwner($userId, $token)
            ->get();

        return inertia('Wishlist/Index', [
            'wishlist' => $wishlist
        ]);
    }

    /**
     * Add or remove an item from the wishlist.
     */
    public function store(Request $request)
    {
        Log::info('Wishlist store request received', [
            'user_id' => Auth::id(),
            'payload' => $request->all(),
        ]);

        $userId = Auth::id();
        $token  = $request->cookie('wishlist_token') ?? Str::uuid()->toString();

        if (!$request->cookie('wishlist_token') && !$userId) {
            // assign cookie only for guests
            cookie()->queue('wishlist_token', $token, 60 * 24 * 30); // 30 days
        }

        // --------------------------
        // 1. Resolve product variant
        // --------------------------
        $variantId = null;

        if ($request->filled('variant_hashid')) {
            $request->validate([
                'variant_hashid' => ['required', 'string'],
            ]);

            $decoded = Hashids::decode($request->input('variant_hashid'));

            if (count($decoded) !== 1) {
                Log::warning('Invalid variant hashid', [
                    'hashid' => $request->input('variant_hashid'),
                ]);
                return back()->with('error', 'Invalid product.');
            }

            $variantId = $decoded[0];
        } elseif ($request->filled('product_variant_id')) {
            $request->validate([
                'product_variant_id' => ['required', 'integer', 'exists:product_variants,id'],
            ]);

            $variantId = (int) $request->input('product_variant_id');
        } else {
            Log::warning('No variant identifier found in request', [
                'payload' => $request->all(),
            ]);
            return back()->with('error', 'No product provided.');
        }

        // --------------------------
        // 2. Check if already in wishlist
        // --------------------------
        $wishlist = Wishlist::forOwner($userId, $token)
            ->where('product_variant_id', $variantId)
            ->first();

        if ($wishlist) {
            // Remove if already exists
            $wishlist->delete();

            Log::info('Variant removed from wishlist', [
                'user_id'    => $userId,
                'token'      => $token,
                'variant_id' => $variantId,
            ]);

            return back()->with('success', 'Removed from your wishlist.');
        }

        // --------------------------
        // 3. Add to wishlist
        // --------------------------
        $wishlist = Wishlist::create([
            'user_id'            => $userId,
            'wishlist_token'     => $userId ? null : $token,
            'product_variant_id' => $variantId,
        ]);

        Log::info('Variant added to wishlist successfully', [
            'user_id'     => $userId,
            'token'       => $userId ? null : $token,
            'variant_id'  => $variantId,
            'wishlist_id' => $wishlist->id,
        ]);

        return back()->with('success', 'Added to your wishlist.');
    }
}
