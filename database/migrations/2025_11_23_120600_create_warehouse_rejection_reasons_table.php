<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('warehouse_rejection_reasons', function (Blueprint $table) {
            $table->id();
            $table->string('name')->unique();
            $table->string('description')->nullable();
            $table->boolean('active')->default(true)->index();
            $table->unsignedInteger('sort_order')->default(0)->index();
            $table->timestamps();
        });

        // Seed a few defaults
        DB::table('warehouse_rejection_reasons')->insert([
            ['name' => 'Wrong items', 'description' => 'Received items do not match the dispatch', 'sort_order' => 10, 'active' => true, 'created_at' => now(), 'updated_at' => now()],
            ['name' => 'Quantity mismatch', 'description' => 'Received quantity differs from expected', 'sort_order' => 20, 'active' => true, 'created_at' => now(), 'updated_at' => now()],
            ['name' => 'Damaged in transit', 'description' => 'Goods damaged during transportation', 'sort_order' => 30, 'active' => true, 'created_at' => now(), 'updated_at' => now()],
            ['name' => 'Expired', 'description' => 'Goods expired or near expiry', 'sort_order' => 40, 'active' => true, 'created_at' => now(), 'updated_at' => now()],
            ['name' => 'Packaging damaged', 'description' => 'Packaging compromised or unsealed', 'sort_order' => 50, 'active' => true, 'created_at' => now(), 'updated_at' => now()],
            ['name' => 'Other', 'description' => 'Other reason (specify details)', 'sort_order' => 1000, 'active' => true, 'created_at' => now(), 'updated_at' => now()],
        ]);
    }

    public function down(): void
    {
        Schema::dropIfExists('warehouse_rejection_reasons');
    }
};
