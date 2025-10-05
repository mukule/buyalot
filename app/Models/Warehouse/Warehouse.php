<?php

namespace App\Models\Warehouse;

use App\Models\Region;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Str;
use Vinkla\Hashids\Facades\Hashids;

class Warehouse extends Model
{
    protected $fillable = [
        'code',
        'name',
        'slug',
        'type',
        'region_id',
        'parent_warehouse_id',
        'email',
        'address',
        'location',
        'active',
        'is_default',
        'capacity',
        'supports_pos',
        'supports_pickup',
        'supports_delivery',
    ];

    protected $casts = [
        'active' => 'boolean',
        'is_default' => 'boolean',
        'supports_pos' => 'boolean',
        'supports_pickup' => 'boolean',
        'supports_delivery' => 'boolean',
    ];

    protected $appends = ['hashid'];

    /*
    |--------------------------------------------------------------------------
    | Boot Hooks
    |--------------------------------------------------------------------------
    */
    public static function boot()
    {
        parent::boot();

        static::creating(function ($warehouse) {
            // Generate unique slug if not set
            if (empty($warehouse->slug)) {
                $warehouse->slug = static::generateUniqueSlug($warehouse->name);
            }
            if (empty($warehouse->code)) {
                $warehouse->code = static::generateUniqueCode();
            }
        });

        static::updating(function ($warehouse) {
            if ($warehouse->isDirty('name')) {
                $warehouse->slug = static::generateUniqueSlug($warehouse->name, $warehouse->id);
            }
        });
    }

    /*
    |--------------------------------------------------------------------------
    | Helpers
    |--------------------------------------------------------------------------
    */

    protected static function generateUniqueSlug(string $name, ?int $ignoreId = null): string
    {
        $slug = Str::slug($name);
        $originalSlug = $slug;
        $counter = 1;

        while (
        static::where('slug', $slug)
            ->when($ignoreId, fn($query) => $query->where('id', '!=', $ignoreId))
            ->exists()
        ) {
            $slug = $originalSlug . '-' . $counter++;
        }

        return $slug;
    }

    protected static function generateUniqueCode(): string
    {
        do {
            $code = 'WH-' . strtoupper(Str::random(6));
        } while (static::where('code', $code)->exists());

        return $code;
    }

    /*
    |--------------------------------------------------------------------------
    | Accessors
    |--------------------------------------------------------------------------
    */

    public function getRouteKey(): string
    {
        return Hashids::encode($this->id);
    }

    public function getHashidAttribute(): string
    {
        return Hashids::encode($this->id);
    }

    public function resolveRouteBinding($value, $field = null): ?Model
    {
        $decoded = Hashids::decode($value);
        if (count($decoded) !== 1) {
            abort(404);
        }

        return $this->where('id', $decoded[0])->firstOrFail();
    }
    public function managers()
    {
        return $this->hasMany(WarehouseManager::class);
    }
    public function region()
    {
        return $this->belongsTo(Region::class);
    }
    public function parent()
    {
        return $this->belongsTo(Warehouse::class, 'parent_warehouse_id');
    }
    public function children()
    {
        return $this->hasMany(Warehouse::class, 'parent_warehouse_id');
    }
}
