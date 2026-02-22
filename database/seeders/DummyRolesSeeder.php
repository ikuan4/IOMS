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

        // Ensure Developer role exists as GLOBAL (not branch-specific)
        Role::updateOrInsert(
            ['slug' => 'developer', 'branch_id' => null],
            [
                'name' => 'Developer',
                'slug' => 'developer',
                'is_active' => 1,
                'is_global' => true,
                'priority' => 1,
                'branch_id' => null,
                'guard_name' => 'web',
                'updated_at' => now(),
                'created_at' => now(),
            ]
        );

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
                // Use simple slug (can be same across branches due to unique constraint on slug+branch_id)
                $slug = Str::slug($roleName);

                // Check if role with this slug already exists in this branch
                Role::updateOrInsert(
                    ['slug' => $slug, 'branch_id' => $branch->id],
                    [
                        'name' => $roleName . ' - ' . $branch->name,
                        'description' => $roleName . ' at ' . $branch->name,
                        'is_active' => 1,
                        'is_global' => false,
                        'priority' => 100,
                        'guard_name' => 'web',
                        'branch_id' => $branch->id,
                        'updated_at' => now(),
                        'created_at' => now(),
                    ]
                );
            }
        }
    }
}
