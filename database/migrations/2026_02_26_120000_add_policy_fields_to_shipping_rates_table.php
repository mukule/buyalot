<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('shipping_rates', function (Blueprint $table) {
            $table->foreignId('zone_id')->nullable()->after('id')->constrained('zones')->nullOnDelete();
            $table->decimal('cod_min_amount', 12, 2)->nullable()->after('door_extra_km_cost')
                ->comment('Min order total (KSh) for Pay on Delivery; null = no restriction');
            $table->decimal('free_shipping_min_amount', 12, 2)->nullable()->after('cod_min_amount')
                ->comment('Min order total (KSh) for free shipping in this zone; null = no free shipping');
        });
    }

    public function down(): void
    {
        Schema::table('shipping_rates', function (Blueprint $table) {
            $table->dropConstrainedForeignId('zone_id');
            $table->dropColumn(['cod_min_amount', 'free_shipping_min_amount']);
        });
    }
};
