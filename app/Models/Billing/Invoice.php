<?php

namespace App\Models\Billing;

use App\Domains\Invoicing\Enums\EtimsStatus;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

class Invoice extends Model
{
    protected $table = 'invoices';

    protected $fillable = [
        'seller_id',
        'buyer_id',
        'buyer_name',
        'buyer_kra_pin',
        'buyer_email',
        'buyer_phone',
        'order_id',
        'number',
        'type',
        'status',
        'issue_date',
        'due_date',
        'currency',
        'fx_rate',
        'subtotal_minor',
        'discount_minor',
        'tax_minor',
        'total_minor',
        'balance_minor',
        'customer_po',
        'reference',
        'billing_address',
        'shipping_address',
        'meta',
        'etims_status',
        'etims_reference',
    ];

    protected $casts = [
        'issue_date' => 'date',
        'due_date' => 'date',
        'billing_address' => 'array',
        'shipping_address' => 'array',
        'meta' => 'array',
        'etims_status' => EtimsStatus::class,
    ];

    public function items(): HasMany
    {
        return $this->hasMany(InvoiceItem::class);
    }

    public function creditNotes(): HasMany
    {
        return $this->hasMany(CreditNote::class);
    }

    public function deliveryNotes(): HasMany
    {
        return $this->hasMany(DeliveryNote::class);
    }

    public function seller(): BelongsTo
    {
        return $this->belongsTo(\App\Models\Seller\SellerAccount::class, 'seller_id');
    }

    public function buyer(): BelongsTo
    {
        return $this->belongsTo(\App\Models\User::class, 'buyer_id');
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
