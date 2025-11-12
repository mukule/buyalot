<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Str;
use App\Models\Traits\HasHashid;

class Region extends Model
{
    use HasHashid;

    protected $fillable = [
        'uuid',
        'name',
        'code',
        'parent_id',
        'level',
        'active',
        'zone_id', 
    ];

    protected $appends = ['hashid'];

    
    protected static function boot()
    {
        parent::boot();

        static::creating(function ($model) {
            if (empty($model->uuid)) {
                $model->uuid = (string) Str::uuid();
            }

            if (empty($model->code) && !empty($model->name)) {
                $model->code = strtoupper(substr(Str::slug($model->name, ''), 0, 5));
            }
        });
    }

    public function parent()
    {
        return $this->belongsTo(self::class, 'parent_id');
    }

    public function children()
    {
        return $this->hasMany(self::class, 'parent_id');
    }

    public function pickupPoints()
    {
        return $this->hasMany(PickupPoint::class);
    }

    public function zone()
    {
        return $this->belongsTo(Zone::class); 
    }

    
    public function scopeLevel($query, string $level)
    {
        return $query->where('level', $level);
    }

    public function scopeActive($query)
{
    return $query->where('active', true);
}

}
