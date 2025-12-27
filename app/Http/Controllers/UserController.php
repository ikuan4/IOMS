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
use App\Http\Controllers\Controller;
use Illuminate\Foundation\Auth\Access\AuthorizesRequests;
use Illuminate\Support\Facades\Log;

class UserController extends Controller
{
    use AuthorizesRequests;
    public function index(Request $requestParam): View
    {
        // assign to local variable explicitly so static analyzers recognize it as initialized
        $request = $requestParam;
        try { $this->authorize('viewAny', User::class); } catch (\Throwable $e) {}

        $search = (string) $request->query('search');
        $status = $request->query('status', 'all');
        $perPage = (int)$request->query('per_page', 10);

        $query = User::with(['role','branch'])->withTrashed();
        if ($search !== '') {
            $query->where(function($q) use ($search) {
                $q->where('name', 'like', "%$search%")
                  ->orWhere('mobile', 'like', "%$search%")
                  ->orWhere('email', 'like', "%$search%");
            });
        }

        if ($status === 'active') {
            $query->where('active', true)->whereNull('deleted_at');
        } elseif ($status === 'deactivated') {
            $query->where('active', false)->whereNull('deleted_at');
        } elseif ($status === 'deleted') {
            $query = User::onlyTrashed()->with(['role','branch']);
            if ($search !== '') {
                $query->where(function($q) use ($search) {
                    $q->where('name', 'like', "%$search%")
                      ->orWhere('mobile', 'like', "%$search%")
                      ->orWhere('email', 'like', "%$search%");
                });
            }
        }

        $users = $query->paginate($perPage)->withQueryString();

        // Provide simple status counts used by the view
        $statusCounts = [
            'all' => User::count(),
            'active' => User::whereNull('deleted_at')->where('active', true)->count(),
            'deactivated' => User::whereNull('deleted_at')->where('active', false)->count(),
            'deleted' => User::onlyTrashed()->count(),
        ];

        return view('users.index', compact('users', 'search', 'status', 'statusCounts'));
    }

    public function create(): View
    {
        try { $this->authorize('create', User::class); } catch (\Throwable $e) {}

        /** @var User $currentUser */
        $currentUser = Auth::user();
        if ($currentUser && method_exists($currentUser, 'getAvailableRolesForAssignment')) {
            $roles = $currentUser->getAvailableRolesForAssignment();
        } else {
            $roles = Role::all();
        }

        $user = new User();
        return view('users.create', compact('roles', 'user'));
    }

     /**
      * Store a newly created user.
      *
      * @param \Illuminate\Http\Request $requestParam
      * @return \Illuminate\Http\RedirectResponse
      */
     public function store(Request $requestParam): RedirectResponse
    {
        // assign to local variable explicitly so static analyzers recognize it as initialized
        $request = $requestParam;
        try { $this->authorize('create', User::class); } catch (\Throwable $e) {}

        $data = $request->validate([
            'name' => ['required', 'string', 'max:255'],
            'mobile' => ['required', 'string', 'max:50', 'unique:users,mobile'],
            'email' => ['nullable', 'email', 'max:255', 'unique:users,email'],
            'password' => ['required', 'string', 'min:8', 'confirmed'],
            'avatar' => ['nullable', 'image', 'max:2048'],
            'role_id' => ['required', 'exists:roles,id'],
            'branch_id' => ['nullable', 'exists:branches,id'],
            'active' => ['nullable', 'boolean'],
        ]);

        /** @var Role|null $selectedRole */
        $selectedRole = Role::find($data['role_id']);

        // Verify branch alignment and active states similar to update()
        if (! (Auth::user() && method_exists(Auth::user(), 'isSuperAdmin') && Auth::user()->isSuperAdmin())) {
            if ($selectedRole && $request->filled('branch_id') && (int)$request->input('branch_id') !== (int)($selectedRole->branch_id ?? 0)) {
                return redirect()->back()->withInput()->with('error', 'Selected role belongs to a different branch.');
            }
        }

        $wantsActive = $request->boolean('active', true);
        if ($wantsActive) {
            try {
                if ($selectedRole && ($selectedRole->trashed() || !$selectedRole->is_active)) {
                    return redirect()->back()->withInput()->with('error', 'Cannot activate user because the selected role is inactive or deleted.');
                }
                $branchIdToCheck = $request->input('branch_id') ?? ($selectedRole->branch_id ?? null);
                if ($branchIdToCheck) {
                    /** @var Branch|null $branch */
                    $branch = Branch::withTrashed()->find($branchIdToCheck);
                    if ($branch && $branch->trashed()) {
                        return redirect()->back()->withInput()->with('error', 'Cannot activate user because the assigned branch is deleted.');
                    }
                }
            } catch (\Throwable $__e) {}
        }

        $user = new User();
        $user->name = $data['name'];
        $user->mobile = $data['mobile'];
        $user->email = $data['email'] ?? null;
        if ($request->hasFile('avatar')) {
            $path = $request->file('avatar')->store('avatars', 'public');
            if (is_string($path)) {
                $user->avatar = $path;
            }
        }
        $user->active = $request->boolean('active', true);
        $user->password = Hash::make($data['password']);
        $user->role_id = $data['role_id'];
        if ($request->filled('branch_id')) {
            $user->branch_id = $request->input('branch_id');
        } else {
            $user->branch_id = $selectedRole->branch_id ?? null;
        }
        $user->email_bounce_count = 0;
        $user->email_bounced_at = null;
        $user->created_by = optional(Auth::user())->id;
        $user->save();

        return redirect()->route('users.index')->with('status', 'User "' . $user->name . '" created successfully.');
    }

    /**
    * Display the specified user.
    *
    * @param User $userParam
    * @return \Illuminate\View\View
     */
    public function show(User $userParam): View
    {
        // assign to local variable explicitly so static analyzers recognize it as initialized
        /** @var User $user */
        $user = $userParam;
        try { $this->authorize('view', $user); } catch (\Throwable $e) {}
        // eager-load related metadata users to avoid extra queries in the view
        $user->load(['role', 'branch', 'createdBy', 'updatedBy', 'lastUpdatedBy', 'deletedBy', 'restoredBy']);

        return view('users.show', compact('user'));
    }

    /**
    * Show the form for editing the specified user.
    *
    * @param User $userParam
    * @return \Illuminate\View\View
     */
    public function edit(User $userParam): View
    {
        // assign to local variable explicitly so static analyzers recognize it as initialized
        /** @var User $user */
        $user = $userParam;
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

    /**
      * Update the specified user.
      *
      * @param \Illuminate\Http\Request $requestParam
    * @param User $userParam
      * @return \Illuminate\Http\RedirectResponse
      */
     public function update(Request $requestParam, User $userParam): RedirectResponse
    {
        // assign to local variable explicitly so static analyzers recognize it as initialized
        $request = $requestParam;
        // assign route-bound param to local variable so static analyzers see it initialized
        /** @var User $user */
        $user = $userParam;
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
            'branch_id' => ['nullable', 'exists:branches,id'],
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
        /** @var Role|null $selectedRole */
        $selectedRole = Role::find($data['role_id']);
        $selectedIsDeveloper = $selectedRole && strtolower(trim($selectedRole->name)) === 'developer';
        $currentIsDeveloper = $currentUser && strtolower(trim(optional($currentUser->role)->name ?? '')) === 'developer';
        if ($selectedIsDeveloper && !$currentIsDeveloper) {
            return redirect()->back()->withInput()->with('error', 'You cannot assign the Developer role.');
        }

        // Enforce strict branch-role alignment: block when provided branch_id
        // does not match the selected role's branch. Developer users bypass.
        if (!($currentUser && $currentUser->isSuperAdmin())) {
            if ($selectedRole && $request->filled('branch_id') && (int)$request->input('branch_id') !== (int)($selectedRole->branch_id ?? 0)) {
                return redirect()->back()->withInput()->with('error', 'Selected role belongs to a different branch.');
            }
        }

        // Prevent activating the user when their assigned role(s) are inactive or soft-deleted
        $wantsActive = $request->boolean('active', false);
        if ($wantsActive) {
            try {
                if ($selectedRole && ($selectedRole->trashed() || !$selectedRole->is_active)) {
                    return redirect()->back()->withInput()->with('error', 'Cannot activate user because the selected role is inactive or deleted.');
                }

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
                // Also verify branch is not soft-deleted
                $branchIdToCheck = $request->input('branch_id') ?? ($selectedRole->branch_id ?? $user->branch_id);
                if ($branchIdToCheck) {
                    /** @var Branch|null $branch */
                    $branch = Branch::withTrashed()->find($branchIdToCheck);
                    if ($branch && $branch->trashed()) {
                        return redirect()->back()->withInput()->with('error', 'Cannot activate user because the assigned branch is deleted.');
                    }
                }
            } catch (\Throwable $__e) {
                // ignore lookup issues
            }
        }

        $user->name = $data['name'];
        $user->mobile = $data['mobile'];
        $user->email = $data['email'] ?? null;
        $user->active = $request->boolean('active', false);
        $user->role_id = $data['role_id'];

        // Ensure user's branch_id aligns with selected role (prefer explicit branch_id)
        if ($request->filled('branch_id')) {
            $user->branch_id = $request->input('branch_id');
        } else {
            $user->branch_id = $selectedRole->branch_id ?? $user->branch_id;
        }

        if (!empty($data['password'])) {
            $user->password = Hash::make($data['password']);
        }

        if ($request->hasFile('avatar')) {
            // remove old avatar when replacing
            if ($user->avatar) {
                Storage::disk('public')->delete($user->avatar);
            }
            $path = $request->file('avatar')->store('avatars', 'public');
            if (is_string($path)) {
                $user->avatar = $path;
            }
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

    public function destroy(User $userParam): RedirectResponse
    {
        // assign to local variable explicitly so static analyzers recognize it as initialized
        /** @var User $user */
        $user = $userParam;
        try { $this->authorize('delete', $user); } catch (\Throwable $e) {}

        $name = $user->name;
        // record who deleted then soft-delete
        $user->deleted_by = optional(Auth::user())->id;
        $user->save();
        $user->delete();

        return redirect()->route('users.index')->with('deleted', 'User "' . $name . '" deleted successfully.');
    }

    public function restore(Request $requestParam, int $id): RedirectResponse
    {
        // assign to local variable explicitly so static analyzers recognize it as initialized
        $request = $requestParam;
        $user = User::withTrashed()->findOrFail($id);
        try { $this->authorize('restore', $user); } catch (\Throwable $e) {}

        $actor = Auth::user();
        $isDev = $actor && method_exists($actor, 'isSuperAdmin') && $actor->isSuperAdmin();

        // If not deleted, nothing to do
        if (! $user->trashed()) {
            return redirect()->route('users.index', ['status' => 'deleted'])->with('error', 'User "' . $user->name . '" is not deleted.');
        }

        // Check branch
        try {
            $branch = Branch::withTrashed()->find($user->branch_id);
            if ($branch && $branch->trashed() && ! $isDev) {
                return redirect()->route('users.index', ['status' => 'deleted'])
                    ->with('error', 'Cannot restore user because the assigned role is inactive or deleted.');
            }
        } catch (\Throwable $__e) {
            // ignore lookup errors
        }

        // Check primary role
        try {
            if ($user->role_id) {
                $role = Role::withTrashed()->find($user->role_id);
                if ($role && ($role->trashed() || ! $role->is_active) && ! $isDev) {
                    $redirectParams = ['status' => 'deleted'];
                    if ($request->filled('search')) {
                        $redirectParams['search'] = $request->input('search');
                    }
                    return redirect()->route('users.index', $redirectParams)
                        ->with('error', 'Cannot restore user because the assigned role is inactive or deleted.');
                }
            }
        } catch (\Throwable $__e) {
            // ignore
        }

        // Check pivot roles
        if (! $isDev) {
            try {
                $pivotRoleIds = DB::table('model_has_roles')
                    ->where('model_type', User::class)
                    ->where('model_id', $user->id)
                    ->pluck('role_id')
                    ->toArray();
                if (! empty($pivotRoleIds)) {
                    $bad = Role::withTrashed()->whereIn('id', $pivotRoleIds)->get()->filter(function ($r) { return $r->trashed() || ! $r->is_active; });
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
                // ignore
            }
        }

        // Perform restore
        if (method_exists($user, 'restoreWithUser')) {
            $user->restoreWithUser();
        } else {
            try {
                $user->restore();
            } catch (\Throwable $__e) {
                // ignore
            }
            try {
                $fresh = $user->fresh();
                if ($fresh && $fresh->trashed()) {
                    DB::table($user->getTable())->where('id', $user->id)->update(['deleted_at' => null]);
                }
                $user->restored_by = optional($actor)->id;
                if (\Illuminate\Support\Facades\Schema::hasColumn($user->getTable(), 'restored_at')) {
                    $user->restored_at = now();
                }
                $user->save();
            } catch (\Throwable $__e) {
                // ignore
            }
        }

        // Ensure DB-level fields
        try {
            DB::table($user->getTable())->where('id', $user->id)->update([
                'deleted_at' => null,
                'restored_by' => optional($actor)->id,
                'restored_at' => now(),
            ]);
        } catch (\Throwable $__e) {
            // ignore
        }

        return redirect()->route('users.index', ['status' => 'deleted'])->with('status', 'User "' . $user->name . '" restored successfully.');
    }
}
