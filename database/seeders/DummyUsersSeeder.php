<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;
use App\Models\User;
use App\Models\Role;
use App\Models\Branch;

class DummyUsersSeeder extends Seeder
{
    public function run()
    {
        $roles = Role::where('is_active', 1)->get();
        $branches = Branch::all();

        if ($roles->isEmpty() || $branches->isEmpty()) {
            $this->call([BranchSeeder::class, DummyRolesSeeder::class]);
            $roles = Role::where('is_active', 1)->get();
            $branches = Branch::all();
        }

        // Create 50 users, distributed across roles and branches
        $totalUsers = 50;
        $roleCount = $roles->count();

        for ($i = 1; $i <= $totalUsers; $i++) {
            $role = $roles->random();
            $branchId = $role->branch_id ?? $branches->random()->id;

            $user = User::factory()->create([
                'name' => 'Dummy User ' . $i,
                'email' => 'dummy' . $i . '@example.test',
                'password' => 'password',
                'active' => 1,
                'branch_id' => $branchId,
                'role_id' => $role->id,
            ]);

            // Ensure pivot mapping for Spatie's model_has_roles table
            try {
                $role->users()->attach($user->id);
            } catch (\Exception $e) {
                // ignore duplicates
            }
        }
    }
}
