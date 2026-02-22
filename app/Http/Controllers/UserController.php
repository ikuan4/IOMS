<?php

namespace App\Http\Controllers;

use App\Models\AuditLog;
use App\Models\User;
use App\Models\Role;
use App\Models\Branch;
use Illuminate\View\View;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Storage;
use Illuminate\Validation\Rule;
use Illuminate\Foundation\Auth\Access\AuthorizesRequests;

class UserController extends Controller
{
    use AuthorizesRequests;

    /**
     * Remove sensitive fields from audit payloads.
     *
     * @param array<string,mixed> $values
     * @return array<string,mixed>
     */
    private function sanitizeUserAuditValues(array $values): array
    {
        unset($values['password'], $values['remember_token']);
        return $values;
    }

    /* -------------------------------------------------------------
     | Index
     |-------------------------------------------------------------*/
    public function index(Request $request): View
    {
        $this->authorize('viewAny', User::class);

        $currentUser = Auth::user();
        if (!$currentUser) {
            abort(403);
        }

        $search  = (string) $request->query('search');
        $status  = $request->query('status', 'all');
        $perPage = (int) $request->query('per_page', 10);

        $query = User::with([
            'globalRole' => function($q) { $q->withTrashed(); },
            'branchRoles' => function($q) { $q->withTrashed(); },
            'branches' => function($q) { $q->withTrashed(); }
        ])->withTrashed();

        // Enforce hierarchy: users can only see users with lower hierarchy (higher priority number)
        if (!$currentUser->isSuperAdmin()) {
            $effectiveRole = $currentUser->effectiveRole();
            $myPriority = $effectiveRole ? ($effectiveRole->priority ?? 999) : 999;
            $activeBranchId = session('active_branch_id');

            // Branch users can only see users in their active branch
            if ($activeBranchId) {
                $query->where(function($q) use ($activeBranchId, $myPriority) {
                    // Users who have a role in the active branch
                    $q->whereHas('branches', function($branchQuery) use ($activeBranchId) {
                        $branchQuery->where('branch_id', $activeBranchId);
                    })->where(function($roleQuery) use ($myPriority) {
                        // Check priority via branchRoles relationship
                        $roleQuery->whereHas('branchRoles', function($brQuery) use ($myPriority) {
                            $brQuery->where('priority', '>', $myPriority);
                        });
                    });
                });
            }
        }

        if ($search !== '') {
            $query->where(fn ($q) =>
                $q->where('name', 'like', "%$search%")
                  ->orWhere('mobile', 'like', "%$search%")
                  ->orWhere('email', 'like', "%$search%")
            );
        }

        if ($status === 'active') {
            $query->where('active', true)->whereNull('deleted_at');
        } elseif ($status === 'deactivated') {
            $query->where('active', false)->whereNull('deleted_at');
        } elseif ($status === 'deleted') {
            $query = User::onlyTrashed()->with(['globalRole', 'branchRoles', 'branches']);
        }

        $users = $query->paginate($perPage)->withQueryString();

        // Build base query for counts with same hierarchy restrictions
        $baseCountQuery = User::query();
        if (!$currentUser->isSuperAdmin()) {
            $effectiveRole = $currentUser->effectiveRole();
            $myPriority = $effectiveRole ? ($effectiveRole->priority ?? 999) : 999;
            $activeBranchId = session('active_branch_id');
            
            if ($activeBranchId) {
                $baseCountQuery->where(function($q) use ($activeBranchId, $myPriority) {
                    $q->whereHas('branches', function($branchQuery) use ($activeBranchId) {
                        $branchQuery->where('branch_id', $activeBranchId);
                    })->where(function($roleQuery) use ($myPriority) {
                        $roleQuery->whereHas('branchRoles', function($brQuery) use ($myPriority) {
                            $brQuery->where('priority', '>', $myPriority);
                        });
                    });
                });
            }
        }

        $statusCounts = [
            'all'         => (clone $baseCountQuery)->count(),
            'active'      => (clone $baseCountQuery)->whereNull('deleted_at')->where('active', true)->count(),
            'deactivated' => (clone $baseCountQuery)->whereNull('deleted_at')->where('active', false)->count(),
            'deleted'     => (clone $baseCountQuery)->onlyTrashed()->count(),
        ];

        // Table refresh uses AJAX, but SPA navigation also uses XHR fetch.
        // SPA navigation needs a full HTML document containing <main.main>.
        $isSpaNavigation = strtolower((string) $request->header('X-SPA-Navigation')) === 'true';
        if ($request->ajax() && ! $isSpaNavigation) {
            /** @var view-string $view */
            $view = 'users._users_table';
            return view($view, compact('users'));
        }

        return view('users.index', compact('users', 'search', 'status', 'statusCounts'));
    }

    /* -------------------------------------------------------------
     | Show
     |-------------------------------------------------------------*/
    public function show(User $user): View
    {
        $this->authorize('view', $user);

        $user->load([
            'globalRole' => function($q) { $q->withTrashed(); },
            'branchRoles' => function($q) { $q->withTrashed(); },
            'branches' => function($q) { $q->withTrashed(); }
        ]);

        return view('users.show', compact('user'));
    }

    /* -------------------------------------------------------------
     | Create
     |-------------------------------------------------------------*/
    public function create(): View
    {
        $this->authorize('create', User::class);

        $currentUser = Auth::user();

        $roles = ($currentUser && method_exists($currentUser, 'getAvailableRolesForAssignment'))
            ? $currentUser->getAvailableRolesForAssignment()
            : Role::all();

        return view('users.create', [
            'user'  => new User(),
            'roles' => $roles,
        ]);
    }

    /* -------------------------------------------------------------
     | Store
     |-------------------------------------------------------------*/
    public function store(Request $request): RedirectResponse
    {
        $this->authorize('create', User::class);

        $actorId = Auth::id();
        /** @var int<0, max>|null $actorIdInt */
        $actorIdInt = null;
        if (is_int($actorId)) {
            $actorIdInt = $actorId;
        } elseif (is_string($actorId) && ctype_digit($actorId)) {
            $actorIdInt = (int) $actorId;
        }

        $data = $request->validate([
            'name'           => ['required', 'string', 'max:255'],
            'mobile'         => ['required', 'string', 'max:50', 'unique:users,mobile'],
            'email'          => ['required', 'email', 'max:255', 'unique:users,email'],
            'password'       => ['required', 'string', 'min:8', 'confirmed'],
            'avatar'         => ['nullable', 'image', 'max:2048'],
            'global_role_id' => ['nullable', 'exists:roles,id'],
            'branch_ids'     => ['nullable', 'array'],
            'branch_ids.*'   => ['exists:branches,id'],
            'role_ids'       => ['nullable', 'array'],
            'role_ids.*'     => ['exists:roles,id'],
            'active'         => ['nullable', 'boolean'],
        ]);

        $currentUser = Auth::user();

        if (!$currentUser) {
            abort(403);
        }

        // Validate: must have either global_role_id OR (branch_ids + role_ids)
        if (!$request->filled('global_role_id') && (!$request->filled('branch_ids') || !$request->filled('role_ids'))) {
            return back()->withInput()->with('error', 'User must have either a global role OR branch assignments with roles.');
        }

        // Validate: cannot have both global_role_id AND branch assignments
        if ($request->filled('global_role_id') && ($request->filled('branch_ids') || $request->filled('role_ids'))) {
            return back()->withInput()->with('error', 'User cannot have both a global role and branch-specific roles.');
        }

        // If global role, validate it's actually global
        if ($request->filled('global_role_id')) {
            $globalRole = Role::withTrashed()->find($data['global_role_id']);
            if (!$globalRole || !$globalRole->is_global) {
                return back()->withInput()->with('error', 'Selected role is not a global role.');
            }

            // Only superadmin can assign global roles
            if (!$currentUser->isSuperAdmin()) {
                return back()->withInput()->with('error', 'Only superadmins can assign global roles.');
            }

            // Check role status
            if ($request->boolean('active', true) && ($globalRole->trashed() || !($globalRole->is_active ?? false))) {
                return back()->withInput()->with('error', 'Cannot activate user because the selected role is inactive or deleted.');
            }
        }

        // If branch roles, validate branch count matches role count
        if ($request->filled('branch_ids') && $request->filled('role_ids')) {
            $branchIds = $data['branch_ids'] ?? [];
            $roleIds = $data['role_ids'] ?? [];

            if (count($branchIds) !== count($roleIds)) {
                return back()->withInput()->with('error', 'Number of branches must match number of roles.');
            }

            // Validate all roles are branch roles
            /** @var \Illuminate\Database\Eloquent\Collection<int, Role> $roles */
            $roles = Role::withTrashed()->whereIn('id', $roleIds)->get();
            foreach ($roles as $role) {
                /** @var Role $role */
                if ($role->is_global) {
                    return back()->withInput()->with('error', 'Cannot assign global role as branch role.');
                }

                // Branch users can only assign roles in their active branch
                if (!$currentUser->isSuperAdmin()) {
                    $activeBranchId = session('active_branch_id');
                    if (!in_array($activeBranchId, $branchIds, true)) {
                        return back()->withInput()->with('error', 'You can only assign users to your active branch.');
                    }

                    // Check if role belongs to the paired branch
                    $roleIndex = array_search($role->id, $roleIds);
                    $pairedBranchId = $branchIds[$roleIndex] ?? null;

                    if ($role->branch_id && $role->branch_id != $pairedBranchId) {
                        return back()->withInput()->with('error', 'Role must belong to the assigned branch.');
                    }
                }

                // Check role status
                if ($request->boolean('active', true) && ($role->trashed() || !($role->is_active ?? false))) {
                    return back()->withInput()->with('error', 'Cannot activate user because one of the selected roles is inactive or deleted.');
                }
            }
        }

        // Use transaction
        return DB::transaction(function () use ($request, $data, $actorIdInt) {
            $user = new User();
            $user->fill($data);
            $user->password   = Hash::make($data['password']);
            $user->active     = $request->boolean('active', true);
            $user->created_by = $actorIdInt;
            $user->global_role_id = $data['global_role_id'] ?? null;

            if ($request->hasFile('avatar')) {
                $path = $request->file('avatar')->store('avatars', 'public');
                if ($path !== false) {
                    $user->avatar = $path;
                }
            }

            $user->save();

            // Handle branch role assignments
            if (!$user->global_role_id && $request->filled('branch_ids') && $request->filled('role_ids')) {
                $branchIds = $data['branch_ids'] ?? [];
                $roleIds = $data['role_ids'] ?? [];

                for ($i = 0; $i < count($branchIds); $i++) {
                    // Insert into branch_user
                    DB::table('branch_user')->insert([
                        'user_id' => $user->id,
                        'branch_id' => $branchIds[$i],
                        'created_at' => now(),
                        'updated_at' => now(),
                    ]);

                    // Insert into branch_user_role
                    DB::table('branch_user_role')->insert([
                        'user_id' => $user->id,
                        'branch_id' => $branchIds[$i],
                        'role_id' => $roleIds[$i],
                        'created_at' => now(),
                        'updated_at' => now(),
                    ]);
                }
            }

            AuditLog::log(
                'create_user',
                $user,
                [],
                $this->sanitizeUserAuditValues($user->toArray())
            );

            return redirect()->route('users.index')
                ->with('status', 'User "' . $user->name . '" created successfully.');
        });
    }

    /* -------------------------------------------------------------
     | Edit
     |-------------------------------------------------------------*/
    public function edit(User $user): View
    {
        $this->authorize('update', $user);

        $currentUser = Auth::user();

        $roles = ($currentUser && method_exists($currentUser, 'getAvailableRolesForAssignment'))
            ? $currentUser->getAvailableRolesForAssignment()
            : Role::all();

        return view('users.edit', compact('user', 'roles'));
    }

    /* -------------------------------------------------------------
     | Update
     |-------------------------------------------------------------*/
    public function update(Request $request, User $user): RedirectResponse
    {
        $this->authorize('update', $user);

        $actorId = Auth::id();
        /** @var int<0, max>|null $actorIdInt */
        $actorIdInt = null;
        if (is_int($actorId)) {
            $actorIdInt = $actorId;
        } elseif (is_string($actorId) && ctype_digit($actorId)) {
            $actorIdInt = (int) $actorId;
        }

        $data = $request->validate([
            'name'           => ['required', 'string', 'max:255'],
            'mobile'         => ['required', Rule::unique('users')->ignore($user->id)],
            'email'          => ['required', 'email', Rule::unique('users', 'email')->ignore($user->id)],
            'password'       => ['nullable', 'confirmed', 'min:8'],
            'global_role_id' => ['nullable', 'exists:roles,id'],
            'branch_ids'     => ['nullable', 'array'],
            'branch_ids.*'   => ['exists:branches,id'],
            'role_ids'       => ['nullable', 'array'],
            'role_ids.*'     => ['exists:roles,id'],
            'active'         => ['nullable', 'boolean'],
        ]);

        $currentUser = Auth::user();

        if (!$currentUser) {
            abort(403);
        }

        // Validate: must have either global_role_id OR (branch_ids + role_ids)
        if (!$request->filled('global_role_id') && (!$request->filled('branch_ids') || !$request->filled('role_ids'))) {
            return back()->withInput()->with('error', 'User must have either a global role OR branch assignments with roles.');
        }

        // Validate: cannot have both global_role_id AND branch assignments
        if ($request->filled('global_role_id') && ($request->filled('branch_ids') || $request->filled('role_ids'))) {
            return back()->withInput()->with('error', 'User cannot have both a global role and branch-specific roles.');
        }

        // If global role, validate it's actually global
        if ($request->filled('global_role_id')) {
            $globalRole = Role::withTrashed()->find($data['global_role_id']);
            if (!$globalRole || !$globalRole->is_global) {
                return back()->withInput()->with('error', 'Selected role is not a global role.');
            }

            // Only superadmin can assign global roles
            if (!$currentUser->isSuperAdmin()) {
                return back()->withInput()->with('error', 'Only superadmins can assign global roles.');
            }

            // Check role status
            if ($request->boolean('active', false) && ($globalRole->trashed() || !($globalRole->is_active ?? false))) {
                return back()->withInput()->with('error', 'Cannot activate user because the selected role is inactive or deleted.');
            }
        }

        // If branch roles, validate branch count matches role count
        if ($request->filled('branch_ids') && $request->filled('role_ids')) {
            $branchIds = $data['branch_ids'] ?? [];
            $roleIds = $data['role_ids'] ?? [];

            if (count($branchIds) !== count($roleIds)) {
                return back()->withInput()->with('error', 'Number of branches must match number of roles.');
            }

            // Validate all roles are branch roles
            $roles = Role::withTrashed()->whereIn('id', $roleIds)->get();
            foreach ($roles as $role) {
                if ($role->is_global) {
                    return back()->withInput()->with('error', 'Cannot assign global role as branch role.');
                }

                // Branch users can only assign roles in their active branch
                if (!$currentUser->isSuperAdmin()) {
                    $activeBranchId = session('active_branch_id');
                    if (!in_array($activeBranchId, $branchIds, true)) {
                        return back()->withInput()->with('error', 'You can only assign users to your active branch.');
                    }

                    // Check if role belongs to the paired branch
                    $roleIndex = array_search($role->id, $roleIds);
                    $pairedBranchId = $branchIds[$roleIndex] ?? null;

                    if ($role->branch_id && $role->branch_id != $pairedBranchId) {
                        return back()->withInput()->with('error', 'Role must belong to the assigned branch.');
                    }
                }

                // Check role status
                if ($request->boolean('active', false) && ($role->trashed() || !($role->is_active ?? false))) {
                    return back()->withInput()->with('error', 'Cannot activate user because one of the selected roles is inactive or deleted.');
                }
            }
        }

        // Use transaction
        $oldValues = $this->sanitizeUserAuditValues($user->toArray());

        return DB::transaction(function () use ($request, $data, $user, $actorIdInt, $oldValues) {
            // Only fill fields that should be mass-assigned (exclude password)
            $user->fill([
                'name' => $data['name'],
                'mobile' => $data['mobile'],
                'email' => $data['email'],
            ]);

            $user->active         = $request->boolean('active', false);
            $user->global_role_id = $data['global_role_id'] ?? null;

            // Only update password if provided
            if (! empty($data['password'])) {
                $user->password = Hash::make($data['password']);
            }

            $user->updated_by = $actorIdInt;
            $user->save();

            // Clear existing branch assignments
            DB::table('branch_user')->where('user_id', $user->id)->delete();
            DB::table('branch_user_role')->where('user_id', $user->id)->delete();

            // Handle new branch role assignments (only if not global user)
            if (!$user->global_role_id && $request->filled('branch_ids') && $request->filled('role_ids')) {
                $branchIds = $data['branch_ids'] ?? [];
                $roleIds = $data['role_ids'] ?? [];

                for ($i = 0; $i < count($branchIds); $i++) {
                    // Insert into branch_user
                    DB::table('branch_user')->insert([
                        'user_id' => $user->id,
                        'branch_id' => $branchIds[$i],
                        'created_at' => now(),
                        'updated_at' => now(),
                    ]);

                    // Insert into branch_user_role
                    DB::table('branch_user_role')->insert([
                        'user_id' => $user->id,
                        'branch_id' => $branchIds[$i],
                        'role_id' => $roleIds[$i],
                        'created_at' => now(),
                        'updated_at' => now(),
                    ]);
                }
            }

            AuditLog::log(
                'update_user',
                $user,
                $oldValues,
                $this->sanitizeUserAuditValues($user->fresh()?->toArray() ?? [])
            );

            return redirect()->route('users.index')
                ->with('status', 'User "' . $user->name . '" updated successfully.');
        });
    }

    /* -------------------------------------------------------------
     | Destroy
     |-------------------------------------------------------------*/
    public function destroy(User $user): RedirectResponse
    {
        $this->authorize('delete', $user);

        $oldValues = $this->sanitizeUserAuditValues($user->toArray());

        $actorId = Auth::id();
        /** @var int<0, max>|null $actorIdInt */
        $actorIdInt = null;
        if (is_int($actorId)) {
            $actorIdInt = $actorId;
        } elseif (is_string($actorId) && ctype_digit($actorId)) {
            $actorIdInt = (int) $actorId;
        }

        $user->deleted_by = $actorIdInt;
        $user->save();
        $user->delete();

        $after = User::withTrashed()->find($user->getKey());
        $afterValues = $after ? $this->sanitizeUserAuditValues($after->toArray()) : [];
        AuditLog::log('delete_user', $user, $oldValues, $afterValues);

        return back()->with('deleted', 'User deleted successfully.');
    }

    /* -------------------------------------------------------------
     | Edit Profile (for developer self-update)
     |-------------------------------------------------------------*/
    public function editProfile(): View
    {
        $user = Auth::user();

        if (!$user) {
            abort(403);
        }

        return view('users.profile', compact('user'));
    }

    /* -------------------------------------------------------------
     | Update Profile (for developer self-update - photo & basic info only)
     |-------------------------------------------------------------*/
    public function updateProfile(Request $request): RedirectResponse
    {
        $user = Auth::user();

        if (!$user) {
            abort(403);
        }

        $data = $request->validate([
            'name'     => ['required', 'string', 'max:255'],
            // keep backward compatible: allow blank to preserve current value
            'email'    => ['nullable', 'email', Rule::unique('users', 'email')->ignore($user->id)],
            'password' => ['nullable', 'confirmed', 'min:8'],
            'avatar'   => ['nullable', 'image', 'max:2048'],
            'remove_avatar' => ['nullable', 'boolean'],
        ]);

        $oldValues = $this->sanitizeUserAuditValues($user->toArray());

        $user->name = $data['name'];
        $user->email = $data['email'] ?? $user->email;

        if (!empty($data['password'])) {
            $user->password = Hash::make($data['password']);
        }

        // Handle avatar upload
        if ($request->hasFile('avatar')) {
            // Delete old avatar if exists
            if ($user->avatar && Storage::disk('public')->exists($user->avatar)) {
                Storage::disk('public')->delete($user->avatar);
            }

            $path = $request->file('avatar')->store('avatars', 'public');
            if ($path !== false) {
                $user->avatar = $path;
            }
        }

        // Handle avatar removal
        if ($request->boolean('remove_avatar', false)) {
            if ($user->avatar && Storage::disk('public')->exists($user->avatar)) {
                Storage::disk('public')->delete($user->avatar);
            }
            $user->avatar = null;
        }

        $user->updated_by = $user->id;
        $user->save();

        AuditLog::log(
            'update_user_profile',
            $user,
            $oldValues,
            $this->sanitizeUserAuditValues($user->fresh()?->toArray() ?? [])
        );

        return redirect()->route('profile.edit')
            ->with('status', 'Profile updated successfully.');
    }

    /* -------------------------------------------------------------
     | Restore
     |-------------------------------------------------------------*/
    public function restore(Request $request, int $id): RedirectResponse
    {
        $user = User::withTrashed()->findOrFail($id);

        $oldValues = $this->sanitizeUserAuditValues($user->toArray());

        // Check global role status if this is a global user
        if ($user->global_role_id) {
            $globalRole = Role::withTrashed()->find($user->global_role_id);

            if ($globalRole instanceof Role) {
                if ($globalRole->trashed() || !($globalRole->is_active ?? false)) {
                    return back()->with('error', 'Cannot restore user because the assigned global role is inactive or deleted.');
                }
            }
        } else {
            // Check branch roles status
            $branchRoleIds = DB::table('branch_user_role')
                ->where('user_id', $user->id)
                ->pluck('role_id');

            if ($branchRoleIds->isNotEmpty()) {
                $branchRoles = Role::withTrashed()->whereIn('id', $branchRoleIds)->get();
                foreach ($branchRoles as $role) {
                    if ($role->trashed() || !($role->is_active ?? false)) {
                        return back()->with('error', 'Cannot restore user because one of the assigned roles is inactive or deleted.');
                    }
                }
            }
        }

        if ($user->trashed()) {
            if (method_exists($user, 'restoreWithUser')) {
                $user->restoreWithUser();
            } else {
                $user->restore();
            }

            $actorId = Auth::id();
            /** @var int<0, max>|null $actorIdInt */
            $actorIdInt = null;
            if (is_int($actorId)) {
                $actorIdInt = $actorId;
            } elseif (is_string($actorId) && ctype_digit($actorId)) {
                $actorIdInt = (int) $actorId;
            }

            // Keep updated_by aligned with the restore actor.
            try {
                $user->updated_by = $actorIdInt;
                $user->save();
            } catch (\Throwable $__e) {
                // ignore
            }

            $after = User::withTrashed()->find($user->getKey());
            $afterValues = $after ? $this->sanitizeUserAuditValues($after->toArray()) : [];
            AuditLog::log('restore_user', $user, $oldValues, $afterValues);
        }

        return back()->with('status', 'User restored successfully.');
    }

    /**
     * Check if a user can be restored or activated (check parent dependencies)
     */
    public function checkDependencies(Request $request, int $id): \Illuminate\Http\JsonResponse
    {
        /** @var User $user */
        $user = User::withTrashed()->with([
            'globalRole' => function($q) { $q->withTrashed(); },
            'branchRoles' => function($q) { $q->withTrashed(); },
            'branches' => function($q) { $q->withTrashed(); }
        ])->findOrFail($id);

        $this->authorize('view', $user);

        $dependencies = [];
        $canProceed = true;

        // Check if user is deleted and needs restore
        if ($user->trashed()) {
            // Check global role status
            if ($user->global_role_id) {
                $globalRole = $user->globalRole;
                if ($globalRole instanceof Role && $globalRole->trashed()) {
                    $canProceed = false;
                    $dependencies[] = [
                        'type' => 'deleted_role',
                        'message' => 'Assigned global role is deleted',
                        'details' => "Role '{$globalRole->name}' must be restored first"
                    ];
                } elseif ($globalRole && !($globalRole->is_active ?? true)) {
                    $canProceed = false;
                    $dependencies[] = [
                        'type' => 'inactive_role',
                        'message' => 'Assigned global role is inactive',
                        'details' => "Role '{$globalRole->name}' must be activated first"
                    ];
                }
            } else {
                // Check branch roles
                $branchRoles = $user->branchRoles;
                foreach ($branchRoles as $role) {
                    if ($role->trashed()) {
                        $canProceed = false;
                        $dependencies[] = [
                            'type' => 'deleted_role',
                            'message' => 'Assigned branch role is deleted',
                            'details' => "Role '{$role->name}' must be restored first"
                        ];
                    } elseif (!($role->is_active ?? true)) {
                        $canProceed = false;
                        $dependencies[] = [
                            'type' => 'inactive_role',
                            'message' => 'Assigned branch role is inactive',
                            'details' => "Role '{$role->name}' must be activated first"
                        ];
                    }
                }

                // Check branch status
                $branches = $user->branches;
                foreach ($branches as $branch) {
                    if ($branch->trashed()) {
                        $canProceed = false;
                        $dependencies[] = [
                            'type' => 'deleted_branch',
                            'message' => 'Assigned branch is deleted',
                            'details' => "Branch '{$branch->name}' must be restored first"
                        ];
                    }
                }
            }
        }

        // Check if user is being activated
        if (!$user->trashed() && !$user->active) {
            // Check global role status
            if ($user->global_role_id) {
                $globalRole = $user->globalRole;
                if ($globalRole instanceof Role && !($globalRole->is_active ?? true)) {
                    $canProceed = false;
                    $dependencies[] = [
                        'type' => 'inactive_role',
                        'message' => 'Assigned global role is inactive',
                        'details' => "Role '{$globalRole->name}' must be activated first"
                    ];
                }
            } else {
                // Check branch roles
                $branchRoles = $user->branchRoles;
                foreach ($branchRoles as $role) {
                    if (!($role->is_active ?? true)) {
                        $canProceed = false;
                        $dependencies[] = [
                            'type' => 'inactive_role',
                            'message' => 'Assigned branch role is inactive',
                            'details' => "Role '{$role->name}' must be activated first"
                        ];
                    }
                }
            }
        }

        return response()->json([
            'can_proceed' => $canProceed,
            'dependencies' => $dependencies,
            'message' => $canProceed
                ? 'User can be restored/activated.'
                : 'Cannot proceed. Please resolve dependencies first.'
        ]);
    }
}
