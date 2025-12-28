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
    private function roleName(?Role $role): ?string
    {
        return $role->name ?? $role->role_name ?? null;
    }

    private function roleIsActive(?Role $role): bool
    {
        if (! $role) {
            return false;
        }

        if (property_exists($role, 'is_active')) {
            return (bool) $role->is_active;
        }

        if (property_exists($role, 'active')) {
            return (bool) $role->active;
        }

        // default safe assumption
        return true;
    }

    /* -------------------------------------------------------------
     | Index
     |-------------------------------------------------------------*/
    public function index(Request $request): View
    {
        try { $this->authorize('viewAny', User::class); } catch (\Throwable $e) {}

        $search  = (string) $request->query('search');
        $status  = $request->query('status', 'all');
        $perPage = (int) $request->query('per_page', 10);

        $query = User::with(['role', 'branch'])->withTrashed();

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

        $statusCounts = [
            'all'         => User::count(),
            'active'      => User::whereNull('deleted_at')->where('active', true)->count(),
            'deactivated' => User::whereNull('deleted_at')->where('active', false)->count(),
            'deleted'     => User::onlyTrashed()->count(),
        ];

        return view('users.index', compact('users', 'search', 'status', 'statusCounts'));
    }

    /* -------------------------------------------------------------
     | Create
     |-------------------------------------------------------------*/
    public function create(): View
    {
        try { $this->authorize('create', User::class); } catch (\Throwable $e) {}

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
        try { $this->authorize('create', User::class); } catch (\Throwable $e) {}

        $data = $request->validate([
            'name'      => ['required', 'string', 'max:255'],
            'mobile'    => ['required', 'string', 'max:50', 'unique:users,mobile'],
            'email'     => ['nullable', 'email', 'max:255', 'unique:users,email'],
            'password'  => ['required', 'string', 'min:8', 'confirmed'],
            'avatar'    => ['nullable', 'image', 'max:2048'],
            'role_id'   => ['required', 'exists:roles,id'],
            'branch_id' => ['nullable', 'exists:branches,id'],
            'active'    => ['nullable', 'boolean'],
        ]);

        $role = Role::withTrashed()->find($data['role_id']);

        if ($request->boolean('active', true)) {
            if ($role && ($role->trashed() || ! $this->roleIsActive($role))) {
                return back()->withInput()->with('error', 'Selected role is inactive or deleted.');
            }
        }

        $user = new User($data);
        $user->password   = Hash::make($data['password']);
        $user->active     = $request->boolean('active', true);
        $user->created_by = optional(Auth::user())->id;

        if ($request->hasFile('avatar')) {
            $user->avatar = $request->file('avatar')->store('avatars', 'public');
        }

        $user->branch_id = $request->input('branch_id') ?? $role->branch_id ?? null;
        $user->save();

        return redirect()->route('users.index')
            ->with('status', 'User "' . $user->name . '" created successfully.');
    }

    /* -------------------------------------------------------------
     | Edit
     |-------------------------------------------------------------*/
    public function edit(User $user): View
    {
        try { $this->authorize('update', $user); } catch (\Throwable $e) {}

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
        try { $this->authorize('update', $user); } catch (\Throwable $e) {}

        $data = $request->validate([
            'name'     => ['required', 'string', 'max:255'],
            'mobile'   => ['required', Rule::unique('users')->ignore($user->id)],
            'email'    => ['nullable', Rule::unique('users')->ignore($user->id)],
            'password' => ['nullable', 'confirmed', 'min:8'],
            'role_id'  => ['required', 'exists:roles,id'],
            'branch_id'=> ['nullable', 'exists:branches,id'],
            'active'   => ['nullable', 'boolean'],
        ]);

        $role = Role::withTrashed()->find($data['role_id']);

        $roleName = strtolower(trim($this->roleName($role) ?? ''));
        $isDevRole = $roleName === 'developer';

        $currentRoleName = strtolower(trim($this->roleName(optional(Auth::user())->role) ?? ''));

        if ($isDevRole && $currentRoleName !== 'developer') {
            return back()->withInput()->with('error', 'You cannot assign Developer role.');
        }

        if ($request->boolean('active', false)) {
            if ($role && ($role->trashed() || ! $this->roleIsActive($role))) {
                return back()->withInput()->with('error', 'Assigned role is inactive or deleted.');
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
    }

    /* -------------------------------------------------------------
     | Destroy
     |-------------------------------------------------------------*/
    public function destroy(User $user): RedirectResponse
    {
        try { $this->authorize('delete', $user); } catch (\Throwable $e) {}

        $user->deleted_by = optional(Auth::user())->id;
        $user->save();
        $user->delete();

        return back()->with('deleted', 'User deleted successfully.');
    }

    /* -------------------------------------------------------------
     | Restore
     |-------------------------------------------------------------*/
    public function restore(Request $request, int $id): RedirectResponse
    {
        $user = User::withTrashed()->findOrFail($id);

        $role = Role::withTrashed()->find($user->role_id);

        if ($role && ($role->trashed() || ! $this->roleIsActive($role))) {
            return back()->with('error', 'Cannot restore user due to inactive role.');
        }

        $user->restore();
        $user->restored_by = optional(Auth::user())->id;
        $user->save();

        return back()->with('status', 'User restored successfully.');
    }
}
