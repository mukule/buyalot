<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration {
    public function up(): void
    {
        if (!Schema::hasTable('cart_reservations')) {
            Schema::create('cart_reservations', function (Blueprint $table) {
                $table->id();
                $table->foreignId('cart_id')->constrained('carts')->cascadeOnDelete();
                $table->foreignId('product_variant_id')->constrained('product_variants')->cascadeOnDelete();
                $table->unsignedInteger('quantity');
                $table->dateTime('expires_at');
                $table->timestamps();
                $table->unique(['cart_id', 'product_variant_id']);
                $table->index(['product_variant_id', 'expires_at']);
            });
        }
    }

    public function down(): void
    {
        Schema::dropIfExists('cart_reservations');
    }
};
