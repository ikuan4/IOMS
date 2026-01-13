<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;
use App\Models\Role;
use App\Models\Branch;
use Illuminate\Support\Str;

class DummyRolesSeeder extends Seeder
{
    public function run()
    {
        $branches = Branch::all();
        if ($branches->isEmpty()) {
            $this->call(BranchSeeder::class);
            $branches = Branch::all();
        }

        // Ensure Developer role exists
        $special = [
            ['name' => 'Developer', 'slug' => 'developer', 'is_active' => 1],
        ];

        foreach ($special as $r) {
            Role::updateOrCreate(['slug' => $r['slug']], array_merge($r, ['guard_name' => 'web']));
        }

        // Common role names in Indian organizations
        $roleNames = [
            'Manager', 'Assistant Manager', 'Team Lead', 'Senior Executive', 'Executive',
            'Operations Manager', 'Sales Manager', 'HR Manager', 'Finance Manager', 'Admin Manager',
            'Accountant', 'Supervisor', 'Coordinator', 'Officer', 'Clerk',
            'Business Analyst', 'Project Manager', 'Product Manager', 'Technical Lead', 'Quality Analyst',
            'Customer Service Executive', 'Marketing Executive', 'Support Engineer', 'Field Officer', 'Branch Manager',
            'Regional Manager', 'Senior Analyst', 'Junior Analyst', 'Data Entry Operator', 'Receptionist',
            'Store Manager', 'Warehouse Manager', 'Logistics Coordinator', 'Purchase Manager', 'Inventory Manager',
            'IT Administrator', 'Network Administrator', 'System Analyst', 'Software Engineer', 'Senior Developer'
        ];

        // Assign 5-10 roles to each branch randomly
        foreach ($branches as $branch) {
            $numberOfRoles = rand(5, 10);
            $selectedRoles = array_rand(array_flip($roleNames), $numberOfRoles);

            foreach ($selectedRoles as $roleName) {
                // Make role name unique by adding branch name
                $uniqueRoleName = $roleName . ' - ' . $branch->name;
                $slug = Str::slug($uniqueRoleName);

                Role::updateOrCreate([
                    'slug' => $slug,
                ], [
                    'name' => $uniqueRoleName,
                    'description' => $roleName . ' at ' . $branch->name,
                    'is_active' => 1,
                    'guard_name' => 'web',
                    'branch_id' => $branch->id,
                ]);
            }
        }
    }
}
