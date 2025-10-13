<?php

namespace App\Models\Customer;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class CustomerAddress extends Model
{
    protected $fillable = [
        'customer_id', 'type', 'label', 'first_name', 'last_name',
        'company', 'address_line_1', 'address_line_2', 'city',
        'state_province', 'postal_code', 'country_code', 'country_name', 'phone', 'is_default',
        'latitude', 'longitude', 'delivery_instructions', 'is_validated', 'validation_data'
    ];

    protected $casts = [
        'is_default' => 'boolean',
        'is_validated' => 'boolean',
        'validation_data' => 'array',
    ];

    protected $appends = ['state', 'country', 'coordinates'];

    protected static function boot()
    {
        parent::boot();

        static::creating(function ($address) {
            if ($address->is_default) {
                // Remove default from other addresses of the same customer and type
                static::where('customer_id', $address->customer_id)
                    ->where('type', $address->type)
                    ->update(['is_default' => false]);
            }
        });

        static::updating(function ($address) {
            if ($address->is_default && $address->isDirty('is_default')) {
                static::where('customer_id', $address->customer_id)
                    ->where('type', $address->type)
                    ->where('id', '!=', $address->id)
                    ->update(['is_default' => false]);
            }
        });
    }

    public function customer(): \Illuminate\Database\Eloquent\Relations\BelongsTo
    {
        return $this->belongsTo(Customer::class);
    }

    // Virtual attributes to align with frontend expectations
    public function getStateAttribute(): ?string
    {
        return $this->attributes['state_province'] ?? null;
    }

    public function getCountryAttribute(): ?string
    {
        // Prefer code (KE) for frontend; adjust if you need country_name instead
        return $this->attributes['country_code'] ?? null;
    }

    public function getCoordinatesAttribute(): ?array
    {
        $lat = $this->attributes['latitude'] ?? null;
        $lng = $this->attributes['longitude'] ?? null;
        if ($lat === null || $lng === null) {
            return null;
        }
        return ['lat' => (float)$lat, 'lng' => (float)$lng];
    }

    // Accessors
    public function getFullNameAttribute(): string
    {
        return trim($this->first_name . ' ' . $this->last_name);
    }

    public function getFullAddressAttribute(): string
    {
        $parts = array_filter([
            $this->address_line_1,
            $this->address_line_2,
            $this->city,
            ($this->attributes['state_province'] ?? null) . ' ' . $this->postal_code,
            ($this->attributes['country_name'] ?? $this->attributes['country_code'] ?? null),
        ]);

        return implode(', ', $parts);
    }

    // Scopes
    public function scopeDefault($query)
    {
        return $query->where('is_default', true);
    }

    public function scopeByType($query, $type)
    {
        return $query->where('type', $type);
    }

    public function makeDefault(): void
    {
        $this->customer->addresses()->update(['is_default' => false]);
        $this->update(['is_default' => true]);
    }

    public function getFormattedAddressAttribute(): string
    {
        $parts = [
            $this->address_line_1,
            $this->address_line_2,
            $this->city,
            $this->attributes['state_province'] ?? null,
            $this->postal_code,
            $this->attributes['country_name'] ?? $this->attributes['country_code'] ?? null,
        ];
        return implode(', ', array_filter($parts));
    }

}
