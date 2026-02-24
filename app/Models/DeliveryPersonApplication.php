<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class DeliveryPersonApplication extends Model
{
    public const STATUS_PENDING = 'pending';
    public const STATUS_APPROVED = 'approved';
    public const STATUS_REJECTED = 'rejected';

    public const TRANSPORT_TYPES = [
        'car',
        'truck',
        'van',
        'motorbike',
        'bicycle',
        'electric_bike',
        'other',
    ];

    protected $fillable = [
        'user_id',
        'name',
        'email',
        'phone',
        'id_number',
        'id_copy_path',
        'kra_pin',
        'kra_copy_path',
        'address',
        'transport_type',
        'transport_registration_number',
        'transport_details',
        'status',
        'rejection_reason',
        'reviewed_at',
        'reviewed_by',
    ];

    protected function casts(): array
    {
        return [
            'transport_details' => 'array',
            'reviewed_at' => 'datetime',
        ];
    }

    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }

    public function reviewedBy(): BelongsTo
    {
        return $this->belongsTo(User::class, 'reviewed_by');
    }

    public function isPending(): bool
    {
        return $this->status === self::STATUS_PENDING;
    }

    public function isApproved(): bool
    {
        return $this->status === self::STATUS_APPROVED;
    }

    public function isRejected(): bool
    {
        return $this->status === self::STATUS_REJECTED;
    }
}
