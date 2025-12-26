<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        if (Schema::hasTable('permissions')) {
            Schema::table('permissions', function (Blueprint $table) {
                if (! Schema::hasColumn('permissions', 'deleted_at')) {
                    $table->softDeletes();
                }
                if (! Schema::hasColumn('permissions', 'deleted_by')) {
                    $table->unsignedBigInteger('deleted_by')->nullable()->index();
                }
                if (! Schema::hasColumn('permissions', 'restored_by')) {
                    $table->unsignedBigInteger('restored_by')->nullable()->index();
                }
                if (! Schema::hasColumn('permissions', 'restored_at')) {
                    $table->timestamp('restored_at')->nullable();
                }
            });
        }

        if (Schema::hasTable('audit_logs')) {
            Schema::table('audit_logs', function (Blueprint $table) {
                if (! Schema::hasColumn('audit_logs', 'deleted_at')) {
                    $table->softDeletes();
                }
                if (! Schema::hasColumn('audit_logs', 'deleted_by')) {
                    $table->unsignedBigInteger('deleted_by')->nullable()->index();
                }
                if (! Schema::hasColumn('audit_logs', 'restored_by')) {
                    $table->unsignedBigInteger('restored_by')->nullable()->index();
                }
                if (! Schema::hasColumn('audit_logs', 'restored_at')) {
                    $table->timestamp('restored_at')->nullable();
                }
            });
        }
    }

    public function down(): void
    {
        if (Schema::hasTable('permissions')) {
            Schema::table('permissions', function (Blueprint $table) {
                if (Schema::hasColumn('permissions', 'restored_at')) {
                    $table->dropColumn('restored_at');
                }
                if (Schema::hasColumn('permissions', 'restored_by')) {
                    $table->dropColumn('restored_by');
                }
                if (Schema::hasColumn('permissions', 'deleted_by')) {
                    $table->dropColumn('deleted_by');
                }
                if (Schema::hasColumn('permissions', 'deleted_at')) {
                    $table->dropSoftDeletes();
                }
            });
        }

        if (Schema::hasTable('audit_logs')) {
            Schema::table('audit_logs', function (Blueprint $table) {
                if (Schema::hasColumn('audit_logs', 'restored_at')) {
                    $table->dropColumn('restored_at');
                }
                if (Schema::hasColumn('audit_logs', 'restored_by')) {
                    $table->dropColumn('restored_by');
                }
                if (Schema::hasColumn('audit_logs', 'deleted_by')) {
                    $table->dropColumn('deleted_by');
                }
                if (Schema::hasColumn('audit_logs', 'deleted_at')) {
                    $table->dropSoftDeletes();
                }
            });
        }
    }
};
