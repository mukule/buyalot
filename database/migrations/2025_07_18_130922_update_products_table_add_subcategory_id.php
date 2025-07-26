<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Run the migrations.
     */
   public function up()
{
    Schema::table('products', function (Blueprint $table) {
        $table->dropForeign(['category_id']);
        $table->dropColumn('category_id');

        $table->foreignId('subcategory_id')->nullable()->constrained()->nullOnDelete();
    });
}

public function down()
{
    Schema::table('products', function (Blueprint $table) {
        $table->dropForeign(['subcategory_id']);
        $table->dropColumn('subcategory_id');

        $table->foreignId('category_id')->nullable()->constrained()->nullOnDelete();
    });
}

};
