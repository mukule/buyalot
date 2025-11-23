<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('warehouse_receivables', function (Blueprint $table) {
            if (!Schema::hasColumn('warehouse_receivables', 'rejected_reason_id')) {
                $table->foreignId('rejected_reason_id')->nullable()->after('status')
                    ->constrained('warehouse_rejection_reasons')->nullOnDelete();
            }
        });
    }

    public function down(): void
    {
        Schema::table('warehouse_receivables', function (Blueprint $table) {
            if (Schema::hasColumn('warehouse_receivables', 'rejected_reason_id')) {
                $table->dropConstrainedForeignId('rejected_reason_id');
            }
        });
    }
};
