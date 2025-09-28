<?php

namespace App\Models\Customer;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class CustomerAcquisitionData extends Model
{
    use HasFactory;

    protected $fillable = [
        'customer_id', 'utm_source', 'utm_medium', 'utm_campaign',
        'utm_term', 'utm_content', 'additional_data'
    ];

    protected $casts = [
        'additional_data' => 'array',
    ];

    public function customer()
    {
        return $this->belongsTo(Customer::class);
    }
}
