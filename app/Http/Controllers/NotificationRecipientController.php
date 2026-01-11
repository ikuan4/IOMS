<?php

namespace App\Http\Controllers;

use App\Models\NotificationRecipient;
use App\Models\User;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;

class NotificationRecipientController extends Controller
{
    /**
     * Display a listing of notification recipients with filters and search
     */
    public function index(Request $request)
    {
        if (!auth()->user()->hasPermission('notification-recipients.view')) {
            abort(403, 'Unauthorized action.');
        }

        $user = Auth::user();
        $search = $request->query('search');
        $status = $request->query('status', 'all');

        // Base query with branch filtering
        $baseQuery = NotificationRecipient::withTrashed()
            ->when(!$user->isSuperAdmin(), function ($q) use ($user) {
                $q->where('branch_id', $user->branch_id);
            });

        $statusCounts = [
            'all' => (clone $baseQuery)->count(),
            'active' => (clone $baseQuery)->whereNull('deleted_at')->where('is_active', true)->count(),
            'inactive' => (clone $baseQuery)->whereNull('deleted_at')->where('is_active', false)->count(),
            'deleted' => (clone $baseQuery)->onlyTrashed()->count(),
        ];

        // Main query
        $query = NotificationRecipient::with(['branch', 'creator', 'updater', 'deletedBy'])
            ->withTrashed()
            ->when(!$user->isSuperAdmin(), function ($q) use ($user) {
                $q->where('branch_id', $user->branch_id);
            })
            ->orderBy('name', 'asc');

        // Search filter
        if ($search) {
            $query->where(function ($q) use ($search) {
                $q->where('name', 'like', "%{$search}%")
                    ->orWhere('designation', 'like', "%{$search}%")
                    ->orWhere('email', 'like', "%{$search}%")
                    ->orWhere('mobile', 'like', "%{$search}%");
            });
        }

        // Status filter
        if ($status === 'active') {
            $query->whereNull('deleted_at')->where('is_active', true);
        } elseif ($status === 'inactive') {
            $query->whereNull('deleted_at')->where('is_active', false);
        } elseif ($status === 'deleted') {
            $query->onlyTrashed();
        }

        $recipients = $query->paginate(10)->appends($request->query());

        return view('notification-recipients.index', compact(
            'recipients',
            'search',
            'status',
            'statusCounts'
        ));
    }

    /**
     * Show the form for creating a new recipient
     */
    public function create()
    {
        if (!auth()->user()->hasPermission('notification-recipients.create')) {
            abort(403, 'Unauthorized action.');
        }

        $recipient = new NotificationRecipient();
        return view('notification-recipients.create', compact('recipient'));
    }

    /**
     * Store a newly created recipient in storage
     */
    public function store(Request $request)
    {
        if (!Auth::user()->hasPermission('notification-recipients.create')) {
            abort(403, 'Unauthorized action.');
        }

        $data = $request->validate([
            'name' => ['required', 'string', 'max:255'],
            'designation' => ['required', 'string', 'max:255'],
            'email' => ['nullable', 'email', 'max:255'],
            'mobile' => ['nullable', 'string', 'max:20'],
            'is_active' => ['nullable', 'boolean'],
        ]);

        $userId = Auth::id();
        $user = Auth::user();

        $recipient = NotificationRecipient::create([
            'branch_id' => $user->branch_id,
            'name' => $data['name'],
            'designation' => $data['designation'],
            'email' => $data['email'] ?? null,
            'mobile' => $data['mobile'] ?? null,
            'is_active' => $request->boolean('is_active', true),
            'created_by' => $userId,
            'updated_by' => $userId,
        ]);

        return redirect()
            ->route('notification-recipients.index')
            ->with('status', 'Notification recipient "' . $recipient->name . '" created successfully.');
    }

    /**
     * Display the specified recipient
     */
    public function show(NotificationRecipient $notificationRecipient)
    {
        if (!auth()->user()->hasPermission('notification-recipients.view')) {
            abort(403, 'Unauthorized action.');
        }

        // Ensure user can only view recipients from their branch
        if (!auth()->user()->isSuperAdmin() && $notificationRecipient->branch_id !== auth()->user()->branch_id) {
            abort(403, 'Unauthorized action.');
        }

        $notificationRecipient->load(['branch', 'creator', 'updater', 'deletedBy']);

        return view('notification-recipients.show', compact('notificationRecipient'));
    }

    /**
     * Show the form for editing the specified recipient
     */
    public function edit(NotificationRecipient $notificationRecipient)
    {
        if (!auth()->user()->hasPermission('notification-recipients.edit')) {
            abort(403, 'Unauthorized action.');
        }

        // Ensure user can only edit recipients from their branch
        if (!auth()->user()->isSuperAdmin() && $notificationRecipient->branch_id !== auth()->user()->branch_id) {
            abort(403, 'Unauthorized action.');
        }

        return view('notification-recipients.edit', compact('notificationRecipient'));
    }

    /**
     * Update the specified recipient in storage
     */
    public function update(Request $request, NotificationRecipient $notificationRecipient)
    {
        if (!auth()->user()->hasPermission('notification-recipients.edit')) {
            abort(403, 'Unauthorized action.');
        }

        // Ensure user can only edit recipients from their branch
        if (!auth()->user()->isSuperAdmin() && $notificationRecipient->branch_id !== auth()->user()->branch_id) {
            abort(403, 'Unauthorized action.');
        }

        $data = $request->validate([
            'name' => ['required', 'string', 'max:255'],
            'designation' => ['required', 'string', 'max:255'],
            'email' => ['nullable', 'email', 'max:255'],
            'mobile' => ['nullable', 'string', 'max:20'],
            'is_active' => ['nullable', 'boolean'],
        ]);

        $notificationRecipient->update([
            'name' => $data['name'],
            'designation' => $data['designation'],
            'email' => $data['email'] ?? null,
            'mobile' => $data['mobile'] ?? null,
            'is_active' => $request->boolean('is_active'),
            'updated_by' => Auth::id(),
        ]);

        return redirect()
            ->route('notification-recipients.index')
            ->with('status', 'Notification recipient "' . $notificationRecipient->name . '" updated successfully.');
    }

    /**
     * Remove the specified recipient from storage
     */
    public function destroy(NotificationRecipient $notificationRecipient)
    {
        if (!auth()->user()->hasPermission('notification-recipients.delete')) {
            abort(403, 'Unauthorized action.');
        }

        // Ensure user can only delete recipients from their branch
        if (!auth()->user()->isSuperAdmin() && $notificationRecipient->branch_id !== auth()->user()->branch_id) {
            abort(403, 'Unauthorized action.');
        }

        $name = $notificationRecipient->name;
        $notificationRecipient->delete();

        return redirect()
            ->route('notification-recipients.index')
            ->with('deleted', 'Notification recipient "' . $name . '" deleted successfully.');
    }

    /**
     * Restore a soft-deleted recipient
     */
    public function restore(Request $request, $id)
    {
        if (!auth()->user()->hasPermission('notification-recipients.restore')) {
            abort(403, 'Unauthorized action.');
        }

        $recipient = NotificationRecipient::withTrashed()->findOrFail($id);

        // Ensure user can only restore recipients from their branch
        if (!auth()->user()->isSuperAdmin() && $recipient->branch_id !== auth()->user()->branch_id) {
            abort(403, 'Unauthorized action.');
        }

        if ($recipient->trashed()) {
            $recipient->restoreWithUser();
            $recipient->is_active = true;
            $recipient->updated_by = auth()->id();
            $recipient->save();

            $message = 'Notification recipient "' . $recipient->name . '" restored successfully.';
            $messageType = 'status';
        } else {
            $message = 'Notification recipient "' . $recipient->name . '" is not deleted.';
            $messageType = 'error';
        }

        $redirectParams = ['status' => 'deleted'];

        if ($request->filled('search')) {
            $redirectParams['search'] = $request->input('search');
        }

        return redirect()
            ->route('notification-recipients.index', $redirectParams)
            ->with($messageType, $message);
    }
}

