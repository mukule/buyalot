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
        Schema::table('warehouse_managers', function (Blueprint $table) {
            $table->string('role', 255)->nullable(false)->change();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('warehouse_managers', function (Blueprint $table) {
            $table->enum('role', [
                'manager',
                'store_manager',
                'assistant_store_manager',
            ])->change();
        });
    }
};
