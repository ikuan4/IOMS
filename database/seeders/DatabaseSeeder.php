<?php

namespace Database\Seeders;

use App\Models\User;
use App\Models\Branch;
use App\Models\Role;
use Illuminate\Database\Console\Seeds\WithoutModelEvents;
use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\DB;

class DatabaseSeeder extends Seeder
{
    use WithoutModelEvents;

    /**
     * Seed the application's database.
     */
    public function run(): void
    {
        // Ensure a clean state for roles and users so Developer and FrancisJr are primary
        DB::table('role_has_permissions')->delete();
        DB::table('model_has_roles')->delete();
        DB::table('users')->delete();
        DB::table('roles')->delete();

        // Clear contract and ticket related tables for a fresh seed
        DB::table('contract_versions')->delete();
        DB::table('contract_version_files')->delete();
        DB::table('contracts')->delete();
        DB::table('contract_types')->delete();
        DB::table('contract_reminders')->delete();
        DB::table('notification_recipients')->delete();
        DB::table('ticket_events')->delete();
        DB::table('ticket_comments')->delete();
        DB::table('ticket_comment_files')->delete();
        DB::table('ticket_files')->delete();
        DB::table('ticket_comment_draft_files')->delete();
        DB::table('ticket_draft_files')->delete();
        DB::table('tickets')->delete();
        DB::table('ticket_types')->delete();
        DB::table('ticket_modules')->delete();

        // Optionally recreate a main branch (not required for Developer)
        $branch = Branch::firstOrCreate(['name' => 'Main Branch'], ['created_by' => null, 'updated_by' => null]);

        // Seed permissions and Developer role first
        $this->call(\Database\Seeders\RolePermissionSeeder::class);
        // Ensure Developer has all permissions and highest priority
        $this->call(\Database\Seeders\GrantDeveloperAllPermissionsSeeder::class);

        // Create FrancisJr user and remove any other users within the Developer seeder
        $this->call(\Database\Seeders\DeveloperUserSeeder::class);

        // Create branches, dummy roles and dummy users
        $this->call(\Database\Seeders\BranchSeeder::class);
        $this->call(\Database\Seeders\DummyRolesSeeder::class);
        $this->call(\Database\Seeders\DummyUsersSeeder::class);

        // Create contracts, contract types, and notification recipients
        $this->call(\Database\Seeders\ContractsSeeder::class);

        // Create ticket types and modules for each branch
        $this->call(\Database\Seeders\TicketTypesAndModulesSeeder::class);

        // Create comprehensive ticket data (types, modules, tickets with comments and events)
        $this->call(\Database\Seeders\ComprehensiveTicketSeeder::class);

        // Canonical permissions seeder (run manually when ready):
        // $this->call(\Database\Seeders\CanonicalPermissionsSeeder::class);
    }
}
