<?php

namespace App\Models\POS;

use App\Models\Warehouse\Warehouse;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

class PosRegister extends Model
{

    protected $fillable = [
        'name',
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
}
