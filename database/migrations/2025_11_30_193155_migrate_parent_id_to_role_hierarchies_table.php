<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;
use Illuminate\Support\Facades\DB;

return new class extends Migration {
    /**
     * Run the migrations.
     */
    public function up(): void
    {
        // Only migrate if parent_id column exists
        if (!Schema::hasColumn('roles', 'parent_id')) {
            return; // Skip if column doesn't exist
        }

        // Migrate existing parent_id relationships to role_hierarchies table
        $roles = DB::table('roles')->whereNotNull('parent_id')->get();

        foreach ($roles as $role) {
            DB::table('role_hierarchies')->insert([
                'parent_role_id' => $role->parent_id,
                'child_role_id' => $role->id,
                'created_at' => now(),
                'updated_at' => now(),
            ]);
        }

        // Drop parent_id column and foreign key
        Schema::table('roles', function (Blueprint $table) {
            $table->dropForeign(['parent_id']);
            $table->dropColumn('parent_id');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        // Add back parent_id column
        Schema::table('roles', function (Blueprint $table) {
            $table->unsignedBigInteger('parent_id')->nullable()->after('description');
            $table->foreign('parent_id')
                ->references('id')
                ->on('roles')
                ->onDelete('set null');
        });

        // Migrate data back from role_hierarchies (taking only first parent if multiple)
        $hierarchies = DB::table('role_hierarchies')
            ->orderBy('id', 'asc')
            ->get()
            ->groupBy('child_role_id');

        foreach ($hierarchies as $childId => $relationships) {
            DB::table('roles')
                ->where('id', $childId)
                ->update(['parent_id' => $relationships->first()->parent_role_id]);
        }

        // Clear role_hierarchies table
        DB::table('role_hierarchies')->truncate();
    }
};
