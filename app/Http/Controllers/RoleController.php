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

class RoleController extends Controller
{
    use AuthorizesRequests;

    public function index(Request $request)
    {
        $this->authorize('viewAny', Role::class);

        /** @var User $currentUser */
        $currentUser = Auth::user();

        // Build base query (without status/search) for counts and scoping
        $baseQuery = Role::with(['users', 'deletedBy', 'branch']);
        if ($currentUser->isSuperAdmin()) {
            $baseQuery->withTrashed();
        } else {
            $manageableRoleIds = $currentUser->getManageableRoles()->pluck('id')->push($currentUser->role_id);

            $userDeletedRoleIds = AuditLog::where('user_id', $currentUser->id)
                ->where('action', 'delete_role')
                ->where('auditable_type', Role::class)
                ->pluck('auditable_id');

            $baseQuery->where(function ($q) use ($manageableRoleIds, $userDeletedRoleIds) {
                $q->whereIn('id', $manageableRoleIds)
                    ->orWhere(function ($subQ) use ($userDeletedRoleIds) {
                        $subQ->whereIn('id', $userDeletedRoleIds)->onlyTrashed();
                    });
            });
        }

        // compute counts for status cards
        $statusCounts = [
            'all' => (clone $baseQuery)->count(),
            'active' => (clone $baseQuery)->whereNull('deleted_at')->where('is_active', true)->count(),
            'inactive' => (clone $baseQuery)->whereNull('deleted_at')->where('is_active', false)->count(),
            'deleted' => (clone $baseQuery)->onlyTrashed()->count(),
        ];

        // Now apply filters (status/search) to a fresh query cloned from base
        $query = (clone $baseQuery);

        if ($request->has('status')) {
            if ($request->status === 'active') {
                $query->whereNull('deleted_at')->where('is_active', true);
            } elseif ($request->status === 'inactive') {
                $query->whereNull('deleted_at')->where('is_active', false);
            } elseif ($request->status === 'deleted') {
                $query->whereNotNull('deleted_at');
            }
        }

        if ($request->has('search') && $request->search) {
            $search = $request->search;
            $query->where(function ($q) use ($search) {
                $q->where('name', 'like', "%{$search}%")
                    ->orWhere('description', 'like', "%{$search}%")
                    ->orWhere('slug', 'like', "%{$search}%");
            });
        }

        // Respect per-page selection from query, with a safe whitelist and default 10
        $allowed = [5,10,15,20,30];
        $perPage = (int) $request->query('per_page', 10);
        if (!in_array($perPage, $allowed, true)) {
            $perPage = 10;
        }

        $roles = $query->orderBy('id', 'asc')->paginate($perPage)->withQueryString();

        // Get users by role if requested (AJAX) — return normalized JSON shape
        if ($request->has('role_id') && $request->ajax()) {
            $role = Role::find($request->role_id);
            $mapped = [];
            if ($role) {
                $this->authorize('view', $role);

                // Users assigned via pivot (model_has_roles)
                $pivotUsers = $role->users()->with('role')->get();

                // Users assigned via direct foreign key on users.role_id
                $directUsers = User::where('role_id', $role->id)->with('role')->get();

                // Merge and deduplicate by id
                $users = $pivotUsers->concat($directUsers)->unique('id')->values();

                $mapped = $users->map(function ($u) {
                    $roleObj = $u->role ? ['id' => $u->role->id, 'name' => $u->role->name, 'slug' => $u->role->slug] : null;
                    return [
                        'id' => $u->id,
                        'name' => $u->name,
                        'email' => $u->email,
                        'active' => (bool) ($u->active ?? $u->is_active ?? false),
                        'custom_roles' => $roleObj ? [$roleObj] : [],
                    ];
                })->values();
            }
            return response()->json($mapped);
        }

        return view('roles.index', compact('roles', 'statusCounts'));
    }

    public function store(Request $request)
    {
        $this->authorize('create', Role::class);
        $validated = $request->validate([
            'name' => 'required|string|max:255|unique:roles,name',
            'description' => 'nullable|string',
            'is_active' => 'boolean',
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
        $validated['is_active'] = $request->has('is_active') ? 1 : 0;

        $role = Role::create($validated);

        if ($request->input('action') === 'save_only') {
            return redirect()->route('roles.index')
                ->with('status', 'Role "' . $role->name . '" created successfully.');
        }

        return redirect()->route('roles.hierarchy', $role->id)
            ->with('status', 'Role "' . $role->name . '" created successfully. Now set its hierarchy.');
    }

    public function show(Role $role)
    {
        $this->authorize('view', $role);
        $role->load('users', 'permissions', 'branch');
        return view('roles.show', compact('role'));
    }

    public function edit(Role $role)
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
    public function create()
    {
        $this->authorize('create', Role::class);
        $permissions = Permission::orderBy('name')->get();
        $branches = Branch::orderBy('name')->get();
        return view('roles.create', compact('permissions', 'branches'));
    }

    public function update(Request $request, Role $role)
    {
        $this->authorize('update', $role);

        $validated = $request->validate([
            'name' => 'required|string|max:255|unique:roles,name,' . $role->id,
            'description' => 'nullable|string',
            'branch_id' => 'nullable|integer|exists:branches,id',
        ]);

        $validated['is_active'] = (int) $request->input('is_active', 0);

        $baseSlug = Str::slug($validated['name']);
        $slug = $baseSlug;
        $counter = 1;

        while (Role::where('slug', $slug)->where('id', '!=', $role->id)->exists()) {
            $slug = $baseSlug . '-' . $counter;
            $counter++;
        }

        $validated['slug'] = $slug;

        $role->fill($validated);
        $role->is_active = $validated['is_active'];
        $role->save();

        // Branch assignment: Developer may choose branch; others get their own branch automatically
        try {
            $currentUser = Auth::user();
            if ($currentUser && $currentUser->isSuperAdmin() && $request->filled('branch_id')) {
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

    public function destroy(Role $role)
    {
        $this->authorize('delete', $role);

        if ($role->isProtected()) {
            return redirect()->route('roles.index')
                ->with('error', 'Cannot delete protected role: ' . $role->name);
        }

        if (Auth::user()->role_id === $role->id) {
            return redirect()->route('roles.index')
                ->with('error', 'You cannot delete your own role.');
        }

        if ($role->users()->count() > 0) {
            return redirect()->route('roles.index')
                ->with('error', 'Cannot delete role with assigned users. Reassign or remove users first.');
        }

        $name = $role->name;
        AuditLog::log('delete_role', $role, $role->toArray());
        $role->delete();

        return redirect()->route('roles.index')
            ->with('deleted', 'Role "' . $name . '" deleted successfully.');
    }

    public function restore($id)
    {
        if (!Auth::user()->hasPermission('roles.restore')) {
            abort(403, 'Unauthorized action.');
        }

        $role = Role::withTrashed()->findOrFail($id);

        $currentUser = Auth::user();
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

    public function managePriority(Request $request, Role $role)
    {
        if (!Auth::user()->hasPermission('roles.manage-priority')) {
            abort(403, 'Unauthorized action.');
        }

        $currentUser = Auth::user();
        $isDeveloper = $currentUser->isSuperAdmin();

        // Provide branches for the dropdown
        $branches = Branch::orderBy('name')->get();

        // Determine selected branch: developer may choose via query, others default to their branch
        if ($isDeveloper) {
            $selectedBranchId = $request->query('branch_id', $branches->first()?->id ?? null);
            $rolesForView = $selectedBranchId
                ? Role::where('branch_id', $selectedBranchId)->orderBy('priority', 'asc')->get()
                : collect([]);
        } else {
            $selectedBranchId = $currentUser->branch_id;
            $rolesForView = Role::where(function ($q) use ($currentUser) {
                $q->where('id', $currentUser->role_id)
                  ->orWhereHas('users', function ($uq) use ($currentUser) {
                      $uq->where('id', $currentUser->id);
                  });
            })->orderBy('priority', 'asc')->get();
        }

        $isHierarchyPage = true;
        return view('roles.hierarchy', compact('role', 'branches', 'selectedBranchId', 'rolesForView', 'isDeveloper', 'isHierarchyPage'));
    }

    private function sortRolesByHierarchy($roles)
    {
        // Hierarchy removed — fall back to ordering by priority then name
        return $roles->sortBy(function ($r) {
            return [$r->priority ?? 100, $r->name];
        })->values();
    }

    private function getVisibleRoleTree($userRole, $allRoles)
    {
        $visibleIds = [$userRole->id];
        if (method_exists($userRole, 'getAllDescendantIds') && \Illuminate\Support\Facades\Schema::hasTable('role_hierarchies')) {
            $descendants = $userRole->getAllDescendantIds()->toArray();
            $descendants = array_filter($descendants, fn($id) => $id !== $userRole->id);
            $visibleIds = array_merge($visibleIds, $descendants);
        }
        return array_unique($visibleIds);
    }

    public function updatePriority(Request $request)
    {
        if (!Auth::user()->hasPermission('roles.manage-priority')) {
            abort(403, 'Unauthorized action.');
        }

        $validated = $request->validate([
            'parents' => 'nullable|array',
            'priorities' => 'nullable|array',
            'priorities.*' => 'nullable|integer|min:1|max:100',
        ]);

        // If the legacy role_hierarchies table is gone, treat this as a priority update
        if (!\Illuminate\Support\Facades\Schema::hasTable('role_hierarchies')) {
            $priorities = $validated['priorities'] ?? [];
            $updatedCount = 0;
            foreach ($priorities as $roleId => $priority) {
                $role = Role::find($roleId);
                if (!$role) continue;
                $old = $role->priority ?? null;
                $role->priority = (int)$priority;
                $role->save();
                if ($old !== $role->priority) $updatedCount++;
            }

            return back()->with('success', 'Role priorities updated successfully. ' . $updatedCount . ' role(s) updated.');
        }

        $currentUser = Auth::user();

        $updatedCount = 0;
        foreach ($validated['parents'] as $roleId => $data) {
            $role = Role::find($roleId);
            if (!$role) {
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
                $parentRole = Role::find($parentId);
                if ($parentRole && !$this->wouldCreateCircularOrRedundant($roleId, $parentId)) {
                    $validParentIds[] = $parentId;
                }
            }

            $oldParents = [];
            if (method_exists($role, 'parents') && \Illuminate\Support\Facades\Schema::hasTable('role_hierarchies')) {
                $oldParents = $role->parents()->pluck('id')->toArray();
                $role->parents()->sync($validParentIds);

                if ($oldParents !== $validParentIds) {
                    AuditLog::log(
                        'update_role_hierarchy',
                        $role,
                        ['parent_ids' => $oldParents],
                        ['parent_ids' => $validParentIds]
                    );
                }
            }

            $updatedCount++;
        }

        return back()->with('success', 'Role hierarchy updated successfully. ' . $updatedCount . ' role(s) updated.');
    }

    private function wouldCreateCircularOrRedundant($roleId, $parentId)
    {
        if (!\Illuminate\Support\Facades\Schema::hasTable('role_hierarchies')) {
            return false;
        }
        $role = Role::find($roleId);
        $parent = Role::find($parentId);

        if (!$role || !$parent) {
            return false;
        }

        if ($roleId == $parentId) {
            return true;
        }

        if (method_exists($role, 'isAncestorOf') && $role->isAncestorOf($parent)) {
            return true;
        }

        $currentParents = [];
        if (method_exists($role, 'parents') && \Illuminate\Support\Facades\Schema::hasTable('role_hierarchies')) {
            $currentParents = $role->parents()->pluck('id')->toArray();
        }

        foreach ($currentParents as $currentParentId) {
            if ($currentParentId == $parentId) {
                continue;
            }

            $currentParent = Role::find($currentParentId);
            if ($currentParent) {
                $ancestorIds = $currentParent->getAllAncestorIds();
                if ($ancestorIds->contains($parentId)) {
                    return true;
                }
            }
        }

        return false;
    }

    public function managePermissions(Role $role)
    {
        $this->authorize('managePermissions', $role);

        $currentUser = Auth::user();
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

    public function updatePermissions(Request $request, Role $role)
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

        $permissions = \App\Models\Permission::whereIn('id', $requestedPermissions)->get();
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
