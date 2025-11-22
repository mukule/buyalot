<?php

namespace App\Models\Customer;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Str;

class CustomerAddress extends Model
{
    protected $fillable = [
        'customer_id', 'pickup_point_id', 'type', 'label', 'first_name', 'last_name',
        'company', 'address_line_1', 'address_line_2', 'city',
        'state_province', 'postal_code', 'country_code', 'country_name', 'phone', 'is_default',
        'latitude', 'longitude', 'delivery_instructions', 'is_validated', 'validation_data', 'uuid'
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

        // Auto-generate UUID on creating
        static::creating(function ($address) {
            if (empty($address->uuid)) {
                $address->uuid = (string) Str::uuid();
            }

            // If a new address is marked default, reset others BEFORE saving
            if ($address->is_default) {
                static::withoutEvents(function () use ($address) {
                    static::where('customer_id', $address->customer_id)
                        ->where('type', $address->type)
                        ->update(['is_default' => false]);
                });
            }
        });

        // Prevent recursion: remove previous updating() logic completely
    }

    /*
    |--------------------------------------------------------------------------
    | Relationships
    |--------------------------------------------------------------------------
    */

    public function customer(): \Illuminate\Database\Eloquent\Relations\BelongsTo
    {
        return $this->belongsTo(Customer::class);
    }

    public function pickupPoint(): \Illuminate\Database\Eloquent\Relations\BelongsTo
    {
        return $this->belongsTo(\App\Models\PickupPoint::class, 'pickup_point_id');
    }

    /*
    |--------------------------------------------------------------------------
    | Accessors / Virtual Attributes
    |--------------------------------------------------------------------------
    */

    public function getStateAttribute(): ?string
    {
        return $this->attributes['state_province'] ?? null;
    }

    public function getCountryAttribute(): ?string
    {
        return $this->attributes['country_code'] ?? null;
    }

    public function getCoordinatesAttribute(): ?array
    {
        $lat = $this->attributes['latitude'] ?? null;
        $lng = $this->attributes['longitude'] ?? null;

        if ($lat === null || $lng === null) {
            return null;
        }

        return ['lat' => (float) $lat, 'lng' => (float) $lng];
    }

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

    /*
    |--------------------------------------------------------------------------
    | Scopes
    |--------------------------------------------------------------------------
    */

    public function scopeDefault($query)
    {
        return $query->where('is_default', true);
    }

    public function scopeByType($query, $type)
    {
        return $query->where('type', $type);
    }

    /*
    |--------------------------------------------------------------------------
    | Default Logic (Single Default Address)
    |--------------------------------------------------------------------------
    */

    public function makeDefault(): void
    {
        \Log::info('--- Running makeDefault() ---', [
            'target_address_id' => $this->id,
            'customer_id' => $this->customer_id,
        ]);

        // Reset all other defaults without triggering events
        static::withoutEvents(function () {
            static::where('customer_id', $this->customer_id)
                ->where('type', $this->type)
                ->update(['is_default' => false]);
        });

        // Mark this address as default without triggering recursive update events
        $this->is_default = true;
        $this->saveQuietly();

        \Log::info('--- Finished makeDefault() ---', [
            'target_address_id' => $this->id,
            'new_is_default' => $this->refresh()->is_default,
        ]);
    }

    /*
    |--------------------------------------------------------------------------
    | Simplified Address Accessor
    |--------------------------------------------------------------------------
    */

    public function getSimplifiedAddressAttribute(): array
    {
        $regionName = null;

        if ($this->pickup_point_id && $this->pickupPoint) {
            $regionName = $this->pickupPoint->region?->name ?? null;
        }

        return [
            'first_name' => $this->first_name,
            'last_name' => $this->last_name,
            'phone' => $this->phone,
            'address' => $this->address_line_1,
            'region' => $regionName,
            'pickup_point' => $this->pickup_point_id,
        ];
    }
}
