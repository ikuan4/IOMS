<?php

namespace App\Http\Controllers;

use App\Models\Branch;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;

class BranchController extends Controller
{
    public function index(Request $request)
    {
        $this->authorize('viewAny', Branch::class);
        // include trashed so deleted branches can be restored from the index
        $query = Branch::withTrashed();
        if ($request->has('search') && $request->search) {
            $s = $request->search;
            $query->where('name', 'like', "%{$s}%");
        }
        $branches = $query->orderBy('id', 'asc')->paginate(15)->withQueryString();
        return view('branches.index', compact('branches'));
    }

    public function create()
    {
        $this->authorize('create', Branch::class);
        return view('branches.create');
    }

    public function store(Request $request)
    {
        $this->authorize('create', Branch::class);
        $validated = $request->validate([
            'name' => 'required|string|max:255|unique:branches,name',
        ]);
        $branch = Branch::create([
            'name' => $validated['name'],
            'created_by' => Auth::id(),
        ]);
        return redirect()->route('branches.index')->with('status', 'Branch created.');
    }

    public function edit(Branch $branch)
    {
        $this->authorize('update', $branch);
        return view('branches.edit', compact('branch'));
    }

    public function show(Request $request, Branch $branch)
    {
        $this->authorize('view', $branch);

        $usersQuery = $branch->users()->with('role');
        if ($request->filled('search')) {
            $s = $request->search;
            $usersQuery->where(function($q) use ($s) {
                $q->where('name', 'like', "%{$s}%")
                  ->orWhere('email', 'like', "%{$s}%")
                  ->orWhere('mobile', 'like', "%{$s}%");
            });
        }

        $users = $usersQuery->orderBy('name')->paginate(15)->withQueryString();

        if ($request->ajax()) {
            return view('branches._users_table', compact('users'));
        }

        return view('branches.show', compact('branch', 'users'));
    }

    public function export(Request $request, Branch $branch)
    {
        $this->authorize('export', $branch);

        $usersQuery = $branch->users()->with('role');
        if ($request->filled('search')) {
            $s = $request->search;
            $usersQuery->where(function($q) use ($s) {
                $q->where('name', 'like', "%{$s}%")
                  ->orWhere('email', 'like', "%{$s}%")
                  ->orWhere('mobile', 'like', "%{$s}%");
            });
        }

        $filename = 'branch-' . $branch->id . '-users-' . date('Ymd_His') . '.csv';

        $callback = function() use ($usersQuery) {
            $handle = fopen('php://output', 'w');
            fputcsv($handle, ['ID','Name','Email','Mobile','Roles','Status','Created At']);
            $usersQuery->chunk(200, function($users) use ($handle) {
                foreach ($users as $u) {
                    $roles = $u->roles->pluck('name')->join('|');
                    fputcsv($handle, [
                        $u->id,
                        $u->name,
                        $u->email,
                        $u->mobile,
                        $roles,
                        $u->active ? 'Active' : 'Inactive',
                        optional($u->created_at)->toDateTimeString(),
                    ]);
                }
            });
            fclose($handle);
        };

        return response()->streamDownload($callback, $filename, [
            'Content-Type' => 'text/csv',
            'Content-Disposition' => 'attachment; filename="' . $filename . '"',
        ]);
    }

    public function update(Request $request, Branch $branch)
    {
        $this->authorize('update', $branch);
        $validated = $request->validate([
            'name' => 'required|string|max:255|unique:branches,name,' . $branch->id,
        ]);
        $branch->update([
            'name' => $validated['name'],
            'updated_by' => Auth::id(),
        ]);
        return redirect()->route('branches.index')->with('status', 'Branch updated.');
    }

    public function destroy(Branch $branch)
    {
        $this->authorize('delete', $branch);
        if ($branch->users()->count()) {
            return redirect()->route('branches.index')->with('error', 'Cannot delete branch with users.');
        }
        $branch->delete();
        return redirect()->route('branches.index')->with('deleted', 'Branch deleted.');
    }

    public function restore($id)
    {
        $branch = Branch::withTrashed()->findOrFail($id);
        $this->authorize('restore', $branch);
        if (! $branch->trashed()) {
            return redirect()->route('branches.index')->with('status', 'Branch is not deleted.');
        }
        $branch->restore();
        return redirect()->route('branches.index')->with('status', 'Branch restored.');
    }
}
