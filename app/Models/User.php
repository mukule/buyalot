<?php

namespace App\Models;


use App\Notifications\UserRegistered;
use App\Traits\CalculatesCommissions;
use App\Traits\HasCommissions;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Foundation\Auth\User as Authenticatable;
use Illuminate\Notifications\Notifiable;
use Spatie\Permission\Traits\HasRoles;

class User extends Authenticatable
{
    use HasFactory, Notifiable, HasRoles, HasCommissions, CalculatesCommissions;

    protected $fillable = [
        'name',
        'email',
        'password',
        'seller_application_id',
        'phone',
        'gender',
        'status',
        'google_id',
        'avatar',
    ];

    protected $hidden = [
        'password',
        'remember_token',
    ];

    protected function casts(): array
    {
        return [
            'email_verified_at' => 'datetime',
            'password' => 'hashed',
        ];
    }

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
        return $this->hasMany(SellerDocument::class);
    }
}
