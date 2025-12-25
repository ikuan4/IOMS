<?php

namespace Database\Seeders;

use App\Models\User;
use App\Models\Branch;
use App\Models\Role;
use Illuminate\Database\Console\Seeds\WithoutModelEvents;
use Illuminate\Database\Seeder;

class DatabaseSeeder extends Seeder
{
    use WithoutModelEvents;

    /**
     * Seed the application's database.
     */
    public function run(): void
    {
        // User::factory(10)->create();

        $branch = Branch::create([
            'name' => 'Main Branch',
            'created_by' => null,
            'updated_by' => null,
        ]);

        $role = Role::create([
            'name' => 'Administrator',
            'branch_id' => $branch->id,
            'created_by' => null,
            'updated_by' => null,
        ]);

        User::factory()->create([
            'name' => 'Test User',
            'email' => 'test@example.com',
            'mobile' => '0123456789',
            'last_updated_at' => now(),
            'last_updated_by' => null,
            'branch_id' => $branch->id,
            'role_id' => $role->id,
        ]);

        // Create developer role and default admin-like user
        $this->call(DeveloperUserSeeder::class);
    }
}
