<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
   
    public function up(): void
    {
        Schema::create('user_policy_agreements', function (Blueprint $table) {
            $table->id();
            $table->foreignId('user_id')
                  ->constrained('users')
                  ->onDelete('cascade'); 
            $table->foreignId('policy_version_id')
                  ->constrained('policy_versions')
                  ->onDelete('cascade'); 
            $table->timestamp('agreed_at')->useCurrent(); 
            $table->timestamps();
        });
    }

   
    public function down(): void
    {
        Schema::dropIfExists('user_policy_agreements');
    }
};
