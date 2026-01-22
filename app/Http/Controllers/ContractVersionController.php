<?php

namespace App\Http\Controllers;

use App\Models\Contract;
use App\Models\ContractVersion;
use App\Models\ContractVersionFile;
use App\Models\StoredFile;
use App\Models\AuditLog;
use Illuminate\Http\Request;
use Illuminate\Support\Carbon;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Storage;

class ContractVersionController extends Controller
{
    /**
     * Show form to create a new version for the contract
     */
    public function create(Contract $contract): mixed
    {
        $user = Auth::user();
        if (!$user || !$user->hasPermission('contracts.versions.create')) {
            abort(403, 'Unauthorized action.');
        }

        // Branch access check
        if (!$user->isSuperAdmin() && $contract->branch_id !== $user->branch_id) {
            abort(403, 'Unauthorized action.');
        }

        // Load latest version for reference
        $latest = $contract->latestVersion;

        return view('contracts.versions.create', compact('contract', 'latest'));
    }

    /**
     * Store a new version for the contract
     */
    public function store(Request $request, Contract $contract): mixed
    {
        $user = Auth::user();
        if (!$user || !$user->hasPermission('contracts.versions.create')) {
            abort(403, 'Unauthorized action.');
        }

        // Branch access check
        if (!$user->isSuperAdmin() && $contract->branch_id !== $user->branch_id) {
            abort(403, 'Unauthorized action.');
        }

        $validated = $request->validate([
            'start_date'  => 'required|date',
            'end_date'    => 'required|date|after:start_date',
            'description' => 'nullable|string|max:1000',
            'files.*'     => 'nullable|file|max:20480', // 20 MB
        ]);

        DB::beginTransaction();
        try {
            // Determine next version number
            $maxVersion = $contract->versions()->max('version_number') ?? 0;
            $nextVersion = $maxVersion + 1;

            // Convert IST to UTC for storage
            $startUtc = Carbon::parse($validated['start_date'], 'Asia/Kolkata')->setTimezone('UTC');
            $endUtc = Carbon::parse($validated['end_date'], 'Asia/Kolkata')->setTimezone('UTC');

            // Create new version
            $version = ContractVersion::create([
                'contract_id'     => $contract->id,
                'version_number'  => $nextVersion,
                'start_date'      => $startUtc,
                'end_date'        => $endUtc,
                'description'     => $validated['description'] ?? null,
                'created_by'      => ($id = Auth::id()) ? (int) $id : null,
                'updated_by'      => ($id = Auth::id()) ? (int) $id : null,
            ]);

            AuditLog::log('create_contract_version', $version, [], $version->toArray());

            // Handle file uploads
            if ($request->hasFile('files')) {
                $addedStoredFileIds = [];
                $linkedPivotIds = [];
                foreach ((array) $request->file('files') as $file) {
                    if (!$file || !$file->isValid()) {
                        continue;
                    }

                    $sha256 = hash_file('sha256', $file->getRealPath());

                    $storedFile = StoredFile::where('branch_id', $contract->branch_id)
                        ->where('sha256', $sha256)
                        ->first();

                    if (!$storedFile) {
                        // Store file
                        $path = $file->store("branches/{$contract->branch_id}/contracts", 'local');

                        $storedFile = StoredFile::create([
                            'branch_id' => $contract->branch_id,
                            'disk' => 'local',
                            'path' => $path,
                            'original_filename' => $file->getClientOriginalName(),
                            'mime_type' => $file->getMimeType(),
                            'size_bytes' => $file->getSize(),
                            'sha256' => $sha256,
                        ]);

                        $addedStoredFileIds[] = $storedFile->getKey();
                    }

                    // Link file to version
                    $pivot = ContractVersionFile::create([
                        'contract_version_id' => $version->id,
                        'stored_file_id'      => $storedFile->id,
                    ]);

                    $linkedPivotIds[] = $pivot->getKey();
                }

                if ($addedStoredFileIds !== [] || $linkedPivotIds !== []) {
                    AuditLog::log('add_contract_version_files', $version, [], [
                        'contract_id' => $contract->getKey(),
                        'stored_file_ids' => $addedStoredFileIds,
                        'contract_version_file_ids' => $linkedPivotIds,
                    ]);
                }
            }

            DB::commit();

            return redirect()
                ->route('contracts.show', $contract->id)
                ->with('success', "Version {$nextVersion} added successfully!");

        } catch (\Exception $e) {
            DB::rollBack();
            return back()
                ->withInput()
                ->withErrors(['error' => 'Failed to create version: ' . $e->getMessage()]);
        }
    }

    /**
     * Show form to edit an existing version
     */
    public function edit(ContractVersion $version): mixed
    {
        $user = Auth::user();
        if (!$user || !$user->hasPermission('contracts.versions.edit')) {
            abort(403, 'Unauthorized action.');
        }

        $contract = $version->contract;
        if (!$contract) {
            abort(404);
        }

        // Branch access check
        if (!$user->isSuperAdmin() && $contract->branch_id !== $user->branch_id) {
            abort(403, 'Unauthorized action.');
        }

        // Load files
        $version->load('files.storedFile');

        return view('contracts.versions.edit', compact('contract', 'version'));
    }

    /**
     * Update an existing version
     */
    public function update(Request $request, ContractVersion $version): mixed
    {
        $user = Auth::user();
        if (!$user || !$user->hasPermission('contracts.versions.edit')) {
            abort(403, 'Unauthorized action.');
        }

        $contract = $version->contract;
        if (!$contract) {
            abort(404);
        }

        // Branch access check
        if (!$user->isSuperAdmin() && $contract->branch_id !== $user->branch_id) {
            abort(403, 'Unauthorized action.');
        }

        $validated = $request->validate([
            'start_date'     => 'required|date',
            'end_date'       => 'required|date|after:start_date',
            'description'    => 'nullable|string|max:1000',
            'files.*'        => 'nullable|file|max:20480', // 20 MB
            'remove_files'   => 'nullable|array',
            'remove_files.*' => 'nullable|integer|exists:contract_version_files,id',
        ]);

        DB::beginTransaction();
        try {
            $oldValues = $version->toArray();

            // Convert IST to UTC for storage
            $startUtc = Carbon::parse($validated['start_date'], 'Asia/Kolkata')->setTimezone('UTC');
            $endUtc = Carbon::parse($validated['end_date'], 'Asia/Kolkata')->setTimezone('UTC');

            // Update version
            $version->update([
                'start_date'  => $startUtc,
                'end_date'    => $endUtc,
                'description' => $validated['description'] ?? null,
                'updated_by'  => ($id = Auth::id()) ? (int) $id : null,
            ]);

            AuditLog::log('update_contract_version', $version, $oldValues, $version->fresh()?->toArray() ?? []);

            // Handle file removals
            if (!empty($validated['remove_files'])) {
                $removed = [];
                foreach ($validated['remove_files'] as $fileId) {
                    if (empty($fileId) || !is_numeric($fileId)) {
                        continue;
                    }

                    $versionFile = ContractVersionFile::find((int) $fileId);
                    if ($versionFile && $versionFile->contract_version_id === $version->id) {
                        $removed[] = [
                            'contract_version_file_id' => $versionFile->getKey(),
                            'stored_file_id' => $versionFile->stored_file_id,
                        ];

                        // Delete from storage
                        $storedFile = $versionFile->storedFile;
                        if ($storedFile) {
                            Storage::disk($storedFile->disk)->delete($storedFile->path);
                            $storedFile->delete();
                        }
                        $versionFile->delete();
                    }
                }

                if ($removed !== []) {
                    AuditLog::log('remove_contract_version_files', $version, [], ['removed' => $removed]);
                }
            }

            // Handle new file uploads
            if ($request->hasFile('files')) {
                $addedStoredFileIds = [];
                $linkedPivotIds = [];
                foreach ((array) $request->file('files') as $file) {
                    if (!$file || !$file->isValid()) {
                        continue;
                    }

                    $sha256 = hash_file('sha256', $file->getRealPath());

                    $storedFile = StoredFile::where('branch_id', $contract->branch_id)
                        ->where('sha256', $sha256)
                        ->first();

                    if (!$storedFile) {
                        // Store file
                        $path = $file->store("branches/{$contract->branch_id}/contracts", 'local');

                        $storedFile = StoredFile::create([
                            'branch_id' => $contract->branch_id,
                            'disk' => 'local',
                            'path' => $path,
                            'original_filename' => $file->getClientOriginalName(),
                            'mime_type' => $file->getMimeType(),
                            'size_bytes' => $file->getSize(),
                            'sha256' => $sha256,
                        ]);

                        $addedStoredFileIds[] = $storedFile->getKey();
                    }

                    // Link file to version
                    $pivot = ContractVersionFile::create([
                        'contract_version_id' => $version->id,
                        'stored_file_id'      => $storedFile->id,
                    ]);

                    $linkedPivotIds[] = $pivot->getKey();
                }

                if ($addedStoredFileIds !== [] || $linkedPivotIds !== []) {
                    AuditLog::log('add_contract_version_files', $version, [], [
                        'contract_id' => $contract->getKey(),
                        'stored_file_ids' => $addedStoredFileIds,
                        'contract_version_file_ids' => $linkedPivotIds,
                    ]);
                }
            }

            DB::commit();

            return redirect()
                ->route('contracts.show', $contract->id)
                ->with('success', "Version {$version->version_number} updated successfully!");

        } catch (\Exception $e) {
            DB::rollBack();
            return back()
                ->withInput()
                ->withErrors(['error' => 'Failed to update version: ' . $e->getMessage()]);
        }
    }

    /**
     * Soft delete a version
     */
    public function destroy(ContractVersion $version): mixed
    {
        $user = Auth::user();
        if (!$user || !$user->hasPermission('contracts.versions.delete')) {
            abort(403, 'Unauthorized action.');
        }

        $contract = $version->contract;
        if (!$contract) {
            abort(404);
        }

        // Branch access check
        if (!$user->isSuperAdmin() && $contract->branch_id !== $user->branch_id) {
            abort(403, 'Unauthorized action.');
        }

        // Prevent deleting if it's the only version
        $activeVersionsCount = $contract->versions()->count();
        if ($activeVersionsCount <= 1) {
            return redirect()
                ->route('contracts.show', $contract->id)
                ->withErrors(['error' => 'Cannot delete the only version of this contract.']);
        }

        $oldValues = $version->toArray();

        // Soft delete the version
        $version->delete();

        $after = ContractVersion::withTrashed()->find($version->getKey());
        AuditLog::log('delete_contract_version', $version, $oldValues, $after?->toArray() ?? []);

        // Reload contract to check status
        $contract->refresh();

        return redirect()
            ->route('contracts.show', $contract->id)
            ->with('success', "Version {$version->version_number} deleted successfully!");
    }

    /**
     * Restore a soft-deleted version
     */
    public function restore(int|string $id): mixed
    {
        $user = Auth::user();
        if (!$user || !$user->hasPermission('contracts.versions.restore')) {
            abort(403, 'Unauthorized action.');
        }

        $version = ContractVersion::onlyTrashed()->findOrFail($id);
        $contract = $version->contract;
        if (!$contract) {
            abort(404);
        }

        // Branch access check
        if (!$user->isSuperAdmin() && $contract->branch_id !== $user->branch_id) {
            abort(403, 'Unauthorized action.');
        }

        $oldValues = $version->toArray();

        if (method_exists($version, 'restoreWithUser')) {
            $version->restoreWithUser();
        } else {
            $version->update(['restored_by' => ($id = Auth::id()) ? (int) $id : null]);
            $version->restore();
        }

        AuditLog::log('restore_contract_version', $version, $oldValues, $version->fresh()?->toArray() ?? []);

        return redirect()
            ->route('contracts.show', $contract->id)
            ->with('success', "Version {$version->version_number} restored successfully!");
    }

    /**
     * Delete a file from a version
     */
    public function deleteFile(Contract $contract, ContractVersion $version, ContractVersionFile $file): mixed
    {
        $user = Auth::user();
        if (!$user || !$user->hasPermission('contracts.versions.edit')) {
            abort(403, 'Unauthorized action.');
        }

        // Branch access check
        if (!$user->isSuperAdmin() && $contract->branch_id !== $user->branch_id) {
            abort(403, 'Unauthorized action.');
        }

        // Ensure the file belongs to this version
        if ($file->contract_version_id !== $version->id) {
            abort(404, 'File not found.');
        }

        $storedFile = $file->storedFile;

        $oldValues = $file->toArray();
        $oldStored = $storedFile ? $storedFile->toArray() : null;

        // Delete the pivot record
        $file->delete();

        // If no other references exist, delete the stored file record and physical file
        if ($storedFile && !ContractVersionFile::where('stored_file_id', $storedFile->id)->exists()) {
            if ($storedFile->path && Storage::disk($storedFile->disk)->exists($storedFile->path)) {
                Storage::disk($storedFile->disk)->delete($storedFile->path);
            }
            $storedFile->delete();
        }

        AuditLog::log('delete_contract_version_file', $file, $oldValues, [
            'contract_id' => $contract->getKey(),
            'contract_version_id' => $version->getKey(),
            'stored_file' => $oldStored,
        ]);

        return redirect()
            ->route('contracts.versions.edit', [$contract, $version])
            ->with('success', 'File deleted successfully!');
    }
}
