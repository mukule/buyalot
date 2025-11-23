<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('warehouse_receivables', function (Blueprint $table) {
            if (!Schema::hasColumn('warehouse_receivables', 'from_warehouse_id')) {
                $table->foreignId('from_warehouse_id')->nullable()->after('warehouse_id')
                    ->constrained('warehouses')->nullOnDelete();
            }
            if (!Schema::hasColumn('warehouse_receivables', 'rejected_by')) {
                $table->foreignId('rejected_by')->nullable()->after('received_at')
                    ->constrained('users')->nullOnDelete();
            }
            if (!Schema::hasColumn('warehouse_receivables', 'rejected_reason')) {
                $table->string('rejected_reason')->nullable()->after('rejected_by');
            }
            if (!Schema::hasColumn('warehouse_receivables', 'rejected_at')) {
                $table->timestamp('rejected_at')->nullable()->after('rejected_reason');
            }

            // Update status enum to include 'rejected' if DB supports enum modification
            // For portability, we convert to string if needed, but here attempt enum alteration for MySQL
        });

        // Attempt to alter enum using raw SQL where applicable (MySQL)
        try {
            $connection = Schema::getConnection()->getDoctrineSchemaManager()->getDatabasePlatform()->getName();
            if ($connection === 'mysql') {
                Schema::getConnection()->statement("ALTER TABLE warehouse_receivables MODIFY COLUMN status ENUM('pending','received','rejected') NOT NULL DEFAULT 'pending'");
            }
        } catch (Throwable $e) {
            // Fallback: ignore if platform doesn't support enum alteration here
        }
    }

    public function down(): void
    {
        // Revert enum where possible
        try {
            $connection = Schema::getConnection()->getDoctrineSchemaManager()->getDatabasePlatform()->getName();
            if ($connection === 'mysql') {
                Schema::getConnection()->statement("ALTER TABLE warehouse_receivables MODIFY COLUMN status ENUM('pending','received') NOT NULL DEFAULT 'pending'");
            }
        } catch (Throwable $e) {
        }

        Schema::table('warehouse_receivables', function (Blueprint $table) {
            if (Schema::hasColumn('warehouse_receivables', 'rejected_at')) {
                $table->dropColumn('rejected_at');
            }
            if (Schema::hasColumn('warehouse_receivables', 'rejected_reason')) {
                $table->dropColumn('rejected_reason');
            }
            if (Schema::hasColumn('warehouse_receivables', 'rejected_by')) {
                $table->dropConstrainedForeignId('rejected_by');
            }
            if (Schema::hasColumn('warehouse_receivables', 'from_warehouse_id')) {
                $table->dropConstrainedForeignId('from_warehouse_id');
            }
        });
    }
};
