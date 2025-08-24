<?php

namespace App\Models\Payment;

use App\Models\Traits\HasHashid;
use App\Models\Traits\HasSlug;
use Illuminate\Database\Eloquent\Model;

class Discount extends Model
{
    use HasSlug, HasHashid;
    protected $table = 'discounts';

    protected $guarded = [];
}
