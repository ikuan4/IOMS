<?php

namespace App\Http\Controllers;

use App\Models\User;
use App\Models\Role;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Auth;
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

        $users = $query->paginate(10)->withQueryString();

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

        $user = new User();
        $user->name = $data['name'];
        $user->mobile = $data['mobile'];
        $user->email = $data['email'] ?? null;
        $user->active = $request->boolean('active', true);
        $user->password = Hash::make($data['password']);
        $user->role_id = $data['role_id'];
        $user->email_bounce_count = 0;
        $user->email_bounced_at = null;
        $user->save();

        return redirect()->route('users.index')->with('status', 'User "' . $user->name . '" created successfully.');
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

        $user->name = $data['name'];
        $user->mobile = $data['mobile'];
        $user->email = $data['email'] ?? null;
        $user->active = $request->boolean('active', false);
        $user->role_id = $data['role_id'];

        if (!empty($data['password'])) {
            $user->password = Hash::make($data['password']);
        }

        if ($request->boolean('reset_bounce')) {
            $user->email_bounce_count = 0;
            $user->email_bounced_at = null;
        }

        $user->save();

        return redirect()->route('users.index')->with('status', 'User "' . $user->name . '" updated successfully.');
    }

    public function destroy(User $user)
    {
        try { $this->authorize('delete', $user); } catch (\Throwable $e) {}

        $name = $user->name;
        $user->delete();

        return redirect()->route('users.index')->with('deleted', 'User "' . $name . '" deleted successfully.');
    }

    public function restore(Request $request, $id)
    {
        $user = User::withTrashed()->findOrFail($id);
        try { $this->authorize('restore', $user); } catch (\Throwable $e) {}

        if ($user->trashed()) {
            if (method_exists($user, 'restoreWithUser')) {
                $user->restoreWithUser();
            } else {
                $user->restore();
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
