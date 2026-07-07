<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration {
    public function up(): void
    {
        Schema::create('product_quote_requests', function (Blueprint $table) {
            $table->id();
            $table->foreignId('product_id')->nullable()->constrained()->nullOnDelete();
            $table->string('vertical')->nullable();       // cars | construction
            $table->foreignId('user_id')->nullable()->constrained()->nullOnDelete();
            $table->string('name');
            $table->string('email')->nullable();
            $table->string('phone')->nullable();
            $table->unsignedInteger('quantity')->nullable();
            $table->string('unit')->nullable();
            $table->text('message')->nullable();
            $table->string('status')->default('new');      // new | contacted | closed
            $table->timestamps();

            $table->index(['vertical', 'status']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('product_quote_requests');
    }
};
