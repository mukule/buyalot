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

   
    protected static function booted()
    {
        static::creating(function ($pickupPoint) {
            if (empty($pickupPoint->uuid)) {
                $pickupPoint->uuid = (string) Str::uuid();
            }

            if (empty($pickupPoint->code)) {
                
                $pickupPoint->code = strtoupper(substr(Str::slug($pickupPoint->name, ''), 0, 5));
            }
        });
    }

   
    public function region()
    {
        return $this->belongsTo(Region::class);
    }

    
    public function scopeActive($query)
    {
        return $query->where('active', true);
    }

   
    public function getDisplayNameAttribute(): string
    {
        return "{$this->name}" . ($this->region ? " ({$this->region->name})" : '');
    }
}
