<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('contract_reminders', function (Blueprint $table) {
            if (!Schema::hasColumn('contract_reminders', 'created_by')) {
                $table->foreignId('created_by')->nullable()->constrained('users')->nullOnDelete()->after('sent_at');
            }
            if (!Schema::hasColumn('contract_reminders', 'updated_by')) {
                $table->foreignId('updated_by')->nullable()->constrained('users')->nullOnDelete()->after('created_by');
            }
            if (!Schema::hasColumn('contract_reminders', 'deleted_by')) {
                $table->foreignId('deleted_by')->nullable()->constrained('users')->nullOnDelete()->after('updated_by');
            }
            if (!Schema::hasColumn('contract_reminders', 'restored_by')) {
                $table->foreignId('restored_by')->nullable()->constrained('users')->nullOnDelete()->after('deleted_by');
            }
            if (!Schema::hasColumn('contract_reminders', 'deleted_at')) {
                $table->softDeletes();
            }
            if (!Schema::hasColumn('contract_reminders', 'restored_at')) {
                $table->timestamp('restored_at')->nullable()->after('deleted_at');
            }
        });
    }

    public function down(): void
    {
        Schema::table('contract_reminders', function (Blueprint $table) {
            if (Schema::hasColumn('contract_reminders', 'restored_at')) {
                $table->dropColumn('restored_at');
            }

            if (Schema::hasColumn('contract_reminders', 'deleted_at')) {
                $table->dropSoftDeletes();
            }

            foreach (['restored_by', 'deleted_by', 'updated_by', 'created_by'] as $col) {
                if (!Schema::hasColumn('contract_reminders', $col)) {
                    continue;
                }

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
