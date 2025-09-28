<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use App\Models\Traits\HasHashid;
use Vinkla\Hashids\Facades\Hashids;

class ProductReview extends Model
{
    use HasHashid;

    protected $fillable = [
        'product_id',
        'user_id',
        'rating',
        'comment',
        'status', // add status to fillable
    ];

    protected $appends = [
        'hashid',
        'images',
    ];

    // ----------------------
    // Status constants
    // ----------------------
    const STATUS_PENDING  = 0;
    const STATUS_APPROVED = 1;
    const STATUS_REJECTED = 2;

    // ----------------------
    // Relationships
    // ----------------------

    public function product(): BelongsTo
    {
        return $this->belongsTo(Product::class);
    }

    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }

    public function reviewImages(): HasMany
    {
        return $this->hasMany(ReviewImage::class, 'review_id');
    }

    // ----------------------
    // Accessors
    // ----------------------

    public function getImagesAttribute(): array
    {
        return $this->relationLoaded('reviewImages')
            ? $this->reviewImages->map(fn($img) => asset('storage/' . $img->path))->toArray()
            : [];
    }

    public function getStatusLabelAttribute(): string
    {
        return match ($this->status) {
            self::STATUS_APPROVED => 'Approved',
            self::STATUS_REJECTED => 'Rejected',
            default => 'Pending',
        };
    }

    // ----------------------
    // Status helper methods
    // ----------------------

    public function approve(): bool
    {
        $this->status = self::STATUS_APPROVED;
        return $this->save();
    }

    public function reject(): bool
    {
        $this->status = self::STATUS_REJECTED;
        return $this->save();
    }

    public function isApproved(): bool
    {
        return $this->status === self::STATUS_APPROVED;
    }

    public function isPending(): bool
    {
        return $this->status === self::STATUS_PENDING;
    }

    public function isRejected(): bool
    {
        return $this->status === self::STATUS_REJECTED;
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
