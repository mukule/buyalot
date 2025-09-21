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
        Schema::create('order_items', function (Blueprint $table) {
            $table->id();
            $table->foreignId('order_id')->constrained()->onDelete('cascade');
            $table->foreignId('product_variant_values_id')->constrained()->onDelete('cascade');
            $table->foreignId('seller_id')->constrained('sellers')->onDelete('cascade');

            $table->integer('quantity');
            $table->decimal('unit_price', 12, 2);
            $table->decimal('total_price', 12, 2);
            $table->decimal('discount_amount', 12, 2)->default(0);
            $table->decimal('tax_amount', 12, 2)->default(0);
            $table->foreignId('commission_id', 5, 2)->nullable();

            // Fulfillment
            $table->integer('quantity_fulfilled')->default(0);
            $table->integer('quantity_cancelled')->default(0);
            $table->integer('quantity_returned')->default(0);

            // Product snapshot at time of order
            $table->json('product_snapshot')->nullable();

            // Additional fields for tracking
            $table->json('metadata')->nullable();

            $table->timestamps();

            $table->index(['order_id', 'seller_id']);
            $table->index('product_variant_values');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('order_items');
    }
};
