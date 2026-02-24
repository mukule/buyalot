<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('customer_addresses', function (Blueprint $table) {
            if (!Schema::hasColumn('customer_addresses', 'region_id')) {
                $table->foreignId('region_id')->nullable()->after('customer_id')->constrained('regions')->nullOnDelete();
            }
        });
    }

    public function down(): void
    {
        Schema::table('customer_addresses', function (Blueprint $table) {
            $table->dropForeign(['region_id']);
        });
    }
};
