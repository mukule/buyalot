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
        Schema::create('cart_items', function (Blueprint $table) {
            $table->id();
            $table->foreignId('cart_id')->constrained()->onDelete('cascade'); 
            $table->foreignId('product_variant_id')->constrained()->onDelete('cascade'); 
            $table->integer('quantity')->default(1);
            $table->decimal('regular_price', 12, 2); 
            $table->decimal('selling_price', 12, 2); 
            $table->decimal('discount', 12, 2)->default(0); 
            $table->timestamps();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('cart_items');
    }
};
