<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class BusinessTypeRequiredDocument extends Model
{
    /**
     * The attributes that are mass assignable.
     *
     * @var array
     */
    protected $fillable = [
        'business_type_id',
        'document_name', 
        'description',
        'is_required'
    ];

    /**
     * The attributes that should be cast.
     *
     * @var array
     */
    protected $casts = [
        'is_required' => 'boolean',
        'created_at' => 'datetime',
        'updated_at' => 'datetime'
    ];

    /**
     * Get the business type that requires this document
     */
    public function businessType(): BelongsTo
    {
        return $this->belongsTo(BusinessType::class);
    }

    /**
     * Get all seller documents submitted for this requirement
     */
    public function sellerDocuments(): HasMany
    {
        return $this->hasMany(SellerDocument::class, 'document_type', 'document_name');
    }

    /**
     * Scope a query to only include required documents
     */
    public function scopeRequired($query)
    {
        return $query->where('is_required', true);
    }

    /**
     * Scope a query to only include optional documents
     */
    public function scopeOptional($query)
    {
        return $query->where('is_required', false);
    }
}