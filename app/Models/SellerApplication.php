<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Vinkla\Hashids\Facades\Hashids;

class SellerApplication extends Model
{
    // Status constants
    public const STATUS_PENDING = 0;
    public const STATUS_APPROVED = 1;
    public const STATUS_REJECTED = 2;

    protected $fillable = [
        'business_type',
        'agreed_to_privacy',

        // Contact Info
        'first_name',
        'last_name',
        'contact_email',
        'contact_phone',

        // Identification
        'identification_type',
        'id_number',
        'passport_number',
        'drivers_license',

        // Business Info
        'business_name',
        'primary_product_category',
        'description',

        // Owner Info
        'owner_first_name',
        'owner_last_name',
        'owner_email',
        'owner_phone',

        // Registration / Tax Info
        'vat_registered',
        'vat_number',
        'company_legal_name',
        'ke_business_reg_number',
        'non_ke_business_reg_number',
        'ke_id_number',
        'passport_number_sp',
        'country',
        'nationality',
        'monthly_revenue',

        // Operations
        'owns_physical_store',
        'retail_store_count',
        'is_supplier_to_retailers',
        'operates_other_marketplaces',
        'marketplace_details',
        'supplier_retail_count',
        'product_count',
        'stock_handling',
        'product_website',
        'product_origin',
        'owned_brands',
        'licensed_brands',
        'product_branding',
        'social_media',
        'business_summary',

        // Discovery
        'discovery_source',
        'referrer_email',
        'share_with_distributors',

        // Progress tracking
        'is_submitted',

        // Status tracking
        'status',
        'status_reason',
        'verified',   
    ];

    protected $casts = [
        'product_categories' => 'array',
        'agreed_to_privacy' => 'boolean',
        'is_submitted' => 'boolean',
        'status' => 'integer',
        'status_reason' => 'string',
        'verified' => 'boolean',
    ];

    protected $appends = ['hashid'];

    public function images()
    {
        return $this->hasMany(SellerApplicationImage::class);
    }

    // Status convenience methods
    public function isPending(): bool
    {
        return $this->status === self::STATUS_PENDING;
    }

    public function isApproved(): bool
    {
        return $this->status === self::STATUS_APPROVED;
    }

    public function isRejected(): bool
    {
        return $this->status === self::STATUS_REJECTED;
    }

      public function isVerified(): bool
    {
        return (bool) $this->verified;
    }

    public function isNotVerified(): bool
    {
        return !$this->isVerified();
    }

    // Hashids integration for route model binding
    public function getRouteKey(): string
    {
        return Hashids::encode($this->id);
    }

    public function getHashidAttribute(): string
    {
        return Hashids::encode($this->id);
    }

    public function resolveRouteBinding($value, $field = null): ?Model
    {
        $decoded = Hashids::decode($value);

        if (count($decoded) !== 1) {
            abort(404);
        }

        return $this->where('id', $decoded[0])->firstOrFail();
    }

    public function user()
{
    return $this->hasOne(User::class, 'seller_application_id');
}

}
