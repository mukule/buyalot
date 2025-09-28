<?php

namespace App\Models\Customer;

use App\Events\CustomerRegistered;
use App\Models\Commission\CommissionCalculation;
use App\Models\Orders\Order;
use App\Models\Traits\HasHashid;
use App\Models\User;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\Relations\HasOne;
use Illuminate\Database\Eloquent\SoftDeletes;
use Illuminate\Foundation\Auth\User as Authenticatable;
use Illuminate\Notifications\Notifiable;
use Laravel\Sanctum\HasApiTokens;

class Customer extends Authenticatable
{
    use Notifiable, SoftDeletes, HasApiTokens, HasHashid;

    use Notifiable, SoftDeletes;

    protected $fillable = [
        'customer_code', 'first_name', 'last_name', 'email', 'phone',
        'date_of_birth', 'gender', 'profile_photo', 'customer_type', 'status',
        'acquisition_source', 'referrer_url','avatar','user_id','google_id',
        'avatar',
        'provider',
        'provider_id',
        'email_verified_at',
        'last_login_at',
        'provider_verified_at',
        'password'
    ];

    protected $casts = [
        'date_of_birth' => 'date',
        'email_verified_at' => 'datetime',
        'password' => 'hashed',
        'provider_verified_at'=>'datetime',
    ];
    protected $hidden = [
        'password',
        'remember_token',
        'email_verified_at',
        'google_id',
        'provider_verified_at',
        'password'
    ];
    protected static function boot()
    {
        parent::boot();

        static::creating(function ($customer) {
            if (empty($customer->customer_code)) {
                $customer->customer_code = self::generatecustomerCode();
            }
        });

        static::created(function ($customer) {
            event(new CustomerRegistered($customer));
        });
    }

    public static function generatecustomerCode(): string
    {
        $lastCustomer = self::withTrashed()
            ->whereNotNull('customer_code')
            ->orderByRaw('CAST(SUBSTRING(customer_code, 3) AS UNSIGNED) DESC')
            ->first();

        if (!$lastCustomer) {
            return 'CU001';
        }

        $lastNumber = (int) substr($lastCustomer->customer_code, 2);
        $nextNumber = $lastNumber + 1;

        return 'CU' . str_pad($nextNumber, 3, '0', STR_PAD_LEFT);
    }

    // Relationships
    public function businessInfo()
    {
        return $this->hasOne(CustomerBusinessInfo::class);
    }

    public function segments()
    {
        return $this->belongsToMany(CustomerSegment::class, 'customer_segment_assignments')
            ->withPivot('assigned_at', 'expires_at')
            ->withTimestamps();
    }

    public function tags()
    {
        return $this->belongsToMany(CustomerTag::class, 'customer_tag_assignments')
            ->withTimestamps();
    }

    public function marketingPreferences()
    {
        return $this->hasOne(CustomerMarketingPreferences::class);
    }

    public function statistics()
    {
        return $this->hasOne(CustomerStatistics::class);
    }

    public function acquisitionData()
    {
        return $this->hasOne(CustomerAcquisitionData::class);
    }

    public function metadata()
    {
        return $this->hasMany(CustomerMetadata::class);
    }

    public function addresses()
    {
        return $this->hasMany(CustomerAddress::class);
    }

    // Helper methods
    public function getFullNameAttribute(): string
    {
        return trim($this->first_name . ' ' . $this->last_name);
    }

    public function isActive(): bool
    {
        return $this->status === 'active';
    }

    public function isBusiness(): bool
    {
        return $this->customer_type === 'business';
    }

    public function getDefaultAddress()
    {
        return $this->addresses()->where('is_default', true)->first();
    }

    public function acceptsMarketing(): bool
    {
        return $this->marketingPreferences?->accepts_marketing ?? false;
    }

    public function defaultAddress(): HasOne
    {
        return $this->hasOne(CustomerAddress::class)->where('is_default', true);
    }

    public function orders(): HasMany
    {
        return $this->hasMany(Order::class);
    }

    public function loyaltyPoints(): HasMany
    {
        return $this->hasMany(CustomerLoyaltyPoint::class);
    }

    public function preferences(): HasOne
    {
        return $this->hasOne(CustomerPreferences::class);
    }

    public function referrals(): HasMany
    {
        return $this->hasMany(CustomerReferral::class, 'referrer_customer_id');
    }

    public function referredBy(): HasMany
    {
        return $this->hasMany(CustomerReferral::class, 'referred_customer_id');
    }

    public function supportTickets(): HasMany
    {
        return $this->hasMany(CustomerSupportTicket::class);
    }

    public function commissionCalculations(): HasMany
    {
        return $this->hasMany(CommissionCalculation::class);
    }

    public function scopeActive($query)
    {
        return $query->where('status', 'active');
    }

    public function scopeVerified($query)
    {
        return $query->whereNotNull('email_verified_at');
    }

    public function scopeByType($query, $type)
    {
        return $query->where('customer_type', $type);
    }

    // Methods
    public function getTotalLoyaltyPoints(): int
    {
        return $this->loyaltyPoints()->where('status', 'active')->sum('points');
    }

    public function getTotalSpent(): float
    {
        return $this->orders()->where('status', 'completed')->sum('total');
    }

    public function getOrderCount(): int
    {
        return $this->orders()->where('status', 'completed')->count();
    }

    public function isVip(): bool
    {
        return $this->customer_type === 'vip' || $this->getTotalSpent() > 10000;
    }

//    public function user()
//    {
//        return $this->belongsTo(User::class);
//    }
}
