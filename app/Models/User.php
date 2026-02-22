<?php

namespace App\Models;

// use Illuminate\Contracts\Auth\MustVerifyEmail;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\SoftDeletes;
use Illuminate\Foundation\Auth\User as Authenticatable;
use Illuminate\Notifications\Notifiable;
use App\Traits\HasAuditFields;
use Illuminate\Database\Eloquent\Casts\Attribute;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Support\Facades\Hash;
use Illuminate\Database\Eloquent\Collection;
use Illuminate\Support\Facades\DB;
use App\Models\Permission;
use App\Models\Role;
use App\Models\Branch;

/**
 * @property int $id
 * @property string|null $avatar
 * @property string $name
 * @property string|null $email
 * @property string $password
 * @property int|null $role_id
 * @property int|null $branch_id
 * @property bool $active
 * @property \Illuminate\Database\Eloquent\Collection|Permission[] $permissions
 * @property \Illuminate\Database\Eloquent\Collection|Role[] $roles
 * @property int|null $created_by
 * @property int|null $updated_by
 * @property int|null $deleted_by
 * @property int|null $restored_by
 * @property \Illuminate\Support\Carbon|null $restored_at
 * @property \Illuminate\Support\Carbon|null $created_at
 * @property \Illuminate\Support\Carbon|null $updated_at
 * @property \Illuminate\Support\Carbon|null $deleted_at
 * @property-read \App\Models\Role|null $role
 * @property-read \App\Models\Branch|null $branch
 */
class User extends Authenticatable
{
    /** @use \Illuminate\Database\Eloquent\Factories\HasFactory<\Database\Factories\UserFactory> */
    use HasFactory, Notifiable, SoftDeletes, HasAuditFields;

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
        'global_role_id',
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
            'deleted_at' => 'datetime',
            'last_updated_at' => 'datetime',
            'last_updated_by' => 'integer',
            'created_by' => 'integer',
            'updated_by' => 'integer',
            'deleted_by' => 'integer',
            'restored_by' => 'integer',
            'restored_at' => 'datetime',
            'email_bounced_at' => 'datetime',
            'email_bounce_count' => 'integer',
            'active' => 'boolean',
            'global_role_id' => 'integer',
            'password' => 'hashed',
        ];
    }

    // ===================================
    // ROLE RELATIONSHIPS (NEW STRUCTURE)
    // ===================================

    /**
     * Global role relationship (for users with global_role_id set).
     *
     * @return \Illuminate\Database\Eloquent\Relations\BelongsTo<\App\Models\Role, $this>
     */
    public function globalRole(): BelongsTo
    {
        return $this->belongsTo(Role::class, 'global_role_id');
    }

    /**
     * Legacy compatibility: redirect to globalRole() for transition period.
     * @deprecated Use globalRole() instead
     * @return \Illuminate\Database\Eloquent\Relations\BelongsTo<\App\Models\Role, $this>
     */
    public function role(): BelongsTo
    {
        return $this->globalRole();
    }

    /**
     * Branches this user is assigned to (via branch_user pivot).
     *
     * @return \Illuminate\Database\Eloquent\Relations\BelongsToMany<\App\Models\Branch, $this>
     */
    public function branches(): \Illuminate\Database\Eloquent\Relations\BelongsToMany
    {
        return $this->belongsToMany(Branch::class, 'branch_user', 'user_id', 'branch_id')
            ->withTimestamps();
    }

    /**
     * Branch roles assigned to this user (via branch_user_role).
     * Note: This returns roles with their branch pivot data.
     *
     * @return \Illuminate\Database\Eloquent\Relations\BelongsToMany<\App\Models\Role, $this>
     */
    public function branchRoles(): \Illuminate\Database\Eloquent\Relations\BelongsToMany
    {
        return $this->belongsToMany(Role::class, 'branch_user_role', 'user_id', 'role_id')
            ->withPivot('branch_id')
            ->withTimestamps();
    }

    /**
     * Get the role for a specific branch.
     *
     * @param int $branchId
     * @return \App\Models\Role|null
     */
    public function roleForBranch(int $branchId): ?Role
    {
        return \DB::table('branch_user_role')
            ->where('user_id', $this->id)
            ->where('branch_id', $branchId)
            ->first()?->role_id 
            ? Role::find(\DB::table('branch_user_role')
                ->where('user_id', $this->id)
                ->where('branch_id', $branchId)
                ->value('role_id'))
            : null;
    }

    /**
     * Get active branch from session.
     *
     * @return \App\Models\Branch|null
     */
    public function activeBranch(): ?Branch
    {
        $activeBranchId = session('active_branch_id');
        
        if (!$activeBranchId) {
            return null;
        }

        return Branch::find($activeBranchId);
    }

    /**
     * Set active branch in session.
     *
     * @param int|null $branchId
     * @return void
     */
    public function setActiveBranch(?int $branchId): void
    {
        if ($branchId === null) {
            session()->forget('active_branch_id');
            return;
        }

        // Verify user has access to this branch
        if ($this->global_role_id) {
            // Global users can access any branch
            session(['active_branch_id' => $branchId]);
        } else {
            // Branch users must be assigned to the branch
            $hasAccess = \DB::table('branch_user')
                ->where('user_id', $this->id)
                ->where('branch_id', $branchId)
                ->exists();

            if ($hasAccess) {
                session(['active_branch_id' => $branchId]);
            }
        }
    }

    /**
     * Roles assigned via pivot model_has_roles
     */
    /**
     * @return \Illuminate\Database\Eloquent\Relations\BelongsToMany<\App\Models\Role, $this>
     */
    public function roles(): \Illuminate\Database\Eloquent\Relations\BelongsToMany
    {
        return $this->belongsToMany(Role::class, 'model_has_roles', 'model_id', 'role_id')
            ->wherePivot('model_type', self::class);
    }

    /**
     * Resolve the user's effective role.
     *
     * NEW STRUCTURE:
     * - If user has global_role_id: return that global role
     * - If user is branch user: return role for active branch (from session)
     * - Otherwise: return null
     */
    public function effectiveRole(): ?Role
    {
        try {
            // Check for global role first
            if ($this->global_role_id) {
                if ($this->relationLoaded('globalRole') && $this->getRelation('globalRole')) {
                    return $this->getRelation('globalRole');
                }
                return $this->globalRole()->first();
            }

            // Branch user: get role for active branch
            $activeBranchId = session('active_branch_id');
            if ($activeBranchId) {
                return $this->roleForBranch($activeBranchId);
            }

            return null;
        } catch (\Throwable) {
            return null;
        }
    }

    /**
     * Check if this user is the protected Super Admin user.
     * NEW STRUCTURE: Check global role with is_global=true and slug=developer
     */
    public function isSuperAdmin(): bool
    {
        // Check global role
        if ($this->global_role_id) {
            $role = $this->effectiveRole();
            if ($role && $role->is_global) {
                $slug = strtolower(trim((string) ($role->slug ?? '')));
                $name = strtolower(trim((string) ($role->name ?? '')));
                if ($slug === 'developer' || $name === 'developer') {
                    return true;
                }
            }
        }

        return false;
    }

    /**
     * Backwards-compatible alias for legacy checks.
     * Some views/controllers call `isDeveloper()`; delegate to `isSuperAdmin()`.
     */
    public function isDeveloper(): bool
    {
        return $this->isSuperAdmin();
    }

    /**
     * Check if user has a specific permission (custom system).
     * Protected Super Admin user bypasses all permission checks.
     *
     * @param mixed $permission  Array of slugs, single slug string, or object with ->slug
     */
    public function hasPermission(mixed $permission): bool
    {
        if ($this->isSuperAdmin()) {
            return true;
        }

        $role = $this->effectiveRole();
        if (!$role) {
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

            if ($role->permissions()->where('slug', $slug)->exists()) {
                return true;
            }
        }

        return false;
    }

    /**
     * @return Collection<int, \App\Models\Permission>
     */
    public function getAllPermissions(): Collection
    {
        if ($this->isSuperAdmin()) {
            return Permission::all();
        }

        $role = $this->effectiveRole();
        if (!$role) {
            return new Collection();
        }

        return $role->permissions()->get();
    }

    /**
     * Get manageable roles for this user (descendants of user's role).
     * Enforces priority hierarchy: users can only assign roles with equal or lower priority.
     *
     * NEW STRUCTURE:
     * - Global users can manage all roles (global + all branch roles)
     * - Branch users can only manage roles within their active branch
     */
    public function getManageableRoles(): \Illuminate\Support\Collection
    {
        // Global user (Developer) sees all active roles
        if ($this->isSuperAdmin()) {
            $roles = Role::where('is_active', true)
                ->whereNull('deleted_at')
                ->orderBy('priority', 'asc')
                ->get();
            return $roles;
        }

        // Branch user: get roles in active branch only
        $activeBranchId = session('active_branch_id');
        if (!$activeBranchId) {
            return collect([]);
        }

        $effectiveRole = $this->effectiveRole();
        if (!$effectiveRole) {
            return collect([]);
        }

        $myPriority = $effectiveRole->priority ?? 999;

        // Get roles in the same branch with priority >= myPriority (lower number = higher authority)
        $roles = Role::where('branch_id', $activeBranchId)
            ->where('is_active', true)
            ->where('is_global', false)
            ->whereNull('deleted_at')
            ->orderBy('priority', 'asc')
            ->get();

        // Filter by priority: can only manage roles with priority >= myPriority
        $roles = $roles->filter(function ($r) use ($myPriority) {
            $rolePriority = $r->priority ?? 999;
            return $rolePriority >= $myPriority;
        })->values();

        return $roles;
    }

    /**
     * Determine whether this user can manage the given role.
     */
    /**
     * @param \App\Models\Role $role
     */
    public function canManageRole(Role $role): bool
    {
        if ($this->isSuperAdmin()) {
            return true;
        }

        $manageable = $this->getManageableRoles()->pluck('id')->toArray();

        return in_array($role->id, $manageable, true);
    }

    /**
     * Alias for role selection in controllers/views.
     *
     * @return \Illuminate\Support\Collection<int, \App\Models\Role>
     */
    public function getAvailableRolesForAssignment(): \Illuminate\Support\Collection
    {
        return $this->getManageableRoles();
    }

    /**
     * Get manageable users for this user (users with descendant roles).
     *
     * NEW STRUCTURE:
     * - Global users can manage all users
     * - Branch users can only manage users in their active branch
     *
     * @return \Illuminate\Support\Collection<int, \App\Models\User>
     */
    public function getManageableUsers(): \Illuminate\Support\Collection
    {
        if ($this->isSuperAdmin()) {
            return User::with(['globalRole'])->where('id', '!=', $this->id)->get();
        }

        // Branch user: manage users in active branch only
        $activeBranchId = session('active_branch_id');
        if (!$activeBranchId) {
            return collect([]);
        }

        // Get users who have a role in this branch via branch_user_role
        return User::with(['globalRole'])
            ->whereHas('branchRoles', function($q) use ($activeBranchId) {
                $q->wherePivot('branch_id', $activeBranchId);
            })
            ->where('id', '!=', $this->id)
            ->get();
    }

    /**
     * Helper method to get user's branch (for legacy compatibility).
     * For global users: returns null
     * For branch users: returns active branch from session
     *
     * @return \App\Models\Branch|null
     */
    public function branch(): ?Branch
    {
        return $this->activeBranch();
    }

    // BelongsTo relation createdBy
    /**
     * @return \Illuminate\Database\Eloquent\Relations\BelongsTo<\App\Models\User, $this>
     */
    public function createdBy(): BelongsTo
    {
        return $this->belongsTo(self::class, 'created_by')
            ->withoutGlobalScopes()
            ->without(['createdBy', 'updatedBy', 'deletedBy', 'restoredBy', 'lastUpdatedBy'])
            ->select(['id', 'name', 'email']);
    }

    // BelongsTo relation updatedBy
    /**
     * @return \Illuminate\Database\Eloquent\Relations\BelongsTo<\App\Models\User, $this>
     */
    public function updatedBy(): BelongsTo
    {
        return $this->belongsTo(self::class, 'updated_by')
            ->withoutGlobalScopes()
            ->without(['createdBy', 'updatedBy', 'deletedBy', 'restoredBy', 'lastUpdatedBy'])
            ->select(['id', 'name', 'email']);
    }

    // BelongsTo relation lastUpdatedBy
    /**
     * @return \Illuminate\Database\Eloquent\Relations\BelongsTo<\App\Models\User, $this>
     */
    public function lastUpdatedBy(): BelongsTo
    {
        return $this->belongsTo(self::class, 'last_updated_by')
            ->withoutGlobalScopes()
            ->without(['createdBy', 'updatedBy', 'deletedBy', 'restoredBy', 'lastUpdatedBy'])
            ->select(['id', 'name', 'email']);
    }

    // BelongsTo relation deletedBy
    /**
     * @return \Illuminate\Database\Eloquent\Relations\BelongsTo<\App\Models\User, $this>
     */
    public function deletedBy(): BelongsTo
    {
        return $this->belongsTo(self::class, 'deleted_by')
            ->withoutGlobalScopes()
            ->without(['createdBy', 'updatedBy', 'deletedBy', 'restoredBy', 'lastUpdatedBy'])
            ->select(['id', 'name', 'email']);
    }

    // BelongsTo relation restoredBy
    /**
     * @return \Illuminate\Database\Eloquent\Relations\BelongsTo<\App\Models\User, $this>
     */
    public function restoredBy(): BelongsTo
    {
        return $this->belongsTo(self::class, 'restored_by')
            ->withoutGlobalScopes()
            ->without(['createdBy', 'updatedBy', 'deletedBy', 'restoredBy', 'lastUpdatedBy'])
            ->select(['id', 'name', 'email']);
    }

    /**
     * Automatically hash password when set.
     * Usage: $user->password = 'plain'; (will be hashed)
     *
     * @return \Illuminate\Database\Eloquent\Casts\Attribute<mixed,mixed>
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
