<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration {
    public function up(): void
    {
        Schema::table('products', function (Blueprint $table) {
            // A listing is "reserved" when this is set (e.g. a car with a deposit).
            // Null = available. Used by the Cars & Motors vertical to show/filter
            // reserved cars and to remove (cancel) a reservation.
            if (! Schema::hasColumn('products', 'reserved_at')) {
                $table->timestamp('reserved_at')->nullable()->after('attributes');
            }
        });
    }

    public function down(): void
    {
        Schema::table('products', function (Blueprint $table) {
            if (Schema::hasColumn('products', 'reserved_at')) {
                $table->dropColumn('reserved_at');
            }
        });
    }
};
