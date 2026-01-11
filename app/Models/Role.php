<?php

namespace App\Models;

use Spatie\Permission\Models\Role as SpatieRole;
use Illuminate\Database\Eloquent\SoftDeletes;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Support\Facades\DB;
use App\Traits\HasAuditFields;
use Illuminate\Database\Eloquent\Relations\BelongsToMany;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Collection as EloquentCollection;
use Illuminate\Support\Facades\Schema;
use App\Models\Branch;
use App\Models\User;
use App\Models\Permission;

/**
 * Role model (hierarchy removed) with audit fields.
 *
 * @property int $id
 * @property string $name
 * @property string|null $role_name
 * @property bool $active
 * @property string|null $slug
 * @property string|null $description
 * @property bool $is_active
 * @property int|null $priority
 * @property int|null $branch_id
 * @property int|null $created_by
 * @property int|null $updated_by
 * @property int|null $deleted_by
 * @property int|null $restored_by
 * @property \Illuminate\Support\Carbon|null $restored_at
 * @property \Illuminate\Support\Carbon|null $created_at
 * @property \Illuminate\Support\Carbon|null $updated_at
 * @property \Illuminate\Support\Carbon|null $deleted_at
 * @property string $guard_name
 * @property EloquentCollection|Permission[] $permissions
 * @property EloquentCollection|User[] $users
 * @property-read \App\Models\User|null $deletedBy
 * @property-read \App\Models\User|null $createdBy
 * @property-read \App\Models\User|null $updatedBy
 * @property-read \App\Models\User|null $restoredBy
 * @property-read \App\Models\Branch|null $branch
 * @use \Illuminate\Database\Eloquent\Factories\HasFactory<\Database\Factories\RoleFactory>
 */
class Role extends SpatieRole
{
    use HasFactory, SoftDeletes, HasAuditFields;

    protected string $guard_name = 'web';

    protected $fillable = [
        'name',
        'slug',
        'description',
        'is_active',
        'priority',
        'branch_id',
        'created_by',
        'updated_by',
        'deleted_by',
        'restored_by',
    ];

    protected $casts = [
        'guard_name' => 'string',
        'is_active' => 'boolean',
        'priority' => 'integer',
        'branch_id' => 'integer',
        'created_by' => 'integer',
        'updated_by' => 'integer',
        'deleted_by' => 'integer',
        'restored_by' => 'integer',
        'restored_at' => 'datetime',
    ];

    public function getNameAttribute(?string $value): ?string
    {
        $name = $value ?? ($this->attributes['role_name'] ?? null);
        return is_string($name) ? $name : null;
    }

    /**
     * Roles that are parents of this role (hierarchy pivot where this is child).
     */
    // Hierarchy functionality removed. Roles are now scoped by `branch_id` and `priority` only.

    public function isSuperAdmin(): bool
    {
        try {
            $name = strtolower(trim($this->name ?? ''));
            return $name === 'developer' || ($this->slug ?? '') === 'developer';
        } catch (\Throwable $e) {
            return false;
        }
    }

    public function isAdmin(): bool
    {
        try {
            $name = strtolower(trim($this->name ?? ''));
            return in_array($name, ['admin', 'administrator']);
        } catch (\Throwable $e) {
            return false;
        }
    }

    public function isProtected(): bool
    {
        // Protected means it is the Developer or Super Admin equivalent
        return $this->isSuperAdmin();
    }

    /**
     * @return \Illuminate\Database\Eloquent\Relations\BelongsToMany<\App\Models\User, $this, \Illuminate\Database\Eloquent\Relations\Pivot, 'pivot'>
     */
    public function users(): BelongsToMany
    {
        return $this->belongsToMany(User::class, 'model_has_roles', 'role_id', 'model_id')
            ->wherePivot('model_type', User::class)
            ->withTimestamps();
    }

    /**
     * Permissions assigned to this role (Spatie pivot table `role_has_permissions`).
     *
     * @return \Illuminate\Database\Eloquent\Relations\BelongsToMany<\App\Models\Permission, $this, \Illuminate\Database\Eloquent\Relations\Pivot, 'pivot'>
     */
    public function permissions(): BelongsToMany
    {
        return $this->belongsToMany(Permission::class, 'role_has_permissions', 'role_id', 'permission_id');
    }

    // --- Legacy hierarchy stubs (present to satisfy static analysis when hierarchy package/table removed) ---
    /**
     * @return \Illuminate\Database\Eloquent\Relations\BelongsToMany<\App\Models\Role, $this>
     */
    public function parents(): BelongsToMany
    {
        return $this->belongsToMany(self::class, 'role_hierarchies', 'child_id', 'parent_id');
    }

    /**
     * Return an empty collection by default. Real implementation existed in hierarchy package.
     * @return \Illuminate\Support\Collection<int, int>
     */
    public function getAllDescendantIds(): \Illuminate\Support\Collection
    {
        return collect([]);
    }

    /**
     * Legacy stub: determine ancestor relationship.
     */
    public function isAncestorOf(Role $role): bool
    {
        return false;
    }

    /**
     * Legacy stub: return ancestor ids.
     * @return \Illuminate\Support\Collection<int, int>
     */
    public function getAllAncestorIds(): \Illuminate\Support\Collection
    {
        return collect([]);
    }

    /**
     * Count users assigned to this role via direct role_id FK.
     */
    public function userCount(): int
    {
        try {
            return User::where('role_id', $this->id)->count();
        } catch (\Throwable $e) {
            return 0;
        }
    }

    /**
     * Branch this role belongs to (optional)
     * @return \Illuminate\Database\Eloquent\Relations\BelongsTo<\App\Models\Branch, $this>
     */
    public function branch(): BelongsTo
    {
        return $this->belongsTo(Branch::class, 'branch_id');
    }

    /**
     * Accessor for `is_active` to ensure boolean return and
     * support both `$role->is_active` and `$role->isActive()` usages.
     */
    public function getIsActiveAttribute(?bool $value): bool
    {
        return (bool) ($value ?? $this->attributes['is_active'] ?? false);
    }

    /**
     * Helper method used in some views/compiled templates.
     */
    public function isActive(): bool
    {
        return (bool) ($this->is_active ?? false);
    }
    // Hierarchy helper methods removed.

    /**
     * @param Builder<$this> $query
     * @return Builder<$this>
     */
    public function scopeActive(Builder $query): Builder
    {
        return $query->where('is_active', true);
    }

    /**
     * @param Builder<$this> $query
     * @return Builder<$this>
     */
    public function scopeByPriority(Builder $query): Builder
    {
        return $query->orderBy('priority', 'asc');
    }
}

