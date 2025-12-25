<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;
use Illuminate\Support\Facades\DB;

return new class extends Migration
{
    /**
     * Run the migrations.
     */
    public function up(): void
    {
        // Ensure the Developer role exists and has highest priority (0)
        if (!Schema::hasTable('roles')) {
            return;
        }

        $hasPriority = Schema::hasColumn('roles', 'priority');

        $developer = DB::table('roles')->where('name', 'Developer')->first();

        if (!$developer) {
            // Insert a Developer role; include priority only if column exists
            $data = [
                'name' => 'Developer',
                'guard_name' => 'web',
                'slug' => 'developer',
                'description' => 'System developer (highest privilege)',
                'is_active' => 1,
                'created_at' => now(),
                'updated_at' => now(),
            ];
            if ($hasPriority) {
                $data['priority'] = 0;
            }
            DB::table('roles')->insert($data);
        } else {
            // Update priority to 0 if column exists
            if ($hasPriority && (($developer->priority ?? 100) !== 0)) {
                DB::table('roles')->where('id', $developer->id)->update(['priority' => 0, 'updated_at' => now()]);
            }
        }
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        // Do not remove the role on rollback; only revert priority to 100
        if (!Schema::hasTable('roles')) {
            return;
        }

        $developer = DB::table('roles')->where('name', 'Developer')->first();
        if ($developer) {
            DB::table('roles')->where('id', $developer->id)->update(['priority' => 100, 'updated_at' => now()]);
        }
    }
};
