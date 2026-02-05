<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;
use Illuminate\Support\Facades\DB;

return new class extends Migration
{
    /**
     * Remove circular FK constraints from audit fields to prevent deadlock.
     * Audit fields remain as indexed columns for referential integrity at application level.
     */
    public function up(): void
    {
        // Drop circular FK constraints in a DB-agnostic manner. Attempt drops and ignore failures.
        try {
            Schema::table('branches', function (Blueprint $table) {
                try {
                    $table->dropForeign(['created_by']);
                } catch (\Throwable $e) {
                }
                try {
                    $table->dropForeign(['updated_by']);
                } catch (\Throwable $e) {
                }
            });
        } catch (\Throwable $e) {
        }

        try {
            Schema::table('roles', function (Blueprint $table) {
                try {
                    $table->dropForeign(['created_by']);
                } catch (\Throwable $e) {
                }
                try {
                    $table->dropForeign(['updated_by']);
                } catch (\Throwable $e) {
                }
            });
        } catch (\Throwable $e) {
        }
    }

    /**
     * Reverse the migrations (re-add constraints with nullOnDelete).
     */
    public function down(): void
    {
        // Recreate the fk constraints; ignore errors if they already exist or cannot be added.
        try {
            Schema::table('branches', function (Blueprint $table) {
                try {
                    $table->foreign('created_by')->references('id')->on('users')->nullOnDelete();
                } catch (\Throwable $e) {
                }
                try {
                    $table->foreign('updated_by')->references('id')->on('users')->nullOnDelete();
                } catch (\Throwable $e) {
                }
            });
        } catch (\Throwable $e) {
        }

        try {
            Schema::table('roles', function (Blueprint $table) {
                try {
                    $table->foreign('created_by')->references('id')->on('users')->nullOnDelete();
                } catch (\Throwable $e) {
                }
                try {
                    $table->foreign('updated_by')->references('id')->on('users')->nullOnDelete();
                } catch (\Throwable $e) {
                }
            });
        } catch (\Throwable $e) {
        }
    }

};
