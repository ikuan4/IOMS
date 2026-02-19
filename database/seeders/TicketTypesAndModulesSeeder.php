<?php

namespace Database\Seeders;

use App\Models\Branch;
use App\Models\TicketType;
use App\Models\TicketModule;
use Illuminate\Database\Seeder;

class TicketTypesAndModulesSeeder extends Seeder
{
    public function run(): void
    {
        $this->command->info('Starting ticket types and modules seeding...');

        $ticketTypeNames = [
            'Bug Report',
            'Feature Request',
            'Enhancement',
            'Documentation',
            'Performance Issue',
            'Security Issue',
            'User Experience',
            'Data Issue',
            'Integration Issue',
            'System Maintenance',
        ];

        $ticketModuleNames = [
            'Authentication',
            'Authorization',
            'Contract Management',
            'Ticket System',
            'User Management',
            'Report Generation',
            'Dashboard',
            'Email System',
            'File Management',
            'Audit Logging',
            'Notifications',
            'API',
            'Database',
            'Export/Import',
            'Search',
        ];

        $branches = Branch::query()->whereNotNull('id')->get();

        if ($branches->isEmpty()) {
            $this->command->warn('No branches found.');
            return;
        }

        $totalTypes = 0;
        $totalModules = 0;

        foreach ($branches as $branch) {
            $this->command->info("Processing branch: {$branch->name}");

            // Create ticket types for this branch
            foreach ($ticketTypeNames as $typeName) {
                TicketType::firstOrCreate(
                    [
                        'branch_id' => $branch->id,
                        'name' => $typeName,
                    ],
                    [
                        'is_active' => true,
                        'created_by' => null,
                        'updated_by' => null,
                    ]
                );
                $totalTypes++;
            }

            // Create ticket modules for this branch
            foreach ($ticketModuleNames as $moduleName) {
                TicketModule::firstOrCreate(
                    [
                        'branch_id' => $branch->id,
                        'name' => $moduleName,
                    ],
                    [
                        'is_active' => true,
                        'created_by' => null,
                        'updated_by' => null,
                    ]
                );
                $totalModules++;
            }

            $this->command->info("  ✓ Created " . count($ticketTypeNames) . " ticket types and " . count($ticketModuleNames) . " modules");
        }

        $this->command->info('✅ Ticket types and modules seeding completed!');
        $this->command->info("   - Total Ticket Types: {$totalTypes}");
        $this->command->info("   - Total Ticket Modules: {$totalModules}");
    }
}
