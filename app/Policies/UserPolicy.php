<?php

namespace App\Policies;

use App\Models\User;
use Illuminate\Support\Facades\DB;

class UserPolicy
{
    public function viewAny(User $user): bool
    {
        return $user->hasPermission('users.view');
    }

    public function view(User $user, User $model): bool
    {
        if (!$user->hasPermission('users.view')) {
            return false;
        }

        if ($user->isSuperAdmin()) {
            return true;
        }

        // Branch users can only view users in their active branch
        $activeBranchId = session('active_branch_id');
        if (!$activeBranchId) {
            return false;
        }

        // Check if model has role in active branch
        $modelHasRoleInBranch = \DB::table('branch_user_role')
            ->where('user_id', $model->id)
            ->where('branch_id', $activeBranchId)
            ->exists();

        if (!$modelHasRoleInBranch && !$model->global_role_id) {
            return false;
        }

        // Check hierarchy: can only view users with lower hierarchy (higher priority number)
        $userEffectiveRole = $user->effectiveRole();
        $userPriority = $userEffectiveRole ? ($userEffectiveRole->priority ?? 999) : 999;
        $modelEffectiveRole = $model->effectiveRole();
        $modelPriority = $modelEffectiveRole ? ($modelEffectiveRole->priority ?? 999) : 999;

        return $modelPriority > $userPriority;
    }

    public function create(User $user): bool
    {
        return $user->hasPermission('users.create');
    }

    public function update(User $user, User $model): bool
    {
        // Only the developer can edit the developer user
        if ($model->isSuperAdmin() && !$user->isSuperAdmin()) {
            return false;
        }

        if (!$user->hasPermission('users.edit')) {
            return false;
        }

        if ($user->isSuperAdmin()) {
            return true;
        }

        // Branch users can only update users in their active branch
        $activeBranchId = session('active_branch_id');
        if (!$activeBranchId) {
            return false;
        }

        // Check if model has role in active branch
        $modelHasRoleInBranch = \DB::table('branch_user_role')
            ->where('user_id', $model->id)
            ->where('branch_id', $activeBranchId)
            ->exists();

        if (!$modelHasRoleInBranch && !$model->global_role_id) {
            return false;
        }

        // Check hierarchy: can only update users with lower hierarchy (higher priority number)
        $userEffectiveRole = $user->effectiveRole();
        $userPriority = $userEffectiveRole ? ($userEffectiveRole->priority ?? 999) : 999;
        $modelEffectiveRole = $model->effectiveRole();
        $modelPriority = $modelEffectiveRole ? ($modelEffectiveRole->priority ?? 999) : 999;

        return $modelPriority > $userPriority;
    }

    public function delete(User $user, User $model): bool
    {
        // Prevent deleting the developer user
        if ($model->isSuperAdmin()) {
            return false;
        }

        if (!$user->hasPermission('users.delete')) {
            return false;
        }

        if ($user->isSuperAdmin()) {
            return true;
        }

        // Branch users can only delete users in their active branch
        $activeBranchId = session('active_branch_id');
        if (!$activeBranchId) {
            return false;
        }

        // Check if model has role in active branch
        $modelHasRoleInBranch = \DB::table('branch_user_role')
            ->where('user_id', $model->id)
            ->where('branch_id', $activeBranchId)
            ->exists();

        if (!$modelHasRoleInBranch && !$model->global_role_id) {
            return false;
        }

        // Check hierarchy: can only delete users with lower hierarchy (higher priority number)
        $userEffectiveRole = $user->effectiveRole();
        $userPriority = $userEffectiveRole ? ($userEffectiveRole->priority ?? 999) : 999;
        $modelEffectiveRole = $model->effectiveRole();
        $modelPriority = $modelEffectiveRole ? ($modelEffectiveRole->priority ?? 999) : 999;

        return $modelPriority > $userPriority;
    }

    public function restore(User $user, User $model): bool
    {
        if (!$user->hasPermission('users.restore')) {
            return false;
        }

        if ($user->isSuperAdmin()) {
            return true;
        }

        // Branch users can only restore users in their active branch
        $activeBranchId = session('active_branch_id');
        if (!$activeBranchId) {
            return false;
        }

        // Check if model has role in active branch
        $modelHasRoleInBranch = \DB::table('branch_user_role')
            ->where('user_id', $model->id)
            ->where('branch_id', $activeBranchId)
            ->exists();

        if (!$modelHasRoleInBranch && !$model->global_role_id) {
            return false;
        }

        // Check hierarchy: can only restore users with lower hierarchy (higher priority number)
        $userEffectiveRole = $user->effectiveRole();
        $userPriority = $userEffectiveRole ? ($userEffectiveRole->priority ?? 999) : 999;
        $modelEffectiveRole = $model->effectiveRole();
        $modelPriority = $modelEffectiveRole ? ($modelEffectiveRole->priority ?? 999) : 999;

        return $modelPriority > $userPriority;
    }
}
