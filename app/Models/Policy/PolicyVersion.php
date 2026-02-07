<?php

namespace App\Models\Policy;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Carbon\Carbon;

class PolicyVersion extends Model
{
    protected $fillable = [
        'policy_id',
        'version_number',
        'content',
        'effective_from',
        'status',
    ];

    // Cast effective_from as DATE only
    protected $casts = [
        'status' => 'boolean',
        'effective_from' => 'date', // only YYYY-MM-DD
    ];

    protected $appends = [
        'effective_from_local',
        'created_at_local',
        'updated_at_local',
    ];

    /* -------------------
       Relationships
    -------------------*/

    public function policy(): BelongsTo
    {
        return $this->belongsTo(Policy::class);
    }

    public function userAgreements(): HasMany
    {
        return $this->hasMany(UserPolicyAgreement::class, 'policy_version_id');
    }

    /* -------------------
       Query Scopes
    -------------------*/

    public function scopeActive($query)
    {
        return $query->where('status', true);
    }

    public function scopeEffective($query)
    {
        return $query->where(function ($q) {
            $q->whereNull('effective_from')
              ->orWhere('effective_from', '<=', now()->toDateString());
        });
    }

    public function scopeLatestVersion($query)
    {
        return $query->orderByDesc('version_number');
    }

    /* -------------------
       Mutators
    -------------------*/

    /**
     * Auto-fill effective_from if null/blank when saving.
     */
    public function setEffectiveFromAttribute($value)
    {
        $this->attributes['effective_from'] = $value
            ? Carbon::parse($value)->toDateString()
            : Carbon::now()->toDateString(); // defaults to today
    }

    /* -------------------
       Accessors for display
    -------------------*/

    public function getEffectiveFromLocalAttribute(): string
    {
        return $this->effective_from
            ? Carbon::parse($this->effective_from)->format('Y-m-d')
            : Carbon::now()->format('Y-m-d');
    }

    public function getCreatedAtLocalAttribute(): string
    {
        return $this->created_at
            ? $this->created_at->format('Y-m-d')
            : '';
    }

    public function getUpdatedAtLocalAttribute(): string
    {
        return $this->updated_at
            ? $this->updated_at->format('Y-m-d')
            : '';
    }


    
}
