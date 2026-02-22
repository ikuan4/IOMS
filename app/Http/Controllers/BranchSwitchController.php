<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use App\Models\Branch;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;

class BranchSwitchController extends Controller
{
    /**
     * Show branch selection modal/page (if needed for future).
     */
    public function index()
    {
        $user = Auth::user();

        if ($user->global_role_id) {
            // Global users can access all branches
            $branches = Branch::whereNull('deleted_at')->orderBy('name')->get();
        } else {
            // Branch users can only access their assigned branches
            $branches = $user->branches()->whereNull('deleted_at')->orderBy('name')->get();
        }

        return view('branches.switch', compact('branches'));
    }

    /**
     * Switch to a different branch.
     */
    public function switch(Request $request)
    {
        $request->validate([
            'branch_id' => 'required|integer|exists:branches,id',
        ]);

        $branchId = $request->input('branch_id');
        $user = Auth::user();

        // Verify user has access to this branch
        if ($user->global_role_id) {
            // Global users can switch to any active branch
            $branch = Branch::where('id', $branchId)->whereNull('deleted_at')->first();
            if (!$branch) {
                if ($request->expectsJson()) {
                    return response()->json(['error' => 'Branch not found'], 404);
                }
                return back()->withErrors(['error' => 'Branch not found']);
            }
        } else {
            // Branch users must be assigned to the branch
            $hasAccess = \DB::table('branch_user')
                ->join('branches', 'branch_user.branch_id', '=', 'branches.id')
                ->where('branch_user.user_id', $user->id)
                ->where('branch_user.branch_id', $branchId)
                ->whereNull('branches.deleted_at')
                ->exists();

            if (!$hasAccess) {
                if ($request->expectsJson()) {
                    return response()->json(['error' => 'You do not have access to this branch'], 403);
                }
                return back()->withErrors(['error' => 'You do not have access to this branch']);
            }
        }

        // Set active branch in session
        session(['active_branch_id' => $branchId]);

        // Always redirect to dashboard (as per requirements)
        if ($request->expectsJson()) {
            return response()->json([
                'success' => true,
                'message' => 'Branch switched successfully',
                'redirect' => route('dashboard'),
            ]);
        }

        return redirect()->route('dashboard')->with('success', 'Branch switched successfully');
    }

    /**
     * Get current active branch (API endpoint).
     */
    public function current()
    {
        $user = Auth::user();
        $activeBranch = $user->activeBranch();

        if (!$activeBranch) {
            return response()->json(['active_branch' => null]);
        }

        return response()->json([
            'active_branch' => [
                'id' => $activeBranch->id,
                'name' => $activeBranch->name,
            ],
        ]);
    }
}
