<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class ShippingRate extends Model
{
    use HasFactory;

    protected $table = 'shipping_rates';

    protected $fillable = [
        'package_size', 
        'base_price',   
    ];

    protected $casts = [
        'base_price' => 'float',
    ];

 
    public function scopePackageSize($query, string $size)
    {
        return $query->where('package_size', $size);
    }

    
    public function costForTier(int $tier): float
    {
        $increment = (float) env('SHIPPING_INCREMENT_PER_TIER', 50);
        return $this->base_price + (($tier - 1) * $increment);
    }

    public function daysForTier(int $tier): int
    {
        $baseDays = (int) env('SHIPPING_DEFAULT_DAYS', 2);
        $increment = (int) env('SHIPPING_DAYS_INCREMENT', 1);

        return $baseDays + (($tier - 1) * $increment);
    }
}
