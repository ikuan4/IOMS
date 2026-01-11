<?php

namespace App\Http\Controllers;

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

    /* -------------------------------------------------------------
     | Helpers (centralized role safety)
     |-------------------------------------------------------------*/
    private function roleName(mixed $role): ?string
    {
        if ($role instanceof Role) {
            return $role->name ?? $role->role_name ?? null;
        }

        if ($role instanceof \Illuminate\Database\Eloquent\Collection && $role->isNotEmpty()) {
            $r = $role->first();
            return $r->name ?? $r->role_name ?? null;
        }

        return null;
    }

    private function roleIsActive(mixed $role): bool
    {
        if ($role instanceof Role) {
            return (bool) ($role->is_active ?? $role->active ?? false);
        }

        if ($role instanceof \Illuminate\Database\Eloquent\Collection) {
            $r = $role->first();
            return $r ? $this->roleIsActive($r) : false;
        }

        return false;
    }

    /* -------------------------------------------------------------
     | Index
     |-------------------------------------------------------------*/
    public function index(Request $request): View
    {
        $this->authorize('viewAny', User::class);

        $currentUser = Auth::user();
        assert($currentUser instanceof User);

        $search  = (string) $request->query('search');
        $status  = $request->query('status', 'all');
        $perPage = (int) $request->query('per_page', 10);

        $query = User::with([
            'role' => function($q) { $q->withTrashed(); },
            'branch' => function($q) { $q->withTrashed(); }
        ])->withTrashed();

        // Enforce hierarchy: users can only see users with lower hierarchy (higher priority number)
        if (!$currentUser->isSuperAdmin()) {
            $myPriority = $currentUser->effectiveRole()?->priority ?? 999;

            $query->where('branch_id', $currentUser->branch_id)
                ->where(function($q) use ($myPriority) {
                    $q->whereHas('role', function($roleQuery) use ($myPriority) {
                        $roleQuery->where('priority', '>', $myPriority);
                    })
                    ->orWhereNull('role_id'); // Include users without roles
                });
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
            $query = User::onlyTrashed()->with(['role', 'branch']);
        }

        $users = $query->paginate($perPage)->withQueryString();

        // Build base query for counts with same hierarchy restrictions
        $baseCountQuery = User::query();
        if (!$currentUser->isSuperAdmin()) {
            $myPriority = $currentUser->effectiveRole()?->priority ?? 999;
            $baseCountQuery->where('branch_id', $currentUser->branch_id)
                ->where(function($q) use ($myPriority) {
                    $q->whereHas('role', function($roleQuery) use ($myPriority) {
                        $roleQuery->where('priority', '>', $myPriority);
                    })
                    ->orWhereNull('role_id');
                });
        }

        $statusCounts = [
            'all'         => (clone $baseCountQuery)->count(),
            'active'      => (clone $baseCountQuery)->whereNull('deleted_at')->where('active', true)->count(),
            'deactivated' => (clone $baseCountQuery)->whereNull('deleted_at')->where('active', false)->count(),
            'deleted'     => (clone $baseCountQuery)->onlyTrashed()->count(),
        ];

        return view('users.index', compact('users', 'search', 'status', 'statusCounts'));
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

        $data = $request->validate([
            'name'      => ['required', 'string', 'max:255'],
            'mobile'    => ['required', 'string', 'max:50', 'unique:users,mobile'],
            'email'     => ['required', 'email', 'max:255', 'unique:users,email'],
            'password'  => ['required', 'string', 'min:8', 'confirmed'],
            'avatar'    => ['nullable', 'image', 'max:2048'],
            'role_id'   => ['required', 'exists:roles,id'],
            'branch_id' => ['nullable', 'exists:branches,id'],
            'active'    => ['nullable', 'boolean'],
        ]);

        // Prevent creation of developer-like users (null role_id and null branch_id)
        // Only the seeder or direct database operation should create the developer user
        if ($data['role_id'] === null) {
            return back()->withInput()->with('error', 'Cannot create user without a role. Developer user must be created via seeder only.');
        }

        $role = Role::withTrashed()->find($data['role_id']);

        // Prevent assigning a role that belongs to a different branch than provided
        $requestedBranchId = $request->input('branch_id') ?? null;
        $currentUser = Auth::user();

        if ($requestedBranchId !== null && $role instanceof Role) {
            if (!$currentUser->isSuperAdmin() && $role->branch_id !== null && $role->branch_id != $requestedBranchId) {
                return back()->withInput()->with('error', 'Selected role belongs to a different branch.');
            }
        }

        // Strict validation: user branch must match role branch (unless role has no branch)
        if ($role instanceof Role && $role->branch_id !== null && $requestedBranchId !== null) {
            if ($role->branch_id != $requestedBranchId) {
                return back()->withInput()->with('error', 'User branch must match role branch.');
            }
        }

        // Use transaction with role status re-check to prevent race conditions
        return DB::transaction(function () use ($request, $data, $role) {
            // Re-check role status inside transaction
            $role = Role::withTrashed()->lockForUpdate()->find($data['role_id']);

            if ($request->boolean('active', true)) {
                if ($role instanceof Role && ($role->trashed() || ! $this->roleIsActive($role))) {
                    return back()->withInput()->with('error', 'Cannot activate user because the selected role is inactive or deleted.');
                }
                }

            $user = new User($data);
            $user->password   = Hash::make($data['password']);
            $user->active     = $request->boolean('active', true);
            $user->created_by = optional(Auth::user())->id;

            if ($request->hasFile('avatar')) {
                $path = $request->file('avatar')->store('avatars', 'public');
                if ($path !== false) {
                    $user->avatar = $path;
                }
            }

            $user->branch_id = $request->input('branch_id') ?? $role->branch_id ?? null;
            $user->save();

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

        $data = $request->validate([
            'name'     => ['required', 'string', 'max:255'],
            'mobile'   => ['required', Rule::unique('users')->ignore($user->id)],
            'email'    => ['required', 'email', Rule::unique('users', 'email')->ignore($user->id)],
            'password' => ['nullable', 'confirmed', 'min:8'],
            'role_id'  => ['required', 'exists:roles,id'],
            'branch_id'=> ['nullable', 'exists:branches,id'],
            'active'   => ['nullable', 'boolean'],
        ]);

        $role = Role::withTrashed()->find($data['role_id']);

        // Prevent assigning a role that belongs to a different branch than provided
        $requestedBranchId = $request->input('branch_id') ?? null;
        $currentUser = Auth::user();

        if ($requestedBranchId !== null && $role instanceof Role) {
            if (!$currentUser->isSuperAdmin() && $role->branch_id !== null && $role->branch_id != $requestedBranchId) {
                return back()->withInput()->with('error', 'Selected role belongs to a different branch.');
            }
        }

        // Strict validation: user branch must match role branch (unless role has no branch)
        if ($role instanceof Role && $role->branch_id !== null && $requestedBranchId !== null) {
            if ($role->branch_id != $requestedBranchId) {
                return back()->withInput()->with('error', 'User branch must match role branch.');
            }
        }

        $roleName = strtolower(trim($this->roleName($role) ?? ''));
        $isDevRole = $roleName === 'developer';

        $currentRoleName = strtolower(trim($this->roleName(optional(Auth::user())->role) ?? ''));

        if ($isDevRole && $currentRoleName !== 'developer') {
            return back()->withInput()->with('error', 'You cannot assign Developer role.');
        }

        // Use transaction with role status re-check
        return DB::transaction(function () use ($request, $data, $user, $role) {
            // Re-check role status inside transaction
            $role = Role::withTrashed()->lockForUpdate()->find($data['role_id']);

            // When activating a user, ensure role is active
            if ($request->boolean('active', false)) {
                if ($role instanceof Role && ($role->trashed() || ! $this->roleIsActive($role))) {
                    return back()->withInput()->with('error', 'Cannot activate user because the selected role is inactive or deleted.');
                }
                }

            $user->fill($data);
            $user->active    = $request->boolean('active', false);
            $user->branch_id = $request->input('branch_id') ?? $role->branch_id ?? $user->branch_id;

            if (! empty($data['password'])) {
                $user->password = Hash::make($data['password']);
            }

            $user->updated_by = optional(Auth::user())->id;
            $user->save();

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

        $user->deleted_by = optional(Auth::user())->id;
        $user->save();
        $user->delete();

        return back()->with('deleted', 'User deleted successfully.');
    }

    /* -------------------------------------------------------------
     | Edit Profile (for developer self-update)
     |-------------------------------------------------------------*/
    public function editProfile(): View
    {
        $user = Auth::user();

        // Only allow if user is developer (no role_id and no branch_id)
        if (!$user->isSuperAdmin()) {
            abort(403, 'Unauthorized. Profile editing is only for developer user.');
        }

        return view('users.profile', compact('user'));
    }

    /* -------------------------------------------------------------
     | Update Profile (for developer self-update - photo & basic info only)
     |-------------------------------------------------------------*/
    public function updateProfile(Request $request): RedirectResponse
    {
        $user = Auth::user();

        // Only allow if user is developer (no role_id and no branch_id)
        if (!$user->isSuperAdmin()) {
            abort(403, 'Unauthorized. Profile updates are only for developer user.');
        }

        $data = $request->validate([
            'name'     => ['required', 'string', 'max:255'],
            'email'    => ['nullable', 'email', Rule::unique('users', 'email')->whereNotNull('email')->ignore($user->id)],
            'password' => ['nullable', 'confirmed', 'min:8'],
            'avatar'   => ['nullable', 'image', 'max:2048'],
            'remove_avatar' => ['nullable', 'boolean'],
        ]);

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

        return redirect()->route('profile.edit')
            ->with('status', 'Profile updated successfully.');
    }

    /* -------------------------------------------------------------
     | Restore
     |-------------------------------------------------------------*/
    public function restore(Request $request, int $id): RedirectResponse
    {
        $user = User::withTrashed()->findOrFail($id);

        $role = Role::withTrashed()->find($user->role_id);

        // Prevent restoring user if assigned role is trashed/inactive or its branch is trashed
        if ($role instanceof Role) {
            if ($role->trashed() || ! $this->roleIsActive($role)) {
                return back()->with('error', 'Cannot restore user because the assigned role is inactive or deleted.');
            }

            // check branch of role (developers may bypass)
            try {
                $currentUser = Auth::user();
                if (!($currentUser && $currentUser->isSuperAdmin()) ) {
                    if ($role->branch_id !== null) {
                        $branch = Branch::withTrashed()->find($role->branch_id);
                        if ($branch && $branch->trashed()) {
                            return back()->with('error', 'Cannot restore user because the assigned role is inactive or deleted.');
                        }
                    }
                }
            } catch (\Throwable $__e) {
                // ignore
            }
        }

        $user->restore();
        $user->restored_by = optional(Auth::user())->id;
        $user->save();

        return back()->with('status', 'User restored successfully.');
    }
}
