<?php

namespace App\Models\Customer;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class CustomerTag extends Model
{
    use HasFactory;

    protected $fillable = ['name', 'color'];

    public function customers()
    {
        return $this->belongsToMany(Customer::class, 'customer_tag_assignments')
            ->withTimestamps();
    }
}
