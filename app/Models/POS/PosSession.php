<?php

namespace App\Models\POS;

use App\Models\Orders\Order;
use App\Models\User;
use App\Services\SellerContext;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

class PosSession extends Model
{

    protected $fillable = [
        'pos_register_id',
        'user_id',
        'opened_at',
        'closed_at',
        'opening_balance',
        'closing_balance',
        'cash_sales_total',
        'mpesa_sales_total',
        'other_sales_total',
        'notes',
        'status',
    ];

    protected $casts = [
        'opened_at' => 'datetime',
        'closed_at' => 'datetime',
        'opening_balance' => 'decimal:2',
        'closing_balance' => 'decimal:2',
        'cash_sales_total' => 'decimal:2',
        'mpesa_sales_total' => 'decimal:2',
        'other_sales_total' => 'decimal:2',
    ];

    public function register(): BelongsTo
    {
        return $this->belongsTo(PosRegister::class, 'pos_register_id');
    }

    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }

    public function orders(): HasMany
    {
        return $this->hasMany(Order::class);
    }

    /**
     * Scope sessions to those accessible by the given user.
     * Admins see all; seller users see only sessions on their seller's registers.
     */
    public function scopeForUser(Builder $query, ?User $user): Builder
    {
        if (! $user) {
            return $query->whereRaw('1 = 0');
        }

        if (SellerContext::isAdmin($user)) {
            return $query;
        }

        $sellerIds = SellerContext::sellerIds($user);

        if ($sellerIds->isEmpty()) {
            return $query->whereRaw('1 = 0');
        }

        return $query->whereHas('register', fn (Builder $q) => $q->whereIn('seller_id', $sellerIds));
    }
}
