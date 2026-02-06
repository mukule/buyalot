<?php

namespace App\Models\Billing;

use App\Domains\Invoicing\Enums\EtimsStatus;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

class DeliveryNote extends Model
{
    protected $table = 'delivery_notes';

    protected $fillable = [
        'invoice_id',
        'number',
        'delivery_address',
        'status',
        'etims_status',
        'etims_reference',
        'notes',
        'meta',
    ];

    protected $casts = [
        'delivery_address' => 'array',
        'etims_status' => EtimsStatus::class,
        'meta' => 'array',
    ];

    public function invoice(): BelongsTo
    {
        return $this->belongsTo(Invoice::class);
    }

    public function items(): HasMany
    {
        return $this->hasMany(DeliveryNoteItem::class);
    }
}
