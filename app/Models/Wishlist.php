<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Support\Str;
use App\Models\Traits\HasHashid;
use Vinkla\Hashids\Facades\Hashids;

class Wishlist extends Model
{
    use HasFactory, HasHashid;

    protected $fillable = [
        'uuid',
        'user_id',
        'wishlist_token',
        'product_variant_id',
    ];

    protected $appends = [
        'hashid',
    ];

    // ----------------------
    // Boot
    // ----------------------

    protected static function boot()
    {
        parent::boot();

        static::creating(function ($wishlist) {
            if (empty($wishlist->uuid)) {
                $wishlist->uuid = (string) Str::uuid();
            }
        });
    }

    // ----------------------
    // Relationships
    // ----------------------

    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class)->withDefault();
    }

    public function productVariant(): BelongsTo
    {
        return $this->belongsTo(ProductVariant::class);
    }

    // If you want to link directly to products through variants
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

    // ----------------------
    // Relations to items (new structure)
    // ----------------------

    public function items()
    {
        return $this->hasMany(WishlistItem::class);
    }
}
