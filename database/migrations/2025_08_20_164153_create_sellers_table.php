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
        Schema::create('sellers', function (Blueprint $table) {
            $table->id();
            $table->string('name');
            $table->string('slug')->unique();
            $table->string('email')->nullable();
            $table->string('phone')->nullable();
            $table->string('business_description')->nullable();
            $table->string('slogan')->nullable();
            $table->string('logo')->nullable();
            $table->text('address')->nullable();
            $table->string('country')->nullable();
            $table->string('currency')->default('KES');
            $table->boolean('is_active')->default(true);
            $table->timestamps();
        });
        Schema::create('seller_user', function (Blueprint $table) {
            $table->id();
            $table->foreignId('seller_id')->constrained()->cascadeOnDelete();
            $table->foreignId('user_id')->constrained()->cascadeOnDelete();
            $table->string('role')->nullable();
            $table->timestamps();

            $table->unique(['seller_id', 'user_id']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('seller_user');
        Schema::dropIfExists('sellers');
    }
};
