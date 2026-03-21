<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Run the migrations.
     */
    public function up(): void
    {
        Schema::table('shipping_rates', function (Blueprint $table) {
            $table->decimal('max_shipping_fee', 12, 2)->nullable()->after('free_shipping_min_amount')
                ->comment('Maximum shipping fee cap (KSh) for any order; null = no cap');
        });
    }

    public function down(): void
    {
        Schema::table('shipping_rates', function (Blueprint $table) {
            $table->dropColumn('max_shipping_fee');
        });
    }
};
