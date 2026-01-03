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
        Schema::table('pos_registers', function (Blueprint $table) {
            $table->string('receipt_type')->default('thermal'); // thermal, standard
            $table->string('invoice_type')->default('standard'); // standard, simplified
            $table->boolean('auto_print_receipt')->default(false);
            $table->json('settings')->nullable();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('pos_registers', function (Blueprint $table) {
            $table->dropColumn(['receipt_type', 'invoice_type', 'auto_print_receipt', 'settings']);
        });
    }
};
