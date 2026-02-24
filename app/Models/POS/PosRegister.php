<?php

namespace App\Models\POS;

use App\Models\User;
use App\Models\Warehouse\Warehouse;
use App\Services\SellerContext;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

class PosRegister extends Model
{

    protected $fillable = [
        'name',
        'seller_id',
        'warehouse_id',
        'status',
        'receipt_type',
        'invoice_type',
        'auto_print_receipt',
        'settings',
    ];

    protected $casts = [
        'auto_print_receipt' => 'boolean',
        'settings' => 'json',
    ];

    public function seller(): BelongsTo
    {
        return $this->belongsTo(\App\Models\Seller\Seller::class, 'seller_id');
    }

    public function warehouse(): BelongsTo
    {
        return $this->belongsTo(Warehouse::class);
    }

    public function sessions(): HasMany
    {
        return $this->hasMany(PosSession::class);
    }

    public function activeSession()
    {
        return $this->hasOne(PosSession::class)->where('status', 'open');
    }

    /**
     * Scope registers to those accessible by the given user (their branch/seller account).
     * Admins see all; seller users see only registers belonging to their seller(s).
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

        return $query->whereIn('seller_id', $sellerIds);
    }
}
