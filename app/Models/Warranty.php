<?php

namespace App\Models;

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

    // Automatically append hashid to model's array/json representation
    protected $appends = ['hashid'];

    /**
     * Relation to Product
     */
    public function product(): BelongsTo
    {
        return $this->belongsTo(Product::class);
    }

    /**
     * Accessor for hashid
     */
    public function getHashidAttribute(): string
    {
        return Hashids::encode($this->id);
    }

    /**
     * Use hashid for route key
     */
    public function getRouteKey(): string
    {
        return $this->hashid;
    }

    /**
     * Resolve binding from hashid
     */
    public function resolveRouteBinding($value, $field = null)
    {
        $decoded = Hashids::decode($value);

        if (count($decoded) !== 1) {
            abort(404);
        }

        return $this->where('id', $decoded[0])->firstOrFail();
    }
}
