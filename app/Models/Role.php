<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Spatie\Permission\Models\Role as SpatieRole;
use Illuminate\Database\Eloquent\SoftDeletes;
use Illuminate\Support\Facades\DB;
use App\Traits\HasAuditFields;
use Illuminate\Database\Eloquent\Relations\BelongsToMany;
use App\Models\Branch;

/**
 * Role model (hierarchy removed) with audit fields.
 */
class Role extends SpatieRole
{
    use HasFactory, SoftDeletes, HasAuditFields;

    protected $guard_name = 'web';

    protected $fillable = [
        'name',
        'slug',
        'description',
        'is_active',
        'priority',
        'branch_id',
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

    public function users(): \Illuminate\Database\Eloquent\Relations\BelongsToMany
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
        $directIds = \App\Models\User::where('role_id', $this->id)->pluck('id')->toArray();

        return count(array_unique(array_merge($pivotIds, $directIds)));
    }

    /**
     * Branch this role belongs to (optional)
     */
    public function branch()
    {
        return $this->belongsTo(Branch::class, 'branch_id');
    }
    // Hierarchy helper methods removed.

    public function scopeActive($query)
    {
        return $query->where('is_active', true);
    }

    public function scopeByPriority($query)
    {
        return $query->orderBy('priority', 'asc');
    }
}

