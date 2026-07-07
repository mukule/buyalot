<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Support\Str;

class CarMake extends Model
{
    protected $fillable = ['name', 'slug', 'active', 'sort_order'];

    protected $casts = [
        'active'     => 'boolean',
        'sort_order' => 'integer',
    ];

    protected static function booted(): void
    {
        static::saving(function (CarMake $make) {
            if (empty($make->slug)) {
                $make->slug = Str::slug($make->name);
            }
        });
    }

    public function models(): HasMany
    {
        return $this->hasMany(CarModel::class);
    }

    public function scopeActive($query)
    {
        return $query->where('active', true);
    }
}
