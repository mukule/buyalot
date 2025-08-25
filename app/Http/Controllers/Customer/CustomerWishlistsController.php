<?php

namespace App\Http\Controllers\Customer;

use App\Http\Controllers\Controller;
use App\Models\Customer\Customer;
use App\Models\Customer\CustomerWishlistItem;
use Illuminate\Http\Request;

class CustomerWishlistsController extends Controller
{
    public function __construct(
        private WishlistService $wishlistService
    ) {}

    public function index(Customer $customer)
    {
        $wishlistItems = $customer->wishlistItems()
            ->with('wishlistable')
            ->latest('added_at')
            ->paginate(20);

        $stats = [
            'total_items' => $customer->wishlistItems()->count(),
            'high_priority' => $customer->wishlistItems()->byPriority('high')->count(),
            'price_drops' => $customer->wishlistItems()->get()->filter->hasPriceDropped()->count(),
        ];

        return view('customers.wishlist.index', compact('customer', 'wishlistItems', 'stats'));
    }

    public function store(Request $request, Customer $customer)
    {
        $validated = $request->validate([
            'wishlistable_type' => 'required|string',
            'wishlistable_id' => 'required|integer',
            'priority' => 'nullable|integer|min:1|max:3',
            'notes' => 'nullable|string',
            'is_private' => 'boolean',
        ]);

        $wishlistItem = $this->wishlistService->addToWishlist(
            $customer,
            $validated['wishlistable_type'],
            $validated['wishlistable_id'],
            $validated
        );

        return back()->with('success', 'Item added to wishlist successfully.');
    }

    public function update(Request $request, Customer $customer, CustomerWishlistItem $wishlistItem)
    {
        $validated = $request->validate([
            'priority' => 'nullable|integer|min:1|max:3',
            'notes' => 'nullable|string',
            'is_private' => 'boolean',
        ]);

        $wishlistItem->update($validated);

        return back()->with('success', 'Wishlist item updated successfully.');
    }

    public function destroy(Customer $customer, CustomerWishlistItem $wishlistItem)
    {
        $wishlistItem->delete();
        return back()->with('success', 'Item removed from wishlist.');
    }

    public function moveToCart(Customer $customer, CustomerWishlistItem $wishlistItem)
    {
        // This would integrate with your cart system
        $this->wishlistService->moveToCart($customer, $wishlistItem);

        return back()->with('success', 'Item moved to cart successfully.');
    }

    public function share(Customer $customer)
    {
        $publicItems = $customer->wishlistItems()->public()->with('wishlistable')->get();
        return view('customers.wishlist.share', compact('customer', 'publicItems'));
    }
}
