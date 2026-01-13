<?php

namespace App\Http\Controllers;

use App\Exports\SystemUsersFullExport;
use App\Models\Branch;
use App\Models\AuditLog;
use App\Models\Role;
use App\Models\User;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Maatwebsite\Excel\Facades\Excel;
use Illuminate\View\View;
use Illuminate\Http\RedirectResponse;
use Symfony\Component\HttpFoundation\StreamedResponse;
use Symfony\Component\HttpFoundation\BinaryFileResponse;

class BranchController extends Controller
{
    public function exportSystemUsersExcel(Request $request): BinaryFileResponse
    {
        $currentUser = Auth::user();
        abort_unless($currentUser && $currentUser->isSuperAdmin(), 403);

        $filename = 'system-users-' . date('Ymd_His') . '.xlsx';
        return Excel::download(new SystemUsersFullExport(), $filename);
    }

    public function index(Request $request): View
    {
        $this->authorize('viewAny', Branch::class);

        $currentUser = Auth::user();

        // include trashed so deleted branches can be restored from the index
        $query = Branch::withTrashed();

        // Non-superadmin users can only see their own branch
        if ($currentUser && !$currentUser->isSuperAdmin()) {
            $query->where('id', $currentUser->branch_id);
        }

        if ($request->has('search') && $request->search) {
            $s = $request->search;
            $query->where('name', 'like', "%{$s}%");
        }

        // Respect per-page selection from query, with a safe whitelist and default 10
        $allowed = [5,10,15,20,30];
        $perPage = (int) $request->query('per_page', 10);
        if (!in_array($perPage, $allowed, true)) {
            $perPage = 10;
        }

        $branches = $query->orderBy('id', 'asc')->paginate($perPage)->withQueryString();

        // Table refresh uses AJAX, but SPA navigation also uses XHR fetch.
        // SPA navigation needs a full HTML document containing <main.main>.
        $isSpaNavigation = strtolower((string) $request->header('X-SPA-Navigation')) === 'true';
        if ($request->ajax() && ! $isSpaNavigation) {
            return view('branches._branches_table', compact('branches'));
        }

        return view('branches.index', compact('branches'));
    }

    public function create(): View
    {
        $this->authorize('create', Branch::class);
        return view('branches.create');
    }

    public function store(Request $request): RedirectResponse
    {
        $this->authorize('create', Branch::class);
        $validated = $request->validate([
            'name' => 'required|string|max:255|unique:branches,name',
        ]);
        $branch = Branch::create([
            'name' => $validated['name'],
            'created_by' => Auth::id(),
        ]);

        AuditLog::log('create_branch', $branch, $branch->toArray());

        return redirect()->route('branches.index')->with('status', 'Branch created.');
    }

    public function edit(Branch $branch): View
    {
        $this->authorize('update', $branch);
        return view('branches.edit', compact('branch'));
    }

    public function show(Request $request, Branch $branch): View
    {
        $this->authorize('view', $branch);

        $usersQuery = $branch->users()->with('role');
        if ($request->filled('search')) {
            $s = (string) $request->search;
            $usersQuery->where(function($q) use ($s) {
                $q->where('name', 'like', "%{$s}%")
                  ->orWhere('email', 'like', "%{$s}%")
                  ->orWhere('mobile', 'like', "%{$s}%");
            });
        }

        // Respect per-page selection from query, with a safe whitelist and default 10
        $allowed = [5,10,15,20,30];
        $perPage = (int) $request->query('per_page', 10);
        if (!in_array($perPage, $allowed, true)) {
            $perPage = 10;
        }

        $users = $usersQuery->orderBy('name')->paginate($perPage)->withQueryString();

        // Table refresh uses AJAX, but SPA navigation also uses XHR fetch.
        // SPA navigation needs a full HTML document containing <main.main>.
        $isSpaNavigation = strtolower((string) $request->header('X-SPA-Navigation')) === 'true';
        if ($request->ajax() && ! $isSpaNavigation) {
            return view('branches._users_table', compact('users'));
        }

        // remember the current branch users list URL so user details can return here
        try {
            session(['users_list_back_url' => $request->fullUrl()]);
        } catch (\Throwable $__e) {
            // ignore session issues
        }

        return view('branches.show', compact('branch', 'users'));
    }

    public function export(Request $request, Branch $branch): StreamedResponse
    {
        $this->authorize('export', $branch);

        $usersQuery = $branch->users()->with('role');
        if ($request->filled('search')) {
            $s = (string) $request->search;
            $usersQuery->where(function($q) use ($s) {
                $q->where('name', 'like', "%{$s}%")
                  ->orWhere('email', 'like', "%{$s}%")
                  ->orWhere('mobile', 'like', "%{$s}%");
            });
        }

        $filename = 'branch-' . $branch->getKey() . '-users-' . date('Ymd_His') . '.csv';

        $callback = function() use ($usersQuery) {
            $handle = @fopen('php://output', 'w');
            if ($handle === false) {
                return;
            }
            fputcsv($handle, ['ID','Name','Email','Mobile','Roles','Status','Created At']);
            $usersQuery->chunk(200, function($users) use ($handle) {
                foreach ($users as $u) {
                    /**
                     * @var \App\Models\User $u
                     * @property string $name
                     * @property string|null $email
                     * @property string $mobile
                     * @property bool $active
                     * @property \Illuminate\Support\Carbon|null $created_at
                     */
                    $roles = $u->roles->pluck('name')->join('|');
                    fputcsv($handle, [
                        $u->getKey(),
                        $u->name ?? '',
                        $u->email ?? '',
                        $u->mobile ?? '',
                        $roles,
                        ($u->active ?? $u->is_active ?? false) ? 'Active' : 'Inactive',
                        optional($u->created_at)->toDateTimeString(),
                    ]);
                }
            });
            @fclose($handle);
        };

        return response()->streamDownload($callback, $filename, [
            'Content-Type' => 'text/csv',
            'Content-Disposition' => 'attachment; filename="' . $filename . '"',
        ]);
    }

    public function update(Request $request, Branch $branch): RedirectResponse
    {
        $this->authorize('update', $branch);
        $validated = $request->validate([
            'name' => 'required|string|max:255|unique:branches,name,' . $branch->getKey(),
        ]);

        $oldValues = $branch->toArray();

        $branch->update([
            'name' => $validated['name'],
            'updated_by' => Auth::id(),
        ]);

        AuditLog::log('update_branch', $branch, $oldValues, $branch->fresh()?->toArray() ?? []);

        return redirect()->route('branches.index')->with('status', 'Branch updated.');
    }

    public function destroy(Branch $branch): RedirectResponse
    {
        $this->authorize('delete', $branch);

        // Requirement: allow deleting branch if it only has inactive users/roles.
        // Block deletion only when ACTIVE (not-deleted) users or active roles exist.
        // Note: Modal validation should prevent reaching here if dependencies exist
        $activeUserCount = $branch->users()->where('active', true)->count();
        $activeRoleCount = $branch->roles()->where('is_active', true)->count();

        if ($activeUserCount > 0 || $activeRoleCount > 0) {
            // Fallback validation (should not reach here if modal is used)
            return redirect()->route('branches.index')
                ->with('error', 'Cannot delete branch due to active dependencies.');
        }

        AuditLog::log('delete_branch', $branch, $branch->toArray());

        $branch->delete();
        return redirect()->route('branches.index')->with('deleted', 'Branch deleted.');
    }

    /**
     * Check if a branch can be deleted (check for dependencies)
     */
    public function checkDeleteDependencies(Branch $branch): \Illuminate\Http\JsonResponse
    {
        $this->authorize('view', $branch);

        // Performance: count() + small samples; don’t load full datasets.
        // Requirement: only ACTIVE users/roles block deletion.
        $activeUsersQuery = User::where('branch_id', $branch->id)
            ->where('active', true);

        $activeUserCount = (clone $activeUsersQuery)->count();
        $activeUsersSample = (clone $activeUsersQuery)
            ->orderBy('id')
            ->limit(5)
            ->get(['id', 'name']);

        $inactiveUserCount = User::where('branch_id', $branch->id)
            ->where('active', false)
            ->count();

        $activeRolesQuery = Role::where('branch_id', $branch->id)
            ->where('is_active', true);

        $activeRoleCount = (clone $activeRolesQuery)->count();
        $activeRolesSample = (clone $activeRolesQuery)
            ->orderBy('id')
            ->limit(5)
            ->get(['id', 'name']);

        $inactiveRoleCount = Role::where('branch_id', $branch->id)
            ->where('is_active', false)
            ->count();

        $canDelete = $activeUserCount === 0 && $activeRoleCount === 0;

        $dependencies = [];
        if ($activeUserCount > 0) {
            $dependencies[] = [
                'type' => 'active_users',
                'count' => $activeUserCount,
                'message' => 'Active users in this branch',
                'items' => $activeUsersSample->map(fn($u) => ['id' => $u->id, 'name' => $u->name])->toArray(),
            ];
        }

        if ($activeRoleCount > 0) {
            $dependencies[] = [
                'type' => 'active_roles',
                'count' => $activeRoleCount,
                'message' => 'Active roles in this branch',
                'items' => $activeRolesSample->map(fn($r) => ['id' => $r->id, 'name' => $r->name])->toArray(),
            ];
        }

        // Inactive children do not block deletion; include counts for UX.
        if ($inactiveUserCount > 0) {
            $dependencies[] = [
                'type' => 'inactive_users_info',
                'count' => $inactiveUserCount,
                'message' => 'Inactive users in this branch (allowed)',
                'items' => [],
            ];
        }

        if ($inactiveRoleCount > 0) {
            $dependencies[] = [
                'type' => 'inactive_roles_info',
                'count' => $inactiveRoleCount,
                'message' => 'Inactive roles in this branch (allowed)',
                'items' => [],
            ];
        }

        $errorMessage = 'Branch can be deleted safely.';
        if (!$canDelete) {
            $parts = [];
            if ($activeUserCount > 0) {
                $parts[] = "{$activeUserCount} active user" . ($activeUserCount > 1 ? 's' : '');
            }
            if ($activeRoleCount > 0) {
                $parts[] = "{$activeRoleCount} active role" . ($activeRoleCount > 1 ? 's' : '');
            }
            $errorMessage = "Cannot delete branch '{$branch->name}' because it has " . implode(' and ', $parts) . ". Please set them inactive or delete them first.";
        }

        return response()->json([
            'can_delete' => $canDelete,
            'dependencies' => $dependencies,
            'message' => $errorMessage
        ]);
    }

    public function restore(int $id): RedirectResponse
    {
        /** @var Branch $branch */
        $branch = Branch::withTrashed()->findOrFail($id);
        $this->authorize('restore', $branch);

        if (! $branch->trashed()) {
            return redirect()->route('branches.index')->with('status', 'Branch is not deleted.');
        }

        // Check if roles need attention
        $trashedRoles = Role::onlyTrashed()
            ->where('branch_id', $branch->getKey())
            ->count();

        if (method_exists($branch, 'restoreWithUser')) {
            try { $branch->restoreWithUser(); } catch (\Throwable $__e) { $branch->restore(); }
        } else {
            $branch->restore();
            try {
                $branch->restored_by = optional(Auth::user())->id;
                try { if (\Illuminate\Support\Facades\Schema::hasColumn($branch->getTable(), 'restored_at')) { $branch->restored_at = now(); } } catch (\Throwable $__e) {}
                $branch->save();
            } catch (\Throwable $__e) {
                // ignore
            }
        }

        AuditLog::log('restore_branch', $branch, [], $branch->fresh()?->toArray() ?? []);

        $message = 'Branch restored.';
        if ($trashedRoles > 0) {
            $message .= " Note: {$trashedRoles} role(s) in this branch are still deleted.";
        }

        return redirect()->route('branches.index')->with('status', $message);
    }
}
