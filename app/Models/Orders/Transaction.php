<?php

namespace App\Models\Orders;

use Illuminate\Database\Eloquent\Model;

class Transaction extends Model
{
    protected $table = 'transactions';
    protected $fillable = ['transaction_id','user_id','payable','gateway','amount','status','payload','reference','receipt_no','receipt_date',];



}
