<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/**
 * Reference data for the Cars & Motors marketplace vertical: the canonical list
 * of vehicle makes (brands) and their models. Powers the cascading make/model
 * selectors on the "post a car" form and the marketplace filters.
 */
return new class extends Migration
{
    public function up(): void
    {
        Schema::create('car_makes', function (Blueprint $table) {
            $table->id();
            $table->string('name')->unique();
            $table->string('slug')->unique();
            $table->boolean('active')->default(true);
            $table->unsignedInteger('sort_order')->default(0);
            $table->timestamps();
        });

        Schema::create('car_models', function (Blueprint $table) {
            $table->id();
            $table->foreignId('car_make_id')->constrained('car_makes')->cascadeOnDelete();
            $table->string('name');
            $table->string('slug');
            $table->boolean('active')->default(true);
            $table->timestamps();

            // A model name is unique within its make (e.g. Toyota → Corolla once).
            $table->unique(['car_make_id', 'slug']);
            $table->index('name');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('car_models');
        Schema::dropIfExists('car_makes');
    }
};
