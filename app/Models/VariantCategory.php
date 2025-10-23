<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Vinkla\Hashids\Facades\Hashids;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\Relations\BelongsToMany;

class VariantCategory extends Model
{
    protected $fillable = [
        'name',
        'default',
        'active', 
    ];

    protected $casts = [
        'default' => 'boolean',
        'active'  => 'boolean', 
    ];

    protected $appends = ['hashid'];

    public function getHashidAttribute(): string
    {
        return Hashids::encode($this->id);
    }

    public function getRouteKey(): string
    {
        return Hashids::encode($this->id);
    }

    public function resolveRouteBinding($value, $field = null): ?Model
    {
        $decoded = Hashids::decode($value);
        if (count($decoded) !== 1) {
            abort(404);
        }
        return $this->where('id', $decoded[0])->firstOrFail();
    }

  
    public function variants(): HasMany
    {
        return $this->hasMany(Variant::class);
    }

   
    public function categories(): BelongsToMany
    {
        return $this->belongsToMany(
            Category::class,
            'category_variants',   
            'variant_category_id', 
            'category_id'         
        );
    }

  
    public function scopeActive($query)
    {
        return $query->where('active', true);
    }

   
    public static function default(): ?VariantCategory
    {
        return self::where('default', true)->first();
    }
}
