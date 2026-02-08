<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('warehouse_receivables', function (Blueprint $table) {
            $table->foreignId('order_return_id')->nullable()->after('order_id')
                ->constrained('order_returns')->nullOnDelete();
        });
    }

    public function down(): void
    {
        Schema::table('warehouse_receivables', function (Blueprint $table) {
            $table->dropForeign(['order_return_id']);
        });
    }
};
