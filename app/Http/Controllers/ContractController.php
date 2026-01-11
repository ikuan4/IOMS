<?php

namespace App\Http\Controllers;

use App\Models\Contract;
use App\Models\ContractReminder;
use App\Models\ContractType;
use App\Models\ContractVersion;
use App\Models\ContractVersionFile;
use App\Models\NotificationRecipient;
use App\Models\StoredFile;
use App\Models\Branch;
use App\Models\User;
use App\Exports\ContractExport;
use App\Mail\ContractExpiryNotification;
use Illuminate\Http\Request;
use Illuminate\Support\Carbon;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Facades\Mail;
use Illuminate\Pagination\LengthAwarePaginator;
use Illuminate\Pagination\Paginator;
use Maatwebsite\Excel\Facades\Excel;

class ContractController extends Controller
{
    /**
     * List contracts with search + status cards + branch filtering
     */
    public function index(Request $request)
    {
        if (!Auth::user()->hasPermission('contracts.view')) {
            abort(403, 'Unauthorized action.');
        }

        $user = Auth::user();
        $search = $request->query('search');
        $status = $request->query('status', 'all');
        $contractTypeId = $request->query('contract_type_id');
        $branchId = $request->query('branch_id');

        // Branch filtering: Super admin can select branch, others see only their branch
        if ($user->isSuperAdmin()) {
            $selectedBranchId = $branchId;
            $branches = Branch::orderBy('name')->get();
        } else {
            $selectedBranchId = $user->branch_id;
            $branches = collect();
        }

        // Base query for counts
        $allForCounts = Contract::with(['latestVersion'])
            ->withTrashed()
            ->when($selectedBranchId, function ($q) use ($selectedBranchId) {
                $q->where('branch_id', $selectedBranchId);
            })
            ->when($contractTypeId, function ($q) use ($contractTypeId) {
                $q->where('contract_type_id', $contractTypeId);
            })
            ->get();

        $notDeleted = $allForCounts->filter(fn($c) => $c->deleted_at === null);

        $statusCounts = [
            'all' => $allForCounts->count(),
            'ongoing' => $notDeleted->where('status', 'Ongoing')->count(),
            'pending' => $notDeleted->where('status', 'Pending')->count(),
            'expiring_soon' => $notDeleted->where('status', 'Expiring Soon')->count(),
            'expired' => $notDeleted->where('status', 'Expired')->count(),
            'inactive' => $notDeleted->where('status', 'Inactive')->count(),
            'deleted' => $allForCounts->filter(fn($c) => $c->deleted_at !== null)->count(),
        ];

        // Main list query
        $query = Contract::with([
            'branch',
            'contractType',
            'creator',
            'updater',
            'latestVersion',
        ])
            ->withTrashed()
            ->when($selectedBranchId, function ($q) use ($selectedBranchId) {
                $q->where('branch_id', $selectedBranchId);
            })
            ->when($contractTypeId, function ($q) use ($contractTypeId) {
                $q->where('contract_type_id', $contractTypeId);
            })
            ->orderByDesc('created_at');

        // Search
        if ($search) {
            $query->where(function ($q) use ($search) {
                $q->where('contract_number', 'like', "%{$search}%")
                    ->orWhere('contract_with', 'like', "%{$search}%")
                    ->orWhereHas('latestVersion', function ($q2) use ($search) {
                        $q2->where('description', 'like', "%{$search}%");
                    });
            });
        }

        // Fetch and filter by derived status
        $contractsCollection = $query->get();

        if ($status !== 'all') {
            $contractsCollection = $contractsCollection->filter(function ($contract) use ($status) {
                if ($status === 'deleted') {
                    return $contract->deleted_at !== null;
                }

                if ($contract->deleted_at !== null) {
                    return false;
                }

                return match ($status) {
                    'ongoing' => $contract->status === 'Ongoing',
                    'pending' => $contract->status === 'Pending',
                    'expiring_soon' => $contract->status === 'Expiring Soon',
                    'expired' => $contract->status === 'Expired',
                    'inactive' => $contract->status === 'Inactive',
                    default => true,
                };
            });
        }

        // Manual pagination
        $perPage = 10;
        $page = Paginator::resolveCurrentPage('page');
        $total = $contractsCollection->count();

        $items = $contractsCollection
            ->slice(($page - 1) * $perPage, $perPage)
            ->values();

        $contracts = new LengthAwarePaginator(
            $items,
            $total,
            $perPage,
            $page,
            [
                'path' => Paginator::resolveCurrentPath(),
                'query' => $request->query(),
            ]
        );

        // Fetch contract types for current branch
        $contractTypes = ContractType::withTrashed()
            ->when($selectedBranchId, function ($q) use ($selectedBranchId) {
                $q->where('branch_id', $selectedBranchId);
            })
            ->orderBy('name', 'asc')
            ->get();

        return view('contracts.index', compact(
            'contracts',
            'search',
            'status',
            'statusCounts',
            'contractTypes',
            'contractTypeId',
            'branches',
            'selectedBranchId'
        ));
    }

    /**
     * Show create form
     */
    public function create()
    {
        if (!Auth::user()->hasPermission('contracts.create')) {
            abort(403, 'Unauthorized action.');
        }

        $user = Auth::user();
        $contract = new Contract();

        // Only active, non-deleted types from user's branch
        $contractTypes = ContractType::where('is_active', true)
            ->where('branch_id', $user->branch_id)
            ->whereNull('deleted_at')
            ->orderBy('name')
            ->get();

        // Active recipients from user's branch
        $recipients = NotificationRecipient::where('is_active', true)
            ->where('branch_id', $user->branch_id)
            ->whereNull('deleted_at')
            ->orderBy('name')
            ->get();

        return view('contracts.create', compact('contract', 'contractTypes', 'recipients'));
    }

    /**
     * Store new contract + version + files + reminders + recipients
     */
    public function store(Request $request)
    {
        if (!Auth::user()->hasPermission('contracts.create')) {
            abort(403, 'Unauthorized action.');
        }

        $data = $request->validate([
            'contract_type_id' => ['required', 'exists:contract_types,id'],
            'contract_with' => ['required', 'string', 'max:255'],
            'grace_period_days' => ['required', 'integer', 'min:0'],
            'description' => ['nullable', 'string'],
            'start_date' => ['required', 'date'],
            'end_date' => ['required', 'date', 'after_or_equal:start_date'],
            'reminder_days' => ['nullable', 'array'],
            'reminder_days.*' => ['nullable', 'integer', 'min:0'],
            'recipient_ids' => ['nullable', 'array'],
            'recipient_ids.*' => ['integer', 'exists:notification_recipients,id'],
            'files' => ['nullable', 'array'],
            'files.*' => [
                'file',
                'max:20480',
                'mimes:pdf,doc,docx,xls,xlsx,odt,ods,txt,png,jpg,jpeg,gif',
            ],
        ]);

        $userId = Auth::id();
        $user = Auth::user();
        $branchId = $user->branch_id;

        DB::transaction(function () use ($request, $data, $userId, $branchId) {
            // Parse dates as IST, store as UTC
            $startIst = Carbon::parse($data['start_date'], 'Asia/Kolkata');
            $endIst = Carbon::parse($data['end_date'], 'Asia/Kolkata');
            $startUtc = $startIst->copy()->timezone('UTC');
            $endUtc = $endIst->copy()->timezone('UTC');

            $type = ContractType::findOrFail($data['contract_type_id']);

            // Create contract with temporary number
            $contract = new Contract();
            $contract->branch_id = $branchId;
            $contract->contract_type_id = $type->id;
            $contract->contract_number = 'TEMP';
            $contract->contract_with = $data['contract_with'];
            $contract->grace_period_days = $data['grace_period_days'];
            $contract->is_active = true;
            $contract->created_by = $userId;
            $contract->updated_by = $userId;
            $contract->save();

            // Generate contract number: CT-{BRANCH_ID}/{TYPE_CODE}/{YYYY}/{id}
            $year = $startIst->year;
            $contract->contract_number = "CT-{$branchId}/{$type->code}/{$year}/{$contract->id}";
            $contract->save();

            // Create version 1
            $version = new ContractVersion();
            $version->contract_id = $contract->id;
            $version->version_number = 1;
            $version->description = $data['description'] ?? null;
            $version->start_date = $startUtc;
            $version->end_date = $endUtc;
            $version->created_by = $userId;
            $version->updated_by = $userId;
            $version->save();

            // Handle file uploads
            if ($request->hasFile('files')) {
                $files = $request->file('files');
                $order = 1;

                foreach ($files as $uploadedFile) {
                    if (!$uploadedFile->isValid()) {
                        continue;
                    }

                    $sha256 = hash_file('sha256', $uploadedFile->getRealPath());

                    // Check for existing file in this branch
                    $stored = StoredFile::where('branch_id', $branchId)
                        ->where('sha256', $sha256)
                        ->first();

                    if (!$stored) {
                        // Store in branches/{branch_id}/contracts/
                        $path = $uploadedFile->store("branches/{$branchId}/contracts", 'local');

                        $stored = StoredFile::create([
                            'branch_id' => $branchId,
                            'disk' => 'local',
                            'path' => $path,
                            'original_filename' => $uploadedFile->getClientOriginalName(),
                            'mime_type' => $uploadedFile->getMimeType(),
                            'size_bytes' => $uploadedFile->getSize(),
                            'sha256' => $sha256,
                        ]);
                    }

                    ContractVersionFile::create([
                        'contract_version_id' => $version->id,
                        'stored_file_id' => $stored->id,
                        'display_order' => $order++,
                    ]);
                }
            }

            // Create reminders
            $reminderDays = collect($data['reminder_days'] ?? [])
                ->filter(fn($v) => $v !== null && $v !== '')
                ->map(fn($v) => (int) $v)
                ->unique()
                ->values();

            foreach ($reminderDays as $days) {
                ContractReminder::create([
                    'contract_id' => $contract->id,
                    'days_before_end' => $days,
                    'is_sent' => false,
                    'sent_at' => null,
                ]);
            }

            // Sync recipients
            $recipientIds = collect($data['recipient_ids'] ?? [])
                ->filter()
                ->map(fn($v) => (int) $v)
                ->unique()
                ->values();

            if ($recipientIds->isNotEmpty()) {
                $contract->notificationRecipients()->sync($recipientIds->all());
            }
        });

        return redirect()
            ->route('contracts.index')
            ->with('status', 'Contract created successfully.');
    }

    /**
     * Show contract details with versions
     */
    public function show(Contract $contract)
    {
        if (!Auth::user()->hasPermission('contracts.versions.view')) {
            abort(403, 'Unauthorized action.');
        }

        // Branch access check
        if (!Auth::user()->isSuperAdmin() && $contract->branch_id !== Auth::user()->branch_id) {
            abort(403, 'Unauthorized action.');
        }

        $contract->load([
            'branch',
            'contractType',
            'versions' => function ($q) {
                $q->withTrashed()->orderBy('version_number', 'desc');
            },
            'versions.files.storedFile',
            'creator',
            'updater',
            'reminders',
            'notificationRecipients',
        ]);

        return view('contracts.show', compact('contract'));
    }

    /**
     * Show edit form
     */
    public function edit(Contract $contract)
    {
        if (!Auth::user()->hasPermission('contracts.edit')) {
            abort(403, 'Unauthorized action.');
        }

        // Branch access check
        if (!Auth::user()->isSuperAdmin() && $contract->branch_id !== Auth::user()->branch_id) {
            abort(403, 'Unauthorized action.');
        }

        $contract->load(['latestVersion.files.storedFile']);

        $contractTypes = ContractType::where('branch_id', $contract->branch_id)
            ->whereNull('deleted_at')
            ->orderBy('name')
            ->get();

        $recipients = NotificationRecipient::where('is_active', true)
            ->where('branch_id', $contract->branch_id)
            ->whereNull('deleted_at')
            ->orderBy('name')
            ->get();

        $reminders = $contract->reminders()->orderBy('days_before_end')->get();

        return view('contracts.edit', compact(
            'contract',
            'contractTypes',
            'recipients',
            'reminders'
        ));
    }

    /**
     * Update contract + latest version
     */
    public function update(Request $request, Contract $contract)
    {
        if (!Auth::user()->hasPermission('contracts.edit')) {
            abort(403, 'Unauthorized action.');
        }

        // Branch access check
        if (!Auth::user()->isSuperAdmin() && $contract->branch_id !== Auth::user()->branch_id) {
            abort(403, 'Unauthorized action.');
        }

        $data = $request->validate([
            'contract_type_id' => ['required', 'exists:contract_types,id'],
            'contract_with' => ['required', 'string', 'max:255'],
            'grace_period_days' => ['required', 'integer', 'min:0'],
            'is_active' => ['nullable', 'boolean'],
            'description' => ['nullable', 'string'],
            'start_date' => ['required', 'date'],
            'end_date' => ['required', 'date', 'after_or_equal:start_date'],
            'reminder_days' => ['nullable', 'array'],
            'reminder_days.*' => ['nullable', 'integer', 'min:0'],
            'recipient_ids' => ['nullable', 'array'],
            'recipient_ids.*' => ['integer', 'exists:notification_recipients,id'],
            'remove_files' => ['nullable', 'array'],
            'remove_files.*' => ['nullable', 'integer', 'exists:contract_version_files,id'],
            'files' => ['nullable', 'array'],
            'files.*' => [
                'file',
                'max:20480',
                'mimes:pdf,doc,docx,xls,xlsx,odt,ods,txt,png,jpg,jpeg,gif',
            ],
        ]);

        $userId = Auth::id();

        DB::transaction(function () use ($request, $contract, $data, $userId) {
            $contract->contract_type_id = $data['contract_type_id'];
            $contract->contract_with = $data['contract_with'];
            $contract->grace_period_days = $data['grace_period_days'];
            $contract->is_active = $request->boolean('is_active');
            $contract->updated_by = $userId;
            $contract->save();

            $version = $contract->latestVersion;

            $startIst = Carbon::parse($data['start_date'], 'Asia/Kolkata');
            $endIst = Carbon::parse($data['end_date'], 'Asia/Kolkata');
            $startUtc = $startIst->copy()->timezone('UTC');
            $endUtc = $endIst->copy()->timezone('UTC');

            if (!$version) {
                $version = new ContractVersion();
                $version->contract_id = $contract->id;
                $version->version_number = 1;
                $version->created_by = $userId;
            }

            $version->description = $data['description'] ?? null;
            $version->start_date = $startUtc;
            $version->end_date = $endUtc;
            $version->updated_by = $userId;
            $version->save();

            // Handle file removals
            if (!empty($data['remove_files'])) {
                $filesToRemove = array_filter($data['remove_files'], fn($v) => !empty($v));
                if (!empty($filesToRemove)) {
                    ContractVersionFile::whereIn('id', $filesToRemove)
                        ->where('contract_version_id', $version->id)
                        ->delete();
                }
            }

            // Handle additional files
            if ($request->hasFile('files')) {
                $files = $request->file('files');
                $maxOrder = $version->files()->max('display_order') ?? 0;
                $order = $maxOrder + 1;

                foreach ($files as $uploadedFile) {
                    if (!$uploadedFile->isValid()) {
                        continue;
                    }

                    $sha256 = hash_file('sha256', $uploadedFile->getRealPath());

                    $stored = StoredFile::where('branch_id', $contract->branch_id)
                        ->where('sha256', $sha256)
                        ->first();

                    if (!$stored) {
                        $path = $uploadedFile->store("branches/{$contract->branch_id}/contracts", 'local');

                        $stored = StoredFile::create([
                            'branch_id' => $contract->branch_id,
                            'disk' => 'local',
                            'path' => $path,
                            'original_filename' => $uploadedFile->getClientOriginalName(),
                            'mime_type' => $uploadedFile->getMimeType(),
                            'size_bytes' => $uploadedFile->getSize(),
                            'sha256' => $sha256,
                        ]);
                    }

                    ContractVersionFile::create([
                        'contract_version_id' => $version->id,
                        'stored_file_id' => $stored->id,
                        'display_order' => $order++,
                    ]);
                }
            }

            // Refresh reminders
            $contract->reminders()->delete();

            $reminderDays = collect($data['reminder_days'] ?? [])
                ->filter(fn($v) => $v !== null && $v !== '')
                ->map(fn($v) => (int) $v)
                ->unique()
                ->values();

            foreach ($reminderDays as $days) {
                ContractReminder::create([
                    'contract_id' => $contract->id,
                    'days_before_end' => $days,
                    'is_sent' => false,
                    'sent_at' => null,
                ]);
            }

            // Sync recipients
            $recipientIds = collect($data['recipient_ids'] ?? [])
                ->filter()
                ->map(fn($v) => (int) $v)
                ->unique()
                ->values();

            $contract->notificationRecipients()->sync($recipientIds->all());
        });

        return redirect()
            ->route('contracts.index')
            ->with('status', 'Contract updated successfully.');
    }

    /**
     * Soft delete contract
     */
    public function destroy(Contract $contract)
    {
        if (!Auth::user()->hasPermission('contracts.delete')) {
            abort(403, 'Unauthorized action.');
        }

        // Branch access check
        if (!Auth::user()->isSuperAdmin() && $contract->branch_id !== Auth::user()->branch_id) {
            abort(403, 'Unauthorized action.');
        }

        $contract->delete();

        return redirect()
            ->route('contracts.index')
            ->with('status', 'Contract deleted successfully.');
    }

    /**
     * Restore soft-deleted contract
     */
    public function restore(Request $request, $id)
    {
        if (!Auth::user()->hasPermission('contracts.restore')) {
            abort(403, 'Unauthorized action.');
        }

        $contract = Contract::withTrashed()->findOrFail($id);

        // Branch access check
        if (!Auth::user()->isSuperAdmin() && $contract->branch_id !== Auth::user()->branch_id) {
            abort(403, 'Unauthorized action.');
        }

        if ($contract->trashed()) {
            $contract->restoreWithUser();

            // Auto-mark past reminders as sent
            $latestVersion = $contract->latestVersion;
            if ($latestVersion && $latestVersion->end_date) {
                $endDate = $latestVersion->end_date;
                $today = Carbon::now()->startOfDay();

                $pastReminders = $contract->reminders()
                    ->where('is_sent', false)
                    ->get()
                    ->filter(function ($reminder) use ($endDate, $today) {
                        $triggerDate = $endDate->copy()->subDays($reminder->days_before_end)->startOfDay();
                        return $triggerDate->lt($today);
                    });

                if ($pastReminders->isNotEmpty()) {
                    foreach ($pastReminders as $reminder) {
                        $reminder->is_sent = true;
                        $reminder->sent_at = Carbon::now();
                        $reminder->save();
                    }

                    $count = $pastReminders->count();
                    $message = "Contract restored successfully. Note: {$count} past reminder(s) have been automatically marked as sent.";
                } else {
                    $message = 'Contract restored successfully.';
                }
            } else {
                $message = 'Contract restored successfully.';
            }
        } else {
            $message = 'Contract is not deleted.';
        }

        $redirectParams = ['status' => 'deleted'];

        if ($request->filled('search')) {
            $redirectParams['search'] = $request->input('search');
        }

        return redirect()
            ->route('contracts.index', $redirectParams)
            ->with('status', $message);
    }

    /**
     * Export contract to Excel
     */
    public function exportExcel(Contract $contract)
    {
        if (!Auth::user()->hasPermission('contracts.export')) {
            abort(403, 'Unauthorized action.');
        }

        // Branch access check
        if (!Auth::user()->isSuperAdmin() && $contract->branch_id !== Auth::user()->branch_id) {
            abort(403, 'Unauthorized action.');
        }

        $contract->load([
            'branch',
            'contractType',
            'creator',
            'updater',
            'versions.files.storedFile',
            'reminders',
            'notificationRecipients'
        ]);

        $sanitizedContractNumber = str_replace(['/', '\\'], '_', $contract->contract_number);
        $filename = 'contract_' . $sanitizedContractNumber . '_' . date('YmdHis') . '.xlsx';

        return Excel::download(new ContractExport($contract), $filename);
    }

    /**
     * Create new version form
     */
    public function createVersion(Contract $contract)
    {
        if (!Auth::user()->hasPermission('contracts.versions.create')) {
            abort(403, 'Unauthorized action.');
        }

        // Branch access check
        if (!Auth::user()->isSuperAdmin() && $contract->branch_id !== Auth::user()->branch_id) {
            abort(403, 'Unauthorized action.');
        }

        if ($contract->trashed()) {
            return redirect()
                ->route('contracts.show', $contract)
                ->with('error', 'Cannot add version to a deleted contract.');
        }

        $contract->load([
            'branch',
            'contractType',
            'latestVersion',
            'versions' => function ($q) {
                $q->orderBy('version_number', 'desc');
            },
            'reminders' => function ($q) {
                $q->orderBy('days_before_end');
            },
        ]);

        return view('contracts.versions.create', compact('contract'));
    }

    /**
     * Store new version
     */
    public function storeVersion(Request $request, Contract $contract)
    {
        if (!Auth::user()->hasPermission('contracts.versions.create')) {
            abort(403, 'Unauthorized action.');
        }

        // Branch access check
        if (!Auth::user()->isSuperAdmin() && $contract->branch_id !== Auth::user()->branch_id) {
            abort(403, 'Unauthorized action.');
        }

        if ($contract->trashed()) {
            return redirect()
                ->route('contracts.show', $contract)
                ->with('error', 'Cannot add version to a deleted contract.');
        }

        $data = $request->validate([
            'description' => ['nullable', 'string'],
            'start_date' => ['required', 'date'],
            'end_date' => ['required', 'date', 'after_or_equal:start_date'],
            'files' => ['nullable', 'array'],
            'files.*' => [
                'file',
                'max:20480',
                'mimes:pdf,doc,docx,xls,xlsx,odt,ods,txt,png,jpg,jpeg,gif',
            ],
            'reminder_days' => ['nullable', 'array'],
            'reminder_days.*' => ['nullable', 'integer', 'min:0'],
        ]);

        $userId = Auth::id();

        DB::transaction(function () use ($request, $contract, $data, $userId) {
            $startIst = Carbon::parse($data['start_date'], 'Asia/Kolkata');
            $endIst = Carbon::parse($data['end_date'], 'Asia/Kolkata');
            $startUtc = $startIst->copy()->timezone('UTC');
            $endUtc = $endIst->copy()->timezone('UTC');

            $nextVersionNumber = ContractVersion::nextVersionNumberFor($contract);

            $version = new ContractVersion();
            $version->contract_id = $contract->id;
            $version->version_number = $nextVersionNumber;
            $version->description = $data['description'] ?? null;
            $version->start_date = $startUtc;
            $version->end_date = $endUtc;
            $version->created_by = $userId;
            $version->updated_by = $userId;
            $version->save();

            // Handle files
            if ($request->hasFile('files')) {
                $files = $request->file('files');
                $order = 1;

                foreach ($files as $uploadedFile) {
                    if (!$uploadedFile->isValid()) {
                        continue;
                    }

                    $sha256 = hash_file('sha256', $uploadedFile->getRealPath());

                    $stored = StoredFile::where('branch_id', $contract->branch_id)
                        ->where('sha256', $sha256)
                        ->first();

                    if (!$stored) {
                        $path = $uploadedFile->store("branches/{$contract->branch_id}/contracts", 'local');

                        $stored = StoredFile::create([
                            'branch_id' => $contract->branch_id,
                            'disk' => 'local',
                            'path' => $path,
                            'original_filename' => $uploadedFile->getClientOriginalName(),
                            'mime_type' => $uploadedFile->getMimeType(),
                            'size_bytes' => $uploadedFile->getSize(),
                            'sha256' => $sha256,
                        ]);
                    }

                    ContractVersionFile::create([
                        'contract_version_id' => $version->id,
                        'stored_file_id' => $stored->id,
                        'display_order' => $order++,
                    ]);
                }
            }

            // Update reminders if provided
            $reminderDays = collect($data['reminder_days'] ?? [])
                ->filter(fn($v) => $v !== null && $v !== '')
                ->map(fn($v) => (int) $v)
                ->unique()
                ->values();

            if ($reminderDays->isNotEmpty()) {
                $contract->reminders()->delete();

                foreach ($reminderDays as $days) {
                    ContractReminder::create([
                        'contract_id' => $contract->id,
                        'days_before_end' => $days,
                        'is_sent' => false,
                        'sent_at' => null,
                    ]);
                }
            }

            $contract->updated_by = $userId;
            $contract->save();
        });

        return redirect()
            ->route('contracts.show', $contract)
            ->with('status', 'New version created successfully.');
    }

    /**
     * Edit version form
     */
    public function editVersion(ContractVersion $version)
    {
        if (!Auth::user()->hasPermission('contracts.versions.edit')) {
            abort(403, 'Unauthorized action.');
        }

        // Branch access check through contract
        $contract = $version->contract;
        if (!Auth::user()->isSuperAdmin() && $contract->branch_id !== Auth::user()->branch_id) {
            abort(403, 'Unauthorized action.');
        }

        $version->load(['contract.branch', 'files.storedFile']);

        $allVersions = $version->contract->versions()
            ->orderBy('version_number', 'desc')
            ->get();

        return view('contracts.versions.edit', compact('version', 'allVersions'));
    }

    /**
     * Update version
     */
    public function updateVersion(Request $request, ContractVersion $version)
    {
        if (!Auth::user()->hasPermission('contracts.versions.edit')) {
            abort(403, 'Unauthorized action.');
        }

        // Branch access check through contract
        $contract = $version->contract;
        if (!Auth::user()->isSuperAdmin() && $contract->branch_id !== Auth::user()->branch_id) {
            abort(403, 'Unauthorized action.');
        }

        $data = $request->validate([
            'description' => ['nullable', 'string'],
            'start_date' => ['required', 'date'],
            'end_date' => ['required', 'date', 'after_or_equal:start_date'],
            'remove_files' => ['nullable', 'array'],
            'remove_files.*' => ['nullable', 'integer', 'exists:contract_version_files,id'],
            'files' => ['nullable', 'array'],
            'files.*' => [
                'file',
                'max:20480',
                'mimes:pdf,doc,docx,xls,xlsx,odt,ods,txt,png,jpg,jpeg,gif',
            ],
        ]);

        $userId = Auth::id();

        DB::transaction(function () use ($request, $version, $contract, $data, $userId) {
            $startIst = Carbon::parse($data['start_date'], 'Asia/Kolkata');
            $endIst = Carbon::parse($data['end_date'], 'Asia/Kolkata');
            $startUtc = $startIst->copy()->timezone('UTC');
            $endUtc = $endIst->copy()->timezone('UTC');

            $version->description = $data['description'] ?? null;
            $version->start_date = $startUtc;
            $version->end_date = $endUtc;
            $version->updated_by = $userId;
            $version->save();

            // Handle file removals
            if (!empty($data['remove_files'])) {
                $filesToRemove = array_filter($data['remove_files'], fn($v) => !empty($v));
                if (!empty($filesToRemove)) {
                    ContractVersionFile::whereIn('id', $filesToRemove)
                        ->where('contract_version_id', $version->id)
                        ->delete();
                }
            }

            // Handle additional files
            if ($request->hasFile('files')) {
                $files = $request->file('files');
                $maxOrder = $version->files()->max('display_order') ?? 0;
                $order = $maxOrder + 1;

                foreach ($files as $uploadedFile) {
                    if (!$uploadedFile->isValid()) {
                        continue;
                    }

                    $sha256 = hash_file('sha256', $uploadedFile->getRealPath());

                    $stored = StoredFile::where('branch_id', $contract->branch_id)
                        ->where('sha256', $sha256)
                        ->first();

                    if (!$stored) {
                        $path = $uploadedFile->store("branches/{$contract->branch_id}/contracts", 'local');

                        $stored = StoredFile::create([
                            'branch_id' => $contract->branch_id,
                            'disk' => 'local',
                            'path' => $path,
                            'original_filename' => $uploadedFile->getClientOriginalName(),
                            'mime_type' => $uploadedFile->getMimeType(),
                            'size_bytes' => $uploadedFile->getSize(),
                            'sha256' => $sha256,
                        ]);
                    }

                    ContractVersionFile::create([
                        'contract_version_id' => $version->id,
                        'stored_file_id' => $stored->id,
                        'display_order' => $order++,
                    ]);
                }
            }

            $contract->updated_by = $userId;
            $contract->save();
        });

        return redirect()
            ->route('contracts.show', $version->contract)
            ->with('status', 'Version updated successfully.');
    }

    /**
     * Send test notification
     */
    public function sendTestNotification(Contract $contract)
    {
        if (!Auth::user()->hasPermission('contracts.edit')) {
            abort(403, 'Unauthorized action.');
        }

        // Branch access check
        if (!Auth::user()->isSuperAdmin() && $contract->branch_id !== Auth::user()->branch_id) {
            abort(403, 'Unauthorized action.');
        }

        $recipients = $contract->notificationRecipients()
            ->where('is_active', true)
            ->whereNotNull('email')
            ->get();

        if ($recipients->isEmpty()) {
            return back()->with('error', 'No active recipients with email addresses found for this contract.');
        }

        $emailAddresses = $recipients->pluck('email')->unique()->values();

        if ($emailAddresses->isEmpty()) {
            return back()->with('error', 'No valid email addresses found for recipients.');
        }

        try {
            foreach ($emailAddresses as $email) {
                Mail::to($email)->send(new ContractExpiryNotification($contract, 0));
            }

            $count = $emailAddresses->count();
            return back()->with('status', "Test notification sent successfully to {$count} recipient(s).");

        } catch (\Exception $e) {
            return back()->with('error', 'Failed to send test notification: ' . $e->getMessage());
        }
    }
}
