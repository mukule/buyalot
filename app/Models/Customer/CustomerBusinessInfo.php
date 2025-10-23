<?php

namespace App\Models\Customer;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class CustomerBusinessInfo extends Model
{
    use HasFactory;

    protected $fillable = [
        'customer_id', 'company_name', 'company_registration',
        'tax_number', 'job_title', 'department'
    ];

    public function customer()
    {
        return $this->belongsTo(Customer::class);
    }
}
