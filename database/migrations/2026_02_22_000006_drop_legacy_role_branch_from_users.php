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
        // Get list of foreign keys on users table
        $foreignKeys = \DB::select("
            SELECT CONSTRAINT_NAME
            FROM information_schema.KEY_COLUMN_USAGE
            WHERE TABLE_SCHEMA = DATABASE()
            AND TABLE_NAME = 'users'
            AND REFERENCED_TABLE_NAME IS NOT NULL
            AND COLUMN_NAME IN ('role_id', 'branch_id')
        ");

        // Drop foreign keys if they exist
        Schema::table('users', function (Blueprint $table) use ($foreignKeys) {
            foreach ($foreignKeys as $fk) {
                $table->dropForeign($fk->CONSTRAINT_NAME);
            }
        });

        // Now drop the columns
        Schema::table('users', function (Blueprint $table) {
            $table->dropColumn(['role_id', 'branch_id']);
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('users', function (Blueprint $table) {
            $table->foreignId('role_id')->nullable()->index();
            $table->foreignId('branch_id')->nullable()->index();
        });
    }
};
