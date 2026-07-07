<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Support\Str;

class CarModel extends Model
{
    protected $fillable = ['car_make_id', 'name', 'slug', 'active'];

    protected $casts = [
        'active' => 'boolean',
    ];

    protected static function booted(): void
    {
        static::saving(function (CarModel $model) {
            if (empty($model->slug)) {
                $model->slug = Str::slug($model->name);
            }
        });
    }

    public function make(): BelongsTo
    {
        return $this->belongsTo(CarMake::class, 'car_make_id');
    }

    public function scopeActive($query)
    {
        return $query->where('active', true);
    }
}
