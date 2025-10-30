<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class Zone extends Model
{
    use HasFactory;

    protected $table = 'zones';

    protected $fillable = [
        'name',
        'is_default_origin',
        'tier',
    ];

    protected $casts = [
        'is_default_origin' => 'boolean',
    ];

    /**
     * A zone can have many regions
     */
    public function regions()
    {
        return $this->hasMany(Region::class);
    }

    /**
     * Scope to get the default origin zone
     */
    public function scopeDefaultOrigin($query)
    {
        return $query->where('is_default_origin', true);
    }
}
