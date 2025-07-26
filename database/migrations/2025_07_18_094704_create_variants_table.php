<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up()
    {
        Schema::create('variants', function (Blueprint $table) {
            $table->id();

            $table->foreignId('variant_category_id')->constrained('variant_categories')->cascadeOnDelete();
            $table->string('value');
            $table->timestamps();

            $table->unique(['variant_category_id', 'value']);
        });
    }

    public function down()
    {
        Schema::dropIfExists('variants');
    }
};
