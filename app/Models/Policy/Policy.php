<?php

namespace App\Models\Policy;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;

class Policy extends Model
{
    protected $fillable = [
        'scope',       // 'seller' or 'customer'
        'title',
        'is_mandatory',
        'status',
        'code',        // make code fillable
    ];

    /**
     * Boot method to set code automatically.
     */
    protected static function booted()
    {
        static::creating(function (Policy $policy) {
            if (empty($policy->code) && !empty($policy->title)) {
                $policy->code = strtolower(str_replace(' ', '_', $policy->title));
            }
        });

        static::updating(function (Policy $policy) {
            // Optionally update code when title changes
            if (empty($policy->code) && !empty($policy->title)) {
                $policy->code = strtolower(str_replace(' ', '_', $policy->title));
            }
        });
    }

    /**
     * Get all versions of this policy.
     */
    public function policyVersions(): HasMany
    {
        return $this->hasMany(PolicyVersion::class);
    }

    /**
     * Get all user agreements linked to this policy through versions.
     */
    public function userAgreements(): HasMany
    {
        return $this->hasManyThrough(
            UserPolicyAgreement::class,
            PolicyVersion::class,
            'policy_id',          
            'policy_version_id',  
            'id',                 
            'id'                  
        );
    }
}
