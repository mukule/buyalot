<?php

namespace App\Models\Customer;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class CustomerAddress extends Model
{
    use HasFactory;

    protected $fillable = [
        'customer_id', 'type', 'label', 'first_name', 'last_name',
        'company', 'address_line_1', 'address_line_2', 'city',
        'state', 'postal_code', 'country', 'phone', 'is_default',
        'coordinates', 'delivery_instructions'
    ];

    protected $casts = [
        'is_default' => 'boolean',
        'coordinates' => 'array',
    ];

    // Relationships
    public function customer(): \Illuminate\Database\Eloquent\Relations\BelongsTo
    {
        return $this->belongsTo(Customer::class);
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
            $this->state . ' ' . $this->postal_code,
            $this->country
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
}
