<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
   
    public function up(): void
    {
        Schema::create('policy_versions', function (Blueprint $table) {
            $table->id();
            $table->foreignId('policy_id')
                  ->constrained('policies')
                  ->onDelete('cascade');
            $table->integer('version_number')->default(1);
            $table->text('content'); 
            $table->timestamp('effective_from')->nullable();
            $table->boolean('status')->default(true); 
            $table->timestamps();
        });
    }

   
    public function down(): void
    {
        Schema::dropIfExists('policy_versions');
    }
};
