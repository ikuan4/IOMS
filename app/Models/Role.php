<?php

namespace App\Models;

use Spatie\Permission\Models\Role as SpatieRole;
use Illuminate\Database\Eloquent\SoftDeletes;
use Illuminate\Support\Facades\DB;
use App\Traits\HasAuditFields;
use Illuminate\Database\Eloquent\Relations\BelongsToMany;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Collection as EloquentCollection;
use App\Models\Branch;
use App\Models\User;
use App\Models\Permission;

/**
 * Role model (hierarchy removed) with audit fields.
 *
 * @property int $id
 * @property string $name
 * @property string|null $slug
 * @property string|null $description
 * @property bool $is_active
 * @property int|null $priority
 * @property int|null $branch_id
 * @property EloquentCollection|Permission[] $permissions
 * @property EloquentCollection|User[] $users
*/
class Role extends SpatieRole
{
    use SoftDeletes, HasAuditFields;

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
            ->withTimestamps();
    }

    /**
     * Count users assigned to this role.
     * Handles both pivot-based assignments (model_has_roles) and direct role_id on users table.
     */
    public function userCount(): int
    {
        // Get IDs assigned via pivot relation (if loaded, use it to avoid N+1)
        if ($this->relationLoaded('users')) {
            $pivotIds = $this->users->pluck('id')->toArray();
        } else {
            $pivotIds = $this->users()->pluck('id')->toArray();
        }

        // Get IDs assigned via users.role_id foreign key
        $directIds = User::where('role_id', $this->id)->pluck('id')->toArray();

        return count(array_unique(array_merge($pivotIds, $directIds)));
    }

    /**
     * Branch this role belongs to (optional)
     */
    /**
     * @return \Illuminate\Database\Eloquent\Relations\BelongsTo<\App\Models\Branch, $this>
     */
    public function branch(): BelongsTo
    {
        return $this->belongsTo(Branch::class, 'branch_id');
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

