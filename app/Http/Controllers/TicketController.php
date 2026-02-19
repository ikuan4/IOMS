<?php

namespace App\Http\Controllers;

use App\Models\AuditLog;
use App\Models\Ticket;
use App\Models\TicketComment;
use App\Models\TicketCommentDraftFile;
use App\Models\TicketCommentFile;
use App\Models\TicketEvent;
use App\Models\TicketDraftFile;
use App\Models\TicketFile;
use App\Models\TicketModule;
use App\Models\TicketType;
use App\Models\User;
use Illuminate\Http\Request;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Schema;
use Illuminate\Support\Facades\Storage;
use Illuminate\Validation\Rule;

class TicketController extends Controller
{
    /**
     * Pending tickets assigned to the current user
     */
    public function pending(Request $request): mixed
    {
        if (!$request->filled('status')) {
            $request->merge(['status' => 'pending']);
        }

        return $this->indexInternal($request, 'tickets.pending.view', true);
    }

    /**
     * Ticket details + timeline
     */
    public function show(Ticket $ticket): mixed
    {
        $user = Auth::user();
        if (!$user) {
            abort(403, 'Unauthorized action.');
        }

        $canViewAll = $user->isSuperAdmin() || $user->hasPermission('tickets.view');
        $canViewMine = $user->isSuperAdmin() || $user->hasPermission('tickets.pending.view');
        if (!$canViewAll && !$canViewMine) {
            abort(403, 'Unauthorized action.');
        }

        if (!$user->isSuperAdmin() && $ticket->branch_id !== $user->branch_id) {
            abort(403, 'Unauthorized action.');
        }

        // If user only has pending-view permission, only allow tickets currently assigned to them.
        if (!$canViewAll && $canViewMine && (string) $ticket->assigned_to !== (string) Auth::id()) {
            abort(403, 'Unauthorized action.');
        }

        $ticket->loadMissing(['ticketType', 'ticketModule', 'assignee', 'branch']);

        if (Schema::hasTable('ticket_files')) {
            $ticket->loadMissing(['files.storedFile']);
        }

        if (Schema::hasTable('ticket_events')) {
            $ticket->loadMissing(['events.actor', 'events.fromUser', 'events.toUser']);
        }

        if (Schema::hasTable('ticket_comments')) {
            $ticket->loadMissing(['comments.user']);

            if (Schema::hasTable('ticket_comment_files')) {
                $ticket->loadMissing(['comments.files.storedFile']);
            }
        }

        $assignees = collect();
        $canForward = $user->isSuperAdmin() || $user->hasPermission('tickets.edit');
        if ($canForward) {
            $assigneesQuery = User::query()->whereNull('deleted_at');
            if (!$user->isSuperAdmin()) {
                $assigneesQuery->where('branch_id', $user->branch_id);
            }
            $assignees = $assigneesQuery->orderBy('name')->get(['id', 'name', 'email']);
        }

        $timeline = collect();

        if ($ticket->relationLoaded('events')) {
            foreach ($ticket->events as $event) {
                $label = match ($event->event_type) {
                    'created' => 'Ticket created',
                    'updated' => 'Ticket updated',
                    'deleted' => 'Ticket deleted',
                    'restored' => 'Ticket restored',
                    'assigned' => 'Assignment changed',
                    'forwarded' => 'Ticket forwarded',
                    'status_changed' => 'Status changed',
                    'commented' => 'Comment added',
                    default => strtoupper(str_replace('_', ' ', (string) $event->event_type)),
                };

                $details = null;
                if ($event->event_type === 'status_changed') {
                    $from = $event->meta['from'] ?? null;
                    $to = $event->meta['to'] ?? null;
                    if ($from !== null || $to !== null) {
                        $details = trim('' . ($from ? strtoupper(str_replace('_', ' ', (string) $from)) : '') . ' → ' . ($to ? strtoupper(str_replace('_', ' ', (string) $to)) : ''), " ");
                    }
                }

                if (in_array($event->event_type, ['assigned', 'forwarded'], true)) {
                    /** @var \App\Models\User|null $fromUser */
                    $fromUser = $event->fromUser;
                    /** @var \App\Models\User|null $toUser */
                    $toUser = $event->toUser;

                    $fromName = $fromUser ? $fromUser->name : ($event->from_user_id ? ('User #' . $event->from_user_id) : 'Unassigned');
                    $toName = $toUser ? $toUser->name : ($event->to_user_id ? ('User #' . $event->to_user_id) : 'Unassigned');
                    $details = $fromName . ' → ' . $toName;

                    $reason = $event->meta['reason'] ?? null;
                    if (is_string($reason) && trim($reason) !== '') {
                        $details .= ' (Reason: ' . trim($reason) . ')';
                    }
                }

                $timeline->push([
                    'kind' => 'event',
                    'created_at' => $event->created_at,
                    /** @var \App\Models\User|null $actor */
                    'actor' => (($actor = $event->actor) ? $actor->name : 'System'),
                    'label' => $label,
                    'details' => $details,
                ]);
            }
        }

        if ($ticket->relationLoaded('comments')) {
            foreach ($ticket->comments as $comment) {
                $attachments = [];
                if ($comment->relationLoaded('files')) {
                    foreach ($comment->files as $cf) {
                        $sf = $cf->storedFile;
                        if (!$sf) {
                            continue;
                        }

                        $attachments[] = [
                            'stored_file_id' => (int) $sf->getKey(),
                            'filename' => (string) $sf->original_filename,
                            'mime_type' => (string) ($sf->mime_type ?? ''),
                            'inline_url' => route('tickets.files.inline', ['ticket' => $ticket->getKey(), 'storedFile' => $sf->getKey()]),
                            'download_url' => route('tickets.files.download', ['ticket' => $ticket->getKey(), 'storedFile' => $sf->getKey()]),
                        ];
                    }
                }

                $timeline->push([
                    'kind' => 'comment',
                    'created_at' => $comment->created_at,
                    /** @var \App\Models\User|null $commentUser */
                    'actor' => (($commentUser = $comment->user) ? $commentUser->name : 'Unknown'),
                    'label' => $comment->is_internal ? 'Internal comment' : 'Comment',
                    'details' => $comment->body,
                    'attachments' => $attachments,
                ]);
            }
        }

        $timeline = $timeline
            ->filter(fn ($x) => !empty($x['created_at']))
            ->sortBy('created_at')
            ->values();

        $commentDraftKey = (string) \Illuminate\Support\Str::uuid();
        return view('tickets.show', compact('ticket', 'timeline', 'assignees', 'canForward', 'commentDraftKey'));
    }

    /**
     * Forward a ticket to another user (logs a forwarded event)
     */
    public function forward(Request $request, Ticket $ticket): mixed
    {
        $user = Auth::user();
        if (!$user || !$user->hasPermission('tickets.edit')) {
            abort(403, 'Unauthorized action.');
        }

        if (!$user->isSuperAdmin() && $ticket->branch_id !== $user->branch_id) {
            abort(403, 'Unauthorized action.');
        }

        $data = $request->validate([
            'to_user_id' => ['required', 'integer', 'exists:users,id'],
            'reason' => ['nullable', 'string', 'max:1000'],
        ]);

        $toUser = User::query()->whereKey($data['to_user_id'])->firstOrFail();
        if (!$user->isSuperAdmin() && $toUser->branch_id !== $ticket->branch_id) {
            return back()->withErrors(['to_user_id' => 'Selected user must be in the same branch.'])->withInput();
        }

        $fromUserId = $ticket->assigned_to;

        // Forwarding updates the assignee and records a movement event.
        $ticket->assigned_to = (int) $toUser->getKey();
        $ticket->save();

        AuditLog::log('forward_ticket', $ticket, [
            'from_user_id' => $fromUserId,
            'to_user_id' => (int) $toUser->getKey(),
            'reason' => $data['reason'] ?? null,
        ]);

        if (Schema::hasTable('ticket_events')) {
            TicketEvent::create([
                'ticket_id' => (int) $ticket->getKey(),
                'branch_id' => (int) $ticket->branch_id,
                'actor_id' => Auth::id(),
                'event_type' => 'forwarded',
                'from_user_id' => $fromUserId ? (int) $fromUserId : null,
                'to_user_id' => (int) $toUser->getKey(),
                'meta' => [
                    'reason' => $data['reason'] ?? null,
                ],
            ]);
        }

        return redirect()
            ->route('tickets.show', $ticket)
            ->with('status', 'Ticket forwarded successfully.');
    }

    /**
     * Add a comment to a ticket (also logs a commented event)
     */
    public function comment(Request $request, Ticket $ticket): mixed
    {
        $user = Auth::user();
        if (!$user) {
            abort(403, 'Unauthorized action.');
        }

        $canViewAll = $user->isSuperAdmin() || $user->hasPermission('tickets.view');
        $canViewMine = $user->isSuperAdmin() || $user->hasPermission('tickets.pending.view');
        if (!$canViewAll && !$canViewMine) {
            abort(403, 'Unauthorized action.');
        }

        if (!$user->isSuperAdmin() && $ticket->branch_id !== $user->branch_id) {
            abort(403, 'Unauthorized action.');
        }

        if (!$canViewAll && $canViewMine && (string) $ticket->assigned_to !== (string) Auth::id()) {
            abort(403, 'Unauthorized action.');
        }

        $data = $request->validate([
            'body' => ['required', 'string', 'max:5000'],
            'is_internal' => ['nullable', 'boolean'],
            'attachments' => ['nullable', 'array'],
            'attachments.*' => ['file', 'max:20480'],
            'draft_key' => ['nullable', 'string', 'max:64'],
        ]);

        if (!Schema::hasTable('ticket_comments')) {
            return back()->withErrors(['body' => 'Ticket comments table is not available.']);
        }

        $comment = TicketComment::create([
            'ticket_id' => (int) $ticket->getKey(),
            'user_id' => Auth::id(),
            'is_internal' => (bool) ($data['is_internal'] ?? true),
            'body' => (string) $data['body'],
        ]);

        // Attach uploaded files to comment
        if (Schema::hasTable('ticket_comment_files')) {
            $this->attachCommentFilesFromRequest($request, $ticket, $comment);

            $draftKey = $data['draft_key'] ?? null;
            if (is_string($draftKey) && $draftKey !== '' && Schema::hasTable('ticket_comment_draft_files')) {
                $draftRows = TicketCommentDraftFile::query()
                    ->where('ticket_id', (int) $ticket->getKey())
                    ->where('draft_key', $draftKey)
                    ->get(['stored_file_id']);

                foreach ($draftRows as $row) {
                    TicketCommentFile::firstOrCreate([
                        'ticket_comment_id' => (int) $comment->getKey(),
                        'stored_file_id' => (int) $row->stored_file_id,
                    ]);
                }

                TicketCommentDraftFile::query()
                    ->where('ticket_id', (int) $ticket->getKey())
                    ->where('draft_key', $draftKey)
                    ->delete();
            }
        }

        AuditLog::log('comment_ticket', $ticket, ['comment_id' => $comment->getKey()]);

        if (Schema::hasTable('ticket_events')) {
            TicketEvent::create([
                'ticket_id' => (int) $ticket->getKey(),
                'branch_id' => (int) $ticket->branch_id,
                'actor_id' => Auth::id(),
                'event_type' => 'commented',
                'meta' => [
                    'comment_id' => (int) $comment->getKey(),
                    'is_internal' => (bool) $comment->is_internal,
                ],
            ]);
        }

        return redirect()
            ->route('tickets.show', $ticket)
            ->with('status', 'Comment added successfully.');
    }

    /**
     * List tickets with pagination + search + status filter
     */
    public function index(Request $request): mixed
    {
        return $this->indexInternal($request, 'tickets.view', false);
    }

    private function indexInternal(Request $request, string $requiredPermission, bool $restrictToAssignee): mixed
    {
        $user = Auth::user();
        if (!$user || (!$user->isSuperAdmin() && !$user->hasPermission($requiredPermission))) {
            abort(403, 'Unauthorized action.');
        }

        $viewMode = $restrictToAssignee ? 'my' : 'all';

        $search = $request->input('search');
        $status = $request->input('status', 'all');
        $assigneeCol = Ticket::assigneeForeignKey();
        $subjectCol = Ticket::subjectColumn();

        $baseQuery = Ticket::withTrashed()
            ->when(!$user->isSuperAdmin(), function ($q) use ($user) {
                $q->where('branch_id', $user->branch_id);
            });

        $notDeleted = (clone $baseQuery)->whereNull('deleted_at');
        if ($restrictToAssignee) {
            $notDeleted->where($assigneeCol, Auth::id());
        }

        if ($restrictToAssignee) {
            $hasEvents = Schema::hasTable('ticket_events');

            $statusCounts = [
                'pending' => (clone $notDeleted)
                    ->whereNotIn('status', ['resolved', 'closed'])
                    ->when($hasEvents, function ($q) {
                        $q->whereDoesntHave('events', function ($eq) {
                            $eq->where('event_type', 'forwarded')->where('to_user_id', Auth::id());
                        });
                    })
                    ->count(),
                'forwarded' => $hasEvents
                    ? (clone $notDeleted)
                        ->whereNotIn('status', ['resolved', 'closed'])
                        ->whereHas('events', function ($eq) {
                            $eq->where('event_type', 'forwarded')->where('to_user_id', Auth::id());
                        })
                        ->count()
                    : 0,
                'resolved' => (clone $notDeleted)->where('status', 'resolved')->count(),
                'closed' => (clone $notDeleted)->where('status', 'closed')->count(),
            ];
        } else {
            $statusCounts = [
                'all' => (clone $baseQuery)->count(),
                'pending' => (clone $baseQuery)
                    ->whereNull('deleted_at')
                    ->where($assigneeCol, Auth::id())
                    ->whereNotIn('status', ['resolved', 'closed'])
                    ->count(),
                'open' => (clone $baseQuery)->whereNull('deleted_at')->where('status', 'open')->count(),
                'in_progress' => (clone $baseQuery)->whereNull('deleted_at')->where('status', 'in_progress')->count(),
                'resolved' => (clone $baseQuery)->whereNull('deleted_at')->where('status', 'resolved')->count(),
                'closed' => (clone $baseQuery)->whereNull('deleted_at')->where('status', 'closed')->count(),
                'deleted' => (clone $baseQuery)->onlyTrashed()->count(),
            ];
        }

        $query = Ticket::withTrashed()
            ->with([
                'ticketType', 
                'ticketModule',
                'assignee' => function ($q) {
                    $q->select(['id', 'name', 'email'])
                      ->without(['createdBy', 'updatedBy', 'deletedBy', 'restoredBy', 'lastUpdatedBy']);
                },
                'branch' => function ($q) {
                    $q->select(['id', 'name'])
                      ->without(['createdBy', 'updatedBy', 'deletedBy', 'restoredBy', 'lastUpdatedBy']);
                }
            ])
            ->when(!$user->isSuperAdmin(), function ($q) use ($user) {
                $q->where('branch_id', $user->branch_id);
            })
            ->orderByDesc('id');

        if ($restrictToAssignee) {
            $query->whereNull('deleted_at')->where($assigneeCol, Auth::id());
        }

        if ($search) {
            $query->where(function ($q) use ($search) {
                $subjectCol = Ticket::subjectColumn();
                $q->where($subjectCol, 'like', "%{$search}%")
                    ->when(Schema::hasColumn('tickets', 'ticket_number'), function ($qq) use ($search) {
                        $qq->orWhere('ticket_number', 'like', "%{$search}%");
                    })
                    ->when(ctype_digit((string) $search), function ($qq) use ($search) {
                        $qq->orWhere('id', (int) $search);
                    })
                    ->orWhere('description', 'like', "%{$search}%")
                    ->orWhereHas('ticketType', function ($tq) use ($search) {
                        $tq->where('name', 'like', "%{$search}%");
                    })
                    ->orWhereHas('ticketModule', function ($mq) use ($search) {
                        $mq->where('name', 'like', "%{$search}%");
                    })
                    ->orWhereHas('assignee', function ($uq) use ($search) {
                        $uq->where('name', 'like', "%{$search}%")
                            ->orWhere('email', 'like', "%{$search}%");
                    });
            });
        }

        if ($restrictToAssignee) {
            switch ($status) {
                case 'pending':
                    $query
                        ->whereNotIn('status', ['resolved', 'closed'])
                        ->when(Schema::hasTable('ticket_events'), function ($q) {
                            $q->whereDoesntHave('events', function ($eq) {
                                $eq->where('event_type', 'forwarded')->where('to_user_id', Auth::id());
                            });
                        });

                    // Sort: priority first (urgent -> high -> medium -> low), then ageing (oldest first)
                    // Applies only to the Pending filter under My Tickets.
                    $query
                        ->reorder()
                        ->orderByRaw("FIELD(priority, 'urgent','high','medium','low')")
                        ->orderBy('created_at', 'asc')
                        ->orderByDesc('id');
                    break;
                case 'forwarded':
                    if (!Schema::hasTable('ticket_events')) {
                        $query->whereRaw('1=0');
                        break;
                    }

                    $query
                        ->whereNotIn('status', ['resolved', 'closed'])
                        ->whereHas('events', function ($eq) {
                            $eq->where('event_type', 'forwarded')->where('to_user_id', Auth::id());
                        });
                    break;
                case 'resolved':
                case 'closed':
                    $query->where('status', $status);
                    break;
                case 'all':
                default:
                    break;
            }
        } else {
            switch ($status) {
                case 'pending':
                    $query
                        ->whereNull('deleted_at')
                        ->where($assigneeCol, Auth::id())
                        ->whereNotIn('status', ['resolved', 'closed']);
                    break;
                case 'open':
                case 'in_progress':
                case 'resolved':
                case 'closed':
                    $query->whereNull('deleted_at')->where('status', $status);
                    break;
                case 'deleted':
                    $query->onlyTrashed();
                    break;
                case 'all':
                default:
                    break;
            }
        }

        $allowed = [5, 10, 15, 20, 30];
        $perPage = (int) $request->query('per_page', 10);
        if (!in_array($perPage, $allowed, true)) {
            $perPage = 10;
        }

        $tickets = $query->paginate($perPage)->withQueryString();

        $isSpaNavigation = strtolower((string) $request->header('X-SPA-Navigation')) === 'true';
        if ($request->ajax() && !$isSpaNavigation) {
            return view('tickets._tickets_table', compact('tickets', 'search', 'status'));
        }

        return view('tickets.index', compact('tickets', 'search', 'status', 'statusCounts', 'viewMode'));
    }

    /**
     * Show create form
     */
    public function create(): mixed
    {
        $user = Auth::user();
        if (!$user || !$user->hasPermission('tickets.create')) {
            abort(403, 'Unauthorized action.');
        }

        $ticket = new Ticket();

        $ticketTypesQuery = TicketType::query()->whereNull('deleted_at')->where('is_active', true);
        $ticketModulesQuery = TicketModule::query()->whereNull('deleted_at')->where('is_active', true);
        $assigneesQuery = User::query()->whereNull('deleted_at');

        if (!$user->isSuperAdmin()) {
            $ticketTypesQuery->where('branch_id', $user->branch_id);
            $ticketModulesQuery->where('branch_id', $user->branch_id);
            $assigneesQuery->where('branch_id', $user->branch_id);
        }

        $ticketTypes = $ticketTypesQuery->orderBy('name')->get();
        $ticketModules = $ticketModulesQuery->orderBy('name')->get();
        $assignees = $assigneesQuery->orderBy('name')->get(['id', 'name', 'email']);

        $draftKey = (string) \Illuminate\Support\Str::uuid();
        return view('tickets.create', compact('ticket', 'ticketTypes', 'ticketModules', 'assignees', 'draftKey'));
    }

    /**
     * Store new ticket
     */
    public function store(Request $request): mixed
    {
        $user = Auth::user();
        if (!$user || !$user->hasPermission('tickets.create')) {
            abort(403, 'Unauthorized action.');
        }

        $branchId = $user->branch_id;
        if ($branchId === null) {
            return back()->withErrors(['branch_id' => 'User branch is not set.']);
        }

        $data = $request->validate([
            'ticket_type_id' => ['required', 'integer', 'exists:ticket_types,id'],
            'ticket_module_id' => ['required', 'integer', 'exists:ticket_modules,id'],
            'subject' => ['required', 'string', 'max:255'],
            'description' => ['nullable', 'string', 'max:5000'],
            'status' => ['required', Rule::in(['open', 'in_progress', 'resolved', 'closed'])],
            'priority' => ['required', Rule::in(['low', 'medium', 'high', 'urgent'])],
            'assigned_to' => ['nullable', 'integer', 'exists:users,id'],
            'due_at' => ['nullable', 'date'],
            'attachments' => ['nullable', 'array'],
            'attachments.*' => ['file', 'max:20480'],
            'draft_key' => ['nullable', 'string', 'max:64'],
        ]);

        $ticketType = TicketType::query()->whereKey($data['ticket_type_id'])->firstOrFail();
        if (!$user->isSuperAdmin() && $ticketType->branch_id !== $branchId) {
            abort(403, 'Unauthorized action.');
        }

        if ($ticketType->deleted_at !== null || !$ticketType->is_active) {
            return back()->withErrors(['ticket_type_id' => 'Selected ticket type is not available.'])->withInput();
        }

        $ticketModule = TicketModule::query()->whereKey($data['ticket_module_id'])->firstOrFail();
        if (!$user->isSuperAdmin() && $ticketModule->branch_id !== $branchId) {
            abort(403, 'Unauthorized action.');
        }

        if ($ticketModule->deleted_at !== null || !$ticketModule->is_active) {
            return back()->withErrors(['ticket_module_id' => 'Selected module is not available.'])->withInput();
        }

        $ticket = new Ticket();
        $ticket->branch_id = (int) $branchId;
        $ticket->ticket_type_id = (int) $data['ticket_type_id'];
        $ticket->ticket_module_id = (int) $data['ticket_module_id'];

        // Compatibility: some legacy schemas require a non-null `tickets.type`.
        if (Schema::hasColumn('tickets', 'type') && empty($ticket->getAttribute('type'))) {
            $ticket->setAttribute('type', (string) $ticketType->name);
        }

        $ticket->subject = $data['subject'];
        $ticket->description = $data['description'] ?? null;
        $ticket->status = $data['status'];
        $ticket->priority = $data['priority'];

        // Compatibility: some legacy schemas require a non-null `tickets.severity`.
        if (Schema::hasColumn('tickets', 'severity') && empty($ticket->getAttribute('severity'))) {
            $ticket->setAttribute('severity', (string) $ticket->priority);
        }

        $ticket->assigned_to = $data['assigned_to'] ?? null;
        $ticket->due_at = $data['due_at'] ?? null;

        // status metadata for newer schemas
        $this->applyStatusMeta($ticket, null, $ticket->status);
        $ticket->save();

        // Attach uploaded files (direct + draft)
        if (Schema::hasTable('ticket_files')) {
            $this->attachTicketFilesFromRequest($request, $ticket);

            $draftKey = $data['draft_key'] ?? null;
            if (is_string($draftKey) && $draftKey !== '' && Schema::hasTable('ticket_draft_files')) {
                $draftRows = TicketDraftFile::query()
                    ->where('draft_key', $draftKey)
                    ->get(['stored_file_id']);

                foreach ($draftRows as $row) {
                    TicketFile::firstOrCreate([
                        'ticket_id' => (int) $ticket->getKey(),
                        'stored_file_id' => (int) $row->stored_file_id,
                    ]);
                }

                TicketDraftFile::query()->where('draft_key', $draftKey)->delete();
            }
        }

        AuditLog::log('create_ticket', $ticket, $ticket->toArray());

        if (Schema::hasTable('ticket_events')) {
            TicketEvent::logForTicket($ticket, 'created', [
                'status' => $ticket->status,
                'priority' => $ticket->priority,
            ]);

            if (!empty($ticket->assigned_to)) {
                TicketEvent::create([
                    'ticket_id' => (int) $ticket->getKey(),
                    'branch_id' => (int) $ticket->branch_id,
                    'actor_id' => Auth::id(),
                    'event_type' => 'assigned',
                    'from_user_id' => null,
                    'to_user_id' => (int) $ticket->assigned_to,
                    'meta' => ['reason' => 'Initial assignment'],
                ]);
            }
        }

        return redirect()
            ->route('tickets.index')
            ->with('status', 'Ticket created successfully.');
    }

    /**
     * Edit form
     */
    public function edit(Ticket $ticket): mixed
    {
        $user = Auth::user();
        if (!$user || !$user->hasPermission('tickets.edit')) {
            abort(403, 'Unauthorized action.');
        }

        if (!$user->isSuperAdmin() && $ticket->branch_id !== $user->branch_id) {
            abort(403, 'Unauthorized action.');
        }

        $ticketTypesQuery = TicketType::query()->whereNull('deleted_at')->where('is_active', true);
        $ticketModulesQuery = TicketModule::query()->whereNull('deleted_at')->where('is_active', true);
        $assigneesQuery = User::query()->whereNull('deleted_at');

        if (!$user->isSuperAdmin()) {
            $ticketTypesQuery->where('branch_id', $user->branch_id);
            $ticketModulesQuery->where('branch_id', $user->branch_id);
            $assigneesQuery->where('branch_id', $user->branch_id);
        }

        $ticketTypes = $ticketTypesQuery->orderBy('name')->get();
        $ticketModules = $ticketModulesQuery->orderBy('name')->get();
        $assignees = $assigneesQuery->orderBy('name')->get(['id', 'name', 'email']);

        $draftKey = (string) \Illuminate\Support\Str::uuid();
        return view('tickets.edit', compact('ticket', 'ticketTypes', 'ticketModules', 'assignees', 'draftKey'));
    }

    /**
     * Update existing ticket
     */
    public function update(Request $request, Ticket $ticket): mixed
    {
        $user = Auth::user();
        if (!$user || !$user->hasPermission('tickets.edit')) {
            abort(403, 'Unauthorized action.');
        }

        if (!$user->isSuperAdmin() && $ticket->branch_id !== $user->branch_id) {
            abort(403, 'Unauthorized action.');
        }

        $data = $request->validate([
            'ticket_type_id' => ['required', 'integer', 'exists:ticket_types,id'],
            'ticket_module_id' => ['required', 'integer', 'exists:ticket_modules,id'],
            'subject' => ['required', 'string', 'max:255'],
            'description' => ['nullable', 'string', 'max:5000'],
            'status' => ['required', Rule::in(['open', 'in_progress', 'resolved', 'closed'])],
            'priority' => ['required', Rule::in(['low', 'medium', 'high', 'urgent'])],
            'assigned_to' => ['nullable', 'integer', 'exists:users,id'],
            'due_at' => ['nullable', 'date'],
            'attachments' => ['nullable', 'array'],
            'attachments.*' => ['file', 'max:20480'],
            'draft_key' => ['nullable', 'string', 'max:64'],
        ]);

        $ticketType = TicketType::query()->whereKey($data['ticket_type_id'])->firstOrFail();
        if (!$user->isSuperAdmin() && $ticketType->branch_id !== $ticket->branch_id) {
            abort(403, 'Unauthorized action.');
        }

        if ($ticketType->deleted_at !== null || !$ticketType->is_active) {
            return back()->withErrors(['ticket_type_id' => 'Selected ticket type is not available.'])->withInput();
        }

        $ticketModule = TicketModule::query()->whereKey($data['ticket_module_id'])->firstOrFail();
        if (!$user->isSuperAdmin() && $ticketModule->branch_id !== $ticket->branch_id) {
            abort(403, 'Unauthorized action.');
        }

        if ($ticketModule->deleted_at !== null || !$ticketModule->is_active) {
            return back()->withErrors(['ticket_module_id' => 'Selected module is not available.'])->withInput();
        }

        $oldValues = $ticket->toArray();
        $oldStatus = (string) ($ticket->status ?? '');
        $oldAssignee = $ticket->assigned_to;

        $ticket->ticket_type_id = (int) $data['ticket_type_id'];
        $ticket->ticket_module_id = (int) $data['ticket_module_id'];

        // Compatibility: some legacy schemas require a non-null `tickets.type`.
        if (Schema::hasColumn('tickets', 'type')) {
            $ticket->setAttribute('type', (string) $ticketType->name);
        }

        $ticket->subject = $data['subject'];
        $ticket->description = $data['description'] ?? null;
        $ticket->status = $data['status'];
        $ticket->priority = $data['priority'];

        // Compatibility: some legacy schemas require a non-null `tickets.severity`.
        if (Schema::hasColumn('tickets', 'severity')) {
            $ticket->setAttribute('severity', (string) $ticket->priority);
        }

        $ticket->assigned_to = $data['assigned_to'] ?? null;
        $ticket->due_at = $data['due_at'] ?? null;

        $this->applyStatusMeta($ticket, $oldStatus, $ticket->status);
        $ticket->save();

        // Attach uploaded files (direct + draft)
        if (Schema::hasTable('ticket_files')) {
            $this->attachTicketFilesFromRequest($request, $ticket);

            $draftKey = $data['draft_key'] ?? null;
            if (is_string($draftKey) && $draftKey !== '' && Schema::hasTable('ticket_draft_files')) {
                $draftRows = TicketDraftFile::query()
                    ->where('draft_key', $draftKey)
                    ->get(['stored_file_id']);

                foreach ($draftRows as $row) {
                    TicketFile::firstOrCreate([
                        'ticket_id' => (int) $ticket->getKey(),
                        'stored_file_id' => (int) $row->stored_file_id,
                    ]);
                }

                TicketDraftFile::query()->where('draft_key', $draftKey)->delete();
            }
        }

        AuditLog::log('update_ticket', $ticket, $oldValues, $ticket->fresh()?->toArray() ?? []);

        if (Schema::hasTable('ticket_events')) {
            if ($oldStatus !== (string) $ticket->status) {
                TicketEvent::create([
                    'ticket_id' => (int) $ticket->getKey(),
                    'branch_id' => (int) $ticket->branch_id,
                    'actor_id' => Auth::id(),
                    'event_type' => 'status_changed',
                    'meta' => ['from' => $oldStatus, 'to' => (string) $ticket->status],
                ]);
            }

            if ((string) $oldAssignee !== (string) $ticket->assigned_to) {
                TicketEvent::create([
                    'ticket_id' => (int) $ticket->getKey(),
                    'branch_id' => (int) $ticket->branch_id,
                    'actor_id' => Auth::id(),
                    'event_type' => 'assigned',
                    'from_user_id' => $oldAssignee ? (int) $oldAssignee : null,
                    'to_user_id' => $ticket->assigned_to ? (int) $ticket->assigned_to : null,
                ]);
            }
        }

        return redirect()
            ->route('tickets.index')
            ->with('status', 'Ticket updated successfully.');
    }

    private function attachTicketFilesFromRequest(Request $request, Ticket $ticket): void
    {
        if (!Schema::hasTable('ticket_files') || !Schema::hasTable('stored_files')) {
            return;
        }

        if (!$request->hasFile('attachments')) {
            return;
        }

        foreach ((array) $request->file('attachments') as $file) {
            if (!$file instanceof UploadedFile) {
                continue;
            }

            $storedFile = $this->storeUploadedFileForTicket($file, (int) $ticket->branch_id, (int) $ticket->getKey());
            TicketFile::firstOrCreate([
                'ticket_id' => (int) $ticket->getKey(),
                'stored_file_id' => (int) $storedFile->getKey(),
            ]);
        }
    }

    private function attachCommentFilesFromRequest(Request $request, Ticket $ticket, TicketComment $comment): void
    {
        if (!Schema::hasTable('ticket_comment_files') || !Schema::hasTable('stored_files')) {
            return;
        }

        if (!$request->hasFile('attachments')) {
            return;
        }

        foreach ((array) $request->file('attachments') as $file) {
            if (!$file instanceof UploadedFile) {
                continue;
            }

            $storedFile = $this->storeUploadedFileForTicket($file, (int) $ticket->branch_id, (int) $ticket->getKey());
            TicketCommentFile::firstOrCreate([
                'ticket_comment_id' => (int) $comment->getKey(),
                'stored_file_id' => (int) $storedFile->getKey(),
            ]);
        }
    }

    private function storeUploadedFileForTicket(UploadedFile $file, int $branchId, int $ticketId): \App\Models\StoredFile
    {
        $sha256 = hash_file('sha256', $file->getRealPath());

        $existing = \App\Models\StoredFile::query()
            ->where('branch_id', $branchId)
            ->where('sha256', $sha256)
            ->first();

        if ($existing) {
            $disk = Storage::disk($existing->disk);
            if ($existing->path && $disk->exists($existing->path)) {
                return $existing;
            }
        }

        $diskName = 'local';
        $path = $file->store("branches/{$branchId}/tickets/{$ticketId}", $diskName);
        if ($path === false) {
            throw new \RuntimeException('Unable to store uploaded file');
        }

        if ($existing) {
            $existing->disk = $diskName;
            $existing->path = $path;
            $existing->original_filename = $file->getClientOriginalName();
            $existing->mime_type = $file->getClientMimeType();
            $existing->size_bytes = $file->getSize();
            $existing->save();
            return $existing;
        }

        try {
            return \App\Models\StoredFile::create([
                'branch_id' => $branchId,
                'disk' => $diskName,
                'path' => $path,
                'original_filename' => $file->getClientOriginalName(),
                'mime_type' => $file->getClientMimeType(),
                'size_bytes' => $file->getSize(),
                'sha256' => $sha256,
            ]);
        } catch (\Throwable $e) {
            $found = \App\Models\StoredFile::query()
                ->where('branch_id', $branchId)
                ->where('sha256', $sha256)
                ->first();

            if ($found) {
                return $found;
            }

            throw $e;
        }
    }

    /**
     * Soft delete ticket
     */
    public function destroy(Ticket $ticket): mixed
    {
        $user = Auth::user();
        if (!$user || !$user->hasPermission('tickets.delete')) {
            abort(403, 'Unauthorized action.');
        }

        if (!$user->isSuperAdmin() && $ticket->branch_id !== $user->branch_id) {
            abort(403, 'Unauthorized action.');
        }

        AuditLog::log('delete_ticket', $ticket, $ticket->toArray());

        if (Schema::hasTable('ticket_events')) {
            TicketEvent::logForTicket($ticket, 'deleted');
        }

        $ticket->delete();

        return redirect()
            ->route('tickets.index')
            ->with('deleted', 'Ticket deleted successfully.');
    }

    /**
     * Restore a soft-deleted ticket
     */
    public function restore(Request $request, int|string $id): mixed
    {
        $user = Auth::user();
        if (!$user || !$user->hasPermission('tickets.restore')) {
            abort(403, 'Unauthorized action.');
        }

        $ticket = Ticket::withTrashed()->findOrFail($id);

        if (!$user->isSuperAdmin() && $ticket->branch_id !== $user->branch_id) {
            abort(403, 'Unauthorized action.');
        }

        if ($ticket->trashed()) {
            $ticket->restoreWithUser();

            // Keep consistent with other modules: updated_by should reflect the restoring user
            $userId = Auth::id();
            $ticket->updated_by = is_int($userId) ? $userId : (ctype_digit((string) $userId) ? (int) $userId : null);
            $ticket->save();

            AuditLog::log('restore_ticket', $ticket, [], $ticket->fresh()?->toArray() ?? []);

            if (Schema::hasTable('ticket_events')) {
                TicketEvent::logForTicket($ticket, 'restored');
            }

            $message = 'Ticket restored successfully.';
            $messageType = 'status';
        } else {
            $message = 'Ticket is not deleted.';
            $messageType = 'error';
        }

        $redirectParams = ['status' => 'deleted'];
        if ($request->filled('search')) {
            $redirectParams['search'] = $request->input('search');
        }

        return redirect()
            ->route('tickets.index', $redirectParams)
            ->with($messageType, $message);
    }

    private function applyStatusMeta(Ticket $ticket, ?string $oldStatus, ?string $newStatus): void
    {
        if ($newStatus === null || $newStatus === '') {
            return;
        }

        // Newer schemas track resolved/closed timestamps + actor IDs.
        // Only attempt updates when those columns exist.
        $hasResolvedAt = Schema::hasColumn('tickets', 'resolved_at');
        $hasResolvedBy = Schema::hasColumn('tickets', 'resolved_by');
        $hasClosedAt = Schema::hasColumn('tickets', 'closed_at');
        $hasClosedBy = Schema::hasColumn('tickets', 'closed_by');

        if ($newStatus === 'resolved') {
            if ($hasResolvedAt && empty($ticket->getAttribute('resolved_at'))) {
                $ticket->setAttribute('resolved_at', now());
            }
            if ($hasResolvedBy && empty($ticket->getAttribute('resolved_by'))) {
                $ticket->setAttribute('resolved_by', Auth::id());
            }
        }

        if ($newStatus === 'closed') {
            if ($hasClosedAt && empty($ticket->getAttribute('closed_at'))) {
                $ticket->setAttribute('closed_at', now());
            }
            if ($hasClosedBy && empty($ticket->getAttribute('closed_by'))) {
                $ticket->setAttribute('closed_by', Auth::id());
            }
        }

        // If moving away from resolved/closed, don't automatically clear timestamps.
        // That history is often useful; if you want clearing behavior we can add it.
        // $oldStatus is intentionally unused.
    }
}
