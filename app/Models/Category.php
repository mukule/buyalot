<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use App\Models\Traits\HasSlug;
use App\Models\Traits\HasHashid;

class Category extends Model
{
    use HasSlug, HasHashid;

    protected $fillable = ['name', 'slug', 'active'];

    protected $casts = [
        'active' => 'boolean',
    ];

    protected $appends = ['hashid'];

    
    protected static string $slugSource = 'name';

    public function subcategories()
    {
        return $this->hasMany(Subcategory::class);
    }
}
