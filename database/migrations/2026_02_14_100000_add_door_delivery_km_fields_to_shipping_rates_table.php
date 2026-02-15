<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('shipping_rates', function (Blueprint $table) {
            $table->decimal('door_fallback_price', 10, 2)->nullable()->after('door_price')->comment('Standard fallback price for first X km');
            $table->decimal('door_fallback_min_km', 8, 2)->nullable()->after('door_fallback_price')->comment('Min km that use fallback price');
            $table->decimal('door_extra_km_cost', 10, 2)->nullable()->after('door_fallback_min_km')->comment('Cost per km beyond fallback threshold');
        });

        // Populate from existing door_price: use as fallback, defaults for km pricing
        $rows = \Illuminate\Support\Facades\DB::table('shipping_rates')->get();
        foreach ($rows as $row) {
            $fallback = $row->door_price && $row->door_price > 0 ? (float) $row->door_price : 250;
            \Illuminate\Support\Facades\DB::table('shipping_rates')->where('id', $row->id)->update([
                'door_fallback_price' => $fallback,
                'door_fallback_min_km' => 10,
                'door_extra_km_cost' => 20,
            ]);
        }
    }

    public function down(): void
    {
        Schema::table('shipping_rates', function (Blueprint $table) {
            $table->dropColumn(['door_fallback_price', 'door_fallback_min_km', 'door_extra_km_cost']);
        });
    }
};
