<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration {
    public function up(): void
    {
        Schema::table('products', function (Blueprint $table) {
            // Flexible, per-vertical structured fields (e.g. car make/model/year/
            // mileage, construction material/unit). Marketplace verticals read and
            // filter on these; ecommerce products simply leave it null.
            if (! Schema::hasColumn('products', 'attributes')) {
                $table->json('attributes')->nullable()->after('specifications');
            }
        });
    }

    public function down(): void
    {
        Schema::table('products', function (Blueprint $table) {
            if (Schema::hasColumn('products', 'attributes')) {
                $table->dropColumn('attributes');
            }
        });
    }
};
