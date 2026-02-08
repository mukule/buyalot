<?php

namespace App\Models\Cart;

use App\Models\User;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\SoftDeletes;
use Illuminate\Support\Str;

class Cart extends Model
{
    use SoftDeletes;

    protected $fillable = [
        'user_id',
        'cart_token',
        'status',
    ];

    protected static function booted()
    {
        static::creating(function ($cart) {
            // Generate a UUID token for guests if not provided
            if (!$cart->cart_token) {
                $cart->cart_token = (string) Str::uuid();
            }

            // Default status
            if (!$cart->status) {
                $cart->status = 'active';
            }
        });
    }

    /**
     * The user who owns the cart (nullable for guests)
     */
    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }

    /**
     * Items in the cart
     */
    public function items(): HasMany
    {
        return $this->hasMany(CartItem::class);
    }

    /**
     * Calculate cart totals
     */
    public function calculateTotals(): array
    {
        $items = $this->relationLoaded('items') ? $this->items : $this->items()->get();

        $subtotal = 0;
        $grandTotal = 0;
        $totalQty = 0;
        $totalDiscount = 0;

        foreach ($items as $item) {
            $qty = (int) $item->quantity;
            $marked = (float) ($item->marked_price ?? $item->unit_price);
            $lineSubtotal = $marked * $qty;

            $lineTotal = (float) ($item->total_price ?? ($item->unit_price * $qty));

            $subtotal += $lineSubtotal;
            $grandTotal += $lineTotal;
            $totalQty += $qty;
            $totalDiscount += max(0, $lineSubtotal - $lineTotal);
        }

        return [
            'subtotal'       => round($subtotal, 2),
            'total_discount' => round($totalDiscount, 2),
            'grand_total'    => round(max(0, $grandTotal), 2),
            'total_quantity' => $totalQty,
            'unique_items'   => $items->count(),
        ];
    }

    /**
     * Scope for active carts
     */
    public function scopeActive($query)
    {
        return $query->where('status', 'active');
    }

    /**
     * Scope for ordered carts
     */
    public function scopeOrdered($query)
    {
        return $query->where('status', 'ordered');
    }
}
