<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('shipping_rates', function (Blueprint $table) {
            $table->id();

            $table->unsignedTinyInteger('tier'); 
            $table->enum('package_size', ['small', 'medium', 'large'])->default('small');
            $table->decimal('base_price', 10, 2)->default(0.00);

            $table->timestamps();

            $table->unique(['tier', 'package_size'], 'unique_tier_size');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('shipping_rates');
    }
};
