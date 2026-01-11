<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;
use Illuminate\Support\Facades\DB;

return new class extends Migration
{
    /**
     * Add composite indexes for frequently queried columns.
     */
    public function up(): void
    {
        Schema::table('users', function (Blueprint $table) {
            // Composite index for active user queries with soft deletes
            if (!$this->indexExists('users', 'users_active_deleted_at_index')) {
                $table->index(['active', 'deleted_at'], 'users_active_deleted_at_index');
            }
        });

        Schema::table('roles', function (Blueprint $table) {
            // Composite index for active role queries by branch
            if (!$this->indexExists('roles', 'roles_is_active_deleted_at_branch_id_index')) {
                $table->index(['is_active', 'deleted_at', 'branch_id'], 'roles_is_active_deleted_at_branch_id_index');
            }
        });

        Schema::table('branches', function (Blueprint $table) {
            // Index for soft delete queries
            if (!$this->indexExists('branches', 'branches_deleted_at_index')) {
                $table->index('deleted_at', 'branches_deleted_at_index');
            }
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('users', function (Blueprint $table) {
            if ($this->indexExists('users', 'users_active_deleted_at_index')) {
                $table->dropIndex('users_active_deleted_at_index');
            }
        });

        Schema::table('roles', function (Blueprint $table) {
            if ($this->indexExists('roles', 'roles_is_active_deleted_at_branch_id_index')) {
                $table->dropIndex('roles_is_active_deleted_at_branch_id_index');
            }
        });

        Schema::table('branches', function (Blueprint $table) {
            if ($this->indexExists('branches', 'branches_deleted_at_index')) {
                $table->dropIndex('branches_deleted_at_index');
            }
        });
    }

    private function indexExists(string $table, string $index): bool
    {
        $conn = Schema::getConnection();
        $dbName = $conn->getDatabaseName();

        // Use raw query instead of Doctrine
        $result = DB::select(
            "SELECT COUNT(*) as count
             FROM information_schema.STATISTICS
             WHERE TABLE_SCHEMA = ?
             AND TABLE_NAME = ?
             AND INDEX_NAME = ?",
            [$dbName, $table, $index]
        );

        return $result[0]->count > 0;
    }
};
