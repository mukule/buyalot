<?php
namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class SellerApplicationImage extends Model
{
    protected $fillable = [
        'seller_application_id',
        'path',
        'original_name',
    ];

  
    public function application(): BelongsTo
    {
        return $this->belongsTo(SellerApplication::class, 'seller_application_id');
    }
}
