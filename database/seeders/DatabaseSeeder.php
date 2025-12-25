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
        // Ensure a clean state for roles and users so Developer and FrancisJr are primary
        \Illuminate\Support\Facades\DB::table('role_has_permissions')->delete();
        \Illuminate\Support\Facades\DB::table('model_has_roles')->delete();
        \Illuminate\Support\Facades\DB::table('role_hierarchies')->delete();
        \Illuminate\Support\Facades\DB::table('users')->delete();
        \Illuminate\Support\Facades\DB::table('roles')->delete();

        // Optionally recreate a main branch (not required for Developer)
        $branch = Branch::firstOrCreate(['name' => 'Main Branch'], ['created_by' => null, 'updated_by' => null]);

        // Seed permissions and Developer role first
        $this->call(\Database\Seeders\RolePermissionSeeder::class);

        // Create FrancisJr user and remove any other users within the Developer seeder
        $this->call(\Database\Seeders\DeveloperUserSeeder::class);

        // Create branches, dummy roles and dummy users
        $this->call(\Database\Seeders\BranchSeeder::class);
        $this->call(\Database\Seeders\DummyRolesSeeder::class);
        $this->call(\Database\Seeders\DummyUsersSeeder::class);

        // Canonical permissions seeder (run manually when ready):
        // $this->call(\Database\Seeders\CanonicalPermissionsSeeder::class);
    }
}
