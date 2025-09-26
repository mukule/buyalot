<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Vinkla\Hashids\Facades\Hashids;

class ReviewImage extends Model
{
    protected $fillable = [
        'review_id',
        'path',
    ];

    protected $appends = [
        'hashid',
        'url',
    ];

    // ----------------------
    // Relationships
    // ----------------------

    public function review(): BelongsTo
    {
        return $this->belongsTo(ProductReview::class, 'review_id');
    }

    // ----------------------
    // Accessors
    // ----------------------

    public function getUrlAttribute(): string
    {
        return asset('storage/' . $this->path);
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
