<?php

namespace App\Http\Controllers;

use App\Models\AuditLog;
use App\Models\User;
use App\Models\Role;
use App\Models\Branch;
use Illuminate\View\View;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\File;
use Illuminate\Support\Facades\Hash;
use Illuminate\Validation\Rule;
use Illuminate\Foundation\Auth\Access\AuthorizesRequests;
use CloudinaryLabs\CloudinaryLaravel\Facades\Cloudinary;

class UserController extends Controller
{
    use AuthorizesRequests;

    private function cloudinaryIsConfigured(): bool
    {
        $disk = (array) config('filesystems.disks.cloudinary', []);
        $url = isset($disk['url']) ? (string) $disk['url'] : '';

        if ($url !== '') {
            // Expected: cloudinary://API_KEY:API_SECRET@CLOUD_NAME
            return (bool) preg_match('/^cloudinary:\/\/[^:\/]+:[^@\/]++@[^\/?#]+/i', $url);
        }

        return !empty($disk['cloud']) && !empty($disk['key']) && !empty($disk['secret']);
    }

    private function assertCloudinaryConfigured(): void
    {
        $disk = (array) config('filesystems.disks.cloudinary', []);
        $hasUrl = !empty($disk['url']);
        $hasKeys = !empty($disk['cloud']) && !empty($disk['key']) && !empty($disk['secret']);

        if ($hasUrl) {
            $url = (string) $disk['url'];
            // Expected: cloudinary://API_KEY:API_SECRET@CLOUD_NAME
            if (!preg_match('/^cloudinary:\/\/[^:\/]+:[^@\/]++@[^\/?#]+/i', $url)) {
                abort(500, 'Cloudinary is misconfigured. CLOUDINARY_URL must look like cloudinary://API_KEY:API_SECRET@CLOUD_NAME.');
            }
            return;
        }

        if (! $hasKeys) {
            abort(500, 'Cloudinary is not configured. Set CLOUDINARY_URL or CLOUDINARY_CLOUD_NAME + CLOUDINARY_API_KEY + CLOUDINARY_API_SECRET.');
        }
    }

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
            'role' => function($q) { $q->withTrashed(); },
            'branch' => function($q) { $q->withTrashed(); }
        ])->withTrashed();

        // Enforce hierarchy: users can only see users with lower hierarchy (higher priority number)
        if (!$currentUser->isSuperAdmin()) {
            $effectiveRole = $currentUser->effectiveRole();
            $myPriority = $effectiveRole ? ($effectiveRole->priority ?? 999) : 999;

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
            $effectiveRole = $currentUser->effectiveRole();
            $myPriority = $effectiveRole ? ($effectiveRole->priority ?? 999) : 999;
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

        $user->load(['role' => function($q) {
            $q->withTrashed();
        }, 'branch' => function($q) {
            $q->withTrashed();
        }]);

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

        // Enforce avatar as file-only: ignore any non-file "avatar" input (e.g. base64 strings).
        if (! $request->hasFile('avatar') && $request->has('avatar')) {
            $request->request->remove('avatar');
        }

        $avatarFile = $request->file('avatar');

        $rules = [
            'name'      => ['required', 'string', 'max:255'],
            'mobile'    => ['required', 'string', 'max:50', 'unique:users,mobile'],
            'email'     => ['required', 'email', 'max:255', 'unique:users,email'],
            'password'  => ['required', 'string', 'min:8', 'confirmed'],
            'role_id'   => ['required', 'exists:roles,id'],
            'branch_id' => ['nullable', 'exists:branches,id'],
            'active'    => ['nullable', 'boolean'],
        ];

        // Validate avatar only when a file is present.
        if ($avatarFile) {
            $rules['avatar'] = ['image', 'max:2048'];
        }

        $data = $request->validate($rules);

        $hasAvatarUpload = $avatarFile instanceof UploadedFile && $avatarFile->isValid();

        // Prevent creation of developer-like users (null role_id and null branch_id)
        // Only the seeder or direct database operation should create the developer user
        if ($data['role_id'] === null) {
            return back()->withInput()->with('error', 'Cannot create user without a role. Developer user must be created via seeder only.');
        }

        $role = Role::withTrashed()->find($data['role_id']);

        // Prevent assigning a role that belongs to a different branch than provided
        $requestedBranchId = $request->input('branch_id') ?? null;
        $currentUser = Auth::user();

        if (!$currentUser) {
            abort(403);
        }

        if ($requestedBranchId !== null && $role instanceof Role) {
            if (!$currentUser->isSuperAdmin() && $role->branch_id !== null && $role->branch_id != $requestedBranchId) {
                return back()->withInput()->with('error', 'Selected role belongs to a different branch.');
            }
        }

        // Strict validation: user branch must match role branch (unless role has no branch)
        if (!$currentUser->isSuperAdmin() && $role instanceof Role && $role->branch_id !== null && $requestedBranchId !== null) {
            if ($role->branch_id != $requestedBranchId) {
                return back()->withInput()->with('error', 'User branch must match role branch.');
            }
        }

        // Use transaction with role status re-check to prevent race conditions
        return DB::transaction(function () use ($request, $data, $role, $actorIdInt, $hasAvatarUpload, $avatarFile) {
            // Re-check role status inside transaction
            $role = Role::withTrashed()->lockForUpdate()->find($data['role_id']);

            if ($request->boolean('active', true)) {
                if ($role instanceof Role && ($role->trashed() || ! $this->roleIsActive($role))) {
                    return back()->withInput()->with('error', 'Cannot activate user because the selected role is inactive or deleted.');
                }
                }

            $user = new User();
            // Do not mass-assign the UploadedFile object; Cloudinary upload is handled below.
            $user->fill(collect($data)->except(['password', 'avatar'])->all());
            $user->password   = Hash::make($data['password']);
            $user->active     = $request->boolean('active', true);
            $user->created_by = $actorIdInt;

            $user->branch_id = $request->input('branch_id') ?? $role->branch_id ?? null;
            $user->save();

            // Cloudinary avatar upload (persistent across Render redeploys).
            // Stores Cloudinary `secure_url` in DB; no local storage/app or public disk usage.
            if ($hasAvatarUpload) {
                $this->assertCloudinaryConfigured();
                $publicId = 'ioms/avatars/user_'.$user->id;

                /** @var array<string,mixed> $result */
                $result = Cloudinary::uploadApi()->upload($avatarFile->getRealPath(), [
                    'folder' => 'ioms/avatars',
                    'public_id' => 'user_'.$user->id,
                    'overwrite' => true,
                    'resource_type' => 'image',
                ]);

                $user->avatar = null; // legacy local-path column (no longer used)
                $user->avatar_url = $result['secure_url'] ?? null;
                $user->avatar_public_id = $result['public_id'] ?? $publicId;
                $user->save();
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

        // Enforce avatar as file-only: ignore any non-file "avatar" input (e.g. base64 strings).
        if (! $request->hasFile('avatar') && $request->has('avatar')) {
            $request->request->remove('avatar');
        }

        $avatarFile = $request->file('avatar');

        $rules = [
            'name'     => ['required', 'string', 'max:255'],
            'mobile'   => ['required', Rule::unique('users')->ignore($user->id)],
            'email'    => ['required', 'email', Rule::unique('users', 'email')->ignore($user->id)],
            'password' => ['nullable', 'confirmed', 'min:8'],
            'remove_avatar' => ['nullable', 'boolean'],
            'role_id'  => ['required', 'exists:roles,id'],
            'branch_id'=> ['nullable', 'exists:branches,id'],
            'active'   => ['nullable', 'boolean'],
        ];

        // Validate avatar only when a file is present.
        if ($avatarFile) {
            $rules['avatar'] = ['image', 'max:2048'];
        }

        $data = $request->validate($rules);

        $hasAvatarUpload = $avatarFile instanceof UploadedFile && $avatarFile->isValid();

        $role = Role::withTrashed()->find($data['role_id']);

        // Prevent assigning a role that belongs to a different branch than provided
        $requestedBranchId = $request->input('branch_id') ?? null;
        $currentUser = Auth::user();

        if (!$currentUser) {
            abort(403);
        }

        if ($requestedBranchId !== null && $role instanceof Role) {
            if (!$currentUser->isSuperAdmin() && $role->branch_id !== null && $role->branch_id != $requestedBranchId) {
                return back()->withInput()->with('error', 'Selected role belongs to a different branch.');
            }
        }

        // Strict validation: user branch must match role branch (unless role has no branch)
        if (!$currentUser->isSuperAdmin() && $role instanceof Role && $role->branch_id !== null && $requestedBranchId !== null) {
            if ($role->branch_id != $requestedBranchId) {
                return back()->withInput()->with('error', 'User branch must match role branch.');
            }
        }

        $roleName = strtolower(trim($this->roleName($role) ?? ''));
        $isDevRole = $roleName === 'developer';

        $currentRoleName = strtolower(trim($this->roleName($currentUser->role) ?? ''));

        if ($isDevRole && $currentRoleName !== 'developer') {
            return back()->withInput()->with('error', 'You cannot assign Developer role.');
        }

        // Use transaction with role status re-check
        $oldValues = $this->sanitizeUserAuditValues($user->toArray());

        return DB::transaction(function () use ($request, $data, $user, $role, $actorIdInt, $oldValues, $hasAvatarUpload, $avatarFile) {
            // Re-check role status inside transaction
            $role = Role::withTrashed()->lockForUpdate()->find($data['role_id']);

            // When activating a user, ensure role is active
            if ($request->boolean('active', false)) {
                if ($role instanceof Role && ($role->trashed() || ! $this->roleIsActive($role))) {
                    return back()->withInput()->with('error', 'Cannot activate user because the selected role is inactive or deleted.');
                }

                // Also block activation if any pivot-assigned roles are inactive/trashed
                $pivotRoleIds = DB::table('model_has_roles')
                    ->where('model_type', User::class)
                    ->where('model_id', $user->id)
                    ->pluck('role_id')
                    ->all();

                if (is_array($pivotRoleIds) && count($pivotRoleIds) > 0) {
                    /** @var \Illuminate\Support\Collection<int, Role> $pivotRoles */
                    $pivotRoles = Role::withTrashed()->whereIn('id', $pivotRoleIds)->get();
                    foreach ($pivotRoles as $pivotRole) {
                        if ($pivotRole->trashed() || ! $this->roleIsActive($pivotRole)) {
                            return back()->withInput()->with('error', 'Cannot activate user because one or more assigned roles are inactive or deleted.');
                        }
                    }
                }
                }

            // Only fill fields that should be mass-assigned (exclude password)
            $user->fill([
                'name' => $data['name'],
                'mobile' => $data['mobile'],
                'email' => $data['email'],
                'role_id' => $data['role_id'],
            ]);

            $user->active    = $request->boolean('active', false);
            $user->branch_id = $request->input('branch_id') ?? $role->branch_id ?? $user->branch_id;

            // Only update password if provided
            if (! empty($data['password'])) {
                $user->password = Hash::make($data['password']);
            }

            $user->updated_by = $actorIdInt;
            $user->save();

            // Cloudinary avatar replacement/removal (persistent storage).
            if ($hasAvatarUpload) {
                $this->assertCloudinaryConfigured();
                // Overwrite the user's Cloudinary asset (stable public_id per user).
                // If an older/different public_id exists, delete it to avoid orphaned assets.
                $targetPublicId = 'ioms/avatars/user_'.$user->id;
                if ($user->avatar_public_id && $user->avatar_public_id !== $targetPublicId) {
                    Cloudinary::uploadApi()->destroy($user->avatar_public_id, ['resource_type' => 'image']);
                }

                /** @var array<string,mixed> $result */
                $result = Cloudinary::uploadApi()->upload($avatarFile->getRealPath(), [
                    'folder' => 'ioms/avatars',
                    'public_id' => 'user_'.$user->id,
                    'overwrite' => true,
                    'resource_type' => 'image',
                ]);

                $user->avatar = null;
                $user->avatar_url = $result['secure_url'] ?? $user->avatar_url;
                $user->avatar_public_id = $result['public_id'] ?? $targetPublicId;
                $user->save();
            }

            if ($request->boolean('remove_avatar', false) && $user->avatar_public_id) {
                $this->assertCloudinaryConfigured();
                Cloudinary::uploadApi()->destroy($user->avatar_public_id, ['resource_type' => 'image']);
                $user->avatar = null;
                $user->avatar_url = null;
                $user->avatar_public_id = null;
                $user->save();
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

        // Enforce avatar as file-only: ignore any non-file "avatar" input (e.g. base64 strings).
        // This also prevents huge base64 strings from being flashed into session on validation errors.
        if (! $request->hasFile('avatar') && $request->has('avatar')) {
            $request->request->remove('avatar');
        }

        $avatarFile = $request->file('avatar');

        $rules = [
            'name'     => ['required', 'string', 'max:255'],
            // keep backward compatible: allow blank to preserve current value
            'email'    => ['nullable', 'email', Rule::unique('users', 'email')->ignore($user->id)],
            'password' => ['nullable', 'confirmed', 'min:8'],
            'remove_avatar' => ['nullable', 'boolean'],
        ];

        // Validate avatar only when a file is present.
        if ($avatarFile) {
            $rules['avatar'] = ['image', 'max:2048'];
        }

        $data = $request->validate($rules);

        $hasAvatarUpload = $avatarFile instanceof UploadedFile && $avatarFile->isValid();

        $oldValues = $this->sanitizeUserAuditValues($user->toArray());

        $user->name = $data['name'];
        $user->email = $data['email'] ?? $user->email;

        if (!empty($data['password'])) {
            $user->password = Hash::make($data['password']);
        }

        // Cloudinary avatar upload / replacement (persistent storage).
        // Only execute when a file is actually uploaded.
        if ($hasAvatarUpload) {
            if ($this->cloudinaryIsConfigured()) {
                try {
                    $targetPublicId = 'ioms/avatars/user_'.$user->id;
                    if ($user->avatar_public_id && $user->avatar_public_id !== $targetPublicId) {
                        Cloudinary::uploadApi()->destroy($user->avatar_public_id, ['resource_type' => 'image']);
                    }

                    /** @var array<string,mixed> $result */
                    $result = Cloudinary::uploadApi()->upload($avatarFile->getRealPath(), [
                        'folder' => 'ioms/avatars',
                        'public_id' => 'user_'.$user->id,
                        'overwrite' => true,
                        'resource_type' => 'image',
                    ]);

                    $user->avatar = null;
                    $user->avatar_url = $result['secure_url'] ?? $user->avatar_url;
                    $user->avatar_public_id = $result['public_id'] ?? $targetPublicId;
                } catch (\Throwable $e) {
                    return back()
                        ->withInput($request->except(['password', 'password_confirmation']))
                        ->with('error', 'Profile photo upload failed (Cloudinary not configured or unreachable).');
                }
            } else {
                // Local fallback (useful for local dev when Cloudinary env vars are not set).
                // Stored under public/storage/avatars which is already present in this repo.
                $dir = public_path('storage/avatars');
                try {
                    if (!File::exists($dir)) {
                        File::makeDirectory($dir, 0755, true);
                    }

                    $ext = (string) ($avatarFile->guessExtension() ?: $avatarFile->getClientOriginalExtension() ?: 'jpg');
                    $ext = strtolower(preg_replace('/[^a-z0-9]+/i', '', $ext) ?: 'jpg');
                    $filename = 'user_'.$user->id.'.'.$ext;

                    $avatarFile->move($dir, $filename);

                    $user->avatar = null;
                    $user->avatar_public_id = null;
                    $user->avatar_url = asset('storage/avatars/'.$filename);
                } catch (\Throwable $e) {
                    return back()
                        ->withInput($request->except(['password', 'password_confirmation']))
                        ->with('error', 'Profile photo upload failed.');
                }
            }
        }

        // Cloudinary avatar removal.
        if ($request->boolean('remove_avatar', false) && $user->avatar_public_id) {
            // Removal should delete the existing Cloudinary asset.
            if ($this->cloudinaryIsConfigured()) {
                try {
                    Cloudinary::uploadApi()->destroy($user->avatar_public_id, ['resource_type' => 'image']);
                } catch (\Throwable $e) {
                    // ignore (we still clear DB pointers below)
                }
            }
            $user->avatar = null;
            $user->avatar_url = null;
            $user->avatar_public_id = null;
        }

        // Local avatar removal fallback (when Cloudinary isn't used).
        if ($request->boolean('remove_avatar', false) && !$user->avatar_public_id && $user->avatar_url) {
            try {
                $path = parse_url((string) $user->avatar_url, PHP_URL_PATH);
                if (is_string($path) && str_contains($path, '/storage/avatars/')) {
                    $basename = basename($path);
                    $full = public_path('storage/avatars/'.$basename);
                    if (is_string($basename) && $basename !== '' && File::exists($full)) {
                        File::delete($full);
                    }
                }
            } catch (\Throwable $e) {
                // ignore
            }
            $user->avatar = null;
            $user->avatar_url = null;
            $user->avatar_public_id = null;
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

        /** @var \App\Models\Role|null $role */
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
                        /** @var \App\Models\Branch|null $branch */
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
        $user = User::withTrashed()->with(['role' => function($q) { $q->withTrashed(); }, 'branch' => function($q) { $q->withTrashed(); }])->findOrFail($id);

        $this->authorize('view', $user);

        $dependencies = [];
        $canProceed = true;

        // Check if user is deleted and needs restore
        if ($user->trashed()) {
            // Check role status
                if ($user->role_id) {
                /** @var Role|null $role */
                $role = $user->role;
                if ($role instanceof Role && $role->trashed()) {
                    $canProceed = false;
                    $dependencies[] = [
                        'type' => 'deleted_role',
                        'message' => 'Assigned role is deleted',
                        'details' => "Role '{$role->name}' must be restored first"
                    ];
                } elseif ($role && !($role->is_active ?? true)) {
                    $canProceed = false;
                    $dependencies[] = [
                        'type' => 'inactive_role',
                        'message' => 'Assigned role is inactive',
                        'details' => "Role '{$role->name}' must be activated first"
                    ];
                }
            }

            // Check branch status
            if ($user->branch_id) {
                /** @var Branch|null $branch */
                $branch = $user->branch;
                if ($branch instanceof Branch && $branch->trashed()) {
                    $canProceed = false;
                    $dependencies[] = [
                        'type' => 'deleted_branch',
                        'message' => 'Assigned branch is deleted',
                        'details' => "Branch '{$branch->name}' must be restored first"
                    ];
                }
            }
        }

        // Check if user is being activated
        if (!$user->trashed() && !$user->active) {
            // Check role status
            if ($user->role_id) {
                /** @var Role|null $role */
                $role = $user->role;
                if ($role instanceof Role && !($role->is_active ?? true)) {
                    $canProceed = false;
                    $dependencies[] = [
                        'type' => 'inactive_role',
                        'message' => 'Assigned role is inactive',
                        'details' => "Role '{$role->name}' must be activated first"
                    ];
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
