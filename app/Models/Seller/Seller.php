<?php

namespace App\Models\Seller;

use App\Models\Product;
use Illuminate\Database\Eloquent\Model;

class Seller extends Model
{
    protected $guarded = [];
    protected $table = 'seller_applications';
    public function products()
    {
        return $this->hasMany(Product::class, 'owner_id');
    }

}
