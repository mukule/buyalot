<?php

namespace App\Models;

use App\Models\Traits\HasHashid;
use Illuminate\Database\Eloquent\Model;

class UserDetail extends Model
{
    protected $fillable = ['user_id', 'phone', 'address','alt_phone','position','dob','idno','contact','contact_name','gender'];

    protected $casts = [
        'dob' => 'date',
    ];
}
