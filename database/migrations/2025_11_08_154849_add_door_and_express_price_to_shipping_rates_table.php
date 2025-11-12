<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('shipping_rates', function (Blueprint $table) {
            $table->decimal('door_price', 10, 2)->default(0)->after('base_price');
            $table->decimal('express_price', 10, 2)->default(0)->after('door_price');
        });
    }

    public function down(): void
    {
        Schema::table('shipping_rates', function (Blueprint $table) {
            $table->dropColumn(['door_price', 'express_price']);
        });
    }
};
