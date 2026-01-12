<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;
use App\Models\Branch;
use App\Models\Role;
use App\Models\User;
use App\Models\ContractType;
use App\Models\Contract;
use App\Models\NotificationRecipient;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Str;

class ComprehensiveDummyDataSeeder extends Seeder
{
    /**
     * Run the database seeds.
     */
    public function run(): void
    {
        $this->command->info('Starting comprehensive dummy data seeding...');

        // Check if data already exists
        if (Branch::where('name', 'LIKE', '%Regional Office%')->exists()) {
            $this->command->warn('Dummy data already exists. Cleaning up first...');
            $this->cleanup();
        }

        // Step 1: Create 3 branches
        $this->command->info('Creating 3 branches...');
        $branches = $this->createBranches();

        // Step 2: Create roles for each branch (at least 5 roles per branch)
        $this->command->info('Creating roles for each branch...');
        $roles = $this->createRolesForBranches($branches);

        // Step 3: Create users for each role (1-7 users per role)
        $this->command->info('Creating users for each role...');
        $users = $this->createUsersForRoles($roles);

        // Step 4: Create 5 contract types
        $this->command->info('Creating 5 contract types...');
        $contractTypes = $this->createContractTypes($branches);

        // Step 5: Create contracts for each type (3-4 contracts per type)
        $this->command->info('Creating contracts for each type...');
        $contracts = $this->createContractsForTypes($contractTypes, $branches, $users);

        // Step 6: Create notification recipients and assign to contracts
        $this->command->info('Creating notification recipients and linking to contracts...');
        $this->createNotificationRecipientsAndLink($contracts, $branches, $users);

        $this->command->info('✅ Comprehensive dummy data seeding completed successfully!');
        $this->command->info("   - Branches: {$branches->count()}");
        $this->command->info("   - Roles: {$roles->count()}");
        $this->command->info("   - Users: {$users->count()}");
        $this->command->info("   - Contract Types: {$contractTypes->count()}");
        $this->command->info("   - Contracts: {$contracts->count()}");
    }

    /**
     * Clean up existing dummy data
     */
    private function cleanup(): void
    {
        // Delete in reverse order of dependencies
        \DB::table('contract_notification_recipient')->whereIn('contract_id',
            Contract::where('contract_number', 'LIKE', 'SA-%')
                ->orWhere('contract_number', 'LIKE', 'VC-%')
                ->orWhere('contract_number', 'LIKE', 'EC-%')
                ->orWhere('contract_number', 'LIKE', 'PA-%')
                ->orWhere('contract_number', 'LIKE', 'LA-%')
                ->pluck('id')
        )->delete();

        Contract::where('contract_number', 'LIKE', 'SA-%')
            ->orWhere('contract_number', 'LIKE', 'VC-%')
            ->orWhere('contract_number', 'LIKE', 'EC-%')
            ->orWhere('contract_number', 'LIKE', 'PA-%')
            ->orWhere('contract_number', 'LIKE', 'LA-%')
            ->forceDelete();

        ContractType::whereIn('code', ['SA', 'VC', 'EC', 'PA', 'LA'])->forceDelete();

        NotificationRecipient::where('email', 'LIKE', '%@ioms.test')->forceDelete();

        // Clean up users and their pivot table entries
        $userIds = User::where('email', 'LIKE', '%@ioms.test')->pluck('id');
        \DB::table('model_has_roles')->whereIn('model_id', $userIds)->where('model_type', 'App\Models\User')->delete();
        User::where('email', 'LIKE', '%@ioms.test')->forceDelete();

        Role::where('name', 'LIKE', '%Regional Office%')
            ->orWhere('name', 'LIKE', '%Metropolitan Branch%')
            ->orWhere('name', 'LIKE', '%Coast Division%')
            ->forceDelete();

        Branch::whereIn('name', [
            'North Regional Office',
            'South Metropolitan Branch',
            'East Coast Division'
        ])->forceDelete();

        $this->command->info('   ✓ Cleanup completed');
    }

    /**
     * Create 3 branches
     */
    private function createBranches()
    {
        $branchNames = [
            'North Regional Office',
            'South Metropolitan Branch',
            'East Coast Division'
        ];

        $branches = collect();

        foreach ($branchNames as $name) {
            $branch = Branch::create([
                'name' => $name,
                'created_by' => 1, // Assuming admin user ID 1 exists
            ]);
            $branches->push($branch);
            $this->command->info("   ✓ Created branch: {$name}");
        }

        return $branches;
    }

    /**
     * Create at least 5 roles for each branch
     */
    private function createRolesForBranches($branches)
    {
        $roleTemplates = [
            ['name' => 'Branch Manager', 'description' => 'Manages all branch operations'],
            ['name' => 'Senior Administrator', 'description' => 'Handles administrative tasks'],
            ['name' => 'Operations Coordinator', 'description' => 'Coordinates daily operations'],
            ['name' => 'Contract Specialist', 'description' => 'Manages contracts and agreements'],
            ['name' => 'Financial Analyst', 'description' => 'Analyzes financial data and reports'],
            ['name' => 'Customer Relations Officer', 'description' => 'Manages customer relationships'],
            ['name' => 'Compliance Officer', 'description' => 'Ensures regulatory compliance'],
        ];

        $roles = collect();
        $priority = 100;

        foreach ($branches as $branch) {
            // Each branch gets 5-7 roles
            $numberOfRoles = rand(5, 7);
            $selectedRoles = collect($roleTemplates)->shuffle()->take($numberOfRoles);

            foreach ($selectedRoles as $roleTemplate) {
                $role = Role::create([
                    'name' => "{$roleTemplate['name']} - {$branch->name}",
                    'slug' => Str::slug($roleTemplate['name'] . ' ' . $branch->name),
                    'description' => $roleTemplate['description'],
                    'is_active' => true,
                    'priority' => $priority++,
                    'branch_id' => $branch->id,
                    'guard_name' => 'web',
                    'created_by' => 1,
                ]);
                $roles->push($role);
                $this->command->info("   ✓ Created role: {$role->name}");
            }
        }

        return $roles;
    }

    /**
     * Create 1-7 users for each role
     */
    private function createUsersForRoles($roles)
    {
        $firstNames = ['John', 'Jane', 'Michael', 'Emily', 'David', 'Sarah', 'Robert', 'Lisa', 'James', 'Mary',
                       'William', 'Jennifer', 'Richard', 'Patricia', 'Thomas', 'Linda', 'Charles', 'Barbara',
                       'Christopher', 'Elizabeth', 'Daniel', 'Susan', 'Matthew', 'Jessica', 'Anthony', 'Karen'];

        $lastNames = ['Smith', 'Johnson', 'Williams', 'Brown', 'Jones', 'Garcia', 'Miller', 'Davis', 'Rodriguez',
                      'Martinez', 'Hernandez', 'Lopez', 'Gonzalez', 'Wilson', 'Anderson', 'Thomas', 'Taylor',
                      'Moore', 'Jackson', 'Martin', 'Lee', 'Thompson', 'White', 'Harris', 'Sanchez', 'Clark'];

        $users = collect();
        $emailCounter = 1;

        foreach ($roles as $role) {
            $numberOfUsers = rand(1, 7);

            for ($i = 0; $i < $numberOfUsers; $i++) {
                $firstName = $firstNames[array_rand($firstNames)];
                $lastName = $lastNames[array_rand($lastNames)];
                $fullName = "{$firstName} {$lastName}";

                $user = User::create([
                    'name' => $fullName,
                    'email' => strtolower(str_replace(' ', '.', $fullName)) . ".{$emailCounter}@ioms.test",
                    'password' => Hash::make('password'),
                    'mobile' => $this->generatePhoneNumber(),
                    'active' => rand(0, 10) > 1, // 90% active
                    'branch_id' => $role->branch_id,
                    'role_id' => $role->id, // Direct role assignment
                    'created_by' => 1,
                ]);

                // Also sync with Spatie's model_has_roles pivot table
                \DB::table('model_has_roles')->insert([
                    'role_id' => $role->id,
                    'model_type' => 'App\Models\User',
                    'model_id' => $user->id,
                ]);

                $users->push($user);
                $emailCounter++;
            }
        }

        $this->command->info("   ✓ Created {$users->count()} users");
        return $users;
    }

    /**
     * Create 5 contract types distributed across branches
     */
    private function createContractTypes($branches)
    {
        $contractTypeData = [
            ['name' => 'Service Agreement', 'code' => 'SA', 'description' => 'Standard service agreements for ongoing services'],
            ['name' => 'Vendor Contract', 'code' => 'VC', 'description' => 'Contracts with external vendors and suppliers'],
            ['name' => 'Employment Contract', 'code' => 'EC', 'description' => 'Employee terms and conditions agreements'],
            ['name' => 'Partnership Agreement', 'code' => 'PA', 'description' => 'Strategic partnership and collaboration contracts'],
            ['name' => 'Lease Agreement', 'code' => 'LA', 'description' => 'Property and equipment lease contracts'],
        ];

        $contractTypes = collect();

        foreach ($contractTypeData as $index => $data) {
            // Distribute contract types across branches
            $branch = $branches[$index % $branches->count()];

            $contractType = ContractType::create([
                'name' => $data['name'],
                'code' => $data['code'],
                'description' => $data['description'],
                'branch_id' => $branch->id,
                'is_active' => true,
                'created_by' => 1,
            ]);

            $contractTypes->push($contractType);
            $this->command->info("   ✓ Created contract type: {$data['name']}");
        }

        return $contractTypes;
    }

    /**
     * Create 3-4 contracts for each contract type
     */
    private function createContractsForTypes($contractTypes, $branches, $users)
    {
        $companies = [
            'TechCorp Solutions', 'Global Industries Inc', 'Metro Services Ltd', 'Urban Development Co',
            'Pacific Trading Group', 'Enterprise Holdings', 'Strategic Partners LLC', 'Innovation Systems',
            'National Contractors', 'Regional Logistics', 'Premier Consulting', 'Advanced Technologies',
            'Dynamic Solutions', 'Professional Services', 'Elite Management', 'Integrated Systems',
            'Quality Assurance Ltd', 'Optimal Solutions', 'Modern Enterprises', 'Future Vision Corp'
        ];

        $contracts = collect();
        $contractCounter = 1000;

        foreach ($contractTypes as $contractType) {
            $numberOfContracts = rand(3, 4);

            for ($i = 0; $i < $numberOfContracts; $i++) {
                $contractCounter++;
                $company = $companies[array_rand($companies)];

                // Use the same branch as the contract type or a random one
                $branch = rand(0, 1) ? $contractType->branch : $branches->random();

                $contract = Contract::create([
                    'contract_number' => "{$contractType->code}-{$contractCounter}",
                    'contract_with' => $company,
                    'contract_type_id' => $contractType->id,
                    'branch_id' => $branch->id,
                    'grace_period_days' => rand(15, 90),
                    'is_active' => rand(0, 10) > 1, // 90% active
                    'created_by' => $users->random()->id,
                ]);

                $contracts->push($contract);
            }
        }

        $this->command->info("   ✓ Created {$contracts->count()} contracts");
        return $contracts;
    }

    /**
     * Create notification recipients and link them to contracts
     */
    private function createNotificationRecipientsAndLink($contracts, $branches, $users)
    {
        $designations = [
            'Contract Manager', 'Legal Advisor', 'Compliance Officer', 'Operations Director',
            'Finance Manager', 'Senior Administrator', 'Project Coordinator', 'Department Head',
            'Regional Manager', 'Branch Supervisor', 'Quality Assurance Lead', 'Technical Lead'
        ];

        $allRecipients = collect();

        // Create a pool of recipients for each branch
        foreach ($branches as $branch) {
            $recipientsPerBranch = rand(5, 8);

            for ($i = 0; $i < $recipientsPerBranch; $i++) {
                $user = $users->where('branch_id', $branch->id)->random();
                $nameParts = explode(' ', $user->name);
                $firstName = $nameParts[0] ?? 'John';
                $lastName = $nameParts[1] ?? 'Doe';

                $recipient = NotificationRecipient::create([
                    'name' => $user->name,
                    'email' => $user->email,
                    'mobile' => $this->generatePhoneNumber(),
                    'designation' => $designations[array_rand($designations)],
                    'branch_id' => $branch->id,
                    'is_active' => rand(0, 10) > 1, // 90% active
                    'created_by' => 1,
                ]);

                $allRecipients->push($recipient);
            }
        }

        $this->command->info("   ✓ Created {$allRecipients->count()} notification recipients");

        // Link recipients to contracts (2+ recipients per contract)
        foreach ($contracts as $contract) {
            // Get recipients from the same branch or any branch
            $branchRecipients = $allRecipients->where('branch_id', $contract->branch_id);

            // If not enough recipients in the same branch, use all recipients
            if ($branchRecipients->count() < 2) {
                $branchRecipients = $allRecipients;
            }

            // Assign 2-4 recipients per contract
            $numberOfRecipients = rand(2, min(4, $branchRecipients->count()));
            $selectedRecipients = $branchRecipients->random($numberOfRecipients);

            foreach ($selectedRecipients as $recipient) {
                $contract->notificationRecipients()->attach($recipient->id, [
                    'created_at' => now(),
                    'updated_at' => now(),
                ]);
            }
        }

        $this->command->info("   ✓ Linked recipients to contracts");
    }

    /**
     * Generate a random phone number
     */
    private function generatePhoneNumber()
    {
        return '+1-' . rand(200, 999) . '-' . rand(200, 999) . '-' . rand(1000, 9999);
    }
}
