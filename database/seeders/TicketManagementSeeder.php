<?php

namespace Database\Seeders;

use App\Models\Branch;
use App\Models\Ticket;
use App\Models\TicketModule;
use App\Models\TicketType;
use App\Models\User;
use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Str;
use Illuminate\Support\Facades\Schema;

class TicketManagementSeeder extends Seeder
{
    public function run(): void
    {
        if (!Schema::hasTable('ticket_types') || !Schema::hasTable('ticket_modules') || !Schema::hasTable('tickets')) {
            $this->command?->warn('Ticket tables are missing; run migrations first.');
            return;
        }

        if (!Schema::hasColumn('tickets', 'ticket_module_id')) {
            $this->command?->warn('tickets.ticket_module_id is missing; run migrations first.');
            return;
        }

        $ticketTitleColumn = Schema::hasColumn('tickets', 'subject')
            ? 'subject'
            : (Schema::hasColumn('tickets', 'title') ? 'title' : null);

        if (!$ticketTitleColumn) {
            $this->command?->warn('Cannot seed tickets: neither tickets.subject nor tickets.title exists.');
            return;
        }

        $assigneeColumn = Schema::hasColumn('tickets', 'assigned_to')
            ? 'assigned_to'
            : (Schema::hasColumn('tickets', 'assignee_id') ? 'assignee_id' : null);

        $dueColumn = Schema::hasColumn('tickets', 'due_at')
            ? 'due_at'
            : (Schema::hasColumn('tickets', 'sla_due_at') ? 'sla_due_at' : null);

        $reporterColumn = Schema::hasColumn('tickets', 'reporter_id') ? 'reporter_id' : null;
        $ticketNumberColumn = Schema::hasColumn('tickets', 'ticket_number') ? 'ticket_number' : null;
        $typeColumn = Schema::hasColumn('tickets', 'type') ? 'type' : null;
        $severityColumn = Schema::hasColumn('tickets', 'severity') ? 'severity' : null;

        $typeNames = [
            'Bug',
            'Feature Request',
            'Support',
            'Maintenance',
            'Access / Permission',
        ];

        $moduleNames = [
            'User Management',
            'Contract Management',
            'Ticket Management',
            'Audit Logs',
            'System Settings',
        ];

        $statuses = ['open', 'in_progress', 'resolved', 'closed'];
        $priorities = ['low', 'medium', 'high', 'urgent'];

        $branches = Branch::query()->whereNull('deleted_at')->orderBy('id')->get();
        if ($branches->isEmpty()) {
            $branches = collect([
                Branch::query()->create([
                    'name' => 'Main Branch',
                    'created_by' => null,
                    'updated_by' => null,
                ]),
            ]);
        }

        foreach ($branches as $branch) {
            $branchId = (int) $branch->id;

            $seedUserId = User::query()
                ->whereNull('deleted_at')
                ->where('active', true)
                ->where('branch_id', $branchId)
                ->orderBy('id')
                ->value('id');

            if (!$seedUserId) {
                $seedUserId = User::query()
                    ->whereNull('deleted_at')
                    ->orderBy('id')
                    ->value('id');
            }

            if ($reporterColumn && !$seedUserId) {
                $this->command?->warn('Cannot seed tickets: no users exist to populate reporter_id.');
                continue;
            }

            // 1) Ticket Types
            foreach ($typeNames as $i => $name) {
                $typeData = [
                    'description' => 'Seeded ticket type: ' . $name,
                    'is_active' => true,
                    'created_by' => $seedUserId,
                    'updated_by' => $seedUserId,
                ];

                if (Schema::hasColumn('ticket_types', 'code')) {
                    $typeData['code'] = 'TT-' . $branchId . '-' . str_pad((string) ($i + 1), 2, '0', STR_PAD_LEFT);
                }

                TicketType::query()->updateOrCreate(
                    ['branch_id' => $branchId, 'name' => $name],
                    $typeData
                );
            }

            // 2) Ticket Modules
            foreach ($moduleNames as $i => $name) {
                $moduleData = [
                    'description' => 'Seeded ticket module: ' . $name,
                    'is_active' => true,
                    'created_by' => $seedUserId,
                    'updated_by' => $seedUserId,
                ];

                if (Schema::hasColumn('ticket_modules', 'code')) {
                    $moduleData['code'] = 'TM-' . $branchId . '-' . str_pad((string) ($i + 1), 2, '0', STR_PAD_LEFT);
                }

                TicketModule::query()->updateOrCreate(
                    ['branch_id' => $branchId, 'name' => $name],
                    $moduleData
                );
            }

            $ticketTypes = TicketType::query()
                ->where('branch_id', $branchId)
                ->whereIn('name', $typeNames)
                ->whereNull('deleted_at')
                ->orderBy('id')
                ->get(['id', 'name']);

            $ticketModules = TicketModule::query()
                ->where('branch_id', $branchId)
                ->whereIn('name', $moduleNames)
                ->whereNull('deleted_at')
                ->orderBy('id')
                ->get(['id', 'name']);

            // 3) Tickets: 2 per (type x module)
            foreach ($ticketTypes as $typeIndex => $type) {
                foreach ($ticketModules as $moduleIndex => $module) {
                    for ($k = 1; $k <= 2; $k++) {
                        $title = sprintf(
                            '[Seed] %s / %s Ticket #%d (Branch %d)',
                            $type->name,
                            $module->name,
                            $k,
                            $branchId
                        );

                        $existsQuery = DB::table('tickets')
                            ->where('branch_id', $branchId)
                            ->where('ticket_type_id', (int) $type->id)
                            ->where('ticket_module_id', (int) $module->id)
                            ->where($ticketTitleColumn, $title);

                        $exists = $existsQuery->exists();

                        if ($exists) {
                            continue;
                        }

                        $status = $statuses[($typeIndex + $moduleIndex + ($k - 1)) % count($statuses)];
                        $priority = $priorities[($typeIndex + ($k - 1)) % count($priorities)];

                        $row = [
                            'branch_id' => $branchId,
                            'ticket_type_id' => (int) $type->id,
                            'ticket_module_id' => (int) $module->id,
                            $ticketTitleColumn => $title,
                            'description' => 'Seeded ticket for testing ticket types + ticket modules.',
                            'status' => $status,
                            'priority' => $priority,
                            'created_at' => now(),
                            'updated_at' => now(),
                        ];

                        if ($ticketNumberColumn) {
                            $row[$ticketNumberColumn] = 'TCK-' . $branchId . '-' . (int) $type->id . '-' . (int) $module->id . '-' . $k;
                        }

                        if ($reporterColumn) {
                            $row[$reporterColumn] = $seedUserId ? (int) $seedUserId : null;
                        }

                        if ($assigneeColumn) {
                            $row[$assigneeColumn] = $seedUserId ? (int) $seedUserId : null;
                        }

                        if ($dueColumn) {
                            $row[$dueColumn] = null;
                        }

                        if ($typeColumn) {
                            $row[$typeColumn] = 'general';
                        }

                        if ($severityColumn) {
                            $row[$severityColumn] = 'medium';
                        }

                        if (Schema::hasColumn('tickets', 'updated_by')) {
                            $row['updated_by'] = $seedUserId ? (int) $seedUserId : null;
                        }

                        DB::table('tickets')->insert($row);
                    }
                }
            }
        }

        $this->command?->info('✓ TicketManagementSeeder: created/updated ticket types, ticket modules, and tickets for each branch.');
    }
}
