<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use App\Models\Wishlist;
use App\Models\Product;
use Illuminate\Support\Facades\Auth;
use Vinkla\Hashids\Facades\Hashids;
 use Illuminate\Support\Facades\Log;

class WishlistController extends Controller
{
    /**
     * Display the authenticated user's wishlist.
     */
    public function index()
    {
        $wishlist = Wishlist::with('product')
            ->where('user_id', Auth::id())
            ->get();

        return inertia('Wishlist/Index', [
            'wishlist' => $wishlist
        ]);
    }

   
   
public function store(Request $request)
{
    Log::info('Wishlist store request received', [
        'user_id' => Auth::id(),
        'payload' => $request->all(),
    ]);

    $userId = Auth::id();
    $productId = null;

    if ($request->filled('product_hashid')) {
        $request->validate([
            'product_hashid' => ['required', 'string'],
        ]);

        $decoded = Hashids::decode($request->input('product_hashid'));
        Log::info('Decoded product hashid', [
            'hashid' => $request->input('product_hashid'),
            'decoded' => $decoded,
        ]);

        if (count($decoded) !== 1) {
            Log::warning('Invalid product hashid', [
                'hashid' => $request->input('product_hashid'),
            ]);
            return back()->with('error', 'Invalid product.');
        }

        $productId = $decoded[0];
    } elseif ($request->filled('product')) {
        $request->validate([
            'product' => ['required', 'integer', 'exists:products,id'],
        ]);

        $productId = (int) $request->input('product');
        Log::info('Using plain product id', ['product_id' => $productId]);
    } else {
        Log::warning('No product or product_hashid found in request', [
            'payload' => $request->all(),
        ]);
        return back()->with('error', 'No product provided.');
    }

    // Check if already in wishlist
    $wishlist = Wishlist::where('user_id', $userId)
        ->where('product_id', $productId)
        ->first();

    if ($wishlist) {
        // Remove if already exists
        $wishlist->delete();

        Log::info('Product removed from wishlist', [
            'user_id' => $userId,
            'product_id' => $productId,
        ]);

        return back()->with('success', 'Product removed from your wishlist.');
    }

   
    $wishlist = Wishlist::create([
        'user_id' => $userId,
        'product_id' => $productId,
    ]);

    Log::info('Product added to wishlist successfully', [
        'user_id' => $userId,
        'product_id' => $productId,
        'wishlist_id' => $wishlist->id,
    ]);

    return back()->with('success', 'Product added to your wishlist.');
}


}
