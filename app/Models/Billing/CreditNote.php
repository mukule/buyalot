<?php

namespace App\Models\Billing;

use App\Domains\Invoicing\Enums\EtimsStatus;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class CreditNote extends Model
{
    protected $table = 'credit_notes';

    protected $fillable = [
        'invoice_id',
        'number',
        'amount_minor',
        'currency',
        'reason',
        'etims_status',
        'etims_reference',
        'meta',
    ];

    protected $casts = [
        'etims_status' => EtimsStatus::class,
        'meta' => 'array',
    ];

    public function invoice(): BelongsTo
    {
        return $this->belongsTo(Invoice::class);
    }
}
