<?php

namespace App\Models;

use App\Models\Products\Product;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class ProductReport extends Model
{
    /** Reasons offered on the "Report abuse" form (key => label). */
    const REASONS = [
        'scam'           => 'Scam / fraud',
        'wrong_info'     => 'Wrong or misleading information',
        'wrong_price'    => 'Wrong price',
        'already_sold'   => 'Already sold / unavailable',
        'seller_unreachable' => 'Seller unreachable',
        'offensive'      => 'Offensive content',
        'other'          => 'Other',
    ];

    protected $fillable = ['product_id', 'user_id', 'reason', 'comment', 'status'];

    public function product(): BelongsTo
    {
        return $this->belongsTo(Product::class)->withoutGlobalScopes();
    }

    public function reporter(): BelongsTo
    {
        return $this->belongsTo(User::class, 'user_id');
    }
}
