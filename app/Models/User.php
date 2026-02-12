<?php

namespace App\Models;


use App\Models\Products\Product;
use App\Models\Seller\Seller;
use App\Models\Traits\HasHashid;
use App\Notifications\UserRegistered;
use App\Traits\CalculatesCommissions;
use App\Traits\HasCommissions;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\Relations\HasOne;
use Illuminate\Foundation\Auth\User as Authenticatable;
use Illuminate\Notifications\Notifiable;
use Laravel\Sanctum\HasApiTokens;
use Spatie\Permission\Traits\HasRoles;


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
        'additional_roles',
        'delivery_application_id',
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
            'additional_roles' => 'array',
        ];
    }
    protected $appends = ['hashid'];

    /**
     * Portal role identifiers used for switching (customer, seller, distributor).
     * 'vendor' is normalized to 'seller'.
     */
    public const PORTAL_ROLES = ['customer', 'seller', 'distributor'];

    /**
     * All portal roles this user has (user_type + additional_roles, with backward compat for secondary_role).
     *
     * @return array<int, string>
     */
    public function getPortalRoles(): array
    {
        $primary = $this->getRawOriginal('user_type') ?? $this->user_type;
        $primary = $primary === 'vendor' ? 'seller' : $primary;

        $extra = $this->additional_roles;
        if (! is_array($extra) && $this->secondary_role) {
            $extra = [$this->secondary_role];
        }
        if (! is_array($extra)) {
            $extra = [];
        }
        $extra = array_map(fn ($r) => $r === 'vendor' ? 'seller' : $r, $extra);

        $all = array_values(array_unique(array_merge([$primary], $extra)));
        return array_values(array_intersect($all, self::PORTAL_ROLES));
    }

    /**
     * Whether this user has a given portal role (customer, seller, or distributor).
     */
    public function hasPortalRole(string $role): bool
    {
        $role = $role === 'vendor' ? 'seller' : $role;
        return in_array($role, $this->getPortalRoles(), true);
    }

    protected static function booted(): void
    {
        static::created(function (User $user) {
            if ($user->delivery_application_id) {
                return;
            }
            $user->notify(new UserRegistered());
        });
    }

    public function sellerApplication(): \Illuminate\Database\Eloquent\Relations\BelongsTo
    {
        return $this->belongsTo(SellerApplication::class);
    }

    public function deliveryApplication(): \Illuminate\Database\Eloquent\Relations\BelongsTo
    {
        return $this->belongsTo(DeliveryPersonApplication::class, 'delivery_application_id');
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
