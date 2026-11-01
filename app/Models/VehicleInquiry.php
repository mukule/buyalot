<?php

namespace App\Models;

use App\Models\Products\Product;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class VehicleInquiry extends Model
{
    const STATUS_DELIVERED = 'delivered';
    const STATUS_BLOCKED   = 'blocked';

    const TYPE_MESSAGE  = 'message';
    const TYPE_CALLBACK = 'callback';
    const TYPE_OFFER    = 'offer';
    const TYPES = [self::TYPE_MESSAGE, self::TYPE_CALLBACK, self::TYPE_OFFER];

    protected $fillable = [
        'product_id',
        'user_id',
        'seller_id',
        'type',
        'offer_amount',
        'name',
        'email',
        'phone',
        'message',
        'status',
        'moderation_checked',
        'moderation_reason',
    ];

    protected $casts = [
        'moderation_checked' => 'boolean',
        'offer_amount'       => 'decimal:2',
    ];

    public function product(): BelongsTo
    {
        return $this->belongsTo(Product::class)->withoutGlobalScopes();
    }

    public function buyer(): BelongsTo
    {
        return $this->belongsTo(User::class, 'user_id');
    }

    public function seller(): BelongsTo
    {
        return $this->belongsTo(User::class, 'seller_id');
    }
}
