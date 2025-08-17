<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;

class BusinessType extends Model
{
    /**
     * The attributes that are mass assignable.
     *
     * @var array
     */
    protected $fillable = [
        'name',
        'slug',
        'description'
    ];

    /**
     * Get the required documents for this business type
     */
    public function requiredDocuments(): HasMany
    {
        return $this->hasMany(BusinessTypeRequiredDocument::class);
    }

    /**
     * Get the seller applications for this business type
     */
    public function applications(): HasMany
    {
        return $this->hasMany(UserApplication::class);
    }

    /**
     * Get the seller documents for this business type
     */
    public function sellerDocuments(): HasMany
    {
        return $this->hasMany(SellerDocument::class);
    }
}