<?php

namespace App\Models;

use App\Models\Products\Product;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Vinkla\Hashids\Facades\Hashids;

class Warranty extends Model
{
    use HasFactory;

    protected $table = 'warranties';

    protected $fillable = [
        'product_id',
        'duration',
        'description',
        'active',
    ];

    protected $casts = [
        'active' => 'boolean',
    ];


    protected $appends = ['hashid'];


    public function product(): BelongsTo
    {
        return $this->belongsTo(Product::class);
    }


    public function getHashidAttribute(): string
    {
        return Hashids::encode($this->id);
    }


    public function getRouteKey(): string
    {
        return $this->hashid;
    }


    public function resolveRouteBinding($value, $field = null)
    {
        $decoded = Hashids::decode($value);

        if (count($decoded) !== 1) {
            abort(404);
        }

        return $this->where('id', $decoded[0])->firstOrFail();
    }
}
