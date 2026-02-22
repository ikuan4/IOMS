<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;
use Illuminate\Support\Facades\DB;

/**
 * Ensure authenticated branch users have an active branch set in their session.
 * Global users bypass this check.
 */
class EnsureActiveBranch
{
    /**
     * Handle an incoming request.
     *
     * @param  \Closure(\Illuminate\Http\Request): (\Symfony\Component\HttpFoundation\Response)  $next
     */
    public function handle(Request $request, Closure $next): Response
    {
        $user = $request->user();

        // Skip if user is not authenticated
        if (!$user) {
            return $next($request);
        }

        // Skip if user is a global user
        if ($user->global_role_id) {
            return $next($request);
        }

        // Branch user: must have active_branch_id in session
        $activeBranchId = session('active_branch_id');

        if ($activeBranchId) {
            $branchIsActive = \DB::table('branches')
                ->where('id', $activeBranchId)
                ->whereNull('deleted_at')
                ->exists();

            $hasAccess = \DB::table('branch_user')
                ->where('user_id', $user->id)
                ->where('branch_id', $activeBranchId)
                ->exists();

            if ($branchIsActive && $hasAccess) {
                return $next($request);
            }

            session()->forget('active_branch_id');
        }

        // Try to auto-initialize from user's first available branch
        $firstBranch = \DB::table('branch_user_role as bur')
            ->join('roles as r', 'r.id', '=', 'bur.role_id')
            ->join('branches as b', 'b.id', '=', 'bur.branch_id')
            ->where('bur.user_id', $user->id)
            ->whereNull('r.deleted_at')
            ->whereNull('b.deleted_at')
            ->where('r.is_active', true)
            ->where('r.is_global', false)
            ->select('bur.branch_id')
            ->first();

        if ($firstBranch) {
            session(['active_branch_id' => $firstBranch->branch_id]);
            return $next($request);
        }

        // No branches available - this should not happen if login validation works
        return redirect()->route('login')
            ->withErrors(['error' => 'Your account does not have access to any branches. Please contact administrator.']);
    }
}
