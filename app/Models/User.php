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
            'role_id' => 'integer',
            'branch_id' => 'integer',
            'password' => 'hashed',
        ];
    }

    // BelongsTo relation to Role
    /**
     * @return \Illuminate\Database\Eloquent\Relations\BelongsTo<\App\Models\Role, $this>
     */
    public function role(): BelongsTo
    {
        return $this->belongsTo(Role::class);
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
     * Uses only direct FK assignment (`users.role_id` => `roles.id`).
     * Pivot table (model_has_roles) is deprecated for User-Role assignments.
     */
    public function effectiveRole(): ?Role
    {
        try {
            if ($this->relationLoaded('role') && $this->getRelation('role')) {
                return $this->getRelation('role');
            }

            if (!empty($this->role_id)) {
                return $this->role()->first();
            }

            return null;
        } catch (\Throwable) {
            return null;
        }
    }

    /**
     * Check if this user is the protected Super Admin user.
     */
    public function isSuperAdmin(): bool
    {
        // Developer user has no role and no branch
        return $this->role_id === null && $this->branch_id === null;
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
     * @return \Illuminate\Support\Collection<int, \App\Models\Role>
     */
    public function getManageableRoles(): \Illuminate\Support\Collection
    {
        // Developer sees all active roles
        if ($this->isSuperAdmin()) {
            $roles = Role::where('is_active', true)
                ->whereNull('deleted_at')
                ->orderBy('priority', 'asc')
                ->get();
            return $roles;
        }

        // Non-developers: return roles within the same branch or the user's own role
        $effectiveRole = $this->effectiveRole();
        if (!$effectiveRole) {
            return collect([]);
        }

        $myPriority = $effectiveRole->priority ?? 999;

        $roles = Role::where('branch_id', $this->branch_id)
          ->where('is_active', true)
          ->whereNull('deleted_at')
          ->orderBy('priority', 'asc')
          ->get();

        // Remove Developer role for non-developer users and enforce priority
        $roles = $roles->reject(function ($r) use ($myPriority) {
            // Reject super admin roles for non-super admins
            if ($r->isSuperAdmin() && !$this->isSuperAdmin()) {
                return true;
            }
            // Reject roles with higher priority (lower number = higher priority)
            $rolePriority = $r->priority ?? 999;
            return $rolePriority < $myPriority;
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
     * @return \Illuminate\Support\Collection<int, \App\Models\User>
     */
    public function getManageableUsers(): \Illuminate\Support\Collection
    {
        if ($this->isSuperAdmin()) {
            return User::with(['role', 'branch'])->where('id', '!=', $this->id)->get();
        }

        // Non-developers: return users in the same branch (branch must exist and be active)
        return User::with(['role', 'branch'])
            ->where('branch_id', $this->branch_id)
            ->where('id', '!=', $this->id)
            ->whereHas('branch', function($q) {
                $q->whereNull('deleted_at');
            })
            ->get();
    }

    // BelongsTo relation to Branch
    /**
     * @return \Illuminate\Database\Eloquent\Relations\BelongsTo<\App\Models\Branch, $this>
     */
    public function branch(): BelongsTo
    {
        return $this->belongsTo(Branch::class);
    }

    // BelongsTo relation createdBy
    /**
     * @return \Illuminate\Database\Eloquent\Relations\BelongsTo<\App\Models\User, $this>
     */
    public function createdBy(): BelongsTo
    {
        return $this->belongsTo(self::class, 'created_by');
    }

    // BelongsTo relation updatedBy
    /**
     * @return \Illuminate\Database\Eloquent\Relations\BelongsTo<\App\Models\User, $this>
     */
    public function updatedBy(): BelongsTo
    {
        return $this->belongsTo(self::class, 'updated_by');
    }

    // BelongsTo relation lastUpdatedBy
    /**
     * @return \Illuminate\Database\Eloquent\Relations\BelongsTo<\App\Models\User, $this>
     */
    public function lastUpdatedBy(): BelongsTo
    {
        return $this->belongsTo(self::class, 'last_updated_by');
    }

    // BelongsTo relation deletedBy
    /**
     * @return \Illuminate\Database\Eloquent\Relations\BelongsTo<\App\Models\User, $this>
     */
    public function deletedBy(): BelongsTo
    {
        return $this->belongsTo(self::class, 'deleted_by');
    }

    // BelongsTo relation restoredBy
    /**
     * @return \Illuminate\Database\Eloquent\Relations\BelongsTo<\App\Models\User, $this>
     */
    public function restoredBy(): BelongsTo
    {
        return $this->belongsTo(self::class, 'restored_by');
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
