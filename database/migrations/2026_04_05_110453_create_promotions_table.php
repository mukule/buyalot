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
        Schema::create('promotions', function (Blueprint $table) {
            $table->id();

            $table->string('title')->nullable();
            $table->string('image');

            // where the banner appears (homepage_hero, sidebar, category_top etc)
            $table->string('position');

            // what the banner links to: category, product, or flash_sale
            $table->enum('link_type', ['category', 'product', 'flash_sale'])->default('category');
            $table->unsignedBigInteger('link_id')->nullable();

            // scheduling
            $table->timestamp('start_date')->nullable();
            $table->timestamp('end_date')->nullable();

            // ordering
            $table->integer('priority')->default(0);

            // activation
            $table->boolean('is_active')->default(true);

            $table->timestamps();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('promotions');
    }
};