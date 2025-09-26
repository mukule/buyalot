<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use App\Models\Traits\HasHashid;
use Vinkla\Hashids\Facades\Hashids;

class Wishlist extends Model
{
    use HasHashid;

    protected $fillable = [
        'user_id',
        'wishlist_token',   // 👈 allow storing for guests
        'product_variant_id',
    ];

    protected $appends = [
        'hashid',
    ];

    // ----------------------
    // Relationships
    // ----------------------

    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class)->withDefault(); // 👈 safe for guests (null)
    }

    public function productVariant(): BelongsTo
    {
        return $this->belongsTo(ProductVariant::class);
    }

    // Optional shortcut to access the actual product via variant
    public function product(): BelongsTo
    {
        return $this->productVariant()->withDefault()->belongsTo(Product::class, 'product_id');
    }

    // ----------------------
    // Hashid helpers
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
    // Scopes
    // ----------------------

    /**
     * Scope to fetch wishlist for current owner (user or guest).
     */
    public function scopeForOwner($query, ?int $userId, ?string $token)
    {
        if ($userId) {
            return $query->where('user_id', $userId);
        }

        return $query->where('wishlist_token', $token);
    }
}
