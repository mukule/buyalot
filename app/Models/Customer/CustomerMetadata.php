<?php

namespace App\Models\Customer;

use Illuminate\Database\Eloquent\Model;

class CustomerMetadata extends Model
{

    protected $fillable = ['customer_id', 'key', 'value', 'type'];

    public function customer()
    {
        return $this->belongsTo(Customer::class);
    }

    public function getValueAttribute($value)
    {
        return match($this->type) {
            'integer' => (int) $value,
            'boolean' => (bool) $value,
            'json' => json_decode($value, true),
            'array' => json_decode($value, true),
            default => $value,
        };
    }

    public function setValueAttribute($value)
    {
        $this->attributes['value'] = match($this->type) {
            'integer' => (string) $value,
            'boolean' => $value ? '1' : '0',
            'json', 'array' => json_encode($value),
            default => $value,
        };
    }
}
