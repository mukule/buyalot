<?php

namespace App\Models\Orders;

use Illuminate\Database\Eloquent\Model;

use App\Interfaces\Payable;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Support\Str;

class Order extends Model implements Payable
{
    use HasFactory;

    protected $fillable = ['user_id', 'amount', 'status'];

    public function getAmount(): float
    {
        return $this->amount;
    }

    public function getDescription(): string
    {
        return "Payment for Order #{$this->id}";
    }

    public function getTransactionId(): string
    {
        return 'ORDER_' . $this->id . '_' . time();
    }
}

