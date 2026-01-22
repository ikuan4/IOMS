<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('stored_files', function (Blueprint $table) {
            if (!Schema::hasColumn('stored_files', 'created_by')) {
                $table->foreignId('created_by')->nullable()->constrained('users')->nullOnDelete()->after('sha256');
            }
            if (!Schema::hasColumn('stored_files', 'updated_by')) {
                $table->foreignId('updated_by')->nullable()->constrained('users')->nullOnDelete()->after('created_by');
            }
            if (!Schema::hasColumn('stored_files', 'deleted_by')) {
                $table->foreignId('deleted_by')->nullable()->constrained('users')->nullOnDelete()->after('updated_by');
            }
            if (!Schema::hasColumn('stored_files', 'restored_by')) {
                $table->foreignId('restored_by')->nullable()->constrained('users')->nullOnDelete()->after('deleted_by');
            }
            if (!Schema::hasColumn('stored_files', 'deleted_at')) {
                $table->softDeletes();
            }
            if (!Schema::hasColumn('stored_files', 'restored_at')) {
                $table->timestamp('restored_at')->nullable()->after('deleted_at');
            }
        });
    }

    public function down(): void
    {
        Schema::table('stored_files', function (Blueprint $table) {
            if (Schema::hasColumn('stored_files', 'restored_at')) {
                $table->dropColumn('restored_at');
            }

            if (Schema::hasColumn('stored_files', 'deleted_at')) {
                $table->dropSoftDeletes();
            }

            foreach (['restored_by', 'deleted_by', 'updated_by', 'created_by'] as $col) {
                if (!Schema::hasColumn('stored_files', $col)) {
                    continue;
                }

                // drop FK first if present
                try {
                    $table->dropForeign([$col]);
                } catch (Throwable $e) {
                    // ignore
                }

                $table->dropColumn($col);
            }
        });
    }
};
