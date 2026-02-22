<?php

namespace Database\Seeders;

use App\Models\Branch;
use App\Models\Ticket;
use App\Models\TicketComment;
use App\Models\TicketEvent;
use App\Models\TicketModule;
use App\Models\TicketType;
use App\Models\User;
use Carbon\Carbon;
use Illuminate\Database\Seeder;

class ComprehensiveTicketSeeder extends Seeder
{
    public function run(): void
    {
        $this->command->info('Starting comprehensive ticket seeding...');

        $branches = Branch::query()->whereNotNull('id')->get();

        if ($branches->isEmpty()) {
            $this->command->warn('No branches found. Run BranchSeeder first.');
            return;
        }

        $ticketDescriptions = [
            'User unable to login to the system',
            'Report generation is taking too long',
            'Dashboard charts not loading correctly',
            'Contract expiry notifications not sent',
            'Email bouncing for certain domains',
            'Database queries becoming slow during peak hours',
            'Export to Excel failing for large datasets',
            'Audit logs not recording all user actions',
            'Permission denied errors for valid users',
            'System crash when uploading large files',
            'Missing data in contract version history',
            'Incorrect role assignment for new users',
            'Batch processing stuck in loop',
            'API responses timing out randomly',
            'User profile updates not reflecting immediately',
        ];

        $commentTexts = [
            'I\'ve encountered this issue as well. Please prioritize.',
            'This is blocking our daily operations.',
            'Can someone from the technical team look into this?',
            'The issue appears to be intermittent.',
            'I\'ve checked the logs and found error code XYZ.',
            'Reproduced the issue in the test environment.',
            'Workaround available: restart the service.',
            'Waiting for vendor response on this issue.',
            'User permissions have been verified.',
            'This seems to be a browser-specific issue.',
            'Performance has degraded since last update.',
            'Confirmed this is working in production.',
            'Need more information from the user.',
            'Temporary fix applied, permanent solution in progress.',
            'This affects only certain user roles.',
        ];

        $eventTypes = ['created', 'status_changed', 'assigned', 'commented', 'priority_changed', 'resolved'];
        $statuses = ['open', 'in_progress', 'resolved', 'closed'];
        $priorities = ['low', 'medium', 'high', 'urgent'];

        $totalTickets = 0;
        $totalComments = 0;
        $totalEvents = 0;

        foreach ($branches as $branch) {
            $this->command->info("Processing branch: {$branch->name}");

            // Get users assigned to this branch
            $branchUsers = User::whereHas('branches', function ($q) use ($branch) {
                $q->where('branch_id', $branch->id);
            })->where('active', true)->get();

            if ($branchUsers->isEmpty()) {
                $this->command->warn("  No active users in {$branch->name}. Skipping...");
                continue;
            }

            // Get ticket types and modules
            $ticketTypes = TicketType::where('branch_id', $branch->id)
                ->where('is_active', true)
                ->get();

            $ticketModules = TicketModule::where('branch_id', $branch->id)
                ->where('is_active', true)
                ->get();

            if ($ticketTypes->isEmpty() || $ticketModules->isEmpty()) {
                $this->command->warn("  Missing ticket types or modules in {$branch->name}. Skipping...");
                continue;
            }

            // Create 5-8 tickets per branch (at least 5 per user type)
            $ticketsPerBranch = rand(20, 35);

            for ($i = 0; $i < $ticketsPerBranch; $i++) {
                $creator = $branchUsers->random();
                $assignee = $branchUsers->random();

                $ticket = Ticket::create([
                    'branch_id' => $branch->id,
                    'ticket_type_id' => $ticketTypes->random()->id,
                    'ticket_module_id' => $ticketModules->random()->id,
                    'subject' => '[' . $priorities[array_rand($priorities)] . '] ' . $ticketDescriptions[array_rand($ticketDescriptions)],
                    'description' => 'Detailed issue description for ticket #' . ($i + 1) . ' in ' . $branch->name . '. Please investigate and resolve.',
                    'status' => $statuses[array_rand($statuses)],
                    'priority' => $priorities[array_rand($priorities)],
                    'assigned_to' => $assignee->id,
                    'created_by' => $creator->id,
                    'updated_by' => $assignee->id,
                    'due_at' => Carbon::now()->addDays(rand(1, 30)),
                    'created_at' => Carbon::now()->subDays(rand(0, 30)),
                    'updated_at' => Carbon::now()->subDays(rand(0, 20)),
                ]);

                $totalTickets++;

                // Create 1-3 comments per ticket
                $numberOfComments = rand(1, 3);
                for ($c = 0; $c < $numberOfComments; $c++) {
                    $commentUser = $branchUsers->random();
                    $commentTime = (clone $ticket->created_at)->addHours(rand(1, 48 * 30));

                    $commentColumn = TicketComment::bodyColumn();

                    $comment = TicketComment::create([
                        'ticket_id' => $ticket->id,
                        'user_id' => $commentUser->id,
                        'is_internal' => rand(0, 1) === 1,
                        $commentColumn => $commentTexts[array_rand($commentTexts)],
                        'created_at' => $commentTime,
                    ]);

                    // Create a comment event
                    TicketEvent::create([
                        'ticket_id' => $ticket->id,
                        'branch_id' => $branch->id,
                        'actor_id' => $commentUser->id,
                        'event_type' => 'commented',
                        'meta' => ['comment_id' => $comment->id],
                        'created_at' => $commentTime,
                    ]);

                    $totalComments++;
                    $totalEvents++;
                }

                // Create 2-4 events per ticket
                $numberOfEvents = rand(2, 4);
                $eventTimeBase = clone $ticket->created_at;

                for ($e = 0; $e < $numberOfEvents; $e++) {
                    $eventType = $eventTypes[array_rand($eventTypes)];
                    $eventTime = (clone $eventTimeBase)->addHours(rand(1, 12));
                    $eventActor = $branchUsers->random();

                    $eventData = [
                        'ticket_id' => $ticket->id,
                        'branch_id' => $branch->id,
                        'actor_id' => $eventActor->id,
                        'event_type' => $eventType,
                        'created_at' => $eventTime,
                    ];

                    // Add relevant meta data based on event type
                    if ($eventType === 'assigned') {
                        $eventData['from_user_id'] = $branchUsers->random()->id;
                        $eventData['to_user_id'] = $assignee->id;
                        $eventData['meta'] = ['previous_assignee' => $eventData['from_user_id']];
                    } elseif ($eventType === 'status_changed') {
                        $eventData['meta'] = [
                            'from_status' => $statuses[array_rand($statuses)],
                            'to_status' => $ticket->status,
                        ];
                    } elseif ($eventType === 'priority_changed') {
                        $eventData['meta'] = [
                            'from_priority' => $priorities[array_rand($priorities)],
                            'to_priority' => $ticket->priority,
                        ];
                    }

                    TicketEvent::create($eventData);
                    $totalEvents++;
                }
            }

            $this->command->info("  ✓ Created {$ticketsPerBranch} tickets with comments and events");
        }

        $this->command->info('✅ Comprehensive ticket seeding completed!');
        $this->command->info("   - Total Tickets: {$totalTickets}");
        $this->command->info("   - Total Comments: {$totalComments}");
        $this->command->info("   - Total Events: {$totalEvents}");
    }
}
