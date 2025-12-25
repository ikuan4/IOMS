<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Spatie\Permission\Models\Permission as SpatiePermission;
// Soft deletes removed: permissions table in this project does not have deleted_at
use App\Traits\HasAuditFields;

class Permission extends SpatiePermission
{
    use HasFactory, HasAuditFields;

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

    public function scopeByGroup($query, $group)
    {
        return $query->where('group', $group);
    }

    public static function getAllGrouped()
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
                $parts = explode('.', $permission->slug);
                $action = end($parts);
                return $order[$action] ?? 999;
            })->values();
        });

        return $grouped;
    }
}
