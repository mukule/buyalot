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
        Schema::create('customer_wishlists', function (Blueprint $table) {
            $table->id();
            $table->foreignId('customer_id')->constrained('customers')->onDelete('cascade');
            $table->string('name')->default('My Wishlist');
            $table->text('description')->nullable();
            $table->enum('visibility', ['private', 'shared', 'public'])->default('private');
            $table->string('share_token')->nullable()->unique();
            $table->boolean('is_default')->default(false);
            $table->integer('items_count')->default(0);
            $table->timestamps();

            $table->index(['customer_id', 'is_default']);
            $table->index(['customer_id', 'visibility']);
            $table->index('share_token');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('customer_wishlists');
    }
};
