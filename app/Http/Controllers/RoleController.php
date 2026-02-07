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

        $currentUser = Auth::user();
        assert($currentUser instanceof User);

        // Build and apply queries
        $baseQuery = $this->buildBaseQuery($currentUser);
        $statusCounts = $this->computeStatusCounts($baseQuery);
        $query = $this->applyFilters(clone $baseQuery, $request);

        // Handle AJAX requests
        $roleId = $request->query('role_id');
        if ($roleId && $request->ajax()) {
            return $this->handleAjaxRoleUsers($roleId);
        }

        // Paginate results
        $perPage = $this->getValidPerPage($request);
        $roles = $query->orderBy('id', 'asc')->paginate($perPage)->withQueryString();

        // Store session URL
        try {
            session(['roles_list_back_url' => $request->fullUrl()]);
        } catch (\Throwable) {
            // Ignore session issues
        }

        // Handle table AJAX requests
        $isSpaNavigation = strtolower((string) $request->header('X-SPA-Navigation')) === 'true';
        if ($request->ajax() && !$isSpaNavigation) {
            return view('roles._roles_table', compact('roles'));
        }

        return view('roles.index', compact('roles', 'statusCounts'));
    }

    /**
     * Build the base scoped query for roles, applying authorization constraints.
     *
     * @param User $user
     * @return \Illuminate\Database\Eloquent\Builder<\App\Models\Role>
     */
    private function buildBaseQuery(User $user): \Illuminate\Database\Eloquent\Builder
    {
        $query = Role::with([
            'deletedBy' => fn($q) => $q->withTrashed(),
            'branch' => fn($q) => $q->withTrashed()
        ]);

        if ($user->isSuperAdmin()) {
            $query->withTrashed();
            return $query;
        }

        // Non-admin: restrict to own branch
        $query->where('branch_id', $user->branch_id);

        $manageableRoleIds = $user->getManageableRoles()->pluck('id')->push($user->role_id);
        $userDeletedRoleIds = $this->getUserDeletedRoleIds($user);

        $query->where(function ($q) use ($manageableRoleIds, $userDeletedRoleIds) {
            $q->whereIn('id', $manageableRoleIds)
                ->orWhere(function ($subQ) use ($userDeletedRoleIds) {
                        // Use explicit deleted_at check instead of onlyTrashed() for static analysis
                        $subQ->whereIn('id', $userDeletedRoleIds)->whereNotNull('deleted_at');
                });
        });

        return $query;
    }

    /**
     * Get deleted role IDs that the user deleted.
     *
     * @param User $user
     * @return \Illuminate\Support\Collection<int, int>
     */
    private function getUserDeletedRoleIds(User $user): Collection
    {
        return AuditLog::where('user_id', $user->id)
            ->where('action', 'delete_role')
            ->where('auditable_type', Role::class)
            ->whereIn('auditable_id', function ($q) use ($user) {
                $q->select('id')->from('roles')->where('branch_id', $user->branch_id);
            })
            ->pluck('auditable_id');
    }

    /**
     * Compute status counts from the base query.
     *
     * @param \Illuminate\Database\Eloquent\Builder<\App\Models\Role> $baseQuery
     * @return array<string,int>
     */
    private function computeStatusCounts(\Illuminate\Database\Eloquent\Builder $baseQuery): array
    {
        return [
            'all' => (clone $baseQuery)->count(),
            'active' => (clone $baseQuery)->whereNull('deleted_at')->where('is_active', true)->count(),
            'inactive' => (clone $baseQuery)->whereNull('deleted_at')->where('is_active', false)->count(),
            'deleted' => (clone $baseQuery)->onlyTrashed()->count(),
        ];
    }

    /**
     * Apply status and search filters to a query.
     *
     * @param \Illuminate\Database\Eloquent\Builder<\App\Models\Role> $query
     * @param Request $request
     * @return \Illuminate\Database\Eloquent\Builder<\App\Models\Role>
     */
    private function applyFilters(\Illuminate\Database\Eloquent\Builder $query, Request $request): \Illuminate\Database\Eloquent\Builder
    {
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

        return $query;
    }

    /**
     * Get validated per-page value from request.
     */
    private function getValidPerPage(Request $request): int
    {
        $allowed = [5, 10, 15, 20, 30];
        $perPage = (int) ($request->query('per_page') ?? 10);
        return in_array($perPage, $allowed, true) ? $perPage : 10;
    }

    /**
     * Handle AJAX request for users assigned to a role.
     */
    private function handleAjaxRoleUsers(mixed $roleId): JsonResponse
    {
        $role = Role::find($roleId);
        if (!($role instanceof Role)) {
            return response()->json([]);
        }

        $this->authorize('view', $role);

        $users = User::where('role_id', $role->id)
            ->with(['role' => fn($q) => $q->withTrashed()])
            ->get();

        $mapped = $users->map(function ($u) {
            return [
                'id' => $u->id,
                'name' => $u->name,
                'email' => $u->email,
                'active' => (bool) ($u->active ?? false),
                'role' => $u->role ? [
                    'id' => $u->role->id,
                    'name' => $u->role->name,
                    'slug' => $u->role->slug
                ] : null,
            ];
        })->values();

        return response()->json($mapped);
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

        /** @var \Illuminate\Database\Eloquent\Model $auditable */
        $auditable = $role;
        AuditLog::log('create_role', $auditable, [], $role->toArray());

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

        $oldValues = $role->toArray();

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
            /** @var \Illuminate\Support\Collection<int, User> $users */
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
            // Silently handle branch assignment failure
        }

        AuditLog::log('update_role', $role, $oldValues, $role->fresh()?->toArray() ?? []);

        return redirect()->route('roles.index')
            ->with('status', 'Role "' . $role->name . '" updated successfully.');
    }

    public function destroy(Request $request, Role $role): RedirectResponse
    {
        $this->authorize('delete', $role);

        if ($role->isProtected()) {
            return redirect()->route('roles.index')
                ->with('error', 'Cannot delete protected role.');
        }

        $currentUser = Auth::user();
        assert($currentUser instanceof User);
        if ($currentUser->role_id === $role->id) {
            return redirect()->route('roles.index')
                ->with('error', 'Cannot delete your own role.');
        }

        // Requirement: allow deleting role if it only has inactive users.
        // Block deletion only when ACTIVE (not-deleted) users are assigned.
        // Note: Modal validation should prevent reaching here if dependencies exist
        $activeUserCount = User::where('role_id', $role->id)
            ->where('active', true)
            ->count();

        if ($activeUserCount > 0) {
            // Fallback validation (should not reach here if modal is used)
            return redirect()->route('roles.index')
                ->with('error', 'Cannot delete role due to active dependencies.');
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

        $oldValues = $role->toArray();

        if (!$currentUser->canManageRole($role)) {
            return redirect()->route('roles.index')
                ->with('error', 'You do not have permission to restore this role.');
        }

        if ($currentUser->role_id === $role->id) {
            return redirect()->route('roles.index')
                ->with('error', 'You cannot restore your own role.');
        }

        $role->restoreWithUser();

        AuditLog::log('restore_role', $role, $oldValues, $role->fresh()?->toArray() ?? []);

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

    /**
     * Check if a role can be deleted (check for dependencies)
     */
    public function checkDeleteDependencies(Role $role): JsonResponse
    {
        $this->authorize('view', $role);

        // Performance: do count() + limit(5) samples (don’t load all users).
        // Requirement: only ACTIVE users block role deletion.
        $activeUsersQuery = User::where('role_id', $role->id)
            ->where('active', true);

        $activeUserCount = (clone $activeUsersQuery)->count();        /** @var \Illuminate\Support\Collection<int, User> $activeUsersSample */        $activeUsersSample = (clone $activeUsersQuery)
            ->orderBy('id')
            ->limit(5)
            ->get(['id', 'name']);

        $inactiveUsersQuery = User::where('role_id', $role->id)
            ->where('active', false);

        $inactiveUserCount = (clone $inactiveUsersQuery)->count();

        $canDelete = $activeUserCount === 0;

        $dependencies = [];
        if ($activeUserCount > 0) {
            $dependencies[] = [
                'type' => 'active_users',
                'count' => $activeUserCount,
                'message' => 'Active users assigned to this role',
                'items' => $activeUsersSample->map(fn($u) => ['id' => $u->id, 'name' => $u->name])->toArray(),
            ];
        }

        // Inactive users do not block deletion, but returning counts helps UX.
        if ($inactiveUserCount > 0) {
            $dependencies[] = [
                'type' => 'inactive_users_info',
                'count' => $inactiveUserCount,
                'message' => 'Inactive users assigned (allowed)',
                'items' => [],
            ];
        }

        $errorMessage = 'Role can be deleted safely.';
        if (!$canDelete && $activeUserCount > 0) {
            $errorMessage = "Cannot delete role '{$role->name}' because it has {$activeUserCount} active user" . ($activeUserCount > 1 ? 's' : '') . ". Please set them inactive or delete them first.";
        }

        return response()->json([
            'can_delete' => $canDelete,
            'dependencies' => $dependencies,
            'message' => $errorMessage
        ]);
    }

    public function managePriority(Request $request, Role $role): \Illuminate\Http\Response
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
            // Developer: only pre-select if explicitly passed via query, otherwise null
            $selectedBranchId = $request->query('branch_id') ? (int)$request->query('branch_id') : null;
            /** @var \Illuminate\Support\Collection<int, Role> $rolesForView */
            $rolesForView = $selectedBranchId
                ? Role::where('branch_id', $selectedBranchId)->orderBy('priority', 'asc')->get()
                : collect([]);
        } else {
            // Non-developer: auto-select their branch
            $selectedBranchId = $currentUser->branch_id;

            // Get user's priority (lower number = higher privilege)
            $effectiveRole = $currentUser->effectiveRole();
            $userPriority = $effectiveRole ? ($effectiveRole->priority ?? 999) : 999;

            // User can only see roles with priority > their own (lower privilege/higher number)
            // This excludes their own role and any roles with higher privilege
            /** @var \Illuminate\Support\Collection<int, Role> $rolesForView */
            $rolesForView = Role::where('branch_id', $currentUser->branch_id)
                ->where('priority', '>', $userPriority)
                ->orderBy('priority', 'asc')
                ->get();
        }

        $isHierarchyPage = true;

        // Prevent browser caching of this page to ensure JavaScript always loads fresh
        return response()
            ->view('roles.hierarchy', compact('role', 'branches', 'selectedBranchId', 'rolesForView', 'isDeveloper', 'isHierarchyPage'))
            ->header('Cache-Control', 'no-cache, no-store, must-revalidate')
            ->header('Pragma', 'no-cache')
            ->header('Expires', '0');
    }

    // getVisibleRoleTree removed: hierarchy feature deprecated, logic inlined where needed.

    public function updatePriority(Request $request): RedirectResponse
    {
        $currentUser = Auth::user();
        assert($currentUser instanceof User);
        if (!$currentUser->hasPermission('roles.manage-priority')) {
            abort(403, 'Unauthorized action.');
        }

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
                    continue;
                }

                $old = $role->priority ?? null;
                $role->priority = (int)$priority;
                $role->save();
                if ($old !== $role->priority) {
                    $updatedCount++;
                    AuditLog::log(
                        'update_role_priority',
                        $role,
                        ['priority' => $old],
                        ['priority' => $role->priority]
                    );
                }
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

        $validated = $request->validate([
            'permissions' => 'nullable|array',
            'permissions.*' => 'exists:permissions,id',
        ]);

        $requestedPermissions = $validated['permissions'] ?? [];

        $currentUser = Auth::user();
        assert($currentUser instanceof User);
        if (!$currentUser->isSuperAdmin()) {
            $userPermissionIds = $currentUser->getAllPermissions()->pluck('id')->toArray();

            $currentRolePermissions = $role->permissions()->pluck('permissions.id')->toArray();

            $hiddenPermissions = array_diff($currentRolePermissions, $userPermissionIds);

            $invalidPermissions = array_diff($requestedPermissions, $userPermissionIds);

            if (!empty($invalidPermissions)) {
                return redirect()->back()
                    ->with('error', 'You cannot grant permissions that you do not have.');
            }

            $requestedPermissions = array_unique(array_merge($requestedPermissions, $hiddenPermissions));
        }

        $oldPermissions = $role->permissions()->pluck('permissions.id')->toArray();

        $permissions = Permission::whereIn('id', $requestedPermissions)->get();
        $role->syncPermissions($permissions);

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
