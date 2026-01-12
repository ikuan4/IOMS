<?php

namespace App\Http\Controllers;

use App\Models\Role;
use App\Models\Permission;
use App\Models\User;
use App\Models\AuditLog;
use App\Models\Branch;
use Illuminate\Http\Request;
use Illuminate\Support\Str;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Log;
use Illuminate\Foundation\Auth\Access\AuthorizesRequests;
use Schema;
use Illuminate\Contracts\View\View as ContractView;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\RedirectResponse;
use Illuminate\Support\Collection;

class RoleController extends Controller
{
    use AuthorizesRequests;

    public function index(Request $request): ContractView|JsonResponse
    {
        $this->authorize('viewAny', Role::class);

        /** @var User|null $currentUser */
        $currentUser = Auth::user();
        assert($currentUser instanceof User);

        // Build base query (without status/search) for counts and scoping
        $baseQuery = Role::with([
            'deletedBy' => function($q) { $q->withTrashed(); },
            'branch' => function($q) { $q->withTrashed(); }
        ]);
        if ($currentUser->isSuperAdmin()) {
            $baseQuery->withTrashed();
        } else {
            // Non-developer users: restrict to own branch only
            $baseQuery->where('branch_id', $currentUser->branch_id);

            $manageableRoleIds = $currentUser->getManageableRoles()->pluck('id')->push($currentUser->role_id);

            $userDeletedRoleIds = AuditLog::where('user_id', $currentUser->id)
                ->where('action', 'delete_role')
                ->where('auditable_type', Role::class)
                ->where('auditable_id', 'IN', function($query) use ($currentUser) {
                    $query->select('id')->from('roles')->where('branch_id', $currentUser->branch_id);
                })
                ->pluck('auditable_id');

            $baseQuery->where(function ($q) use ($manageableRoleIds, $userDeletedRoleIds) {
                $q->whereIn('id', $manageableRoleIds)
                    ->orWhere(function ($subQ) use ($userDeletedRoleIds) {
                        $subQ->whereIn('id', $userDeletedRoleIds)->onlyTrashed();
                    });
            });
        }

        // compute counts for status cards using the scoped baseQuery
        $statusCounts = [
            'all' => (clone $baseQuery)->count(),
            'active' => (clone $baseQuery)->whereNull('deleted_at')->where('is_active', true)->count(),
            'inactive' => (clone $baseQuery)->whereNull('deleted_at')->where('is_active', false)->count(),
            'deleted' => (clone $baseQuery)->onlyTrashed()->count(),
        ];

        // Now apply filters (status/search) to a fresh query cloned from base
        $query = (clone $baseQuery);

        $status = $request->query('status');
        if ($status !== null) {
            if ($status === 'active') {
                $query->whereNull('deleted_at')->where('is_active', true);
            } elseif ($status === 'inactive') {
                $query->whereNull('deleted_at')->where('is_active', false);
            } elseif ($status === 'deleted') {
                $query->whereNotNull('deleted_at');
            }
        }

        $search = $request->query('search');
        if (is_string($search) && $search !== '') {
            $query->where(function ($q) use ($search) {
                $q->where('name', 'like', "%{$search}%")
                    ->orWhere('description', 'like', "%{$search}%")
                    ->orWhere('slug', 'like', "%{$search}%");
            });
        }

        // Respect per-page selection from query, with a safe whitelist and default 10
        $allowed = [5,10,15,20,30];
        $perPage = (int) ($request->query('per_page') ?? 10);
        if (!in_array($perPage, $allowed, true)) {
            $perPage = 10;
        }

        $roles = $query->orderBy('id', 'asc')->paginate($perPage)->withQueryString();

        // remember the current roles list URL so the role details page can return here
        try {
            session(['roles_list_back_url' => $request->fullUrl()]);
        } catch (\Throwable $__e) {
            // ignore session issues
        }

        // Get users by role if requested (AJAX) — return normalized JSON shape
        $roleId = $request->query('role_id');
        if ($roleId && $request->ajax()) {
            /** @var Role|null $role */
            $role = Role::find($roleId);
            $mapped = [];
            if ($role instanceof Role) {
                $this->authorize('view', $role);

                // Users assigned via direct foreign key on users.role_id
                $users = User::where('role_id', $role->id)
                    ->with(['role' => function($q) { $q->withTrashed(); }])
                    ->get();

                $mapped = $users->map(function ($u) {
                    $roleObj = $u->role ? ['id' => $u->role->id, 'name' => $u->role->name, 'slug' => $u->role->slug] : null;
                    return [
                        'id' => $u->id,
                        'name' => $u->name,
                        'email' => $u->email,
                        'active' => (bool) ($u->active ?? false),
                        'role' => $roleObj,
                    ];
                })->values();
            }
            return response()->json($mapped);
        }

        // Handle regular table AJAX requests
        if ($request->ajax() && !$roleId) {
            /** @var view-string $view */
            $view = 'roles._roles_table';
            return view($view, compact('roles'));
        }

        return view('roles.index', compact('roles', 'statusCounts'));
    }

    public function store(Request $request): RedirectResponse
    {
        $this->authorize('create', Role::class);
        $validated = $request->validate([
            'name' => 'required|string|max:255|unique:roles,name',
            'description' => 'nullable|string',
            'is_active' => 'boolean',
            'branch_id' => 'nullable|integer|exists:branches,id',
        ]);

        $baseSlug = Str::slug($validated['name']);
        $slug = $baseSlug;
        $counter = 1;

        while (Role::where('slug', $slug)->exists()) {
            $slug = $baseSlug . '-' . $counter;
            $counter++;
        }

        $validated['slug'] = $slug;
        $validated['guard_name'] = 'web';
        $validated['is_active'] = $request->boolean('is_active');

        // Set branch_id: Developer can choose, others get their own branch
        $currentUser = Auth::user();
        assert($currentUser instanceof User);
        if ($currentUser->isSuperAdmin() && $request->filled('branch_id')) {
            $validated['branch_id'] = $request->input('branch_id');
        } else {
            $validated['branch_id'] = $currentUser->branch_id ?? null;
        }

        $role = Role::create($validated);

        if ($request->input('action') === 'save_only') {
            return redirect()->route('roles.index')
                ->with('status', 'Role "' . $role->name . '" created successfully.');
        }

        return redirect()->route('roles.hierarchy', $role->id)
            ->with('status', 'Role "' . $role->name . '" created successfully. Now set its hierarchy.');
    }

    public function show(Role $role): ContractView
    {
        $this->authorize('view', $role);
        $role->load('users', 'permissions', 'branch');
        return view('roles.show', compact('role'));
    }

    public function edit(Role $role): ContractView
    {
        $this->authorize('update', $role);
        // Provide all permissions to the edit view so the checkboxes can render
        $permissions = Permission::orderBy('name')->get();
        $role->load('permissions');
        $branches = Branch::orderBy('name')->get();
        return view('roles.edit', compact('role', 'permissions', 'branches'));
    }

    /**
     * Show the form for creating a new role.
     */
    public function create(): ContractView
    {
        $this->authorize('create', Role::class);
        $permissions = Permission::orderBy('name')->get();
        $branches = Branch::orderBy('name')->get();
        return view('roles.create', compact('permissions', 'branches'));
    }

    public function update(Request $request, Role $role): RedirectResponse
    {
        $this->authorize('update', $role);

        $validated = $request->validate([
            'name' => 'required|string|max:255|unique:roles,name,' . $role->id,
            'description' => 'nullable|string',
            'branch_id' => 'nullable|integer|exists:branches,id',
        ]);

        // Handle is_active properly - checkbox sends 1 if checked, hidden field sends 0 if unchecked
        // When checkbox is checked, both values are sent, so we check if '1' is in the array
        $isActiveValue = $request->input('is_active');
        if (is_array($isActiveValue)) {
            // Multiple values sent, check if '1' is present
            $validated['is_active'] = in_array('1', $isActiveValue) || in_array(1, $isActiveValue);
        } else {
            // Single value sent
            $validated['is_active'] = (bool) $isActiveValue;
        }

        $baseSlug = Str::slug($validated['name']);
        $slug = $baseSlug;
        $counter = 1;

        while (Role::where('slug', $slug)->where('id', '!=', $role->id)->exists()) {
            $slug = $baseSlug . '-' . $counter;
            $counter++;
        }

        $validated['slug'] = $slug;

        // If role is being deactivated, handle mapped active users when requested
        $wasActive = (bool) $role->is_active;
        $willBeActive = (bool) $validated['is_active'];

        if ($wasActive && !$willBeActive) {
            // Prevent deactivating own role
            $currentUser = Auth::user();
            assert($currentUser instanceof User);
            if ($currentUser->role_id === $role->id) {
                return redirect()->route('roles.index')
                    ->with('error', 'You cannot deactivate your own role.');
            }

            $deactivateFlag = $request->boolean('deactivate_mapped_users');
            // Count active mapped users (only direct role_id assignment)
            $users = User::where('role_id', $role->id)
                ->where('active', true)
                ->whereNull('deleted_at')
                ->get();

            if ($users->count() > 0 && !$deactivateFlag) {
                // Ask for confirmation if not provided; do not persist changes yet
                return redirect()->back()->withInput()->with('error', 'Role "' . $role->name . '" is assigned to ' . $users->count() . ' active user(s). Confirm deactivation to set them inactive.');
            }

            if ($deactivateFlag) {
                foreach ($users as $u) {
                    try {
                        $u->active = false;
                        $u->save();
                    } catch (\Throwable $__e) {
                        // ignore individual failures
                    }
                }
            }
        }

        // Persist role changes after handling mapped users
        $role->fill($validated);
        $role->is_active = $willBeActive;
        $role->save();

        // Branch assignment: Developer may choose branch; others get their own branch automatically
        try {
            $currentUser = Auth::user();
            assert($currentUser instanceof User);
            if ($currentUser->isSuperAdmin() && $request->filled('branch_id')) {
                $role->branch_id = $request->input('branch_id');
            } else {
                $role->branch_id = $currentUser->branch_id ?? null;
            }
            $role->save();
        } catch (\Throwable $e) {
            Log::warning('Failed to assign branch on role update: ' . $e->getMessage());
        }

        return redirect()->route('roles.index')
            ->with('status', 'Role "' . $role->name . '" updated successfully.');
    }

    public function destroy(Request $request, Role $role): RedirectResponse
    {
        $this->authorize('delete', $role);

        if ($role->isProtected()) {
            return redirect()->route('roles.index')
                ->with('error', 'Cannot delete protected role: ' . $role->name);
        }

        $currentUser = Auth::user();
        assert($currentUser instanceof User);
        if ($currentUser->role_id === $role->id) {
            return redirect()->route('roles.index')
                ->with('error', 'You cannot delete your own role.');
        }

        // Check if role has assigned users
        $userCount = User::where('role_id', $role->id)->count();
        if ($userCount > 0) {
            return redirect()->route('roles.index')
                ->with('error', "Cannot delete role '{$role->name}' because it is assigned to {$userCount} user(s). Please reassign or remove users first.");
        }



        // If confirmation provided, soft-delete active mapped users first
        if ($request->boolean('soft_delete_mapped_users')) {
            try {
                // Gather active mapped users (only direct role_id)
                $users = User::where('role_id', $role->id)
                    ->where('active', true)
                    ->whereNull('deleted_at')
                    ->get();

                foreach ($users as $u) {
                    try {
                        $u->delete();
                    } catch (\Throwable $__e) {
                        // ignore individual failures
                    }
                }
            } catch (\Throwable $__e) {
                // ignore
            }
        }

        $name = $role->name;
        AuditLog::log('delete_role', $role, $role->toArray());
        $role->delete();

        return redirect()->route('roles.index')
            ->with('deleted', 'Role "' . $name . '" deleted successfully.');
    }

    public function restore(int $id): RedirectResponse
    {
        $currentUser = Auth::user();
        assert($currentUser instanceof User);
        if (!$currentUser->hasPermission('roles.restore')) {
            abort(403, 'Unauthorized action.');
        }

        $role = Role::withTrashed()->findOrFail($id);

        if (!$currentUser->canManageRole($role)) {
            return redirect()->route('roles.index')
                ->with('error', 'You do not have permission to restore this role.');
        }

        if ($currentUser->role_id === $role->id) {
            return redirect()->route('roles.index')
                ->with('error', 'You cannot restore your own role.');
        }

        $role->restoreWithUser();

        return redirect()->route('roles.index')
            ->with('status', 'Role "' . $role->name . '" restored successfully.');
    }

    /**
     * Return JSON with count of active mapped users for the given role.
     */
    public function mappedActiveUsers(Role $role): JsonResponse
    {
        $this->authorize('view', $role);

        $usersCount = User::where('role_id', $role->id)
            ->where('active', true)
            ->whereNull('deleted_at')
            ->count();

        return response()->json(['count' => $usersCount]);
    }

    public function managePriority(Request $request, Role $role): ContractView
    {
        $currentUser = Auth::user();
        assert($currentUser instanceof User);
        if (!$currentUser->hasPermission('roles.manage-priority')) {
            abort(403, 'Unauthorized action.');
        }

        $isDeveloper = $currentUser->isSuperAdmin();

        // Provide branches for the dropdown
        $branches = Branch::orderBy('name')->get();

        // Determine selected branch: developer may choose via query, others default to their branch
            if ($isDeveloper) {
            $selectedBranchId = $request->query('branch_id') ?? $branches->first()?->id;
            $rolesForView = $selectedBranchId
                ? Role::where('branch_id', $selectedBranchId)->orderBy('priority', 'asc')->get()
                : collect([]);
        } else {
            $selectedBranchId = $currentUser->branch_id;

            // Get user's priority (lower number = higher privilege)
            $effectiveRole = $currentUser->effectiveRole();
            $userPriority = $effectiveRole ? ($effectiveRole->priority ?? 999) : 999;

            // User can only see roles with priority > their own (lower privilege/higher number)
            // This excludes their own role and any roles with higher privilege
            $rolesForView = Role::where('branch_id', $currentUser->branch_id)
                ->where('priority', '>', $userPriority)
                ->orderBy('priority', 'asc')
                ->get();
        }

        $isHierarchyPage = true;
        return view('roles.hierarchy', compact('role', 'branches', 'selectedBranchId', 'rolesForView', 'isDeveloper', 'isHierarchyPage'));
    }

    // getVisibleRoleTree removed: hierarchy feature deprecated, logic inlined where needed.

    public function updatePriority(Request $request): RedirectResponse
    {
        $currentUser = Auth::user();
        assert($currentUser instanceof User);
        if (!$currentUser->hasPermission('roles.manage-priority')) {
            abort(403, 'Unauthorized action.');
        }

        // Log the request data for debugging
        \Log::info('updatePriority called', [
            'all_data' => $request->all(),
            'priorities_param' => $request->input('priorities'),
        ]);

        $validated = $request->validate([
            'parents' => 'nullable|array',
            'priorities' => 'nullable|array',
            'priorities.*' => 'nullable|integer|min:1|max:100',
        ]);

        // If the legacy role_hierarchies table is gone, treat this as a priority update
        if (!Schema::hasTable('role_hierarchies')) {
            $priorities = $validated['priorities'] ?? [];
            $updatedCount = 0;

            // Get current user's priority for validation
            $effectiveRole = $currentUser->effectiveRole();
            $userPriority = $effectiveRole ? ($effectiveRole->priority ?? 999) : 999;

            \Log::info('Processing priorities', ['priorities' => $priorities, 'count' => count($priorities), 'userPriority' => $userPriority]);

            foreach ($priorities as $roleId => $priority) {
                /** @var Role|null $role */
                $role = Role::find($roleId);
                if (!($role instanceof Role)) continue;

                // Validate branch: non-developer users can only update roles in their branch
                if (!$currentUser->isSuperAdmin() && $role->branch_id !== $currentUser->branch_id) {
                    continue;
                }

                // Non-superadmin users can only assign priorities > their own (lower privilege)
                if (!$currentUser->isSuperAdmin() && (int)$priority <= $userPriority) {
                    \Log::warning("User attempted to assign invalid priority", [
                        'role_id' => $roleId,
                        'attempted_priority' => $priority,
                        'user_priority' => $userPriority
                    ]);
                    continue;
                }

                $old = $role->priority ?? null;
                $role->priority = (int)$priority;
                $role->save();
                \Log::info("Updated role {$roleId}", ['old' => $old, 'new' => $role->priority]);
                if ($old !== $role->priority) $updatedCount++;
            }

            return back()->with('success', 'Role priorities updated successfully. ' . $updatedCount . ' role(s) updated.');
        }


        $updatedCount = 0;
        foreach ($validated['parents'] as $roleId => $data) {
            /** @var Role|null $role */
            $role = Role::find($roleId);
            if (!($role instanceof Role)) {
                continue;
            }

            if ($role->isProtected()) {
                continue;
            }

            if (!$currentUser->isSuperAdmin() && !$currentUser->canManageRole($role)) {
                continue;
            }

            $parentIds = [];
            if (isset($data['parent_ids'])) {
                if (is_array($data['parent_ids'])) {
                    $parentIds = array_filter($data['parent_ids'], fn($id) => !empty($id));
                } elseif (!empty($data['parent_ids'])) {
                    $parentIds = [$data['parent_ids']];
                }
            }

            $validParentIds = [];
            foreach ($parentIds as $parentId) {
                /** @var Role|null $parentRole */
                $parentRole = Role::find($parentId);
                if ($parentRole instanceof Role && !$this->wouldCreateCircularOrRedundant($roleId, $parentId)) {
                    $validParentIds[] = $parentId;
                }
            }

            $oldParents = [];

            $updatedCount++;
        }

        return back()->with('success', 'Role hierarchy updated successfully. ' . $updatedCount . ' role(s) updated.');
    }

    private function wouldCreateCircularOrRedundant(int $roleId, int $parentId): bool
    {
        // Hierarchy support removed; treat as no circularity by default.
        return false;
    }

    public function managePermissions(Role $role): ContractView
    {
        $this->authorize('managePermissions', $role);

        $currentUser = Auth::user();
        assert($currentUser instanceof User);
        $user = $currentUser;
        $isSuperAdmin = $user->isSuperAdmin();

        $allPermissions = Permission::getAllGrouped();

        if (!$isSuperAdmin) {
            $userPermissions = $user->getAllPermissions();
            $userPermissionIds = $userPermissions->pluck('id')->toArray();

            $permissions = $allPermissions->map(function ($groupPerms) use ($userPermissionIds) {
                return $groupPerms->filter(function ($perm) use ($userPermissionIds) {
                    return in_array($perm->id, $userPermissionIds);
                });
            })->filter(function ($groupPerms) {
                return $groupPerms->isNotEmpty();
            });
        } else {
            $permissions = $allPermissions;
        }

        $rolePermissions = $role->permissions->pluck('id')->toArray();
        $userPermissionIds = $isSuperAdmin ? [] : $user->getAllPermissions()->pluck('id')->toArray();

        return view('roles.permissions', compact('role', 'permissions', 'rolePermissions', 'isSuperAdmin', 'userPermissionIds'));
    }

    public function updatePermissions(Request $request, Role $role): RedirectResponse
    {
        $this->authorize('updatePermissions', $role);

        Log::info('updatePermissions called', [
            'role_id' => $role->id,
            'role_name' => $role->name,
            'request_permissions' => $request->input('permissions'),
            'request_all' => $request->all(),
        ]);

        $validated = $request->validate([
            'permissions' => 'nullable|array',
            'permissions.*' => 'exists:permissions,id',
        ]);

        $requestedPermissions = $validated['permissions'] ?? [];

        Log::info('After validation', [ 'requestedPermissions' => $requestedPermissions, ]);

        $currentUser = Auth::user();
        assert($currentUser instanceof User);
        if (!$currentUser->isSuperAdmin()) {
            $userPermissionIds = $currentUser->getAllPermissions()->pluck('id')->toArray();

            $currentRolePermissions = $role->permissions()->pluck('permissions.id')->toArray();

            $hiddenPermissions = array_diff($currentRolePermissions, $userPermissionIds);

            $invalidPermissions = array_diff($requestedPermissions, $userPermissionIds);

            Log::info('Permission validation', [
                'requestedPermissions' => $requestedPermissions,
                'invalidPermissions' => $invalidPermissions,
                'hiddenPermissions' => $hiddenPermissions,
            ]);

            if (!empty($invalidPermissions)) {
                Log::warning('User tried to grant permissions they dont have', [ 'invalidPermissions' => $invalidPermissions, ]);
                return redirect()->back()
                    ->with('error', 'You cannot grant permissions that you do not have.');
            }

            $requestedPermissions = array_unique(array_merge($requestedPermissions, $hiddenPermissions));
        }

        Log::info('Before syncPermissions', [ 'final_requestedPermissions' => $requestedPermissions, ]);

        $oldPermissions = $role->permissions()->pluck('permissions.id')->toArray();

        Log::info('Calling syncPermissions', [ 'role_id' => $role->id, 'oldPermissions' => $oldPermissions, 'newPermissions' => $requestedPermissions, ]);

        $permissions = Permission::whereIn('id', $requestedPermissions)->get();
        $role->syncPermissions($permissions);

        Log::info('After syncPermissions', [ 'role_id' => $role->id, 'permissions_count' => $role->permissions()->count(), 'permissions' => $role->permissions()->pluck('id')->toArray(), ]);

        if ($oldPermissions !== $requestedPermissions) {
            AuditLog::log(
                'update_role_permissions',
                $role,
                [ 'permission_ids' => $oldPermissions, 'permission_count' => count($oldPermissions), ],
                [ 'permission_ids' => $requestedPermissions, 'permission_count' => count($requestedPermissions), ]
            );
        }

        return redirect()->route('roles.permissions', $role->id)
            ->with('status', 'Permissions for role "' . $role->name . '" updated successfully.');
    }
}
