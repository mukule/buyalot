<?php

namespace App\Models\Products;

use App\Models\User;
use Illuminate\Database\Eloquent\Model;

class ProductRestock extends Model
{
    protected $fillable = [
        'product_id',
        'product_variant_id',
        'quantity',
        'note',
        'restocked_by',
    ];

    public function user()
    {
        return $this->belongsTo(User::class, 'restocked_by');
    }
}
