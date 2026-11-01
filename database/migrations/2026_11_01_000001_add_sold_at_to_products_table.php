<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

/**
 * Cars & Motors vehicle listings have a listing status instead of stock:
 * available (default) / reserved (reserved_at) / sold (sold_at).
 */
return new class extends Migration
{
    public function up(): void
    {
        Schema::table('products', function (Blueprint $table) {
            if (! Schema::hasColumn('products', 'sold_at')) {
                $table->timestamp('sold_at')->nullable()->after('reserved_at');
            }
        });

        // Vehicles are one-off listings: give existing ones a stock of 1 so the
        // shared cart/listing code never treats them as "out of stock".
        $vehicleIds = DB::table('products')
            ->whereJsonContains('marketplaces', 'cars')
            ->where(fn ($q) => $q->whereNull('attributes->listing_type')->orWhere('attributes->listing_type', 'vehicle'))
            ->pluck('id');

        if ($vehicleIds->isNotEmpty()) {
            DB::table('product_variants')
                ->whereIn('product_id', $vehicleIds)
                ->where('stock', '<', 1)
                ->update(['stock' => 1]);
        }
    }

    public function down(): void
    {
        Schema::table('products', function (Blueprint $table) {
            if (Schema::hasColumn('products', 'sold_at')) {
                $table->dropColumn('sold_at');
            }
        });
    }
};
