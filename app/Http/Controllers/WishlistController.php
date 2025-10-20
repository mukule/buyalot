<?php

namespace App\Http\Controllers;

use App\Models\Wishlist;
use App\Services\WishlistService;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Storage;
use Vinkla\Hashids\Facades\Hashids;

class WishlistController extends Controller
{
    protected WishlistService $wishlistService;

    public function __construct(Request $request)
    {
        $this->wishlistService = new WishlistService($request);
    }

    /**
     * Display the wishlist (user or guest).
     */
    public function index(Request $request)
    {
        $userId = Auth::id();
        $token  = $request->cookie('wishlist_token');

        $wishlist = Wishlist::with(['productVariant.product.primaryImage'])
            ->forOwner($userId, $token)
            ->get()
            ->map(function ($item) {
                $product = $item->productVariant?->product;
                $primaryImageUrl = $product?->primaryImage
                    ? Storage::disk('s3')->url($product->primaryImage->image_path)
                    : null;

                return [
                    'id' => $item->id,
                    'product_variant_id' => $item->product_variant_id,
                    'created_at' => $item->created_at,
                    'productVariant' => [
                        'id' => $item->productVariant?->id,
                        'sku' => $item->productVariant?->sku,
                        'price' => $item->productVariant?->selling_price,
                        'stock_quantity' => $item->productVariant?->stock,
                        'product' => [
                            'id' => $product?->id,
                            'name' => $product?->name,
                            'slug' => $product?->slug,
                            'thumbnail' => $primaryImageUrl,
                        ],
                    ],
                ];
            });

        return inertia('Customer/Wishlist', [
            'wishlist' => $wishlist,
        ]);
    }

    /**
     * Toggle a product variant in the wishlist.
     */
  
   
    public function store(Request $request)
{
    $variantIdentifier = $request->input('variant_hashid') ?? $request->input('product_variant_id');

    if (!$variantIdentifier) {
        return back()->with('error', 'No product provided.');
    }

    try {
        $added = $this->wishlistService->toggle($request, $variantIdentifier);

        return back()->with('success', $added
            ? 'Added to your wishlist.'
            : 'Removed from your wishlist.'
        );
    } catch (\InvalidArgumentException $e) {
        return back()->with('error', 'Invalid product.');
    } catch (\Throwable $e) {
        
        return back()->with('error', 'Something went wrong while updating your wishlist.');
    }
}




    /**
     * Remove a wishlist item by its ID.
     */
    public function destroy($wishlist_id, Request $request)
    {
        $userId = Auth::id();
        $token  = $request->cookie('wishlist_token');

        $wishlist = Wishlist::forOwner($userId, $token)
            ->where('id', $wishlist_id)
            ->first();

        if (! $wishlist) {
            return redirect()->back()->with('error', 'Wishlist not found.');
        }

        $wishlist->delete();

        return redirect()->back()->with('success', 'Wishlist item removed successfully.');
    }
}
