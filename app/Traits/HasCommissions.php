<?php

namespace App\Traits;

use App\Models\Commission\CommissionCalculation;
use App\Models\Commission\CommissionInvoice;
use App\Models\Commission\SellerSubscription;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\Relations\HasOne;

/**
 * @method hasOne(string $class, string $string)
 * @method hasMany(string $class, string $string)
 */
trait HasCommissions
{
    public function subscription(): HasOne
    {
        return $this->hasOne(SellerSubscription::class, 'seller_id')->active();
    }

    public function subscriptions(): HasMany
    {
        return $this->hasMany(SellerSubscription::class, 'seller_id');
    }

    public function commissionCalculations(): HasMany
    {
        return $this->hasMany(CommissionCalculation::class, 'seller_id');
    }

    public function commissionInvoices(): HasMany
    {
        return $this->hasMany(CommissionInvoice::class, 'seller_id');
    }

    public function getTotalCommissionsDue(): float
    {
        return $this->commissionCalculations()
            ->where('status', 'confirmed')
            ->sum('commission_amount');
    }

    public function getTotalCommissionsPaid(): float
    {
        return $this->commissionInvoices()
            ->where('status', 'paid')
            ->sum('total_amount');
    }

    public function getCommissionBalance(): float
    {
        return $this->getTotalCommissionsDue() - $this->getTotalCommissionsPaid();
    }

    public function hasActiveSubscription(): bool
    {
        return $this->subscription !== null;
    }
}
