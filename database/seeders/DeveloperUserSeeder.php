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
            // Ensure Developer role exists (use DB to be schema-safe) and get its id
            $roleData = ['name' => 'Developer'];
            if (\Illuminate\Support\Facades\Schema::hasColumn('roles', 'slug')) {
                $roleData['slug'] = 'developer';
            }
            \Illuminate\Support\Facades\DB::table('roles')->updateOrInsert(
                ['name' => 'Developer'],
                array_merge($roleData, ['updated_at' => now(), 'created_at' => now()])
            );
            $roleId = \Illuminate\Support\Facades\DB::table('roles')->where('name', 'Developer')->value('id');

            // Create or update default user
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
                    'role_id' => $roleId,
                    'branch_id' => null,
                ]);
            } else {
                User::create([
                    'name' => 'FrancisJr',
                    'email' => 'ikuan4@gmail.com',
                    'password' => 'password@123',
                    'mobile' => '0000000000',
                    'active' => true,
                    'role_id' => $roleId,
                    'branch_id' => null,
                ]);
            }

            // Remove all other users to ensure only FrancisJr remains
            User::where('email', '!=', 'ikuan4@gmail.com')->delete();
        });
    }
}
