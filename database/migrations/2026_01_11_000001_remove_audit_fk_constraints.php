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
        // Check and drop FK constraints if they exist
        $this->dropForeignKeyIfExists('branches', 'branches_created_by_foreign');
        $this->dropForeignKeyIfExists('branches', 'branches_updated_by_foreign');
        $this->dropForeignKeyIfExists('roles', 'roles_created_by_foreign');
        $this->dropForeignKeyIfExists('roles', 'roles_updated_by_foreign');
    }

    /**
     * Reverse the migrations (re-add constraints with nullOnDelete).
     */
    public function down(): void
    {
        Schema::table('branches', function (Blueprint $table) {
            if (!$this->foreignKeyExists('branches', 'branches_created_by_foreign')) {
                $table->foreign('created_by')->references('id')->on('users')->nullOnDelete();
            }
            if (!$this->foreignKeyExists('branches', 'branches_updated_by_foreign')) {
                $table->foreign('updated_by')->references('id')->on('users')->nullOnDelete();
            }
        });

        Schema::table('roles', function (Blueprint $table) {
            if (!$this->foreignKeyExists('roles', 'roles_created_by_foreign')) {
                $table->foreign('created_by')->references('id')->on('users')->nullOnDelete();
            }
            if (!$this->foreignKeyExists('roles', 'roles_updated_by_foreign')) {
                $table->foreign('updated_by')->references('id')->on('users')->nullOnDelete();
            }
        });
    }

    private function dropForeignKeyIfExists(string $table, string $foreignKey): void
    {
        if ($this->foreignKeyExists($table, $foreignKey)) {
            Schema::table($table, function (Blueprint $table) use ($foreignKey) {
                $table->dropForeign($foreignKey);
            });
        }
    }

    private function foreignKeyExists(string $table, string $foreignKey): bool
    {
        $conn = Schema::getConnection();
        $dbName = $conn->getDatabaseName();

        $exists = DB::select(
            "SELECT CONSTRAINT_NAME
             FROM information_schema.TABLE_CONSTRAINTS
             WHERE CONSTRAINT_SCHEMA = ?
             AND TABLE_NAME = ?
             AND CONSTRAINT_NAME = ?
             AND CONSTRAINT_TYPE = 'FOREIGN KEY'",
            [$dbName, $table, $foreignKey]
        );

        return count($exists) > 0;
    }
};
