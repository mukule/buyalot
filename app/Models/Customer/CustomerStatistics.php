<?php

namespace App\Models\Customer;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class CustomerStatistics extends Model
{
    use HasFactory;

    protected $fillable = [
        'customer_id', 'first_order_at', 'last_order_at', 'total_orders',
        'total_spent', 'average_order_value', 'lifetime_days',
        'customer_lifetime_value', 'days_since_last_order'
    ];

    protected $casts = [
        'first_order_at' => 'datetime',
        'last_order_at' => 'datetime',
        'total_spent' => 'decimal:2',
        'average_order_value' => 'decimal:2',
        'customer_lifetime_value' => 'decimal:2',
    ];

    public function customer()
    {
        return $this->belongsTo(Customer::class);
    }

    public function updateFromOrder($order)
    {
        $this->increment('total_orders');
        $this->increment('total_spent', $order->total);

        if (!$this->first_order_at) {
            $this->first_order_at = now();
        }

        $this->last_order_at = now();
        $this->average_order_value = $this->total_spent / $this->total_orders;
        $this->lifetime_days = $this->first_order_at->diffInDays(now());

        $this->save();
    }
}
