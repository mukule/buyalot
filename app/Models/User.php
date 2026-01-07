<?php

namespace App\Models;


use App\Models\Seller\Seller;
use App\Models\Traits\HasHashid;
use App\Notifications\UserRegistered;
use App\Traits\CalculatesCommissions;
use App\Traits\HasCommissions;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Foundation\Auth\User as Authenticatable;
use Illuminate\Notifications\Notifiable;
use Laravel\Sanctum\HasApiTokens;
use Spatie\Permission\Traits\HasRoles;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\Relations\HasOne;


class User extends Authenticatable
{
    use HasFactory, HasApiTokens, Notifiable, HasRoles, HasCommissions,HasHashid,CalculatesCommissions;

    protected $fillable = [
        'name',
        'email',
        'password',
        'seller_application_id',
        'phone',
        'status',
        'google_id',
        'avatar',
        'provider',
        'provider_id',
        'email_verified_at',
        'last_login_at',
        'provider_verified_at',
        'user_type',
        'secondary_role',
    ];

    protected $hidden = [
        'password',
        'remember_token',
        'email_verified_at',
        'google_id',
        'provider_verified_at'
    ];

    protected function casts(): array
    {
        return [
            'email_verified_at' => 'datetime',
            'password' => 'hashed',
            'provider_verified_at'=>'datetime',
            'last_login_at'=>'datetime',
            'google_id'=>'string',
            'provider'=>'string',
            'provider_id'=>'string',
        ];
    }
    protected $appends = ['hashid'];

    protected static function booted(): void
    {
        static::created(function (User $user) {
            $user->notify(new UserRegistered());
        });
    }

    public function sellerApplication()
    {
        return $this->belongsTo(SellerApplication::class);
    }

    public function sellerDocuments()
    {
        return $this->hasMany(SellerDocument::class, 'user_id');
    }

    public function products()
    {
        return $this->hasMany(Product::class, 'owner_id');
    }

    public function wishlists(): HasMany
    {
        return $this->hasMany(Wishlist::class, 'customer_id');
    }

    public function customer(): HasOne
    {
        return $this->hasOne(\App\Models\Customer\Customer::class, 'user_id');
    }
    public function userDetail()
    {
        return $this->hasOne(UserDetail::class, 'user_id');
    }

    public function sellers()
    {
        return $this->belongsToMany(Seller::class, 'seller_user', 'user_id', 'seller_id')
            ->withPivot('role');
    }



    public function scopeForSeller(Builder $query, $sellerIds): Builder
    {
        $ids = collect($sellerIds)->flatten()->filter()->values();
        if ($ids->isEmpty()) {

            return $query->whereRaw('1 = 0');
        }
        $sellerTable = (new \App\Models\Seller\Seller())->getTable();
        return $query->whereHas('sellers', function (Builder $q) use ($ids, $sellerTable) {

            $q->whereIn($sellerTable . '.id', $ids);
        });
    }
}
