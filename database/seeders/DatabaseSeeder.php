<?php

namespace Database\Seeders;

use Illuminate\Database\Console\Seeds\WithoutModelEvents;
use Illuminate\Database\Seeder;
use Illuminate\Support\Arr;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

class DatabaseSeeder extends Seeder
{
    use WithoutModelEvents;

    /**
     * Seed the application's database.
     */
    public function run(): void
    {
        $tableNames = config('permission.table_names', []);
        $spatieTables = [
            'roles' => Arr::get($tableNames, 'roles', 'roles'),
            'permissions' => Arr::get($tableNames, 'permissions', 'permissions'),
            'model_has_roles' => Arr::get($tableNames, 'model_has_roles', 'model_has_roles'),
            'model_has_permissions' => Arr::get($tableNames, 'model_has_permissions', 'model_has_permissions'),
            'role_has_permissions' => Arr::get($tableNames, 'role_has_permissions', 'role_has_permissions'),
        ];

        $deleteAllRowsIfExists = function (string $table) {
            if (!Schema::hasTable($table)) {
                $this->command?->warn("Skipping cleanup: table [$table] does not exist.");
                return;
            }

            DB::table($table)->delete();
        };

        // Ensure a clean state for roles and users so Developer and FrancisJr are primary.
        // Guarded so this seeder never crashes on fresh/provisioning databases.
        $deleteAllRowsIfExists($spatieTables['role_has_permissions']);
        $deleteAllRowsIfExists($spatieTables['model_has_roles']);
        $deleteAllRowsIfExists($spatieTables['model_has_permissions']);
        $deleteAllRowsIfExists('user_role');
        $deleteAllRowsIfExists('users');
        $deleteAllRowsIfExists($spatieTables['roles']);
        $deleteAllRowsIfExists($spatieTables['permissions']);

        // Create branches first (other seeders depend on it)
        if (Schema::hasTable('branches')) {
            $this->call(\Database\Seeders\BranchSeeder::class);
        } else {
            $this->command?->warn('Skipping BranchSeeder: table [branches] does not exist.');
        }

        // Spatie permissions + roles
        $spatieCoreChecks = [
            $spatieTables['roles'],
            $spatieTables['permissions'],
            $spatieTables['role_has_permissions'],
        ];
        $missingSpatieCoreTables = array_values(array_filter(
            $spatieCoreChecks,
            static fn (string $table) => !Schema::hasTable($table)
        ));
        $hasSpatieCore = empty($missingSpatieCoreTables);

        if ($hasSpatieCore) {
            $this->call(\Database\Seeders\RolePermissionSeeder::class);
            $this->call(\Database\Seeders\GrantDeveloperAllPermissionsSeeder::class);
        } else {
            $missingList = empty($missingSpatieCoreTables) ? '' : (' Missing: ' . implode(', ', $missingSpatieCoreTables));
            $this->command?->warn('Skipping permission/role seeders: Spatie permission tables are missing.' . $missingList);
        }

        // Create FrancisJr user
        if (Schema::hasTable('users')) {
            $this->call(\Database\Seeders\DeveloperUserSeeder::class);
        } else {
            $this->command?->warn('Skipping DeveloperUserSeeder: table [users] does not exist.');
        }

        // Dummy roles/users depend on roles/users tables
        if (Schema::hasTable($spatieTables['roles']) && Schema::hasTable('users')) {
            $this->call(\Database\Seeders\DummyRolesSeeder::class);
            $this->call(\Database\Seeders\DummyUsersSeeder::class);
        } else {
            $this->command?->warn('Skipping DummyRolesSeeder/DummyUsersSeeder: required tables are missing.');
        }

        // Contracts, contract types, and notification recipients
        // Keep this guarded so production deployments don\'t fail if a feature migration is missing.
        if (Schema::hasTable('contracts')) {
            $this->call(\Database\Seeders\ContractsSeeder::class);
        } else {
            $this->command?->warn('Skipping ContractsSeeder: table [contracts] does not exist.');
        }

        // Canonical permissions seeder (run manually when ready):
        // $this->call(\Database\Seeders\CanonicalPermissionsSeeder::class);
    }
}
