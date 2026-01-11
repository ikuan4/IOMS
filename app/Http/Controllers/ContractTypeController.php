<?php

namespace App\Http\Controllers;

use App\Models\ContractType;
use App\Models\User;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Validation\Rule;

class ContractTypeController extends Controller
{
    /**
     * List contract types with pagination + search + status filter
     */
    public function index(Request $request)
    {
        if (!Auth::user()->hasPermission('contract-types.view')) {
            abort(403, 'Unauthorized action.');
        }

        $user = Auth::user();
        $search = $request->query('search');
        $status = $request->query('status', 'all');

        // Base query with branch filtering
        $baseQuery = ContractType::withTrashed()
            ->when(!$user->isSuperAdmin(), function ($q) use ($user) {
                $q->where('branch_id', $user->branch_id);
            });

        $statusCounts = [
            'all' => (clone $baseQuery)->count(),
            'active' => (clone $baseQuery)->whereNull('deleted_at')->where('is_active', true)->count(),
            'inactive' => (clone $baseQuery)->whereNull('deleted_at')->where('is_active', false)->count(),
            'deleted' => (clone $baseQuery)->onlyTrashed()->count(),
        ];

        // Main query for the table
        $query = ContractType::withTrashed()
            ->with(['branch', 'creator', 'updater'])
            ->when(!$user->isSuperAdmin(), function ($q) use ($user) {
                $q->where('branch_id', $user->branch_id);
            })
            ->orderBy('name');

        if ($search) {
            $query->where(function ($q) use ($search) {
                $q->where('name', 'like', "%{$search}%")
                    ->orWhere('description', 'like', "%{$search}%")
                    ->orWhere('code', 'like', "%{$search}%");
            });
        }

        switch ($status) {
            case 'active':
                $query->whereNull('deleted_at')->where('is_active', true);
                break;
            case 'inactive':
                $query->whereNull('deleted_at')->where('is_active', false);
                break;
            case 'deleted':
                $query->onlyTrashed();
                break;
            case 'all':
            default:
                break;
        }

        $contractTypes = $query->paginate(10)->withQueryString();

        return view('contract-types.index', compact(
            'contractTypes',
            'search',
            'status',
            'statusCounts'
        ));
    }

    /**
     * Show create form
     */
    public function create()
    {
        if (!Auth::user()->hasPermission('contract-types.create')) {
            abort(403, 'Unauthorized action.');
        }

        $contractType = new ContractType();

        return view('contract-types.create', compact('contractType'));
    }

    /**
     * Store new contract type
     */
    public function store(Request $request)
    {
        if (!Auth::user()->hasPermission('contract-types.create')) {
            abort(403, 'Unauthorized action.');
        }

        $user = Auth::user();
        $branchId = $user->branch_id;

        $data = $request->validate([
            'name' => [
                'required',
                'string',
                'max:255',
                Rule::unique('contract_types', 'name')
                    ->where('branch_id', $branchId),
            ],
            'description' => ['nullable', 'string', 'max:1000'],
            'is_active' => ['nullable', 'boolean'],
        ]);

        // Generate unique code
        $code = ContractType::generateCode($data['name'], $branchId);

        $contractType = new ContractType();
        $contractType->branch_id = $branchId;
        $contractType->name = $data['name'];
        $contractType->description = $data['description'] ?? null;
        $contractType->code = $code;
        $contractType->is_active = $request->boolean('is_active', true);
        $contractType->created_by = Auth::id();
        $contractType->updated_by = Auth::id();
        $contractType->save();

        return redirect()
            ->route('contract-types.index')
            ->with('status', 'Contract type "' . $contractType->name . '" created successfully.');
    }

    /**
     * Edit form
     */
    public function edit(ContractType $contractType)
    {
        if (!auth()->user()->hasPermission('contract-types.edit')) {
            abort(403, 'Unauthorized action.');
        }

        // Ensure user can only edit contract types from their branch
        if (!auth()->user()->isSuperAdmin() && $contractType->branch_id !== auth()->user()->branch_id) {
            abort(403, 'Unauthorized action.');
        }

        return view('contract-types.edit', compact('contractType'));
    }

    /**
     * Update existing contract type
     */
    public function update(Request $request, ContractType $contractType)
    {
        if (!auth()->user()->hasPermission('contract-types.edit')) {
            abort(403, 'Unauthorized action.');
        }

        // Ensure user can only edit contract types from their branch
        if (!auth()->user()->isSuperAdmin() && $contractType->branch_id !== auth()->user()->branch_id) {
            abort(403, 'Unauthorized action.');
        }

        $data = $request->validate([
            'name' => [
                'required',
                'string',
                'max:255',
                Rule::unique('contract_types', 'name')
                    ->where('branch_id', $contractType->branch_id)
                    ->ignore($contractType->id),
            ],
            'description' => ['nullable', 'string', 'max:1000'],
            'is_active' => ['nullable', 'boolean'],
        ]);

        $contractType->name = $data['name'];
        $contractType->description = $data['description'] ?? null;
        $contractType->is_active = $request->boolean('is_active');

        // Regenerate code if name changed
        if ($contractType->isDirty('name')) {
            $contractType->code = ContractType::generateCode($data['name'], $contractType->branch_id, $contractType->id);
        }

        $contractType->updated_by = auth()->id();
        $contractType->save();

        return redirect()
            ->route('contract-types.index')
            ->with('status', 'Contract type "' . $contractType->name . '" updated successfully.');
    }

    /**
     * Soft delete contract type
     */
    public function destroy(ContractType $contractType)
    {
        if (!auth()->user()->hasPermission('contract-types.delete')) {
            abort(403, 'Unauthorized action.');
        }

        // Ensure user can only delete contract types from their branch
        if (!auth()->user()->isSuperAdmin() && $contractType->branch_id !== auth()->user()->branch_id) {
            abort(403, 'Unauthorized action.');
        }

        $name = $contractType->name;
        $contractType->delete();

        return redirect()
            ->route('contract-types.index')
            ->with('deleted', 'Contract type "' . $name . '" deleted successfully.');
    }

    /**
     * Restore a soft-deleted contract type
     */
    public function restore(Request $request, $id)
    {
        if (!auth()->user()->hasPermission('contract-types.restore')) {
            abort(403, 'Unauthorized action.');
        }

        $contractType = ContractType::withTrashed()->findOrFail($id);

        // Ensure user can only restore contract types from their branch
        if (!auth()->user()->isSuperAdmin() && $contractType->branch_id !== auth()->user()->branch_id) {
            abort(403, 'Unauthorized action.');
        }

        if ($contractType->trashed()) {
            $contractType->restoreWithUser();
            $contractType->is_active = true;
            $contractType->updated_by = auth()->id();
            $contractType->save();

            $message = 'Contract type "' . $contractType->name . '" restored successfully.';
            $messageType = 'status';
        } else {
            $message = 'Contract type "' . $contractType->name . '" is not deleted.';
            $messageType = 'error';
        }

        $redirectParams = ['status' => 'deleted'];

        if ($request->filled('search')) {
            $redirectParams['search'] = $request->input('search');
        }

        return redirect()
            ->route('contract-types.index', $redirectParams)
            ->with($messageType, $message);
    }
}

