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
        Schema::create('pos_settings', function (Blueprint $table) {
            $table->id();
            $table->string('business_name')->nullable();
            $table->string('business_address')->nullable();
            $table->string('business_phone')->nullable();
            $table->string('business_email')->nullable();
            $table->string('tax_number')->nullable();
            $table->decimal('vat_percentage', 5, 2)->default(0);
            $table->boolean('vat_enabled')->default(false);
            $table->string('currency_symbol')->default('KES');
            $table->text('receipt_header')->nullable();
            $table->text('receipt_footer')->nullable();
            $table->string('invoice_prefix')->default('INV-');
            $table->string('receipt_prefix')->default('RCPT-');
            $table->json('metadata')->nullable();
            $table->timestamps();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('pos_settings');
    }
};
