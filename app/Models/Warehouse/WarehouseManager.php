<?php

namespace App\Models\Warehouse;

use App\Models\Traits\HasHashid;
use App\Models\User;
use Illuminate\Database\Eloquent\Model;

class WarehouseManager extends Model
{
    Use HasHashid;
    protected $fillable = [
        'warehouse_id', 'user_id', 'name', 'phone', 'email', 'role', 'active'
    ];

    public function warehouse()
    {
        return $this->belongsTo(Warehouse::class);
    }

    public function user()
    {
        return $this->belongsTo(User::class);
    }
}
