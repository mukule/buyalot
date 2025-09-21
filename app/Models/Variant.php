<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Vinkla\Hashids\Facades\Hashids;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\BelongsToMany;

class Variant extends Model
{
    protected $fillable = [
        'variant_category_id', 
        'value',              
        'is_active',
        
    ];

    protected $appends = ['hashid'];

    protected $casts = [
        'is_active' => 'boolean',
    ];

    // ---------------------------
    // Relationships
    // ---------------------------

    /**
     * The category this variant belongs to (e.g., "Color")
     */
    public function category(): BelongsTo
    {
        return $this->belongsTo(VariantCategory::class, 'variant_category_id');
    }

    /**
     * All products using this variant, with pivot data
     */
    public function products(): BelongsToMany
    {
        return $this->belongsToMany(Product::class, 'product_variant_values')
            ->using(ProductVariantValue::class)
            ->withPivot([
                'stock',
                'regular_price', 
                'selling_price',
                // Include other pivot fields as needed
            ]);
    }

    // ---------------------------
    // Helpers
    // ---------------------------

    public function getHashidAttribute(): string
    {
        return Hashids::encode($this->id);
    }
}