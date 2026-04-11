<?php

namespace App\Models\Policy;

use App\Models\Traits\HasSlug;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Str;

class Policy extends Model
{
    use HasSlug;

    protected static string $slugSource = 'title';

    protected $fillable = [
        'scope',
        'title',
        'is_mandatory',
        'status',
        'code',
    ];

    protected static function booted()
    {
        // Generate code safely
        static::creating(function (Policy $policy) {
            if (empty($policy->code) && !empty($policy->title)) {
                $policy->code = Str::slug($policy->title, '_');
            }
        });

        static::updating(function (Policy $policy) {
            if ($policy->isDirty('title')) {
                $policy->code = Str::slug($policy->title, '_');
            }
        });

        
        static::saved(function () {
            Cache::forget('customer_policies');
        });

        static::deleted(function () {
            Cache::forget('customer_policies');
        });
    }

    public function policyVersions(): HasMany
    {
        return $this->hasMany(PolicyVersion::class);
    }

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

    public function scopeActive($query)
{
    return $query->where('status', 1);
}

public function scopeCustomer($query)
{
    return $query->where('scope', 'customer');
}


    public function scopeSeller($query)
    {
        return $query->where('scope', 'seller');
    }

    public function latestActiveVersion()
{
    return $this->hasOne(PolicyVersion::class)
        ->where('status', true)
        ->where(function ($q) {
            $q->whereNull('effective_from')
              ->orWhere('effective_from', '<=', now()->toDateString());
        })
        ->latest('version_number');
}

}
