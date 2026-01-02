<?php

namespace App\Http\Controllers;

use App\Models\Wishlist;
use App\Models\WishlistItem;
use App\Services\WishlistService;
use App\Services\DiscountService;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Storage;
use Vinkla\Hashids\Facades\Hashids;

class WishlistController extends Controller
{
    protected WishlistService $wishlistService;
    protected DiscountService $discountService;

    public function __construct(Request $request, DiscountService $discountService)
    {
        $this->wishlistService = new WishlistService($request);
        $this->discountService = $discountService;
    }

    /**
     * Display the wishlist (user or guest).
     */
    public function index(Request $request)
    {
        $userId = Auth::id();
        $token  = $request->cookie('wishlist_token');

        $items = WishlistItem::with(['productVariant.product.primaryImage'])
            ->whereHas('wishlist', function ($query) use ($userId, $token) {
                $query->forOwner($userId, $token);
            })
            ->get();

        $variantIds = $items->pluck('product_variant_id')->unique()->toArray();
        $discounts = collect($this->discountService->calculateDiscounts($variantIds))->keyBy('product_variant_id');

        $wishlist = $items->map(function ($item) use ($discounts) {
            $variant = $item->productVariant;
            $product = $variant?->product;
            $discountData = $discounts->get($item->product_variant_id);

            return [
                'id' => $item->id,
                'product_variant_id' => $item->product_variant_id,
                'created_at' => $item->created_at,
                'productVariant' => [
                    'id' => $variant?->id,
                    'sku' => $variant?->sku,
                    'price' => $discountData['final_price'] ?? $variant?->selling_price,
                    'marked_price' => $discountData['marked_price'] ?? $variant?->marked_price,
                    'discount_percent' => $discountData['discount_percentage'] ?? 0,
                    'has_discount' => $discountData['has_discount'] ?? false,
                    'stock_quantity' => $variant?->stock,
                    'product' => [
                        'id' => $product?->id,
                        'name' => $product?->name,
                        'slug' => $product?->slug,
                        'thumbnail' => $product?->primary_image_url,
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

        $wishlistItem = WishlistItem::whereHas('wishlist', function ($query) use ($userId, $token) {
                $query->forOwner($userId, $token);
            })
            ->where('id', $wishlist_id)
            ->first();

        if (! $wishlistItem) {
            return redirect()->back()->with('error', 'Wishlist item not found.');
        }

        $wishlistItem->delete();

        return redirect()->back()->with('success', 'Wishlist item removed successfully.');
    }
}
