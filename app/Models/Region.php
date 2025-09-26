<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use App\Models\Traits\HasHashid;

class Region extends Model
{
    use HasHashid;

    protected $fillable = ['name', 'code'];

    protected $appends = ['hashid'];
}
