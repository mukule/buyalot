<?php

namespace App\Models\Customer;

use App\Models\Commission\CommissionCalculation;
use App\Models\Orders\Order;
use App\Models\Traits\HasHashid;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\Relations\HasOne;
use Illuminate\Database\Eloquent\SoftDeletes;
use Laravel\Sanctum\HasApiTokens;

class Customer extends Model
{
    use HasFactory, SoftDeletes, HasApiTokens, HasHashid;

    protected $fillable = [
        'first_name', 'last_name', 'email', 'phone', 'date_of_birth',
        'gender', 'customer_type', 'status', 'email_verified_at',
        'phone_verified_at', 'avatar', 'language', 'timezone',
        'marketing_consent', 'last_login_at', 'registration_source',
        'customer_since', 'notes'
    ];

    protected $casts = [
        'date_of_birth' => 'date',
        'email_verified_at' => 'datetime',
        'phone_verified_at' => 'datetime',
        'marketing_consent' => 'boolean',
        'last_login_at' => 'datetime',
        'customer_since' => 'datetime',
    ];

    protected $hidden = [
        'remember_token',
    ];

    // Relationships
    public function addresses(): HasMany
    {
        return $this->hasMany(CustomerAddress::class);
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

    public function wishlistItems(): HasMany
    {
        return $this->hasMany(CustomerWishlistItem::class);
    }

    public function commissionCalculations(): HasMany
    {
        return $this->hasMany(CommissionCalculation::class);
    }

    // Accessors & Mutators
    public function getFullNameAttribute(): string
    {
        return trim($this->first_name . ' ' . $this->last_name);
    }

    public function getInitialsAttribute(): string
    {
        return strtoupper(substr($this->first_name, 0, 1) . substr($this->last_name, 0, 1));
    }

    // Scopes
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
}
