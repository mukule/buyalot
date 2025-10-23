<?php

namespace App\Models\Customer;

use App\Models\Traits\HasHashid;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\MorphTo;

class CustomerWishlistItem extends Model
{
    use HasFactory, HasHashid;

    protected $fillable = [
        'customer_id', 'wishlistable_type', 'wishlistable_id',
        'added_at', 'priority', 'notes', 'is_private',
        'price_when_added', 'currency', 'notification_sent'
    ];

    protected $casts = [
        'added_at' => 'datetime',
        'priority' => 'integer',
        'is_private' => 'boolean',
        'price_when_added' => 'decimal:2',
        'notification_sent' => 'boolean',
    ];

    // Relationships
    public function customer(): BelongsTo
    {
        return $this->belongsTo(Customer::class);
    }

    public function wishlistable(): MorphTo
    {
        return $this->morphTo();
    }

    // Scopes
    public function scopeByPriority($query, $priority = 'high')
    {
        $priorities = ['low' => 1, 'medium' => 2, 'high' => 3];
        return $query->where('priority', $priorities[$priority] ?? 2);
    }

    public function scopePublic($query)
    {
        return $query->where('is_private', false);
    }

    public function scopePrivate($query)
    {
        return $query->where('is_private', true);
    }

    // Methods
    public function getPriorityTextAttribute(): string
    {
        return match($this->priority) {
            1 => 'low',
            2 => 'medium',
            3 => 'high',
            default => 'medium'
        };
    }

    public function hasPriceDropped(): bool
    {
        if (!$this->wishlistable || !$this->price_when_added) {
            return false;
        }

        $currentPrice = $this->wishlistable->price ?? 0;
        return $currentPrice < $this->price_when_added;
    }
}
