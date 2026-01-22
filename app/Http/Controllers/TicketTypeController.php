<?php

namespace App\Http\Controllers;

use App\Models\AuditLog;
use App\Models\TicketType;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Validation\Rule;

class TicketTypeController extends Controller
{
    /**
     * List ticket types with pagination + search + status filter
     */
    public function index(Request $request): mixed
    {
        $user = Auth::user();
        if (!$user || !$user->hasPermission('ticket-types.view')) {
            abort(403, 'Unauthorized action.');
        }

        $search = $request->query('search');
        $status = $request->query('status', 'all');

        $baseQuery = TicketType::withTrashed()
            ->when(!$user->isSuperAdmin(), function ($q) use ($user) {
                $q->where('branch_id', $user->branch_id);
            });

        $statusCounts = [
            'all' => (clone $baseQuery)->count(),
            'active' => (clone $baseQuery)->whereNull('deleted_at')->where('is_active', true)->count(),
            'inactive' => (clone $baseQuery)->whereNull('deleted_at')->where('is_active', false)->count(),
            'deleted' => (clone $baseQuery)->onlyTrashed()->count(),
        ];

        $query = TicketType::withTrashed()
            ->with(['branch'])
            ->when(!$user->isSuperAdmin(), function ($q) use ($user) {
                $q->where('branch_id', $user->branch_id);
            })
            ->orderBy('name');

        if ($search) {
            $query->where(function ($q) use ($search) {
                $q->where('name', 'like', "%{$search}%")
                    ->orWhere('description', 'like', "%{$search}%");
            });
        }

        switch ($status) {
            case 'active':
                $query->whereNull('deleted_at')->where('is_active', true);
                break;
            case 'inactive':
                $query->whereNull('deleted_at')->where('is_active', false);
                break;
            case 'deleted':
                $query->onlyTrashed();
                break;
            case 'all':
            default:
                break;
        }

        $allowed = [5, 10, 15, 20, 30];
        $perPage = (int) $request->query('per_page', 10);
        if (!in_array($perPage, $allowed, true)) {
            $perPage = 10;
        }

        $ticketTypes = $query->paginate($perPage)->withQueryString();

        $isSpaNavigation = strtolower((string) $request->header('X-SPA-Navigation')) === 'true';
        if ($request->ajax() && !$isSpaNavigation) {
            return view('ticket-types._ticket_types_table', compact('ticketTypes', 'search', 'status'));
        }

        return view('ticket-types.index', compact('ticketTypes', 'search', 'status', 'statusCounts'));
    }

    /**
     * Show create form
     */
    public function create(): mixed
    {
        $user = Auth::user();
        if (!$user || !$user->hasPermission('ticket-types.create')) {
            abort(403, 'Unauthorized action.');
        }

        $ticketType = new TicketType();

        return view('ticket-types.create', compact('ticketType'));
    }

    /**
     * Store new ticket type
     */
    public function store(Request $request): mixed
    {
        $user = Auth::user();
        if (!$user || !$user->hasPermission('ticket-types.create')) {
            abort(403, 'Unauthorized action.');
        }

        $branchId = $user->branch_id;
        if ($branchId === null) {
            return back()->withErrors(['branch_id' => 'User branch is not set.']);
        }

        $data = $request->validate([
            'name' => [
                'required',
                'string',
                'max:255',
                Rule::unique('ticket_types', 'name')->where('branch_id', $branchId),
            ],
            'description' => ['nullable', 'string', 'max:2000'],
            'is_active' => ['nullable', 'boolean'],
        ]);

        $ticketType = new TicketType();
        $ticketType->branch_id = (int) $branchId;
        $ticketType->name = $data['name'];
        $ticketType->description = $data['description'] ?? null;
        $ticketType->is_active = $request->boolean('is_active', true);
        $ticketType->save();

        AuditLog::log('create_ticket_type', $ticketType, $ticketType->toArray());

        return redirect()
            ->route('ticket-types.index')
            ->with('status', 'Ticket type "' . $ticketType->name . '" created successfully.');
    }

    /**
     * Edit form
     */
    public function edit(TicketType $ticketType): mixed
    {
        $user = Auth::user();
        if (!$user || !$user->hasPermission('ticket-types.edit')) {
            abort(403, 'Unauthorized action.');
        }

        if (!$user->isSuperAdmin() && $ticketType->branch_id !== $user->branch_id) {
            abort(403, 'Unauthorized action.');
        }

        return view('ticket-types.edit', compact('ticketType'));
    }

    /**
     * Update existing ticket type
     */
    public function update(Request $request, TicketType $ticketType): mixed
    {
        $user = Auth::user();
        if (!$user || !$user->hasPermission('ticket-types.edit')) {
            abort(403, 'Unauthorized action.');
        }

        if (!$user->isSuperAdmin() && $ticketType->branch_id !== $user->branch_id) {
            abort(403, 'Unauthorized action.');
        }

        $data = $request->validate([
            'name' => [
                'required',
                'string',
                'max:255',
                Rule::unique('ticket_types', 'name')
                    ->where('branch_id', $ticketType->branch_id)
                    ->ignore($ticketType->id),
            ],
            'description' => ['nullable', 'string', 'max:2000'],
            'is_active' => ['nullable', 'boolean'],
        ]);

        $oldValues = $ticketType->toArray();

        $ticketType->name = $data['name'];
        $ticketType->description = $data['description'] ?? null;
        $ticketType->is_active = $request->boolean('is_active');
        $ticketType->save();

        AuditLog::log('update_ticket_type', $ticketType, $oldValues, $ticketType->fresh()?->toArray() ?? []);

        return redirect()
            ->route('ticket-types.index')
            ->with('status', 'Ticket type "' . $ticketType->name . '" updated successfully.');
    }

    /**
     * Soft delete ticket type
     */
    public function destroy(TicketType $ticketType): mixed
    {
        $user = Auth::user();
        if (!$user || !$user->hasPermission('ticket-types.delete')) {
            abort(403, 'Unauthorized action.');
        }

        if (!$user->isSuperAdmin() && $ticketType->branch_id !== $user->branch_id) {
            abort(403, 'Unauthorized action.');
        }

        $activeTicketCount = $ticketType->tickets()
            ->whereNull('deleted_at')
            ->count();

        if ($activeTicketCount > 0) {
            return redirect()
                ->route('ticket-types.index')
                ->with('error', 'Cannot delete ticket type due to ticket dependencies.');
        }

        AuditLog::log('delete_ticket_type', $ticketType, $ticketType->toArray());

        $name = $ticketType->name;
        $ticketType->delete();

        return redirect()
            ->route('ticket-types.index')
            ->with('deleted', 'Ticket type "' . $name . '" deleted successfully.');
    }

    /**
     * Check if a ticket type can be deleted (dependencies)
     */
    public function checkDeleteDependencies(TicketType $ticketType): \Illuminate\Http\JsonResponse
    {
        $user = Auth::user();
        if (!$user || !$user->hasPermission('ticket-types.view')) {
            abort(403, 'Unauthorized action.');
        }

        if (!$user->isSuperAdmin() && $ticketType->branch_id !== $user->branch_id) {
            abort(403, 'Unauthorized action.');
        }

        $ticketsQuery = $ticketType->tickets()->whereNull('deleted_at');
        $ticketCount = (clone $ticketsQuery)->count();
        $ticketsSample = (clone $ticketsQuery)
            ->orderBy('id')
            ->limit(5)
            ->get(['id', 'subject']);

        $canDelete = $ticketCount === 0;

        $dependencies = [];
        if ($ticketCount > 0) {
            $dependencies[] = [
                'type' => 'tickets',
                'count' => $ticketCount,
                'message' => 'Tickets using this ticket type',
                'items' => $ticketsSample->map(fn($t) => [
                    'id' => $t->id,
                    'name' => $t->subject,
                ])->toArray(),
            ];
        }

        $message = 'Ticket type can be deleted safely.';
        if (!$canDelete && $ticketCount > 0) {
            $message = "Cannot delete ticket type '{$ticketType->name}' because it has {$ticketCount} ticket" . ($ticketCount > 1 ? 's' : '') . '.';
        }

        return response()->json([
            'can_delete' => $canDelete,
            'dependencies' => $dependencies,
            'message' => $message,
        ]);
    }

    /**
     * Restore a soft-deleted ticket type
     */
    public function restore(Request $request, int|string $id): mixed
    {
        $user = Auth::user();
        if (!$user || !$user->hasPermission('ticket-types.restore')) {
            abort(403, 'Unauthorized action.');
        }

        $ticketType = TicketType::withTrashed()->findOrFail($id);

        if (!$user->isSuperAdmin() && $ticketType->branch_id !== $user->branch_id) {
            abort(403, 'Unauthorized action.');
        }

        if ($ticketType->trashed()) {
            $ticketType->restoreWithUser();
            $ticketType->is_active = true;
            $ticketType->save();

            AuditLog::log('restore_ticket_type', $ticketType, [], $ticketType->fresh()?->toArray() ?? []);

            $message = 'Ticket type "' . $ticketType->name . '" restored successfully.';
            $messageType = 'status';
        } else {
            $message = 'Ticket type "' . $ticketType->name . '" is not deleted.';
            $messageType = 'error';
        }

        $redirectParams = ['status' => 'deleted'];
        if ($request->filled('search')) {
            $redirectParams['search'] = $request->input('search');
        }

        return redirect()
            ->route('ticket-types.index', $redirectParams)
            ->with($messageType, $message);
    }
}
