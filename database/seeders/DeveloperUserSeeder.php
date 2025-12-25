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
            // Ensure Developer role exists and is protected
            $role = Role::firstOrCreate(
                ['name' => 'Developer'],
                ['branch_id' => null, 'created_by' => null, 'updated_by' => null, 'is_protected' => true]
            );

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
                    'role_id' => $role->id,
                    'branch_id' => null,
                ]);
            } else {
                User::create([
                    'name' => 'FrancisJr',
                    'email' => 'ikuan4@gmail.com',
                    'password' => 'password@123',
                    'mobile' => '0000000000',
                    'active' => true,
                    'role_id' => $role->id,
                    'branch_id' => null,
                ]);
            }
        });
    }
}
