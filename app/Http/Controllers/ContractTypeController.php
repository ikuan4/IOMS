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
    public function index(Request $request): mixed
    {
        $user = Auth::user();
        if (!$user || !$user->hasPermission('contract-types.view')) {
            abort(403, 'Unauthorized action.');
        }
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

        // Respect per-page selection from query, with a safe whitelist and default 10
        $allowed = [5,10,15,20,30];
        $perPage = (int) $request->query('per_page', 10);
        if (!in_array($perPage, $allowed, true)) {
            $perPage = 10;
        }

        $contractTypes = $query->paginate($perPage)->withQueryString();

        // Table refresh uses AJAX, but SPA navigation also uses XHR fetch.
        // SPA navigation needs a full HTML document containing <main.main>.
        $isSpaNavigation = strtolower((string) $request->header('X-SPA-Navigation')) === 'true';
        if ($request->ajax() && ! $isSpaNavigation) {
            /** @var view-string $view */
            $view = 'contract-types._contract_types_table';
            return view($view, compact('contractTypes', 'search', 'status'));
        }

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
    public function create(): mixed
    {
        $user = Auth::user();
        if (!$user || !$user->hasPermission('contract-types.create')) {
            abort(403, 'Unauthorized action.');
        }

        $contractType = new ContractType();

        return view('contract-types.create', compact('contractType'));
    }

    /**
     * Store new contract type
     */
    public function store(Request $request): mixed
    {
        $user = Auth::user();
        if (!$user || !$user->hasPermission('contract-types.create')) {
            abort(403, 'Unauthorized action.');
        }
        $branchId = $user->branch_id;
        if ($branchId === null) {
            return back()->withErrors(['branch_id' => 'User branch is not set.']);
        }

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
        $code = ContractType::generateCode($data['name'], (int) $branchId);

        $contractType = new ContractType();
        $contractType->branch_id = (int) $branchId;
        $contractType->name = $data['name'];
        $contractType->description = $data['description'] ?? null;
        $contractType->code = $code;
        $contractType->is_active = $request->boolean('is_active', true);
        $userId = Auth::id();
        /** @var int<0, max>|null $userIdInt */
        $userIdInt = null;
        if (is_int($userId)) {
            $userIdInt = $userId;
        } elseif (is_string($userId) && ctype_digit($userId)) {
            $userIdInt = (int) $userId;
        }
        if ($userIdInt !== null && $userIdInt < 0) {
            $userIdInt = null;
        }
        $contractType->created_by = $userIdInt;
        $contractType->updated_by = $userIdInt;
        $contractType->save();

        return redirect()
            ->route('contract-types.index')
            ->with('status', 'Contract type "' . $contractType->name . '" created successfully.');
    }

    /**
     * Edit form
     */
    public function edit(ContractType $contractType): mixed
    {
        $user = Auth::user();
        if (!$user || !$user->hasPermission('contract-types.edit')) {
            abort(403, 'Unauthorized action.');
        }

        // Ensure user can only edit contract types from their branch
        if (!$user->isSuperAdmin() && $contractType->branch_id !== $user->branch_id) {
            abort(403, 'Unauthorized action.');
        }

        return view('contract-types.edit', compact('contractType'));
    }

    /**
     * Update existing contract type
     */
    public function update(Request $request, ContractType $contractType): mixed
    {
        $user = Auth::user();
        if (!$user || !$user->hasPermission('contract-types.edit')) {
            abort(403, 'Unauthorized action.');
        }

        // Ensure user can only edit contract types from their branch
        if (!$user->isSuperAdmin() && $contractType->branch_id !== $user->branch_id) {
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

        $userId = Auth::id();
        /** @var int<0, max>|null $userIdInt */
        $userIdInt = null;
        if (is_int($userId)) {
            $userIdInt = $userId;
        } elseif (is_string($userId) && ctype_digit($userId)) {
            $userIdInt = (int) $userId;
        }
        if ($userIdInt !== null && $userIdInt < 0) {
            $userIdInt = null;
        }
        $contractType->updated_by = $userIdInt;
        $contractType->save();

        return redirect()
            ->route('contract-types.index')
            ->with('status', 'Contract type "' . $contractType->name . '" updated successfully.');
    }

    /**
     * Soft delete contract type
     */
    public function destroy(ContractType $contractType): mixed
    {
        $user = Auth::user();
        if (!$user || !$user->hasPermission('contract-types.delete')) {
            abort(403, 'Unauthorized action.');
        }

        // Ensure user can only delete contract types from their branch
        if (!$user->isSuperAdmin() && $contractType->branch_id !== $user->branch_id) {
            abort(403, 'Unauthorized action.');
        }

        // Requirement: allow deleting contract type if it only has inactive contracts.
        // Block deletion only when ACTIVE (not-deleted) contracts exist.
        // Note: Modal validation should prevent reaching here if dependencies exist
        $activeContractCount = $contractType->contracts()
            ->where('is_active', true)
            ->whereNull('deleted_at')
            ->count();

        if ($activeContractCount > 0) {
            // Fallback validation (should not reach here if modal is used)
            return redirect()
                ->route('contract-types.index')
                ->with('error', 'Cannot delete contract type due to active dependencies.');
        }

        $name = $contractType->name;
        $contractType->delete();

        return redirect()
            ->route('contract-types.index')
            ->with('deleted', 'Contract type "' . $name . '" deleted successfully.');
    }

    /**
     * Check if a contract type can be deleted (check for dependencies)
     */
    public function checkDeleteDependencies(ContractType $contractType): \Illuminate\Http\JsonResponse
    {
        $user = Auth::user();
        if (!$user || !$user->hasPermission('contract-types.view')) {
            abort(403, 'Unauthorized action.');
        }

        // Ensure user can only check contract types from their branch
        if (!$user->isSuperAdmin() && $contractType->branch_id !== $user->branch_id) {
            abort(403, 'Unauthorized action.');
        }

        // Performance: count() + small samples
        // Requirement: only ACTIVE contracts block deletion.
        $activeContractsQuery = $contractType->contracts()
            ->where('is_active', true)
            ->whereNull('deleted_at');

        $activeContractCount = (clone $activeContractsQuery)->count();
        $activeContractsSample = (clone $activeContractsQuery)
            ->orderBy('id')
            ->limit(5)
            ->get(['id', 'contract_number', 'contract_with']);

        $inactiveContractCount = $contractType->contracts()
            ->where('is_active', false)
            ->whereNull('deleted_at')
            ->count();

        $canDelete = $activeContractCount === 0;

        $dependencies = [];
        if ($activeContractCount > 0) {
            $dependencies[] = [
                'type' => 'active_contracts',
                'count' => $activeContractCount,
                'message' => 'Active contracts using this contract type',
                'items' => $activeContractsSample->map(fn($c) => [
                    'id' => $c->id,
                    'name' => $c->contract_number . ' - ' . $c->contract_with
                ])->toArray(),
            ];
        }

        // Inactive contracts do not block deletion; include counts for UX.
        if ($inactiveContractCount > 0) {
            $dependencies[] = [
                'type' => 'inactive_contracts_info',
                'count' => $inactiveContractCount,
                'message' => 'Inactive contracts using this type (allowed)',
                'items' => [],
            ];
        }

        $errorMessage = 'Contract type can be deleted safely.';
        if (!$canDelete && $activeContractCount > 0) {
            $errorMessage = "Cannot delete contract type '{$contractType->name}' because it has {$activeContractCount} active contract" . ($activeContractCount > 1 ? 's' : '') . ". Please set them inactive or delete them first.";
        }

        return response()->json([
            'can_delete' => $canDelete,
            'dependencies' => $dependencies,
            'message' => $errorMessage
        ]);
    }

    /**
     * Restore a soft-deleted contract type
     */
    public function restore(Request $request, int|string $id): mixed
    {
        $user = Auth::user();
        if (!$user || !$user->hasPermission('contract-types.restore')) {
            abort(403, 'Unauthorized action.');
        }

        $contractType = ContractType::withTrashed()->findOrFail($id);

        // Ensure user can only restore contract types from their branch
        if (!$user->isSuperAdmin() && $contractType->branch_id !== $user->branch_id) {
            abort(403, 'Unauthorized action.');
        }

        if ($contractType->trashed()) {
            $contractType->restoreWithUser();
            $contractType->is_active = true;
            $userId = Auth::id();
            /** @var int<0, max>|null $userIdInt */
            $userIdInt = null;
            if (is_int($userId)) {
                $userIdInt = $userId;
            } elseif (is_string($userId) && ctype_digit($userId)) {
                $userIdInt = (int) $userId;
            }
            if ($userIdInt !== null && $userIdInt < 0) {
                $userIdInt = null;
            }
            $contractType->updated_by = $userIdInt;
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

