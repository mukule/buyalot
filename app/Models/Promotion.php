<?php

namespace App\Models;

use App\Models\Products\Product; // <-- correct namespace
use App\Models\Category;
use App\Models\Traits\HasHashid;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Casts\Attribute; // <-- import this

class Promotion extends Model
{
    use HasFactory, HasHashid;

    protected $fillable = [
        'title',
        'image',
        'position',
        'link_type',
        'link_id',
        'start_date',
        'end_date',
        'priority',
        'is_active',
    ];

    protected $casts = [
        'start_date' => 'datetime',
        'end_date'   => 'datetime',
        'is_active'  => 'boolean',
    ];

    protected $appends = ['image_url']; // <-- append attribute

    /**
     * Scope for active promotions
     */
    public function scopeActive($query)
    {
        return $query->where('is_active', true)
                     ->where(function ($q) {
                         $q->whereNull('start_date')
                           ->orWhere('start_date', '<=', now());
                     })
                     ->where(function ($q) {
                         $q->whereNull('end_date')
                           ->orWhere('end_date', '>=', now());
                     });
    }

    /**
     * Resolve the actual URL for the promotion
     */
    public function getUrlAttribute(): string
    {
        switch ($this->link_type) {
            case 'category':
                return $this->category ? route('categories.show', $this->category->slug) : '#';
            case 'product':
                return $this->product ? route('products.show', $this->product->slug) : '#';
            default:
                return '#';
        }
    }

    /**
     * Relationships
     */
    public function category()
    {
        return $this->belongsTo(Category::class, 'link_id');
    }

    public function product()
    {
        return $this->belongsTo(Product::class, 'link_id');
    }

    /**
     * Image URL accessor
     */
    protected function imageUrl(): Attribute
    {
        return Attribute::get(fn () => $this->image ? url("storage/{$this->image}") : null);
    }
}