<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class CategoryVariant extends Model
{
    protected $table = 'category_variants';

    protected $fillable = [
        'category_id',
        'variant_category_id',
    ];

    
    public $timestamps = true;

    
    public function category()
    {
        return $this->belongsTo(Category::class);
    }

    public function variantCategory()
    {
        return $this->belongsTo(VariantCategory::class);
    }
}
