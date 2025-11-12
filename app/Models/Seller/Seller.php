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

    public function users()
    {
        return $this->belongsToMany(\App\Models\User::class, 'seller_user', 'seller_id', 'user_id')
            ->withPivot(['role', 'is_owner'])
            ->withTimestamps();
    }
}
