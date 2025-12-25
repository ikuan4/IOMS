<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Spatie\Permission\Models\Role as SpatieRole;
use Illuminate\Database\Eloquent\SoftDeletes;
use Illuminate\Support\Facades\DB;
use App\Traits\HasAuditFields;

/**
 * @property int $id
 * @property string $name
 * @property string $slug
 * @property string|null $description
 * @property bool $is_active
 * @property string $guard_name
 * @property int|null $created_by
 * @property int|null $updated_by
 * @property int|null $deleted_by
 * @property int|null $restored_by
 * @property \Carbon\Carbon|null $created_at
 * @property \Carbon\Carbon|null $updated_at
 * @property \Carbon\Carbon|null $deleted_at
 * @property-read \Illuminate\Database\Eloquent\Collection $permissions
 * @property-read \Illuminate\Database\Eloquent\Collection $users
 * @property-read \Illuminate\Database\Eloquent\Collection $parents
 * @property-read \Illuminate\Database\Eloquent\Collection $children
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Spatie\Permission\Models\Role as SpatieRole;
use Illuminate\Database\Eloquent\SoftDeletes;
use Illuminate\Support\Facades\DB;
use App\Traits\HasAuditFields;
use Illuminate\Database\Eloquent\Relations\BelongsToMany;

/**
 * Role model with hierarchy helpers and audit fields.
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
    public function parents(): \Illuminate\Database\Eloquent\Relations\BelongsToMany
    {
        return $this->belongsToMany(Role::class, 'role_hierarchies', 'child_role_id', 'parent_role_id')
            ->select('roles.id', 'roles.name', 'roles.slug', 'roles.description', 'roles.is_active', 'roles.created_at', 'roles.updated_at')
            ->withTimestamps();
    }

    /**
     * Get child roles (many-to-many hierarchical).
     */
    public function children(): \Illuminate\Database\Eloquent\Relations\BelongsToMany
    {
        return $this->belongsToMany(Role::class, 'role_hierarchies', 'parent_role_id', 'child_role_id')
            ->select('roles.id', 'roles.name', 'roles.slug', 'roles.description', 'roles.is_active', 'roles.created_at', 'roles.updated_at')
            ->withTimestamps();
    }

    public function isSuperAdmin(): bool
    {
        return strtolower($this->name) === 'super-admin' || strtolower($this->name) === 'super admin';
    }

    public function isAdmin(): bool
    {
        return strtolower($this->name) === 'admin';
    }

    public function isProtected(): bool
    {
        return $this->isSuperAdmin() || $this->isAdmin();
    }

    public function users(): \Illuminate\Database\Eloquent\Relations\BelongsToMany
    {
        return $this->belongsToMany(User::class, 'model_has_roles', 'role_id', 'model_id')
            ->withTimestamps();
    }

    public function allDescendants()
    {
        return $this->children()->with('allDescendants');
    }

    public function allAncestors()
    {
        return $this->parents()->with('allAncestors');
    }

    public function getAllDescendantIds($allRoles = null)
    {
        $ids = collect([$this->id]);

        if ($allRoles !== null) {
            $childrenIds = DB::table('role_hierarchies')
                ->where('parent_role_id', $this->id)
                ->pluck('child_role_id');

            foreach ($childrenIds as $childId) {
                $child = $allRoles->firstWhere('id', $childId);
                if ($child) {
                    $ids = $ids->merge($child->getAllDescendantIds($allRoles));
                }
            }
        } else {
            foreach ($this->children as $child) {
                $ids = $ids->merge($child->getAllDescendantIds());
            }
        }

        return $ids->unique();
    }

    public function getAllDescendantIdsOptimized()
    {
        $allRoles = self::all()->keyBy('id');
        return $this->getAllDescendantIds($allRoles);
    }

    public function getAllAncestorIds($allRoles = null)
    {
        $ids = collect([]);

        if ($allRoles !== null) {
            $parentIds = DB::table('role_hierarchies')
                ->where('child_role_id', $this->id)
                ->pluck('parent_role_id');

            foreach ($parentIds as $parentId) {
                $parent = $allRoles->firstWhere('id', $parentId);
                if ($parent) {
                    $ids->push($parent->id);
                    $ids = $ids->merge($parent->getAllAncestorIds($allRoles));
                }
            }
        } else {
            foreach ($this->parents as $parent) {
                $ids->push($parent->id);
                $ids = $ids->merge($parent->getAllAncestorIds());
            }
        }

        return $ids->unique();
    }

    public function getAllAncestorIdsOptimized()
    {
        $allRoles = self::all()->keyBy('id');
        return $this->getAllAncestorIds($allRoles);
    }

    public function isAncestorOf(Role $role): bool
    {
        return $this->getAllDescendantIds()->contains($role->id);
    }

    public function isDescendantOf(Role $role): bool
    {
        return $this->getAllAncestorIds()->contains($role->id);
    }

    public function attachParent(Role $parent)
    {
        if ($this->id === $parent->id || $this->isAncestorOf($parent)) {
            throw new \Exception('Cannot create circular hierarchy.');
        }

        $this->parents()->syncWithoutDetaching([$parent->id]);
        return $this;
    }

    public function attachChild(Role $child)
    {
        if ($this->id === $child->id || $this->isDescendantOf($child)) {
            throw new \Exception('Cannot create circular hierarchy.');
        }

        $this->children()->syncWithoutDetaching([$child->id]);
        return $this;
    }

    public function detachParent(Role $parent)
    {
        $this->parents()->detach($parent->id);
        return $this;
    }

    public function detachChild(Role $child)
    {
        $this->children()->detach($child->id);
        return $this;
    }

    public function parent()
    {
        return $this->parents()->first();
    }

    public function allChildren()
    {
        return $this->allDescendants();
    }

    public function scopeActive($query)
    {
        return $query->where('is_active', true);
    }

    public function scopeByPriority($query)
    {
        return $query->orderBy('priority', 'asc');
    }
}

