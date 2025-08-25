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
        'product_id',
    ];

    protected $appends = [
        'hashid',
    ];

    // ----------------------
    // Relationships
    // ----------------------

    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }

    public function product(): BelongsTo
    {
        return $this->belongsTo(Product::class);
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
}
