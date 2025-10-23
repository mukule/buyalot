<?php

namespace App\Traits;

use App\Models\Payment\Payment;
use Illuminate\Database\Eloquent\Relations\MorphMany;

trait HasPayments
{
    public function payments(): MorphMany
    {
        return $this->morphMany(Payment::class, 'payable');
    }

    public function getCompletedPaymentsAttribute()
    {
        return $this->payments()->where('status', 'completed')->get();
    }

    public function getTotalPaidAttribute(): float
    {
        return $this->payments()
            ->where('status', 'completed')
            ->sum('amount');
    }
}
