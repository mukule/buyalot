<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Vinkla\Hashids\Facades\Hashids;

class OrderItem extends Model
{
    use HasFactory;

    protected $fillable = [
        'ulid',
        'order_id',
        'product_id',
        'variant_id',
        'seller_id',
        'quantity',
        'unit_price',
        'total_price',
        'discount_amount',
        'tax_amount',
        'commission_id',
        'quantity_fulfilled',
        'quantity_cancelled',
        'quantity_returned',
        'product_snapshot',
        'metadata',
    ];

    protected $casts = [
        'unit_price' => 'decimal:2',
        'total_price' => 'decimal:2',
        'discount_amount' => 'decimal:2',
        'tax_amount' => 'decimal:2',
        'product_snapshot' => 'array',
        'metadata' => 'array',
    ];

    protected $appends = ['hashid'];

    /*
    |--------------------------------------------------------------------------
    | Relationships
    |--------------------------------------------------------------------------
    */

    public function order(): BelongsTo
    {
        return $this->belongsTo(Order::class);
    }

    public function product(): BelongsTo
    {
        return $this->belongsTo(Product::class);
    }

    public function variant(): BelongsTo
    {
        return $this->belongsTo(ProductVariant::class, 'variant_id');
    }

    public function seller(): BelongsTo
    {
        return $this->belongsTo(User::class, 'seller_id');
    }

    /*
    |--------------------------------------------------------------------------
    | Hashid for public references
    |--------------------------------------------------------------------------
    */
    public function getRouteKey(): string
    {
        return $this->hashid;
    }

    public function getHashidAttribute(): string
    {
        return Hashids::encode($this->id);
    }

    public function resolveRouteBinding($value, $field = null): ?Model
    {
        $decoded = Hashids::decode($value);
        if (count($decoded) !== 1) {
            abort(404);
        }
        return $this->where('id', $decoded[0])->firstOrFail();
    }

    /*
    |--------------------------------------------------------------------------
    | Helpers
    |--------------------------------------------------------------------------
    */

    public function isFulfilled(): bool
    {
        return $this->quantity > 0 && $this->quantity_fulfilled >= $this->quantity;
    }

    public function isPartiallyFulfilled(): bool
    {
        return $this->quantity_fulfilled > 0 && $this->quantity_fulfilled < $this->quantity;
    }

    public function isCancelled(): bool
    {
        return $this->quantity_cancelled > 0;
    }

    public function isReturned(): bool
    {
        return $this->quantity_returned > 0;
    }
}
