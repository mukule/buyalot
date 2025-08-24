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
        Schema::create('customer_addresses', function (Blueprint $table) {
                $table->id();
                $table->foreignId('customer_id')->constrained('customers')->onDelete('cascade');
                $table->string('type')->default('shipping'); // shipping, billing, both
                $table->string('label')->nullable(); // Home, Work,
                $table->string('first_name');
                $table->string('last_name');
                $table->string('company')->nullable();
                $table->string('address_line_1');
                $table->string('address_line_2')->nullable();
                $table->string('city');
                $table->string('state_province');
                $table->string('postal_code');
                $table->string('country_code', 2);
                $table->string('country_name');
                $table->string('phone')->nullable();
                $table->decimal('latitude', 10, 8)->nullable();
                $table->decimal('longitude', 11, 8)->nullable();
                $table->boolean('is_default')->default(false);
                $table->boolean('is_validated')->default(false);
                $table->json('validation_data')->nullable();
                $table->text('delivery_instructions')->nullable();
                $table->timestamps();

                $table->index(['customer_id', 'type']);
                $table->index(['customer_id', 'is_default']);
                $table->index(['country_code', 'state_province']);
                $table->index(['postal_code', 'country_code']);
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('customer_addresses');
    }
};
