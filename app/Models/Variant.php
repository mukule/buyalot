<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Vinkla\Hashids\Facades\Hashids;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class Variant extends Model
{
    protected $fillable = [
        'variant_category_id',
        'value',
        'is_active', 
    ];

    protected $appends = ['hashid'];

    protected $casts = [
        'is_active' => 'boolean', // ✅ Automatically cast to true/false
    ];

    public function getHashidAttribute(): string
    {
        return Hashids::encode($this->id);
    }

    public function category(): BelongsTo
    {
        return $this->belongsTo(VariantCategory::class, 'variant_category_id');
    }

    public function products()
{
    return $this->belongsToMany(Product::class, 'product_variant_values');
}

}
