<?php

namespace App\Models;

// use Illuminate\Contracts\Auth\MustVerifyEmail;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\SoftDeletes;
use Illuminate\Foundation\Auth\User as Authenticatable;
use Illuminate\Notifications\Notifiable;
use Illuminate\Database\Eloquent\Casts\Attribute;
use Illuminate\Support\Facades\Hash;
use App\Models\Permission;

/**
 * @property int $id
 * @property string|null $avatar
 * @property string $name
 * @property string|null $email
 * @property string $password
 */
class User extends Authenticatable
{
    /** @use HasFactory<\Database\Factories\UserFactory> */
    use HasFactory, Notifiable, SoftDeletes;

    /**
     * The attributes that are mass assignable.
     *
     * @var list<string>
     */
    protected $fillable = [
        'name',
        'email',
        'password',
        'avatar',
        'mobile',
        'last_updated_by',
        'last_updated_at',
        'created_by',
        'updated_by',
        'deleted_by',
        'restored_by',
        'active',
        'email_bounce_count',
        'email_bounced_at',
        'role_id',
        'branch_id',
    ];

    /**
     * The attributes that should be hidden for serialization.
     *
     * @var list<string>
     */
    protected $hidden = [
        'password',
        'remember_token',
    ];

    /**
     * Get the attributes that should be cast.
     *
     * @return array<string, string>
     */
    protected function casts(): array
    {
        return [
            'email_verified_at' => 'datetime',
            'mobile_verified_at' => 'datetime',
            'created_at' => 'datetime',
            'updated_at' => 'datetime',
            'last_updated_at' => 'datetime',
            'last_updated_by' => 'integer',
            'created_by' => 'integer',
            'updated_by' => 'integer',
            'deleted_by' => 'integer',
            'restored_by' => 'integer',
            'email_bounced_at' => 'datetime',
            'email_bounce_count' => 'integer',
            'active' => 'boolean',
            'role_id' => 'integer',
            'branch_id' => 'integer',
            'password' => 'hashed',
        ];
    }

    public function role()
    {
        return $this->belongsTo(Role::class);
    }

    /**
     * Check if this user is the protected Super Admin user.
     */
    public function isSuperAdmin(): bool
    {
        return $this->id === config('roles.protected_user_id', 1);
    }

    /**
     * Check if user has a specific permission (custom system).
     * Protected Super Admin user bypasses all permission checks.
     */
    public function hasPermission($permission)
    {
        if ($this->isSuperAdmin()) {
            return true;
        }

        if (!$this->role) {
            return false;
        }

        // Normalize incoming permission(s) to an array of slugs
        $slugs = [];
        if (is_array($permission)) {
            $slugs = $permission;
        } elseif (is_string($permission)) {
            $slugs = [$permission];
        } elseif (is_object($permission) && isset($permission->slug)) {
            $slugs = [$permission->slug];
        } else {
            return false;
        }

        $aliases = config('permissions.aliases', []);

        foreach ($slugs as $s) {
            $slug = $s;
            if (isset($aliases[$slug])) {
                $slug = $aliases[$slug];
            }

            if ($this->role->permissions()->where('slug', $slug)->exists()) {
                return true;
            }
        }

        return false;
    }

    /**
     * Get all permissions from user's role.
     */
    public function getAllPermissions()
    {
        if ($this->isSuperAdmin()) {
            return Permission::all();
        }

        if (!$this->role) {
            return collect([]);
        }

        return $this->role->permissions;
    }

    /**
     * Get manageable roles for this user (descendants of user's role).
     */
    public function getManageableRoles()
    {
        if ($this->isSuperAdmin()) {
            $roles = Role::where('id', '!=', config('roles.super_admin_id', 1))
                ->where('is_active', true)
                ->whereNull('deleted_at')
                ->get();
        } else {
            if (!$this->role) {
                return collect([]);
            }

            $descendantIds = $this->role->getAllDescendantIdsOptimized();
            $descendantIds = $descendantIds->reject(fn($id) => $id === $this->role_id);

            // Exclude Super Admin role for non-super-admin users
            $superAdminId = config('roles.super_admin_id', 1);
            $roles = Role::whereIn('id', $descendantIds)
                ->where('id', '!=', $superAdminId)
                ->where('is_active', true)
                ->whereNull('deleted_at')
                ->get();
        }

        // Remove Developer role for non-developer users
        $roles = $roles->reject(function ($r) {
            $isDeveloperRole = strtolower(trim($r->name ?? '')) === 'developer';
            $currentIsDeveloper = strtolower(trim($this->role->name ?? '')) === 'developer';
            return $isDeveloperRole && !$currentIsDeveloper;
        });

        // If current user is Developer ensure their Developer role appears
        if (strtolower(trim($this->role->name ?? '')) === 'developer') {
            $developerRole = Role::whereRaw('lower(name) = ?', ['developer'])->first();
            if ($developerRole && !$roles->contains('id', $developerRole->id)) {
                $roles->push($developerRole);
            }
        }

        // Sort by hierarchy level (root first), then alphabetically
        return $roles->sortBy([
            function ($role) {
                return $role->getAllAncestorIds()->count();
            },
            function ($role) {
                return strtolower($role->name);
            }
        ])->values();
    }

    /**
     * Alias for role selection in controllers/views.
     */
    public function getAvailableRolesForAssignment()
    {
        return $this->getManageableRoles();
    }

    /**
     * Get manageable users for this user (users with descendant roles).
     */
    public function getManageableUsers()
    {
        if ($this->isSuperAdmin()) {
            return User::withTrashed()->where('id', '!=', $this->id)->with('role')->get();
        }

        $manageableRoleIds = $this->getManageableRoles()->pluck('id');

        return User::withTrashed()->whereIn('role_id', $manageableRoleIds)
            ->where('id', '!=', $this->id)
            ->with('role')
            ->get();
    }

    public function branch()
    {
        return $this->belongsTo(Branch::class);
    }

    /**
     * Automatically hash password when set.
     * Usage: $user->password = 'plain'; (will be hashed)
     */
    protected function password(): Attribute
    {
        return Attribute::make(
            set: fn($value) => $value
                ? (Hash::needsRehash($value) ? Hash::make($value) : $value)
                : null
        );
    }
}
