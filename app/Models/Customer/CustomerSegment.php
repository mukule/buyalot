<?php

namespace App\Models\Customer;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class CustomerSegment extends Model
{
    use HasFactory;

    protected $fillable = ['name', 'description', 'criteria'];

    protected $casts = [
        'criteria' => 'array',
    ];

    public function customers()
    {
        return $this->belongsToMany(Customer::class, 'customer_segment_assignments')
            ->withPivot('assigned_at', 'expires_at')
            ->withTimestamps();
    }
}
