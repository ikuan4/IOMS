<?php

namespace App\Http\Controllers;

use App\Models\Role;
use App\Models\Permission;
use App\Models\User;
use App\Models\AuditLog;
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

        $query = Role::with(['users', 'parents', 'deletedBy']);

        if ($currentUser->isSuperAdmin() || $currentUser->role?->slug === 'admin') {
            $query->withTrashed();
        } else {
            $manageableRoleIds = $currentUser->getManageableRoles()->pluck('id')->push($currentUser->role_id);

            $userDeletedRoleIds = AuditLog::where('user_id', $currentUser->id)
                ->where('action', 'delete_role')
                ->where('auditable_type', Role::class)
                ->pluck('auditable_id');

            $query->where(function ($q) use ($manageableRoleIds, $userDeletedRoleIds) {
                $q->whereIn('id', $manageableRoleIds)
                    ->orWhere(function ($subQ) use ($userDeletedRoleIds) {
                        $subQ->whereIn('id', $userDeletedRoleIds)->onlyTrashed();
                    });
            });
        }

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

        $roles = $query->orderBy('id', 'asc')->get();

        // Get users by role if requested
        $usersByRole = [];
        if ($request->has('role_id') && $request->ajax()) {
            $role = Role::find($request->role_id);
            if ($role) {
                $this->authorize('view', $role);
                $usersByRole = $role->users()->with('role')->get();
            }
            return response()->json($usersByRole);
        }

        return view('roles.index', compact('roles'));
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
        $role->load('users', 'permissions', 'parent', 'children');
        return view('roles.show', compact('role'));
    }

    public function edit(Role $role)
    {
        $this->authorize('update', $role);
        // Provide all permissions to the edit view so the checkboxes can render
        $permissions = Permission::orderBy('name')->get();
        $role->load('permissions');
        return view('roles.edit', compact('role', 'permissions'));
    }

    /**
     * Show the form for creating a new role.
     */
    public function create()
    {
        $this->authorize('create', Role::class);
        $permissions = Permission::orderBy('name')->get();
        return view('roles.create', compact('permissions'));
    }

    public function update(Request $request, Role $role)
    {
        $this->authorize('update', $role);

        $validated = $request->validate([
            'name' => 'required|string|max:255|unique:roles,name,' . $role->id,
            'description' => 'nullable|string',
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

    public function managePriority(Role $role)
    {
        if (!Auth::user()->hasPermission('roles.manage-priority')) {
            abort(403, 'Unauthorized action.');
        }

        $roles = Role::with([
            'children' => function ($query) {
                $query->withoutTrashed()->where('is_active', 1);
            },
            'parents' => function ($query) {
                $query->withoutTrashed()->where('is_active', 1);
            },
            'users'
        ])->where('is_active', 1)->get();

        $sortedRoles = $this->sortRolesByHierarchy($roles);

        $isSuperAdmin = Auth::user()->isSuperAdmin();

        $managedRoleIds = [];
        $visibleRoleIds = [];

        if (!$isSuperAdmin) {
            $currentUser = Auth::user();
            $userRole = $currentUser->role;
            if ($userRole) {
                $allDescendants = $userRole->getAllDescendantIds()->toArray();
                $managedRoleIds = array_filter($allDescendants, function ($id) use ($userRole) {
                    return $id !== $userRole->id;
                });

                $visibleRoleIds = $this->getVisibleRoleTree($userRole, $roles);
            }

            $sortedRoles = $sortedRoles->filter(function ($r) use ($visibleRoleIds) {
                return in_array($r->id, $visibleRoleIds);
            })->values();
        }

        return view('roles.hierarchy', compact('role', 'roles', 'sortedRoles', 'isSuperAdmin', 'managedRoleIds', 'visibleRoleIds'));
    }

    private function sortRolesByHierarchy($roles)
    {
        $sorted = [];
        $visited = [];

        $rootRoles = $roles->filter(function ($role) {
            return $role->parents->isEmpty();
        });

        $queue = $rootRoles->values()->all();

        while (!empty($queue)) {
            $current = array_shift($queue);

            if (in_array($current->id, $visited)) {
                continue;
            }

            $visited[] = $current->id;
            $sorted[] = $current;

            foreach ($current->children as $child) {
                if (!in_array($child->id, $visited)) {
                    $queue[] = $child;
                }
            }
        }

        foreach ($roles as $role) {
            if (!in_array($role->id, $visited)) {
                $sorted[] = $role;
            }
        }

        return collect($sorted);
    }

    private function getVisibleRoleTree($userRole, $allRoles)
    {
        $visibleIds = [];
        $visibleIds[] = $userRole->id;
        $descendants = $userRole->getAllDescendantIds()->toArray();
        $descendants = array_filter($descendants, function ($id) use ($userRole) {
            return $id !== $userRole->id;
        });
        $visibleIds = array_merge($visibleIds, $descendants);
        return array_unique($visibleIds);
    }

    public function updatePriority(Request $request)
    {
        if (!Auth::user()->hasPermission('roles.manage-priority')) {
            abort(403, 'Unauthorized action.');
        }

        $validated = $request->validate([
            'parents' => 'nullable|array',
        ]);

        if (!isset($validated['parents'])) {
            return back()->with('success', 'Role hierarchy updated successfully.');
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

            $updatedCount++;
        }

        return back()->with('success', 'Role hierarchy updated successfully. ' . $updatedCount . ' role(s) updated.');
    }

    private function wouldCreateCircularOrRedundant($roleId, $parentId)
    {
        $role = Role::find($roleId);
        $parent = Role::find($parentId);

        if (!$role || !$parent) {
            return false;
        }

        if ($roleId == $parentId) {
            return true;
        }

        if ($role->isAncestorOf($parent)) {
            return true;
        }

        $currentParents = $role->parents()->pluck('id')->toArray();

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
