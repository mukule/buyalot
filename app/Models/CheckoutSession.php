<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class CheckoutSession extends Model
{
    // Mass assignable fields
    protected $fillable = [
        'cart_id',
        'customer_id',
        'cart_amount',
        'discount_amount',
        'tax_amount',
        'shipping_amount',
        'amount',
        'currency',
        'ref_num',
        'status',
        'order_status',
    ];

    /**
     * Boot method to auto-generate ref_num on creating.
     */
    protected static function booted()
    {
        static::creating(function ($session) {
            if (empty($session->ref_num)) {
                $session->ref_num = self::generateRefNum();
            }
        });
    }

    /**
     * Generate a unique 6-character alphanumeric reference number.
     */
    public static function generateRefNum(): string
    {
        $characters = '0123456789ABCDEFGHIJKLMNOPQRSTUVWXYZ';
        do {
            $ref = '';
            for ($i = 0; $i < 6; $i++) {
                $ref .= $characters[random_int(0, strlen($characters) - 1)];
            }
        } while (self::where('ref_num', $ref)->exists());

        return $ref;
    }

    /**
     * Relationship to Cart.
     */
    public function cart()
    {
        return $this->belongsTo(Cart::class);
    }

    /**
     * Relationship to Customer.
     */
    // public function customer()
    // {
    //     return $this->belongsTo(Customer::class);
    // }
}
