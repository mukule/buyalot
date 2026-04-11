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
            $unitPrice = (float) ($item->unit_price ?? 0);
            $lineTotal = (float) ($item->total_price ?? ($unitPrice * $qty));

            $subtotal += $lineTotal;
            $grandTotal += $lineTotal;
            $totalQty += $qty;
            // Discount is handled elsewhere or implicitly in unit_price
            $totalDiscount += 0;
        }

        return [
            'subtotal'       => (int) round($subtotal),
            'total_discount' => (int) round($totalDiscount),
            'grand_total'    => (int) round(max(0, $grandTotal)),
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
