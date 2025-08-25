<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Vinkla\Hashids\Facades\Hashids;

class ProductVariantValue extends Model
{
    protected $table = 'product_variant_values';

    protected $fillable = [
        'product_variant_id',
        'variant_id',
    ];

    protected $appends = [
        'hashid',
    ];

    // ----------------------
    // Hashid for routes and API
    // ----------------------
    public function getHashidAttribute(): string
    {
        return Hashids::encode($this->id);
    }

    public function getRouteKey(): string
    {
        return $this->hashid;
    }

    public function resolveRouteBinding($value, $field = null): ?Model
    {
        $decoded = Hashids::decode($value);
        if (count($decoded) !== 1) {
            abort(404);
        }
        return $this->where('id', $decoded[0])->firstOrFail();
    }

    // ----------------------
    // Relationships
    // ----------------------

    /**
     * The SKU this variant value belongs to
     */
    public function productVariant(): BelongsTo
    {
        return $this->belongsTo(ProductVariant::class);
    }

    /**
     * The variant (e.g., Size "L", Color "Red")
     */
    public function variant(): BelongsTo
    {
        return $this->belongsTo(Variant::class);
    }
}
