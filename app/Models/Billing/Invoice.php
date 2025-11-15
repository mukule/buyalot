<?php

namespace App\Models\Billing;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

class Invoice extends Model
{
    protected $table = 'invoices';
    protected $guarded = [];

    protected $casts = [
        'issue_date' => 'date',
        'due_date' => 'date',
        'billing_address' => 'array',
        'shipping_address' => 'array',
        'meta' => 'array',
    ];

    public function items(): HasMany
    {
        return $this->hasMany(InvoiceItem::class);
    }

    public function seller(): BelongsTo
    {
        // maps to sellers table via SellerAccount
        return $this->belongsTo(\App\Models\Seller\SellerAccount::class, 'seller_id');
    }

    public function markIssued(): void
    {
        if ($this->status !== 'draft') {
            return;
        }
        $this->status = 'issued';
        $this->issue_date = now()->toDateString();
        $this->save();
    }

    public function applyAllocation(int $amountMinor): void
    {
        $newBalance = max(0, (int)$this->balance_minor - (int)$amountMinor);
        $this->balance_minor = $newBalance;
        if ($newBalance === 0) {
            $this->status = 'paid';
        } else {
            $this->status = 'partially_paid';
        }
        $this->save();
    }
}
