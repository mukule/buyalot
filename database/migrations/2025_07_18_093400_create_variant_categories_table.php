<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up()
    {
        Schema::create('variant_categories', function (Blueprint $table) {
            $table->id();
            $table->string('name')->unique(); 
            $table->timestamps();
        });

        
        DB::table('variant_categories')->insert([
            'name' => 'Color',
            'created_at' => now(),
            'updated_at' => now(),
        ]);
    }

    public function down()
    {
        Schema::dropIfExists('variant_categories');
    }
};
