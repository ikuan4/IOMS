<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\Schema;
use Illuminate\Support\Facades\DB;
use Spatie\Permission\Models\Permission;

class GrantDeveloperAllPermissionsSeeder extends Seeder
{
    public function run()
    {
        if (!Schema::hasTable('roles') || !Schema::hasTable('permissions') || !Schema::hasTable('role_has_permissions')) {
            return;
        }

        $developer = DB::table('roles')->where('name', 'Developer')->first();
        if (!$developer) {
            // Developer role may be created by migration; if still missing, create minimal entry
            $id = DB::table('roles')->insertGetId([
                'name' => 'Developer',
                'guard_name' => 'web',
                'slug' => 'developer',
                'description' => 'System developer (highest privilege)',
                'is_active' => 1,
                'priority' => 0,
                'created_at' => now(),
                'updated_at' => now(),
            ]);
        } else {
            $id = $developer->id;
            // Ensure priority is 0
            if (($developer->priority ?? 100) !== 0) {
                DB::table('roles')->where('id', $id)->update(['priority' => 0, 'updated_at' => now()]);
            }
        }

        // Grant all permissions to Developer role
        $permissionIds = Permission::pluck('id')->toArray();
        if (!empty($permissionIds)) {
            // Remove existing to avoid duplicates
            DB::table('role_has_permissions')->where('role_id', $id)->delete();

            $rows = array_map(function ($permId) use ($id) {
                return [
                    'permission_id' => $permId,
                    'role_id' => $id,
                ];
            }, $permissionIds);

            // Bulk insert
            if (!empty($rows)) {
                DB::table('role_has_permissions')->insert($rows);
            }
        }
    }
}
