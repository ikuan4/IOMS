<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;
use App\Models\Branch;
use App\Models\User;
use App\Models\ContractType;
use App\Models\Contract;
use App\Models\ContractVersion;
use App\Models\NotificationRecipient;
use Illuminate\Support\Str;
use Carbon\Carbon;

class ContractsSeeder extends Seeder
{
    // Indian company names and types
    private $companyNames = [
        'Tata Consultancy Services', 'Infosys Limited', 'Wipro Technologies', 'Tech Mahindra',
        'HCL Technologies', 'Reliance Industries', 'Bharti Airtel', 'ICICI Bank',
        'HDFC Bank', 'State Bank of India', 'Axis Bank', 'Kotak Mahindra Bank',
        'Larsen & Toubro', 'Mahindra & Mahindra', 'Asian Paints', 'ITC Limited',
        'Sun Pharmaceutical', 'Dr. Reddy\'s Laboratories', 'Cipla Limited', 'Lupin Pharma',
        'Maruti Suzuki', 'Bajaj Auto', 'Hero MotoCorp', 'TVS Motors',
        'Godrej Industries', 'Aditya Birla Group', 'Vedanta Resources', 'JSW Steel',
        'Ultratech Cement', 'ACC Cement', 'Ambuja Cement', 'Grasim Industries',
        'Hindustan Unilever', 'Britannia Industries', 'Nestle India', 'Dabur India',
        'Patanjali Ayurved', 'Emami Limited', 'Marico Industries', 'Colgate Palmolive India'
    ];

    private $contractTypeNames = [
        'Service Agreement', 'Maintenance Contract', 'Supply Agreement', 'Software License',
        'Lease Agreement', 'Consultancy Agreement', 'AMC Agreement', 'Vendor Agreement',
        'Partnership Agreement', 'Distribution Agreement', 'Marketing Agreement', 'Sales Agreement',
        'Employment Contract', 'Non-Disclosure Agreement', 'Purchase Order', 'Master Service Agreement',
        'IT Support Contract', 'Security Services', 'Cleaning Services', 'Catering Services',
        'Insurance Policy', 'Rental Agreement', 'Equipment Lease', 'Vehicle Lease',
        'Training Agreement', 'Franchise Agreement', 'Licensing Agreement', 'Telecom Services'
    ];

    // Indian first names for notification recipients
    private $firstNames = [
        'Rajesh', 'Priya', 'Amit', 'Sneha', 'Vikram', 'Anjali', 'Rahul', 'Pooja', 'Arjun', 'Kavita',
        'Sanjay', 'Neha', 'Aditya', 'Riya', 'Karthik', 'Divya', 'Rohan', 'Ananya', 'Suresh', 'Meera',
        'Vishal', 'Shreya', 'Nikhil', 'Tanvi', 'Deepak', 'Ishita', 'Manish', 'Swati', 'Arun', 'Nisha'
    ];

    private $lastNames = [
        'Sharma', 'Verma', 'Singh', 'Kumar', 'Patel', 'Gupta', 'Reddy', 'Agarwal', 'Joshi', 'Mehta',
        'Nair', 'Shah', 'Rao', 'Iyer', 'Pillai', 'Jain', 'Desai', 'Kulkarni', 'Pandey', 'Mishra'
    ];

    private $designations = [
        'General Manager', 'Deputy Manager', 'Assistant Manager', 'Senior Manager', 'Manager',
        'Chief Operating Officer', 'Vice President', 'Director', 'Senior Executive', 'Executive',
        'Regional Head', 'Branch Head', 'Department Head', 'Team Leader', 'Coordinator'
    ];

    public function run(): void
    {
        $this->command->info('Creating contract types, contracts, and notification recipients...');

        $branches = Branch::where('name', '!=', 'Main Branch')
            ->whereNotNull('id')
            ->get();

        if ($branches->isEmpty()) {
            $this->command->warn('No branches found (excluding Main Branch). Please run BranchSeeder first.');
            return;
        }

        foreach ($branches as $branch) {
            // Ensure branch has an ID
            if (!$branch->id) {
                $this->command->warn("Branch {$branch->name} has no ID. Skipping...");
                continue;
            }

            $this->command->info("Processing {$branch->name} (ID: {$branch->id})...");

            // Get users from this branch to use as creators
            $branchUsers = User::where('branch_id', $branch->id)->get();

            if ($branchUsers->isEmpty()) {
                $this->command->warn("No users found in {$branch->name}. Skipping...");
                continue;
            }

            // Step 1: Create notification recipients for this branch (7-12)
            $this->createNotificationRecipients($branch, $branchUsers, rand(7, 12));

            // Step 2: Create contract types for this branch (3-7)
            $contractTypes = $this->createContractTypes($branch, $branchUsers, rand(3, 7));

            // Step 3: Create contracts for each contract type (5-8 per type)
            $this->createContractsForTypes($branch, $contractTypes, $branchUsers);
        }

        $this->command->info('Contract seeding completed!');
    }

    /**
     * Create notification recipients for a branch
     */
    private function createNotificationRecipients(Branch $branch, $users, int $count): void
    {
        $creator = $users->random();

        for ($i = 0; $i < $count; $i++) {
            $firstName = $this->firstNames[array_rand($this->firstNames)];
            $lastName = $this->lastNames[array_rand($this->lastNames)];
            $fullName = $firstName . ' ' . $lastName;
            $designation = $this->designations[array_rand($this->designations)];

            // Generate email (invalid domain)
            $emailSlug = Str::slug($fullName);
            $email = $emailSlug . rand(10, 99) . '@notification.invalid';

            // Generate Indian mobile number
            $mobile = $this->generateIndianMobile();

            NotificationRecipient::create([
                'branch_id' => $branch->id,
                'name' => $fullName,
                'designation' => $designation,
                'email' => $email,
                'mobile' => $mobile,
                'is_active' => true,
                'created_by' => $creator->id,
            ]);
        }

        $this->command->info("  ✓ Created {$count} notification recipients");
    }

    /**
     * Create contract types for a branch
     */
    private function createContractTypes(Branch $branch, $users, int $count): array
    {
        $creator = $users->random();
        $contractTypes = [];
        $usedTypes = [];
        $usedCodes = [];

        for ($i = 0; $i < $count; $i++) {
            // Get a unique contract type name
            do {
                $typeName = $this->contractTypeNames[array_rand($this->contractTypeNames)];
            } while (in_array($typeName, $usedTypes));

            $usedTypes[] = $typeName;

            // Generate unique 3-character code
            do {
                $code = strtoupper(substr(str_replace(' ', '', $typeName), 0, 3));
                if (strlen($code) < 3) {
                    $code = str_pad($code, 3, 'X');
                }
            } while (in_array($code, $usedCodes));

            $usedCodes[] = $code;

            $contractType = ContractType::create([
                'branch_id' => $branch->id,
                'name' => $typeName,
                'description' => $typeName . ' for ' . $branch->name,
                'code' => $code,
                'is_active' => true,
                'created_by' => $creator->id,
            ]);

            $contractTypes[] = $contractType;
        }

        $this->command->info("  ✓ Created {$count} contract types");
        return $contractTypes;
    }

    /**
     * Create contracts for each contract type
     */
    private function createContractsForTypes(Branch $branch, array $contractTypes, $users): void
    {
        $totalContracts = 0;

        foreach ($contractTypes as $contractType) {
            $numberOfContracts = rand(5, 8);

            for ($i = 0; $i < $numberOfContracts; $i++) {
                $creator = $users->random();
                $companyName = $this->companyNames[array_rand($this->companyNames)];

                // Generate contract number: CT-{BRANCH_ID}/{TYPE_CODE}/{YYYY}/{sequential}
                $year = now()->year;
                $sequential = str_pad($i + 1, 4, '0', STR_PAD_LEFT);
                $contractNumber = "CT-{$branch->id}/{$contractType->code}/{$year}/{$sequential}";

                // Random grace period
                $gracePeriod = [15, 30, 45, 60, 90][array_rand([15, 30, 45, 60, 90])];

                $contract = Contract::create([
                    'branch_id' => $branch->id,
                    'contract_type_id' => $contractType->id,
                    'contract_number' => $contractNumber,
                    'contract_with' => $companyName,
                    'grace_period_days' => $gracePeriod,
                    'is_active' => true,
                    'created_by' => $creator->id,
                ]);

                // Create a contract version (each contract needs at least one version)
                $this->createContractVersion($contract, $creator->id);

                $totalContracts++;
            }
        }

        $this->command->info("  ✓ Created {$totalContracts} contracts with versions");
    }

    /**
     * Create a contract version
     */
    private function createContractVersion(Contract $contract, int $userId): void
    {
        // Random start date in the past (1-12 months ago)
        $startDate = Carbon::now()->subMonths(rand(1, 12))->startOfDay();

        // End date 1-3 years from start date
        $endDate = (clone $startDate)->addYears(rand(1, 3))->endOfDay();

        ContractVersion::create([
            'contract_id' => $contract->id,
            'version_number' => 1,
            'description' => 'Initial version',
            'start_date' => $startDate,
            'end_date' => $endDate,
            'created_by' => $userId,
        ]);
    }

    /**
     * Generate a valid Indian mobile number (10 digits starting with 6-9)
     */
    private function generateIndianMobile(): string
    {
        $firstDigit = rand(6, 9);
        $remainingDigits = '';
        for ($i = 0; $i < 9; $i++) {
            $remainingDigits .= rand(0, 9);
        }
        return $firstDigit . $remainingDigits;
    }
}
