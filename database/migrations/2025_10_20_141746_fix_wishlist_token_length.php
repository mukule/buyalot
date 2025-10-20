<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;
use Illuminate\Support\Str;

return new class extends Migration
{
    public function up()
    {
       
        Schema::table('wishlists', function (Blueprint $table) {
            $table->string('wishlist_token', 512)->nullable()->change();
        });

        
        $wishlists = \App\Models\Wishlist::whereNotNull('wishlist_token')
            ->whereRaw('LENGTH(wishlist_token) > 36')
            ->get();

        foreach ($wishlists as $wishlist) {
           
            $wishlist->update([
                'wishlist_token' => Str::uuid()->toString()
            ]);
        }

        
        Schema::table('wishlists', function (Blueprint $table) {
            $table->string('wishlist_token', 36)->nullable()->change();
        });
    }

    public function down()
    {
        // In case of rollback, we can't recover the original encrypted tokens
        // but we can set the column size back
        Schema::table('wishlists', function (Blueprint $table) {
            $table->string('wishlist_token', 255)->nullable()->change();
        });
    }
};