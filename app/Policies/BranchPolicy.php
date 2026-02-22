<?php

namespace App\Policies;

use App\Models\User;
use App\Models\Branch;
use Illuminate\Support\Facades\DB;

class BranchPolicy
{
    public function viewAny(User $user): bool
    {
        if ($user->isSuperAdmin()) return true;
        return $user->hasPermission('branches.view');
    }

    public function view(User $user, Branch $branch): bool
    {
        if ($user->isSuperAdmin()) return true;

        if (!$user->hasPermission('branches.view')) {
            return false;
        }

        // Branch users can only view branches they're assigned to
        if (!$user->global_role_id) {
            $hasAccessToBranch = \DB::table('branch_user')
                ->where('user_id', $user->id)
                ->where('branch_id', $branch->id)
                ->exists();
            
            return $hasAccessToBranch;
        }

        return true;
    }

    public function create(User $user): bool
    {
        if ($user->isSuperAdmin()) return true;
        return $user->hasPermission('branches.create');
    }

    public function update(User $user, Branch $branch): bool
    {
        if ($user->isSuperAdmin()) return true;

        if (!$user->hasPermission('branches.edit')) {
            return false;
        }

        // Branch users can only edit their active branch
        if (!$user->global_role_id) {
            $activeBranchId = session('active_branch_id');
            if (!$activeBranchId) {
                return false;
            }
            
            return $branch->id === $activeBranchId;
        }

        return true;
    }

    public function delete(User $user, Branch $branch): bool
    {
        if ($user->isSuperAdmin()) return true;

        if (!$user->hasPermission('branches.delete')) {
            return false;
        }

        // Branch users can only delete their active branch
        if (!$user->global_role_id) {
            $activeBranchId = session('active_branch_id');
            if (!$activeBranchId) {
                return false;
            }
            
            return $branch->id === $activeBranchId;
        }

        return true;
    }

    public function restore(User $user, Branch $branch): bool
    {
        if ($user->isSuperAdmin()) return true;

        if (!$user->hasPermission('branches.restore')) {
            return false;
        }

        // Branch users can only restore branches they're assigned to
        if (!$user->global_role_id) {
            $hasAccessToBranch = \DB::table('branch_user')
                ->where('user_id', $user->id)
                ->where('branch_id', $branch->id)
                ->exists();
            
            return $hasAccessToBranch;
        }

        return true;
    }

    public function export(User $user, Branch $branch): bool
    {
        if ($user->isSuperAdmin()) return true;

        if (!$user->hasPermission('branches.export')) {
            return false;
        }

        // Branch users can only export branches they're assigned to
        if (!$user->global_role_id) {
            $hasAccessToBranch = \DB::table('branch_user')
                ->where('user_id', $user->id)
                ->where('branch_id', $branch->id)
                ->exists();
            
            return $hasAccessToBranch;
        }

        return true;
    }
}
