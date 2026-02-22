<?php

namespace Database\Seeders;

use App\Models\Branch;
use App\Models\Role;
use App\Models\User;
use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\DB;

class DeveloperUserSeeder extends Seeder
{
    public function run(): void
    {
        DB::transaction(function () {
            // Ensure Developer role exists as global role with priority=1
            $roleData = ['name' => 'Developer', 'is_global' => true, 'priority' => 1, 'branch_id' => null];
            if (\Illuminate\Support\Facades\Schema::hasColumn('roles', 'slug')) {
                $roleData['slug'] = 'developer';
            }
            DB::table('roles')->updateOrInsert(
                ['name' => 'Developer'],
                array_merge($roleData, ['updated_at' => now(), 'created_at' => now()])
            );

            // Get the Developer role ID
            $developerRoleId = DB::table('roles')->where('name', 'Developer')->value('id');

            // Create or update developer user with global_role_id
            $user = User::withTrashed()->where('email', 'ikuan4@gmail.com')->first();

            if ($user) {
                // restore if soft-deleted
                if (method_exists($user, 'restore') && $user->trashed()) {
                    $user->restore();
                }
                $user->update([
                    'name' => 'FrancisJr',
                    'password' => 'password@123',
                    'mobile' => $user->mobile ?? '0000000000',
                    'active' => true,
                    'global_role_id' => $developerRoleId,
                ]);
            } else {
                User::create([
                    'name' => 'FrancisJr',
                    'email' => 'ikuan4@gmail.com',
                    'password' => 'password@123',
                    'mobile' => '0000000000',
                    'active' => true,
                    'global_role_id' => $developerRoleId,
                ]);
            }

            // Do not remove other users; keep branch users for validation and testing
        });
    }
}
