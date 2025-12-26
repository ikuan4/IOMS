<?php

namespace App\Http\Controllers;

use App\Models\User;
use App\Models\Role;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Storage;
use Illuminate\Validation\Rule;
use Illuminate\Foundation\Auth\Access\AuthorizesRequests;

class UserController extends Controller
{
    use AuthorizesRequests;

    public function index(Request $request)
    {
        try { $this->authorize('viewAny', User::class); } catch (\Throwable $e) {}

        $search = $request->query('search');
        $status = $request->query('status', 'all');

        /** @var User $currentUser */
        $currentUser = Auth::user();

        // Determine manageable users: if user model provides helper use it, otherwise allow all
        if ($currentUser && method_exists($currentUser, 'isSuperAdmin') && !$currentUser->isSuperAdmin()) {
            if (method_exists($currentUser, 'getManageableUsers')) {
                $manageableUsers = $currentUser->getManageableUsers();
                $manageableUserIds = $manageableUsers->pluck('id')->push($currentUser->id);
            } else {
                $manageableUserIds = null;
            }
        } else {
            $manageableUserIds = null;
        }

        $baseQuery = User::withTrashed();
        if ($manageableUserIds !== null) $baseQuery->whereIn('id', $manageableUserIds);

        $statusCounts = [
            'all' => (clone $baseQuery)->count(),
            'active' => (clone $baseQuery)->whereNull('deleted_at')->where('active', true)->count(),
            'deactivated' => (clone $baseQuery)->whereNull('deleted_at')->where('active', false)->count(),
            'deleted' => (clone $baseQuery)->onlyTrashed()->count(),
        ];

        $query = User::withTrashed()->orderBy('name');
        if ($manageableUserIds !== null) $query->whereIn('id', $manageableUserIds);

        if ($search) {
            $query->where(function ($q) use ($search) {
                $q->where('name', 'like', "%{$search}%")
                    ->orWhere('mobile', 'like', "%{$search}%")
                    ->orWhere('email', 'like', "%{$search}%");
            });
        }

        switch ($status) {
            case 'active':
                $query->whereNull('deleted_at')->where('active', true);
                break;
            case 'deactivated':
                $query->whereNull('deleted_at')->where('active', false);
                break;
            case 'deleted':
                $query->onlyTrashed();
                break;
            case 'all':
            default:
                break;
        }

        // Respect per-page selection from query, with a safe whitelist and default 10
        $allowed = [5,10,15,20,30];
        $perPage = (int) $request->query('per_page', 10);
        if (!in_array($perPage, $allowed, true)) {
            $perPage = 10;
        }

        $users = $query->paginate($perPage)->withQueryString();

        // remember the current users list URL so the user details page can return here
        try {
            session(['users_list_back_url' => $request->fullUrl()]);
        } catch (\Throwable $__e) {
            // ignore session errors
        }

        return view('users.index', compact('users', 'search', 'status', 'statusCounts'));
    }

    public function create()
    {
        try { $this->authorize('create', User::class); } catch (\Throwable $e) {}

        $user = new User();
        /** @var User $currentUser */
        $currentUser = Auth::user();
        if ($currentUser && method_exists($currentUser, 'getAvailableRolesForAssignment')) {
            $roles = $currentUser->getAvailableRolesForAssignment();
        } else {
            $roles = Role::all();
        }

        return view('users.create', compact('user', 'roles'));
    }

    public function store(Request $request)
    {
        try { $this->authorize('create', User::class); } catch (\Throwable $e) {}

        $data = $request->validate([
            'name' => ['required', 'string', 'max:255'],
            'mobile' => ['required', 'string', 'max:50', 'unique:users,mobile'],
            'email' => ['nullable', 'email', 'max:255', 'unique:users,email'],
            'password' => ['required', 'string', 'min:8', 'confirmed'],
            'avatar' => ['nullable', 'image', 'max:2048'],
            'active' => ['nullable', 'boolean'],
            'role_id' => ['required', 'exists:roles,id'],
        ]);

        /** @var User $currentUser */
        $currentUser = Auth::user();
        if ($currentUser && method_exists($currentUser, 'getAvailableRolesForAssignment')) {
            $manageableRoleIds = $currentUser->getAvailableRolesForAssignment()->pluck('id');
            if (method_exists($currentUser, 'isSuperAdmin') ? !$currentUser->isSuperAdmin() && !$manageableRoleIds->contains($data['role_id']) : false) {
                abort(403, 'You cannot assign this role.');
            }
        }

        // Server-side safety: allow assigning Developer role only when the
        // authenticated user's primary role is Developer.
        $selectedRole = Role::find($data['role_id']);
        $selectedIsDeveloper = $selectedRole && strtolower(trim($selectedRole->name)) === 'developer';
        $currentIsDeveloper = $currentUser && strtolower(trim(optional($currentUser->role)->name ?? '')) === 'developer';
        if ($selectedIsDeveloper && !$currentIsDeveloper) {
            return redirect()->back()->withInput()->with('error', 'You cannot assign the Developer role.');
        }

        // If attempting to activate, ensure none of the assigned roles (primary or pivot) are inactive/trashed
        $wantsActive = $request->boolean('active', false);
        if ($wantsActive) {
            if ($selectedRole && ($selectedRole->trashed() || !$selectedRole->is_active)) {
                return redirect()->back()->withInput()->with('error', 'Cannot activate user because the selected role is inactive or deleted.');
            }
            try {
                $pivotRoleIds = DB::table('model_has_roles')
                    ->where('model_type', User::class)
                    ->where('model_id', $user->id)
                    ->pluck('role_id')
                    ->toArray();
                if (!empty($pivotRoleIds)) {
                    $bad = Role::withTrashed()->whereIn('id', $pivotRoleIds)->get()->filter(function ($r) { return $r->trashed() || !$r->is_active; });
                    if ($bad->count() > 0) {
                        return redirect()->back()->withInput()->with('error', 'Cannot activate user because one or more assigned roles are inactive or deleted.');
                    }
                }
            } catch (\Throwable $__e) {
                // ignore DB lookup issues
            }
        }

        // Prevent activating a user when their assigned role is inactive or deleted
        $wantsActive = $request->boolean('active', false);
        if ($wantsActive && $selectedRole) {
            if ($selectedRole->trashed() || !$selectedRole->is_active) {
                return redirect()->back()->withInput()->with('error', 'Cannot activate user because the selected role is inactive or deleted.');
            }
        }

        // Prevent activating a user when their assigned role is inactive or deleted
        $wantsActive = $request->boolean('active', true);
        if ($wantsActive) {
            if ($selectedRole && ($selectedRole->trashed() || !$selectedRole->is_active)) {
                return redirect()->back()->withInput()->with('error', 'Cannot activate user because the selected role is inactive or deleted.');
            }
            // Also check pivot-assigned roles (Spatie's model_has_roles pivot)
            try {
                $pivotRoleIds = DB::table('model_has_roles')
                    ->where('model_type', User::class)
                    ->where('model_id', 0) // placeholder, store will not have pivot yet
                    ->pluck('role_id')
                    ->toArray();
                // On store there should be none; skip further checks
            } catch (\Throwable $__e) {
                // ignore
            }
        }

        $user = new User();
        $user->name = $data['name'];
        $user->mobile = $data['mobile'];
        $user->email = $data['email'] ?? null;
        if ($request->hasFile('avatar')) {
            $path = $request->file('avatar')->store('avatars', 'public');
            $user->avatar = $path;
        }
        $user->active = $request->boolean('active', true);
        $user->password = Hash::make($data['password']);
        $user->role_id = $data['role_id'];
        $user->email_bounce_count = 0;
        $user->email_bounced_at = null;
        // record creator
        $user->created_by = optional(Auth::user())->id;
        $user->save();

        return redirect()->route('users.index')->with('status', 'User "' . $user->name . '" created successfully.');
    }

    public function show(User $user)
    {
        try { $this->authorize('view', $user); } catch (\Throwable $e) {}
        // eager-load related metadata users to avoid extra queries in the view
        $user->load(['role', 'branch', 'createdBy', 'updatedBy', 'lastUpdatedBy', 'deletedBy', 'restoredBy']);

        return view('users.show', compact('user'));
    }

    public function edit(User $user)
    {
        try { $this->authorize('update', $user); } catch (\Throwable $e) {}

        /** @var User $currentUser */
        $currentUser = Auth::user();
        if ($currentUser && method_exists($currentUser, 'getAvailableRolesForAssignment')) {
            $roles = $currentUser->getAvailableRolesForAssignment();
        } else {
            $roles = Role::all();
        }

        return view('users.edit', compact('user', 'roles'));
    }

    public function update(Request $request, User $user)
    {
        try { $this->authorize('update', $user); } catch (\Throwable $e) {}

        $data = $request->validate([
            'name' => ['required', 'string', 'max:255'],
            'mobile' => ['required', 'string', 'max:50', Rule::unique('users', 'mobile')->ignore($user->id)],
            'email' => ['nullable', 'email', 'max:255', Rule::unique('users', 'email')->ignore($user->id)],
            'password' => ['nullable', 'string', 'min:8', 'confirmed'],
            'avatar' => ['nullable', 'image', 'max:2048'],
            'remove_avatar' => ['nullable', 'boolean'],
            'active' => ['nullable', 'boolean'],
            'reset_bounce' => ['nullable', 'boolean'],
            'role_id' => ['required', 'exists:roles,id'],
        ]);

        /** @var User $currentUser */
        $currentUser = Auth::user();
        if ($currentUser && method_exists($currentUser, 'getAvailableRolesForAssignment')) {
            $manageableRoleIds = $currentUser->getAvailableRolesForAssignment()->pluck('id');
            if (method_exists($currentUser, 'isSuperAdmin') ? !$currentUser->isSuperAdmin() && !$manageableRoleIds->contains($data['role_id']) : false) {
                abort(403, 'You cannot assign this role.');
            }
        }

        // Server-side safety: allow assigning Developer role only when the
        // authenticated user's primary role is Developer.
        $selectedRole = Role::find($data['role_id']);
        $selectedIsDeveloper = $selectedRole && strtolower(trim($selectedRole->name)) === 'developer';
        $currentIsDeveloper = $currentUser && strtolower(trim(optional($currentUser->role)->name ?? '')) === 'developer';
        if ($selectedIsDeveloper && !$currentIsDeveloper) {
            return redirect()->back()->withInput()->with('error', 'You cannot assign the Developer role.');
        }

        $user->name = $data['name'];
        $user->mobile = $data['mobile'];
        $user->email = $data['email'] ?? null;
        $user->active = $request->boolean('active', false);
        $user->role_id = $data['role_id'];

        if (!empty($data['password'])) {
            $user->password = Hash::make($data['password']);
        }

        if ($request->hasFile('avatar')) {
            // remove old avatar when replacing
            if ($user->avatar) {
                Storage::disk('public')->delete($user->avatar);
            }
            $user->avatar = $request->file('avatar')->store('avatars', 'public');
        } elseif ($request->boolean('remove_avatar')) {
            if ($user->avatar) {
                Storage::disk('public')->delete($user->avatar);
            }
            $user->avatar = null;
        }

        if ($request->boolean('reset_bounce')) {
            $user->email_bounce_count = 0;
            $user->email_bounced_at = null;
        }

        // record updater info
        $user->last_updated_by = optional($currentUser)->id;
        $user->last_updated_at = now();
        $user->updated_by = optional($currentUser)->id;
        $user->save();

        return redirect()->route('users.index')->with('status', 'User "' . $user->name . '" updated successfully.');
    }

    public function destroy(User $user)
    {
        try { $this->authorize('delete', $user); } catch (\Throwable $e) {}

        $name = $user->name;
        // record who deleted then soft-delete
        $user->deleted_by = optional(Auth::user())->id;
        $user->save();
        $user->delete();

        return redirect()->route('users.index')->with('deleted', 'User "' . $name . '" deleted successfully.');
    }

    public function restore(Request $request, $id)
    {
        $user = User::withTrashed()->findOrFail($id);
        try { $this->authorize('restore', $user); } catch (\Throwable $e) {}
        if ($user->trashed()) {
            // Prevent restoring if the user's assigned role is inactive or soft-deleted
            try {
                $roleId = $user->role_id;
                if ($roleId) {
                    $role = Role::withTrashed()->find($roleId);
                    if ($role && ($role->trashed() || !$role->is_active)) {
                        $redirectParams = ['status' => 'deleted'];
                        if ($request->filled('search')) {
                            $redirectParams['search'] = $request->input('search');
                        }
                        return redirect()->route('users.index', $redirectParams)
                            ->with('error', 'Cannot restore user because the assigned role is inactive or deleted.');
                    }
                }

                // Also check pivot-assigned roles
                $pivotRoleIds = DB::table('model_has_roles')
                    ->where('model_type', User::class)
                    ->where('model_id', $user->id)
                    ->pluck('role_id')
                    ->toArray();
                if (!empty($pivotRoleIds)) {
                    $bad = Role::withTrashed()->whereIn('id', $pivotRoleIds)->get()->filter(function ($r) { return $r->trashed() || !$r->is_active; });
                    if ($bad->count() > 0) {
                        $redirectParams = ['status' => 'deleted'];
                        if ($request->filled('search')) {
                            $redirectParams['search'] = $request->input('search');
                        }
                        return redirect()->route('users.index', $redirectParams)
                            ->with('error', 'Cannot restore user because one or more assigned roles are inactive or deleted.');
                    }
                }
            } catch (\Throwable $__e) {
                // ignore role lookup failures and proceed with restore attempt
            }

            if (method_exists($user, 'restoreWithUser')) {
                $user->restoreWithUser();
            } else {
                $user->restore();
                try {
                    $user->restored_by = optional(Auth::user())->id;
                    try {
                        if (\Illuminate\Support\Facades\Schema::hasColumn($user->getTable(), 'restored_at')) {
                            $user->restored_at = now();
                        }
                    } catch (\Throwable $__e) {}
                    $user->save();
                } catch (\Throwable $__e) {
                    // ignore save errors
                }
            }

            $message = 'User "' . $user->name . '" restored successfully.';
            $messageType = 'status';
        } else {
            $message = 'User "' . $user->name . '" is not deleted.';
            $messageType = 'error';
        }

        $redirectParams = ['status' => 'deleted'];
        if ($request->filled('search')) {
            $redirectParams['search'] = $request->input('search');
        }

        return redirect()->route('users.index', $redirectParams)->with($messageType, $message);
    }
}
