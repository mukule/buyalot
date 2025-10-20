<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class Country extends Model
{
    use HasFactory;

    protected $fillable = [
        'iso2',
        'name',
        'flag_path',
    ];

    protected $appends = ['flag_url'];

    public function getFlagUrlAttribute(): ?string
    {
        if (!$this->flag_path) {
            return null;
        }
        // Expose via public storage symlink (/storage)
        return asset('storage/' . ltrim($this->flag_path, '/'));
    }
}
