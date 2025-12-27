<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Spatie\Permission\Models\Permission as SpatiePermission;
use Illuminate\Database\Eloquent\SoftDeletes;
use App\Traits\HasAuditFields;
use Illuminate\Database\Eloquent\Relations\Relation;
use Illuminate\Database\Eloquent\Builder;

/**
 * @property string $guard_name
 */
/**
 * @use \Illuminate\Database\Eloquent\Factories\HasFactory<\Database\Factories\PermissionFactory>
 */
class Permission extends SpatiePermission
{
    use HasFactory, SoftDeletes, HasAuditFields;

    /** @var string */
    protected $guard_name = 'web';

    protected $fillable = [
        'name',
        'slug',
        'guard_name',
        'group',
        'description',
        'created_by',
        'updated_by',
        'deleted_by',
        'restored_by',
    ];

    protected $casts = [
        'deleted_at' => 'datetime',
        'restored_at' => 'datetime',
        'deleted_by' => 'integer',
        'restored_by' => 'integer',
    ];

    /**
     * @param \Illuminate\Database\Eloquent\Builder<\App\Models\Permission> $query
     * @param string|null $group
     * @return \Illuminate\Database\Eloquent\Builder<\App\Models\Permission>
     */
    public function scopeByGroup(Builder $query, ?string $group): Builder
    {
        return $query->where('group', $group);
    }

    /**
     * @return \Illuminate\Support\Collection<(int|string), \Illuminate\Database\Eloquent\Collection<int, Permission>>
     */
    public static function getAllGrouped(): \Illuminate\Support\Collection
    {
        $permissions = static::orderBy('group')->orderBy('id')->get();

        $order = [
            'view' => 1,
            'create' => 2,
            'edit' => 3,
            'delete' => 4,
            'restore' => 5,
        ];

        $grouped = $permissions->groupBy('group')->map(function ($groupPermissions) use ($order) {
            return $groupPermissions->sortBy(function ($permission) use ($order) {
                $parts = explode('.', $permission->slug ?? '');
                $action = end($parts);
                return $order[$action] ?? 999;
            })->values();
        });

        return $grouped;
    }
}
