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
        // Create indexes in a DB-agnostic way — attempt and ignore if already present.
        try {
            Schema::table('users', function (Blueprint $table) {
                $table->index(['active', 'deleted_at'], 'users_active_deleted_at_index');
            });
        } catch (\Throwable $e) {
            // index likely exists or DB-specific error; ignore to keep migration idempotent
        }

        try {
            Schema::table('roles', function (Blueprint $table) {
                $table->index(['is_active', 'deleted_at', 'branch_id'], 'roles_is_active_deleted_at_branch_id_index');
            });
        } catch (\Throwable $e) {
        }

        try {
            Schema::table('branches', function (Blueprint $table) {
                $table->index('deleted_at', 'branches_deleted_at_index');
            });
        } catch (\Throwable $e) {
        }
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        try {
            Schema::table('users', function (Blueprint $table) {
                $table->dropIndex('users_active_deleted_at_index');
            });
        } catch (\Throwable $e) {
        }

        try {
            Schema::table('roles', function (Blueprint $table) {
                $table->dropIndex('roles_is_active_deleted_at_branch_id_index');
            });
        } catch (\Throwable $e) {
        }

        try {
            Schema::table('branches', function (Blueprint $table) {
                $table->dropIndex('branches_deleted_at_index');
            });
        } catch (\Throwable $e) {
        }
    }

};
