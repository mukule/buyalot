<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Vinkla\Hashids\Facades\Hashids;

class ProductStatus extends Model
{
    protected $table = 'product_statuses';

    protected $fillable = [
        'name',
        'label',
        'color_class',
    ];

    // Append hashid to model attributes
    protected $appends = ['hashid'];

    /**
     * Hashid accessor
     */
    public function getHashidAttribute(): string
    {
        return Hashids::encode($this->id);
    }

    /**
     * Route model binding key
     */
    public function getRouteKey(): string
    {
        return $this->hashid;
    }

    /**
     * Resolve route binding using hashid
     */
    public function resolveRouteBinding($value, $field = null): ?Model
    {
        $decoded = Hashids::decode($value);

        if (count($decoded) !== 1) {
            abort(404);
        }

        return $this->where('id', $decoded[0])->firstOrFail();
    }

    /**
     * Relationship to products
     */
    public function products(): HasMany
    {
        return $this->hasMany(Product::class, 'status_id');
    }
}
