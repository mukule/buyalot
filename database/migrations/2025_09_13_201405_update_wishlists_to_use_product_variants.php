<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        // Drop the existing table
        Schema::dropIfExists('wishlists');

        // Recreate the table fresh
        Schema::create('wishlists', function (Blueprint $table) {
            $table->id();
            $table->foreignId('user_id')->constrained()->onDelete('cascade');
            $table->foreignId('product_variant_id')->constrained('product_variants')->onDelete('cascade');
            $table->timestamps();

            // Avoid duplicate wishlist entries
            $table->unique(['user_id', 'product_variant_id']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('wishlists');
    }
};
