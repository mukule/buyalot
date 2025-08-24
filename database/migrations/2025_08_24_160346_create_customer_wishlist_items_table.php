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
        Schema::create('customer_wishlist_items', function (Blueprint $table) {
            $table->id();
            $table->foreignId('wishlist_id')->constrained('customer_wishlists')->onDelete('cascade');
            $table->string('wishable_type'); // products, product_variants
            $table->unsignedBigInteger('wishable_id');
            $table->integer('quantity')->default(1);
            $table->decimal('price_when_added', 10, 2)->nullable();
            $table->text('notes')->nullable();
            $table->integer('priority')->default(0); // 1-5 priority rating
            $table->boolean('notify_on_sale')->default(false);
            $table->boolean('notify_on_restock')->default(false);
            $table->timestamp('last_viewed_at')->nullable();
            $table->timestamps();

            $table->index(['wishable_type', 'wishable_id']);
            $table->index(['wishlist_id', 'created_at']);
            $table->unique(['wishlist_id', 'wishable_type', 'wishable_id'], 'wishlist_item_unique');

        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('customer_wishlist_items');
    }
};
