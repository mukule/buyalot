<?php

namespace App\Services;

use App\Models\Customer\Customer;
use App\Models\Customer\CustomerWishlistItem;

class WishlistService
{
    public function addToWishlist(
        Customer $customer,
        string $wishlistableType,
        int $wishlistableId,
        array $options = []
    ): CustomerWishlistItem {
        // Check if item already exists
        $existing = $customer->wishlistItems()
            ->where('wishlistable_type', $wishlistableType)
            ->where('wishlistable_id', $wishlistableId)
            ->first();

        if ($existing) {
            return $existing;
        }
        $wishlistable = $wishlistableType::find($wishlistableId);
        $currentPrice = $wishlistable?->price ?? null;

        return CustomerWishlistItem::create([
            'customer_id' => $customer->id,
            'wishlistable_type' => $wishlistableType,
            'wishlistable_id' => $wishlistableId,
            'added_at' => now(),
            'priority' => $options['priority'] ?? 2,
            'notes' => $options['notes'] ?? null,
            'is_private' => $options['is_private'] ?? false,
            'price_when_added' => $currentPrice,
            'currency' => 'USD',
        ]);
    }

    public function removeFromWishlist(Customer $customer, string $wishlistableType, int $wishlistableId): bool
    {
        return $customer->wishlistItems()
                ->where('wishlistable_type', $wishlistableType)
                ->where('wishlistable_id', $wishlistableId)
                ->delete() > 0;
    }

    public function moveToCart(Customer $customer, CustomerWishlistItem $wishlistItem): void
    {
        // $customer->cart()->add($wishlistItem->wishlistable);

        // Remove from wishlist after moving to cart
        $wishlistItem->delete();
    }

    public function checkPriceDrops(Customer $customer): array
    {
        $wishlistItems = $customer->wishlistItems()->with('wishlistable')->get();
        $priceDrops = [];

        foreach ($wishlistItems as $item) {
            if ($item->hasPriceDropped()) {
                $priceDrops[] = [
                    'item' => $item,
                    'old_price' => $item->price_when_added,
                    'new_price' => $item->wishlistable->price,
                    'savings' => $item->price_when_added - $item->wishlistable->price,
                ];
            }
        }

        return $priceDrops;
    }
}
