<?php

namespace App\Console\Commands;

use Illuminate\Console\Command;
use App\Models\Branch;
use App\Models\Role;
use App\Models\User;
use App\Models\ContractType;
use App\Models\Contract;
use App\Models\NotificationRecipient;

class VerifyDummyData extends Command
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'verify:dummy-data';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'Verify the seeded dummy data and display statistics';

    /**
     * Execute the console command.
     */
    public function handle()
    {
        $this->info('========================================');
        $this->info('IOMS Dummy Data Verification Report');
        $this->info('========================================');
        $this->newLine();

        // Overall Statistics
        $this->info('📊 Overall Statistics:');
        $this->line("   - Branches: " . Branch::count());
        $this->line("   - Roles: " . Role::count());
        $this->line("   - Users: " . User::count());
        $this->line("   - Contract Types: " . ContractType::count());
        $this->line("   - Contracts: " . Contract::count());
        $this->line("   - Notification Recipients: " . NotificationRecipient::count());
        $this->newLine();

        // Branch Details
        $this->info('🏢 Branches Details:');
        $branches = Branch::with(['roles'])->get();
        foreach ($branches as $branch) {
            $userCount = User::where('branch_id', $branch->id)->count();
            $contractCount = Contract::where('branch_id', $branch->id)->count();
            $this->line("   • {$branch->name}");
            $this->line("     - Roles: {$branch->roles->count()}");
            $this->line("     - Users: {$userCount}");
            $this->line("     - Contracts: {$contractCount}");
        }
        $this->newLine();

        // Role Details
        $this->info('👥 Roles with User Counts:');
        $roles = Role::withCount('users')->where('name', 'LIKE', '%Regional Office%')
            ->orWhere('name', 'LIKE', '%Metropolitan Branch%')
            ->orWhere('name', 'LIKE', '%Coast Division%')
            ->get();
        foreach ($roles->take(5) as $role) {
            $this->line("   • {$role->name}");
            $this->line("     - Users: {$role->users_count}");
        }
        if ($roles->count() > 5) {
            $this->line("   ... and " . ($roles->count() - 5) . " more roles");
        }
        $this->newLine();

        // Contract Types
        $this->info('📋 Contract Types with Contracts:');
        $contractTypes = ContractType::withCount('contracts')->get();
        foreach ($contractTypes as $type) {
            $this->line("   • {$type->name} ({$type->code})");
            $this->line("     - Contracts: {$type->contracts_count}");
        }
        $this->newLine();

        // Sample Contract
        $this->info('📝 Sample Contract with Recipients:');
        $sampleContract = Contract::with(['notificationRecipients', 'contractType', 'branch'])->first();
        if ($sampleContract) {
            $this->line("   • Contract: {$sampleContract->contract_number}");
            $this->line("     - Type: {$sampleContract->contractType->name}");
            $this->line("     - With: {$sampleContract->contract_with}");
            $this->line("     - Branch: {$sampleContract->branch->name}");
            $this->line("     - Recipients: {$sampleContract->notificationRecipients->count()}");
            foreach ($sampleContract->notificationRecipients as $recipient) {
                $this->line("       - {$recipient->name} ({$recipient->designation})");
            }
        }
        $this->newLine();

        $this->info('✅ Verification Complete!');

        return Command::SUCCESS;
    }
}
