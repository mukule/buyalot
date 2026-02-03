<?php

namespace App\Models\Warehouse;

use App\Models\Products\ProductVariant;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class WarehouseProductInventory extends Model
{

    use HasFactory;

    protected $fillable = [
        'warehouse_id',
        'product_variant_id',
        'stock',
        'reserved_stock',
        'damaged_stock',
        'regular_price',
        'selling_price',
        'cost_price',
    ];

    public function warehouse()
    {
        return $this->belongsTo(Warehouse::class);
    }

    public function productVariant()
    {
        return $this->belongsTo(ProductVariant::class);
    }
}
