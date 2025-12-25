<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Run the migrations.
     */
    public function up(): void
    {
        if (Schema::hasTable('model_has_roles') && !Schema::hasColumn('model_has_roles', 'created_at')) {
            Schema::table('model_has_roles', function (Blueprint $table) {
                $table->timestamp('created_at')->nullable();
                $table->timestamp('updated_at')->nullable();
            });
        }

        if (Schema::hasTable('role_has_permissions') && !Schema::hasColumn('role_has_permissions', 'created_at')) {
            Schema::table('role_has_permissions', function (Blueprint $table) {
                $table->timestamp('created_at')->nullable();
                $table->timestamp('updated_at')->nullable();
            });
        }
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        if (Schema::hasTable('model_has_roles') && Schema::hasColumn('model_has_roles', 'created_at')) {
            Schema::table('model_has_roles', function (Blueprint $table) {
                if (Schema::hasColumn('model_has_roles', 'created_at')) {
                    $table->dropColumn(['created_at', 'updated_at']);
                }
            });
        }

        if (Schema::hasTable('role_has_permissions') && Schema::hasColumn('role_has_permissions', 'created_at')) {
            Schema::table('role_has_permissions', function (Blueprint $table) {
                if (Schema::hasColumn('role_has_permissions', 'created_at')) {
                    $table->dropColumn(['created_at', 'updated_at']);
                }
            });
        }
    }
};
