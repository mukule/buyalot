<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use App\Models\Traits\HasHashid;

class Region extends Model
{
    use HasHashid;

    protected $fillable = ['name', 'code', 'parent_id', 'level'];

    protected $appends = ['hashid'];

    // Relationships
    public function parent()
    {
        return $this->belongsTo(self::class, 'parent_id');
    }

    public function children()
    {
        return $this->hasMany(self::class, 'parent_id');
    }

    // Scopes
    public function scopeLevel($query, string $level)
    {
        return $query->where('level', $level);
    }
}
