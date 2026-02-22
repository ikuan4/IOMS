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
        // Drop the existing unique constraint on 'name'
        Schema::table('roles', function (Blueprint $table) {
            $table->dropUnique(['name']);
        });

        // Add unique constraint on (slug, branch_id)
        // This allows same slug across branches, but prevents duplicates within a branch
        Schema::table('roles', function (Blueprint $table) {
            $table->unique(['slug', 'branch_id'], 'roles_slug_branch_unique');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('roles', function (Blueprint $table) {
            $table->dropUnique('roles_slug_branch_unique');
        });

        Schema::table('roles', function (Blueprint $table) {
            $table->unique('name');
        });
    }
};
