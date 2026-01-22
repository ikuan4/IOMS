<?php

namespace App\Http\Controllers;

use App\Models\AuditLog;
use App\Models\TicketModule;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Validation\Rule;

class TicketModuleController extends Controller
{
    /**
     * List ticket modules with pagination + search + status filter
     */
    public function index(Request $request): mixed
    {
        $user = Auth::user();
        if (!$user || !$user->hasPermission('ticket-modules.view')) {
            abort(403, 'Unauthorized action.');
        }

        $search = $request->query('search');
        $status = $request->query('status', 'all');

        $baseQuery = TicketModule::withTrashed()
            ->when(!$user->isSuperAdmin(), function ($q) use ($user) {
                $q->where('branch_id', $user->branch_id);
            });

        $statusCounts = [
            'all' => (clone $baseQuery)->count(),
            'active' => (clone $baseQuery)->whereNull('deleted_at')->where('is_active', true)->count(),
            'inactive' => (clone $baseQuery)->whereNull('deleted_at')->where('is_active', false)->count(),
            'deleted' => (clone $baseQuery)->onlyTrashed()->count(),
        ];

        $query = TicketModule::withTrashed()
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

        $ticketModules = $query->paginate($perPage)->withQueryString();

        $isSpaNavigation = strtolower((string) $request->header('X-SPA-Navigation')) === 'true';
        if ($request->ajax() && !$isSpaNavigation) {
            return view('ticket-modules._ticket_modules_table', compact('ticketModules', 'search', 'status'));
        }

        return view('ticket-modules.index', compact('ticketModules', 'search', 'status', 'statusCounts'));
    }

    /**
     * Show create form
     */
    public function create(): mixed
    {
        $user = Auth::user();
        if (!$user || !$user->hasPermission('ticket-modules.create')) {
            abort(403, 'Unauthorized action.');
        }

        $ticketModule = new TicketModule();

        return view('ticket-modules.create', compact('ticketModule'));
    }

    /**
     * Store new ticket module
     */
    public function store(Request $request): mixed
    {
        $user = Auth::user();
        if (!$user || !$user->hasPermission('ticket-modules.create')) {
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
                Rule::unique('ticket_modules', 'name')->where('branch_id', $branchId),
            ],
            'description' => ['nullable', 'string', 'max:2000'],
            'is_active' => ['nullable', 'boolean'],
        ]);

        $ticketModule = new TicketModule();
        $ticketModule->branch_id = (int) $branchId;
        $ticketModule->name = $data['name'];
        $ticketModule->description = $data['description'] ?? null;
        $ticketModule->is_active = $request->boolean('is_active', true);
        $ticketModule->save();

        AuditLog::log('create_ticket_module', $ticketModule, $ticketModule->toArray());

        return redirect()
            ->route('ticket-modules.index')
            ->with('status', 'Ticket module "' . $ticketModule->name . '" created successfully.');
    }

    /**
     * Edit form
     */
    public function edit(TicketModule $ticketModule): mixed
    {
        $user = Auth::user();
        if (!$user || !$user->hasPermission('ticket-modules.edit')) {
            abort(403, 'Unauthorized action.');
        }

        if (!$user->isSuperAdmin() && $ticketModule->branch_id !== $user->branch_id) {
            abort(403, 'Unauthorized action.');
        }

        return view('ticket-modules.edit', compact('ticketModule'));
    }

    /**
     * Update existing ticket module
     */
    public function update(Request $request, TicketModule $ticketModule): mixed
    {
        $user = Auth::user();
        if (!$user || !$user->hasPermission('ticket-modules.edit')) {
            abort(403, 'Unauthorized action.');
        }

        if (!$user->isSuperAdmin() && $ticketModule->branch_id !== $user->branch_id) {
            abort(403, 'Unauthorized action.');
        }

        $data = $request->validate([
            'name' => [
                'required',
                'string',
                'max:255',
                Rule::unique('ticket_modules', 'name')
                    ->where('branch_id', $ticketModule->branch_id)
                    ->ignore($ticketModule->id),
            ],
            'description' => ['nullable', 'string', 'max:2000'],
            'is_active' => ['nullable', 'boolean'],
        ]);

        $oldValues = $ticketModule->toArray();

        $ticketModule->name = $data['name'];
        $ticketModule->description = $data['description'] ?? null;
        $ticketModule->is_active = $request->boolean('is_active');
        $ticketModule->save();

        AuditLog::log('update_ticket_module', $ticketModule, $oldValues, $ticketModule->fresh()?->toArray() ?? []);

        return redirect()
            ->route('ticket-modules.index')
            ->with('status', 'Ticket module "' . $ticketModule->name . '" updated successfully.');
    }

    /**
     * Soft delete ticket module
     */
    public function destroy(TicketModule $ticketModule): mixed
    {
        $user = Auth::user();
        if (!$user || !$user->hasPermission('ticket-modules.delete')) {
            abort(403, 'Unauthorized action.');
        }

        if (!$user->isSuperAdmin() && $ticketModule->branch_id !== $user->branch_id) {
            abort(403, 'Unauthorized action.');
        }

        $activeTicketCount = $ticketModule->tickets()
            ->whereNull('deleted_at')
            ->count();

        if ($activeTicketCount > 0) {
            return redirect()
                ->route('ticket-modules.index')
                ->with('error', 'Cannot delete ticket module due to ticket dependencies.');
        }

        AuditLog::log('delete_ticket_module', $ticketModule, $ticketModule->toArray());

        $name = $ticketModule->name;
        $ticketModule->delete();

        return redirect()
            ->route('ticket-modules.index')
            ->with('deleted', 'Ticket module "' . $name . '" deleted successfully.');
    }

    /**
     * Check if a ticket module can be deleted (dependencies)
     */
    public function checkDeleteDependencies(TicketModule $ticketModule): \Illuminate\Http\JsonResponse
    {
        $user = Auth::user();
        if (!$user || !$user->hasPermission('ticket-modules.view')) {
            abort(403, 'Unauthorized action.');
        }

        if (!$user->isSuperAdmin() && $ticketModule->branch_id !== $user->branch_id) {
            abort(403, 'Unauthorized action.');
        }

        $ticketsQuery = $ticketModule->tickets()->whereNull('deleted_at');
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
                'message' => 'Tickets using this ticket module',
                'items' => $ticketsSample->map(fn($t) => [
                    'id' => $t->id,
                    'name' => $t->subject,
                ])->toArray(),
            ];
        }

        $message = 'Ticket module can be deleted safely.';
        if (!$canDelete && $ticketCount > 0) {
            $message = "Cannot delete ticket module '{$ticketModule->name}' because it has {$ticketCount} ticket" . ($ticketCount > 1 ? 's' : '') . '.';
        }

        return response()->json([
            'can_delete' => $canDelete,
            'dependencies' => $dependencies,
            'message' => $message,
        ]);
    }

    /**
     * Restore a soft-deleted ticket module
     */
    public function restore(Request $request, int|string $id): mixed
    {
        $user = Auth::user();
        if (!$user || !$user->hasPermission('ticket-modules.restore')) {
            abort(403, 'Unauthorized action.');
        }

        $ticketModule = TicketModule::withTrashed()->findOrFail($id);

        if (!$user->isSuperAdmin() && $ticketModule->branch_id !== $user->branch_id) {
            abort(403, 'Unauthorized action.');
        }

        if ($ticketModule->trashed()) {
            $ticketModule->restoreWithUser();
            $ticketModule->is_active = true;
            $ticketModule->save();

            AuditLog::log('restore_ticket_module', $ticketModule, [], $ticketModule->fresh()?->toArray() ?? []);

            $message = 'Ticket module "' . $ticketModule->name . '" restored successfully.';
            $messageType = 'status';
        } else {
            $message = 'Ticket module "' . $ticketModule->name . '" is not deleted.';
            $messageType = 'error';
        }

        $redirectParams = ['status' => 'deleted'];
        if ($request->filled('search')) {
            $redirectParams['search'] = $request->input('search');
        }

        return redirect()
            ->route('ticket-modules.index', $redirectParams)
            ->with($messageType, $message);
    }
}
