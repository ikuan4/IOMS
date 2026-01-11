<?php

namespace App\Http\Controllers;

use App\Models\User;
use App\Models\Contract;
use App\Models\ContractReminder;
use App\Models\ContractType;
use App\Models\ContractVersion;
use App\Models\ContractVersionFile;
use App\Models\NotificationRecipient;
use App\Models\StoredFile;
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

/**
 * @phpstan-type AuthUser User
 */
class ContractController extends Controller
{
    /**
     * List contracts with basic search + status cards.
     *
     * Status is derived in PHP from the latest version and grace_period_days,
     * then we manually paginate the collection.
     */
    public function index(Request $request)
    {
        if (!Auth::user()->hasPermission('contracts.view')) {
            abort(403, 'Unauthorized action.');
        }

        $search = $request->query('search');
        $status = $request->query('status', 'all'); // all | ongoing | pending | expiring_soon | expired | inactive | deleted
        $contractTypeId = $request->query('contract_type_id');

        /**
         * 1) STATUS COUNTS (ignore search, but respect contract type filter)
         *
         * We rely on the Contract model's "status" accessor
         * (Ongoing / Pending / Expiring Soon / Expired / Inactive).
         * Deleted is detected via ->trashed().
         */
        $allForCounts = Contract::with(['latestVersion'])
            ->withTrashed()
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
            'deleted' => $allForCounts->filter->trashed()->count(),
        ];

        /**
         * 2) MAIN LIST QUERY (includes deleted, then filtered in PHP)
         */
        $query = Contract::with([
            'contractType',
            'creator',
            'updater',
            'latestVersion',
        ])
            ->withTrashed()
            ->when($contractTypeId, function ($q) use ($contractTypeId) {
                $q->where('contract_type_id', $contractTypeId);
            })
            ->orderByDesc('created_at');

        // Simple search
        if ($search) {
            $query->where(function ($q) use ($search) {
                $q->where('contract_number', 'like', "%{$search}%")
                    ->orWhere('contract_with', 'like', "%{$search}%")
                    ->orWhereHas('latestVersion', function ($q2) use ($search) {
                        $q2->where('description', 'like', "%{$search}%");
                    });
            });
        }

        // Fetch all matching rows, then filter by derived status
        $contractsCollection = $query->get();

        if ($status !== 'all') {
            $contractsCollection = $contractsCollection->filter(function ($contract) use ($status) {
                /** @var Contract $contract */
                // Deleted tab → only trashed rows
                if ($status === 'deleted') {
                    return $contract->trashed();
                }

                // Other tabs → ignore trashed rows
                if ($contract->trashed()) {
                    return false;
                }

                // Match against the derived status accessor
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

        /**
         * 3) Manual pagination on the filtered collection
         */
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

        // Fetch all contract types (including soft-deleted)
        $contractTypes = ContractType::withTrashed()
            ->orderBy('name', 'asc')
            ->get();

        return view('contracts.index', compact(
            'contracts',
            'search',
            'status',
            'statusCounts',
            'contractTypes',
            'contractTypeId'
        ));
    }



    /**
     * Show create form.
     */
    public function create()
    {
        if (!Auth::user()->hasPermission('contracts.create')) {
            abort(403, 'Unauthorized action.');
        }

        $contract = new Contract();

        // Only active, non-deleted types
        $contractTypes = ContractType::where('is_active', true)
            ->whereNull('deleted_at')
            ->orderBy('name')
            ->get();

        // Active notification recipients (all OFF by default in UI)
        $recipients = NotificationRecipient::where('is_active', true)
            ->whereNull('deleted_at')
            ->orderBy('name')
            ->get();

        return view('contracts.create', compact('contract', 'contractTypes', 'recipients'));
    }

    /**
     * Store a new contract + its initial version + files + reminders + recipient toggles.
     *
     * Version 1 is created automatically.
     * Contract number is generated once and never changed.
     */
    public function store(Request $request)
    {
        $data = $request->validate([
            'contract_type_id' => ['required', 'exists:contract_types,id'],
            'contract_with' => ['required', 'string', 'max:255'],
            'grace_period_days' => ['required', 'integer', 'min:0'],

            // Version 1 fields
            'description' => ['nullable', 'string'],
            'start_date' => ['required', 'date'],
            'end_date' => ['required', 'date', 'after_or_equal:start_date'],

            // Reminders (optional, X days before end_date)
            'reminder_days' => ['nullable', 'array'],
            'reminder_days.*' => ['nullable', 'integer', 'min:0'],

            // Notification recipients (toggles ON)
            'recipient_ids' => ['nullable', 'array'],
            'recipient_ids.*' => ['integer', 'exists:notification_recipients,id'],

            // Files (optional, multiple)
            'files' => ['nullable', 'array'],
            'files.*' => [
                'file',
                'max:20480', // 20 MB in kilobytes
                'mimes:pdf,doc,docx,xls,xlsx,odt,ods,txt,png,jpg,jpeg,gif',
            ],
        ]);

        $userId = Auth::id();

        DB::transaction(function () use ($request, $data, $userId) {
            // Parse dates as IST, store as UTC
            $startIst = Carbon::parse($data['start_date'], 'Asia/Kolkata');
            $endIst = Carbon::parse($data['end_date'], 'Asia/Kolkata');

            $startUtc = $startIst->copy()->timezone('UTC');
            $endUtc = $endIst->copy()->timezone('UTC');

            /** @var \App\Models\ContractType $type */
            $type = ContractType::findOrFail($data['contract_type_id']);

            // 1) Create contract with temporary contract_number to get ID
            $contract = new Contract();
            $contract->contract_type_id = $type->id;
            $contract->contract_number = 'TEMP'; // will be replaced
            $contract->contract_with = $data['contract_with'];
            $contract->grace_period_days = $data['grace_period_days'];
            $contract->is_active = true;
            $contract->created_by = $userId;
            $contract->updated_by = $userId;
            $contract->save();

            // 2) Generate final contract_number using CT/{TYPE3}/{YYYY}/{id}
            $prefixRaw = preg_replace('/\s+/', '', (string) $type->name);
            $prefix3 = strtoupper(mb_substr($prefixRaw, 0, 3));
            $year = $startIst->year;

            $contract->contract_number = "CT/{$prefix3}/{$year}/{$contract->id}";
            $contract->save();

            // 3) Create version 1
            $version = new ContractVersion();
            $version->contract_id = $contract->id;
            $version->version_number = 1;
            $version->description = $data['description'] ?? null;
            $version->start_date = $startUtc;
            $version->end_date = $endUtc;
            $version->created_by = $userId;
            $version->updated_by = $userId;
            $version->save();

            // 4) Handle file uploads (optional)
            if ($request->hasFile('files')) {
                $files = $request->file('files');
                $order = 1;

                foreach ($files as $uploadedFile) {
                    if (!$uploadedFile->isValid()) {
                        continue;
                    }

                    $sha256 = hash_file('sha256', $uploadedFile->getRealPath());

                    // Deduplication
                    $stored = StoredFile::where('sha256', $sha256)->first();

                    if (!$stored) {
                        $path = $uploadedFile->store('contracts', 'local');

                        $stored = StoredFile::create([
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

            // 5) Reminders (per-contract)
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

            // 6) Notification recipients (pivot)
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
     * Show a single contract + version history.
     *
     * - Top: contract metadata
     * - Bottom: table of versions, each with its attachments.
     */
    public function show(Contract $contract)
    {
        // Permission: Only users with View Contract Versions can access details page
        if (!Auth::user() || !Auth::user()->hasPermission('contracts.versions.view')) {
            abort(403, 'You do not have permission to view contract versions.');
        }

        $contract->load([
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
     * Show edit form (edits contract fields + latest version metadata).
     *
     * For now, we allow editing:
     * - contract_with
     * - grace_period_days
     * - is_active (toggle)
     * - latest version description / dates
     * - reminders
     * - recipients
     * - add more files to latest version
     */
    public function edit(Contract $contract)
    {
        $contract->load(['latestVersion.files.storedFile']);

        $contractTypes = ContractType::whereNull('deleted_at')
            ->orderBy('name')
            ->get();

        $recipients = NotificationRecipient::where('is_active', true)
            ->whereNull('deleted_at')
            ->orderBy('name')
            ->get();

        // Current reminder config
        $reminders = $contract->reminders()->orderBy('days_before_end')->get();

        return view('contracts.edit', compact(
            'contract',
            'contractTypes',
            'recipients',
            'reminders'
        ));
    }

    /**
     * Update contract + latest version.
     * (We are not creating a new version here yet; that will be a separate action.)
     */
    public function update(Request $request, Contract $contract)
    {
        $data = $request->validate([
            'contract_type_id' => ['required', 'exists:contract_types,id'],
            'contract_with' => ['required', 'string', 'max:255'],
            'grace_period_days' => ['required', 'integer', 'min:0'],
            'is_active' => ['nullable', 'boolean'],

            // Latest version fields
            'description' => ['nullable', 'string'],
            'start_date' => ['required', 'date'],
            'end_date' => ['required', 'date', 'after_or_equal:start_date'],

            // Reminders
            'reminder_days' => ['nullable', 'array'],
            'reminder_days.*' => ['nullable', 'integer', 'min:0'],

            // Recipients
            'recipient_ids' => ['nullable', 'array'],
            'recipient_ids.*' => ['integer', 'exists:notification_recipients,id'],

            // Files to remove
            'remove_files' => ['nullable', 'array'],
            'remove_files.*' => ['nullable', 'integer', 'exists:contract_version_files,id'],

            // Additional files for latest version
            'files' => ['nullable', 'array'],
            'files.*' => [
                'file',
                'max:20480', // 20 MB
                'mimes:pdf,doc,docx,xls,xlsx,odt,ods,txt,png,jpg,jpeg,gif',
            ],
        ]);

        $userId = Auth::id();

        DB::transaction(function () use ($request, $contract, $data, $userId) {
            // Update contract (but DO NOT change contract_number)
            $contract->contract_type_id = $data['contract_type_id'];
            $contract->contract_with = $data['contract_with'];
            $contract->grace_period_days = $data['grace_period_days'];
            $contract->is_active = $request->boolean('is_active');
            $contract->updated_by = $userId;
            $contract->save();

            // Update latest version (if missing, create version 1)
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

            // Handle additional files for this version
            if ($request->hasFile('files')) {
                $files = $request->file('files');

                // Start order after existing ones
                $maxOrder = $version->files()->max('display_order') ?? 0;
                $order = $maxOrder + 1;

                foreach ($files as $uploadedFile) {
                    if (!$uploadedFile->isValid()) {
                        continue;
                    }

                    $sha256 = hash_file('sha256', $uploadedFile->getRealPath());

                    $stored = StoredFile::where('sha256', $sha256)->first();

                    if (!$stored) {
                        $path = $uploadedFile->store('contracts', 'local');

                        $stored = StoredFile::create([
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

            // Refresh reminders: simple approach — delete and re-create
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
     * Restore a soft-deleted contract.
     */
    public function restore(Request $request, $id)
    {
        if (!Auth::user()->hasPermission('contracts.restore')) {
            abort(403, 'Unauthorized action.');
        }

        $contract = Contract::withTrashed()->findOrFail($id);

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
                    $message = "Contract restored successfully. Note: {$count} past reminder(s) have been automatically marked as sent since their trigger dates have passed.";
                } else {
                    $message = 'Contract restored successfully.';
                }
            } else {
                $message = 'Contract restored successfully.';
            }
        } else {
            $message = 'Contract is not deleted.';
        }

        // After restore, you can stay on Deleted tab with same search
        $redirectParams = ['status' => 'deleted'];

        if ($request->filled('search')) {
            $redirectParams['search'] = $request->input('search');
        }

        return redirect()
            ->route('contracts.index', $redirectParams)
            ->with('status', $message);
    }


    /**
     * Soft-delete contract (for now we just use SoftDeletes).
     * Deleting a contract cascades to versions / reminders / pivots via FK.
     */
    public function destroy(Contract $contract)
    {
        $contract->delete();

        return redirect()
            ->route('contracts.index')
            ->with('status', 'Contract deleted (soft deleted).');
    }

    /**
     * Export contract details to Excel
     */
    public function exportExcel(Contract $contract)
    {
        // Load all required relationships
        $contract->load([
            'contractType',
            'creator',
            'updater',
            'versions.files.storedFile',
            'reminders',
            'notificationRecipients'
        ]);

        // Sanitize contract number for filename (remove / and \ characters)
        $sanitizedContractNumber = str_replace(['/', '\\'], '_', $contract->contract_number);
        $filename = 'contract_' . $sanitizedContractNumber . '_' . date('YmdHis') . '.xlsx';

        return Excel::download(new ContractExport($contract), $filename);
    }

    /**
     * Download all attachments for a specific version
     */
    public function downloadVersionAttachments(ContractVersion $version)
    {
        $files = $version->files()->with('storedFile')->get();

        if ($files->isEmpty()) {
            return back()->with('error', 'No attachments found for this version.');
        }

        // Download each file
        foreach ($files as $versionFile) {
            $storedFile = $versionFile->storedFile;

            if (!$storedFile) {
                continue;
            }

            $disk = Storage::disk($storedFile->disk);

            if (!$disk->exists($storedFile->path)) {
                continue;
            }

            // Return the first file download response
            // For multiple files, browser will handle multiple download requests via JavaScript
            return response()->download(
                $disk->path($storedFile->path),
                $storedFile->original_filename
            );
        }

        return back()->with('error', 'Failed to download attachments.');
    }

    /**
     * Send test notification for a contract
     */
    public function sendTestNotification(Contract $contract)
    {
        // Load recipients with valid emails
        $recipients = $contract->notificationRecipients()
            ->where('is_active', true)
            ->whereNotNull('email')
            ->get();

        if ($recipients->isEmpty()) {
            return back()->with('error', 'No active recipients with email addresses found for this contract.');
        }

        // Get unique email addresses
        $emailAddresses = $recipients->pluck('email')->unique()->values();

        if ($emailAddresses->isEmpty()) {
            return back()->with('error', 'No valid email addresses found for recipients.');
        }

        try {
            // Send test notification (use 0 days as it's a test)
            foreach ($emailAddresses as $email) {
                Mail::to($email)->send(new ContractExpiryNotification($contract, 0));
            }

            $count = $emailAddresses->count();
            return back()->with('status', "Test notification sent successfully to {$count} recipient(s).");

        } catch (\Exception $e) {
            return back()->with('error', 'Failed to send test notification: ' . $e->getMessage());
        }
    }

    /**
     * Show form to create a new version for a contract.
     *
     * Only accessible when contract is not deleted.
     */
    public function createVersion(Contract $contract)
    {
        // Ensure contract is not deleted
        if ($contract->trashed()) {
            return redirect()
                ->route('contracts.show', $contract)
                ->with('error', 'Cannot add version to a deleted contract.');
        }

        // Load contract with relationships needed for the form
        $contract->load([
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
     * Store a new version for a contract.
     *
     * Creates a new version with auto-incremented version_number,
     * handles file uploads, and optionally updates reminders.
     */
    public function storeVersion(Request $request, Contract $contract)
    {
        // Ensure contract is not deleted
        if ($contract->trashed()) {
            return redirect()
                ->route('contracts.show', $contract)
                ->with('error', 'Cannot add version to a deleted contract.');
        }

        $data = $request->validate([
            // Version fields
            'description' => ['nullable', 'string'],
            'start_date' => ['required', 'date'],
            'end_date' => ['required', 'date', 'after_or_equal:start_date'],

            // Files (optional, multiple)
            'files' => ['nullable', 'array'],
            'files.*' => [
                'file',
                'max:20480', // 20 MB in kilobytes
                'mimes:pdf,doc,docx,xls,xlsx,odt,ods,txt,png,jpg,jpeg,gif',
            ],

            // Reminders (optional update)
            'reminder_days' => ['nullable', 'array'],
            'reminder_days.*' => ['nullable', 'integer', 'min:0'],
        ]);

        $userId = Auth::id();

        DB::transaction(function () use ($request, $contract, $data, $userId) {
            // Parse dates as IST, store as UTC
            $startIst = Carbon::parse($data['start_date'], 'Asia/Kolkata');
            $endIst = Carbon::parse($data['end_date'], 'Asia/Kolkata');

            $startUtc = $startIst->copy()->timezone('UTC');
            $endUtc = $endIst->copy()->timezone('UTC');

            // Calculate next version number
            $nextVersionNumber = ContractVersion::nextVersionNumberFor($contract);

            // Create new version
            $version = new ContractVersion();
            $version->contract_id = $contract->id;
            $version->version_number = $nextVersionNumber;
            $version->description = $data['description'] ?? null;
            $version->start_date = $startUtc;
            $version->end_date = $endUtc;
            $version->created_by = $userId;
            $version->updated_by = $userId;
            $version->save();

            // Handle file uploads (if any)
            if ($request->hasFile('files')) {
                $files = $request->file('files');
                $order = 1;

                foreach ($files as $uploadedFile) {
                    if (!$uploadedFile->isValid()) {
                        continue;
                    }

                    $sha256 = hash_file('sha256', $uploadedFile->getRealPath());

                    // Deduplication: check if file already exists
                    $stored = StoredFile::where('sha256', $sha256)->first();

                    if (!$stored) {
                        $path = $uploadedFile->store('contracts', 'local');

                        $stored = StoredFile::create([
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
            // If any reminder days are filled, replace all existing reminders
            $reminderDays = collect($data['reminder_days'] ?? [])
                ->filter(fn($v) => $v !== null && $v !== '')
                ->map(fn($v) => (int) $v)
                ->unique()
                ->values();

            if ($reminderDays->isNotEmpty()) {
                // Delete existing reminders and create new ones
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

            // Update contract's updated_by and updated_at
            $contract->updated_by = $userId;
            $contract->save();
        });

        return redirect()
            ->route('contracts.show', $contract)
            ->with('status', 'New version created successfully.');
    }

    /**
     * Show form to edit a specific version
     */
    public function editVersion(ContractVersion $version)
    {
        $version->load(['contract', 'files.storedFile']);

        // Get all versions for this contract for context
        $allVersions = $version->contract->versions()
            ->orderBy('version_number', 'desc')
            ->get();

        return view('contracts.versions.edit', compact('version', 'allVersions'));
    }

    /**
     * Update a specific version
     */
    public function updateVersion(Request $request, ContractVersion $version)
    {
        $data = $request->validate([
            'description' => ['nullable', 'string'],
            'start_date' => ['required', 'date'],
            'end_date' => ['required', 'date', 'after_or_equal:start_date'],

            // Files to remove
            'remove_files' => ['nullable', 'array'],
            'remove_files.*' => ['nullable', 'integer', 'exists:contract_version_files,id'],

            // New files to upload
            'files' => ['nullable', 'array'],
            'files.*' => [
                'file',
                'max:20480', // 20 MB in kilobytes
                'mimes:pdf,doc,docx,xls,xlsx,odt,ods,txt,png,jpg,jpeg,gif',
            ],
        ]);

        $userId = Auth::id();

        DB::transaction(function () use ($request, $data, $version, $userId) {
            // Parse dates as IST, store as UTC
            $startIst = Carbon::parse($data['start_date'], 'Asia/Kolkata');
            $endIst = Carbon::parse($data['end_date'], 'Asia/Kolkata');

            $startUtc = $startIst->copy()->timezone('UTC');
            $endUtc = $endIst->copy()->timezone('UTC');

            // Update version
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

            // Handle new file uploads
            if ($request->hasFile('files')) {
                $files = $request->file('files');

                // Get current max display order
                $maxOrder = ContractVersionFile::where('contract_version_id', $version->id)
                    ->max('display_order') ?? 0;
                $order = $maxOrder + 1;

                foreach ($files as $uploadedFile) {
                    if (!$uploadedFile->isValid()) {
                        continue;
                    }

                    $sha256 = hash_file('sha256', $uploadedFile->getRealPath());

                    // Deduplication: check if file already exists
                    $stored = StoredFile::where('sha256', $sha256)->first();

                    if (!$stored) {
                        $path = $uploadedFile->store('contracts', 'local');

                        $stored = StoredFile::create([
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

            // Update contract's updated_by and updated_at
            $version->contract->updated_by = $userId;
            $version->contract->save();
        });

        return redirect()
            ->route('contracts.show', $version->contract)
            ->with('status', 'Version updated successfully.');
    }

    /**
     * Soft delete a specific version
     */
    public function destroyVersion(ContractVersion $version)
    {
        $contract = $version->contract;

        // Prevent deleting if it's the only version
        $activeVersionsCount = $contract->versions()->count();
        if ($activeVersionsCount <= 1) {
            return redirect()
                ->route('contracts.show', $contract)
                ->with('error', 'Cannot delete the only version of this contract.');
        }

        // Soft delete the version
        $version->delete();

        return redirect()
            ->route('contracts.show', $contract)
            ->with('status', 'Version deleted successfully.');
    }

    /**
     * Restore a soft-deleted version
     */
    public function restoreVersion($id)
    {
        $version = ContractVersion::withTrashed()->findOrFail($id);
        $contract = $version->contract;

        // Restore the version
        $version->restoreWithUser();

        return redirect()
            ->route('contracts.show', $contract)
            ->with('status', 'Version restored successfully.');
    }
}


