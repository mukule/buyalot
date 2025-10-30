<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Str;

class PickupPoint extends Model
{
    protected $fillable = [
        'uuid',
        'region_id',
        'name',
        'code',
        'description',
        'address',
        'contact_phone',
        'contact_email',
        'active',
    ];

    protected $casts = [
        'active' => 'boolean',
    ];

    /**
     * Automatically generate UUID and code on create
     */
    protected static function booted()
    {
        static::creating(function ($pickupPoint) {
            if (empty($pickupPoint->uuid)) {
                $pickupPoint->uuid = (string) Str::uuid();
            }

            if (empty($pickupPoint->code)) {
                // Generate a code from the name, e.g., "Nairobi Hub" -> "NAIRO"
                $pickupPoint->code = strtoupper(substr(Str::slug($pickupPoint->name, ''), 0, 5));
            }
        });
    }

    /**
     * Relationships
     */
    public function region()
    {
        return $this->belongsTo(Region::class);
    }

    /**
     * Scope for active pickup points
     */
    public function scopeActive($query)
    {
        return $query->where('active', true);
    }

    /**
     * Accessor for full display label
     */
    public function getDisplayNameAttribute(): string
    {
        return "{$this->name}" . ($this->region ? " ({$this->region->name})" : '');
    }
}
