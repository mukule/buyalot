<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration {
    /**
     * Run the migrations.
     */
    public function up(): void
    {
        Schema::table('seller_documents', function (Blueprint $table) {
            if (!Schema::hasColumn('seller_documents', 'user_id')) {
                $table->foreignId('user_id')->after('id')->constrained()->onDelete('cascade');
            }

            if (!Schema::hasColumn('seller_documents', 'document_type_id')) {
                $table->foreignId('document_type_id')->after('user_id')->constrained()->onDelete('cascade');
            }

            if (!Schema::hasColumn('seller_documents', 'file_path')) {
                $table->string('file_path')->nullable()->after('document_type_id');
            }

            if (!Schema::hasColumn('seller_documents', 'status')) {
                $table->enum('status', ['pending', 'approved', 'rejected'])->default('pending')->after('file_path');
            }

            if (!Schema::hasColumn('seller_documents', 'rejection_reason')) {
                $table->text('rejection_reason')->nullable()->after('status');
            }

            // Add unique constraint only if not already defined
            $table->unique(['user_id', 'document_type_id'], 'seller_documents_user_doc_unique');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('seller_documents', function (Blueprint $table) {
            $table->dropUnique('seller_documents_user_doc_unique');
            $table->dropForeign(['user_id']);
            $table->dropForeign(['document_type_id']);
            $table->dropColumn([
                'user_id',
                'document_type_id',
                'file_path',
                'status',
                'rejection_reason',
            ]);
        });
    }
};
