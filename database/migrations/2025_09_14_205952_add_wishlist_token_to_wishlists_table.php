<?php


use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration {
    public function up(): void
    {
        Schema::table('wishlists', function (Blueprint $table) {
            // Make user_id nullable for guest entries
            $table->unsignedBigInteger('user_id')->nullable()->change();

            // Add wishlist_token for guests
            $table->uuid('wishlist_token')->nullable()->index()->after('user_id');
        });
    }

    public function down(): void
    {
        Schema::table('wishlists', function (Blueprint $table) {
            $table->dropColumn('wishlist_token');
            $table->unsignedBigInteger('user_id')->nullable(false)->change();
        });
    }
};
