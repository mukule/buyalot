<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('countries', function (Blueprint $table) {
            $table->id();
            $table->char('iso2', 2)->unique();
            $table->string('name')->nullable();
            $table->string('flag_path')->nullable(); // stored relative to public storage disk (e.g., flags/KE.png)
            $table->timestamps();

            $table->index('iso2');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('countries');
    }
};
