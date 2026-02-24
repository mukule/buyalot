<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Run the migrations.
     * Each seller/branch account has their own terminals. Terminals are scoped by seller_id.
     */
    public function up(): void
    {
        Schema::table('pos_registers', function (Blueprint $table) {
            $table->foreignId('seller_id')
                ->nullable()
                ->after('id')
                ->constrained('seller_applications')
                ->nullOnDelete();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('pos_registers', function (Blueprint $table) {
            $table->dropConstrainedForeignId('seller_id');
        });
    }
};
