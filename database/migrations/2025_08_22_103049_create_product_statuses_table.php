<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('product_statuses', function (Blueprint $table) {
            $table->id();
            $table->string('name')->unique(); 
            $table->string('label');          
            $table->string('color_class')->nullable(); 
            $table->timestamps();
        });

        // Optionally, add a foreign key in products table
        Schema::table('products', function (Blueprint $table) {
            $table->foreignId('status_id')
                  ->after('status') // optional: after the old integer column
                  ->nullable()
                  ->constrained('product_statuses')
                  ->nullOnDelete();
        });
    }

    public function down(): void
    {
        Schema::table('products', function (Blueprint $table) {
            $table->dropForeign(['status_id']);
            $table->dropColumn('status_id');
        });

        Schema::dropIfExists('product_statuses');
    }
};
