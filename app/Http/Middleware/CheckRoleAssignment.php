<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;

/**
 * Check if user has valid role assignment (global OR branch).
 * This middleware should run after authentication.
 */
class CheckRoleAssignment
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

        // Check if user has a valid global role (active, not deleted, global)
        if ($user->global_role_id) {
            $hasGlobalRole = \DB::table('roles')
                ->where('id', $user->global_role_id)
                ->whereNull('deleted_at')
                ->where('is_active', true)
                ->where('is_global', true)
                ->exists();

            if ($hasGlobalRole) {
                return $next($request);
            }
        }

        // Check if user has any valid branch role assignments
        $hasBranchRoles = \DB::table('branch_user_role as bur')
            ->join('roles as r', 'r.id', '=', 'bur.role_id')
            ->join('branches as b', 'b.id', '=', 'bur.branch_id')
            ->where('bur.user_id', $user->id)
            ->whereNull('r.deleted_at')
            ->whereNull('b.deleted_at')
            ->where('r.is_active', true)
            ->where('r.is_global', false)
            ->exists();

        if ($hasBranchRoles) {
            return $next($request);
        }

        // User has no role assignments - logout and show error
        \Auth::logout();
        $request->session()->invalidate();
        $request->session()->regenerateToken();

        return redirect()->route('login')
            ->withErrors(['error' => 'Your account does not have any assigned role. Please contact administrator.']);
    }
}
