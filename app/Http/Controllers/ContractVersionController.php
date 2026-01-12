<?php

namespace App\Http\Controllers;

use App\Models\Contract;
use App\Models\ContractVersion;
use App\Models\ContractVersionFile;
use App\Models\StoredFile;
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
    public function create(Contract $contract)
    {
        if (!Auth::user()->hasPermission('contracts.versions.create')) {
            abort(403, 'Unauthorized action.');
        }

        // Branch access check
        if (!Auth::user()->isSuperAdmin() && $contract->branch_id !== Auth::user()->branch_id) {
            abort(403, 'Unauthorized action.');
        }

        // Load latest version for reference
        $latest = $contract->latestVersion;

        return view('contracts.versions.create', compact('contract', 'latest'));
    }

    /**
     * Store a new version for the contract
     */
    public function store(Request $request, Contract $contract)
    {
        if (!Auth::user()->hasPermission('contracts.versions.create')) {
            abort(403, 'Unauthorized action.');
        }

        // Branch access check
        if (!Auth::user()->isSuperAdmin() && $contract->branch_id !== Auth::user()->branch_id) {
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
            $startUtc = Carbon::createFromFormat('Y-m-d', $validated['start_date'], 'Asia/Kolkata')
                ->setTimezone('UTC');
            $endUtc = Carbon::createFromFormat('Y-m-d', $validated['end_date'], 'Asia/Kolkata')
                ->setTimezone('UTC');

            // Create new version
            $version = ContractVersion::create([
                'contract_id'     => $contract->id,
                'version_number'  => $nextVersion,
                'start_date'      => $startUtc,
                'end_date'        => $endUtc,
                'description'     => $validated['description'] ?? null,
                'created_by'      => Auth::id(),
                'updated_by'      => Auth::id(),
            ]);

            // Handle file uploads
            if ($request->hasFile('files')) {
                foreach ($request->file('files') as $file) {
                    // Store file
                    $path = $file->store('contracts', 'private');
                    $storedFile = StoredFile::create([
                        'file_path'       => $path,
                        'original_name'   => $file->getClientOriginalName(),
                        'file_size'       => $file->getSize(),
                        'mime_type'       => $file->getMimeType(),
                        'uploaded_by'     => Auth::id(),
                    ]);

                    // Link file to version
                    ContractVersionFile::create([
                        'contract_version_id' => $version->id,
                        'stored_file_id'      => $storedFile->id,
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
    public function edit(ContractVersion $version)
    {
        if (!Auth::user()->hasPermission('contracts.versions.edit')) {
            abort(403, 'Unauthorized action.');
        }

        $contract = $version->contract;

        // Branch access check
        if (!Auth::user()->isSuperAdmin() && $contract->branch_id !== Auth::user()->branch_id) {
            abort(403, 'Unauthorized action.');
        }

        // Load files
        $version->load('files.storedFile');

        return view('contracts.versions.edit', compact('contract', 'version'));
    }

    /**
     * Update an existing version
     */
    public function update(Request $request, ContractVersion $version)
    {
        if (!Auth::user()->hasPermission('contracts.versions.edit')) {
            abort(403, 'Unauthorized action.');
        }

        $contract = $version->contract;

        // Branch access check
        if (!Auth::user()->isSuperAdmin() && $contract->branch_id !== Auth::user()->branch_id) {
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
            // Convert IST to UTC for storage
            $startUtc = Carbon::createFromFormat('Y-m-d', $validated['start_date'], 'Asia/Kolkata')
                ->setTimezone('UTC');
            $endUtc = Carbon::createFromFormat('Y-m-d', $validated['end_date'], 'Asia/Kolkata')
                ->setTimezone('UTC');

            // Update version
            $version->update([
                'start_date'  => $startUtc,
                'end_date'    => $endUtc,
                'description' => $validated['description'] ?? null,
                'updated_by'  => Auth::id(),
            ]);

            // Handle file removals
            if (!empty($validated['remove_files'])) {
                foreach ($validated['remove_files'] as $fileId) {
                    if (empty($fileId)) continue;

                    $versionFile = ContractVersionFile::find($fileId);
                    if ($versionFile && $versionFile->contract_version_id === $version->id) {
                        // Delete from storage
                        if ($versionFile->storedFile) {
                            Storage::disk('private')->delete($versionFile->storedFile->file_path);
                            $versionFile->storedFile->delete();
                        }
                        $versionFile->delete();
                    }
                }
            }

            // Handle new file uploads
            if ($request->hasFile('files')) {
                foreach ($request->file('files') as $file) {
                    // Store file
                    $path = $file->store('contracts', 'private');
                    $storedFile = StoredFile::create([
                        'file_path'       => $path,
                        'original_name'   => $file->getClientOriginalName(),
                        'file_size'       => $file->getSize(),
                        'mime_type'       => $file->getMimeType(),
                        'uploaded_by'     => Auth::id(),
                    ]);

                    // Link file to version
                    ContractVersionFile::create([
                        'contract_version_id' => $version->id,
                        'stored_file_id'      => $storedFile->id,
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
    public function destroy(ContractVersion $version)
    {
        if (!Auth::user()->hasPermission('contracts.versions.delete')) {
            abort(403, 'Unauthorized action.');
        }

        $contract = $version->contract;

        \Log::info('Version deletion started', [
            'version_id' => $version->id,
            'version_number' => $version->version_number,
            'contract_id' => $contract->id,
            'contract_deleted_at_before' => $contract->deleted_at,
        ]);

        // Branch access check
        if (!Auth::user()->isSuperAdmin() && $contract->branch_id !== Auth::user()->branch_id) {
            abort(403, 'Unauthorized action.');
        }

        // Prevent deleting if it's the only version
        $activeVersionsCount = $contract->versions()->count();
        if ($activeVersionsCount <= 1) {
            return redirect()
                ->route('contracts.show', $contract->id)
                ->withErrors(['error' => 'Cannot delete the only version of this contract.']);
        }

        // Soft delete the version
        $version->delete();

        // Reload contract to check status
        $contract->refresh();

        \Log::info('Version deletion completed', [
            'version_deleted' => $version->trashed(),
            'contract_deleted_at_after' => $contract->deleted_at,
        ]);

        return redirect()
            ->route('contracts.show', $contract->id)
            ->with('success', "Version {$version->version_number} deleted successfully!");
    }

    /**
     * Restore a soft-deleted version
     */
    public function restore($id)
    {
        if (!Auth::user()->hasPermission('contracts.versions.restore')) {
            abort(403, 'Unauthorized action.');
        }

        $version = ContractVersion::onlyTrashed()->findOrFail($id);
        $contract = $version->contract;

        // Branch access check
        if (!Auth::user()->isSuperAdmin() && $contract->branch_id !== Auth::user()->branch_id) {
            abort(403, 'Unauthorized action.');
        }

        $version->update(['restored_by' => Auth::id()]);
        $version->restore();

        return redirect()
            ->route('contracts.show', $contract->id)
            ->with('success', "Version {$version->version_number} restored successfully!");
    }

    /**
     * Delete a file from a version
     */
    public function deleteFile(Contract $contract, ContractVersion $version, ContractVersionFile $file)
    {
        if (!Auth::user()->hasPermission('contracts.versions.edit')) {
            abort(403, 'Unauthorized action.');
        }

        // Branch access check
        if (!Auth::user()->isSuperAdmin() && $contract->branch_id !== Auth::user()->branch_id) {
            abort(403, 'Unauthorized action.');
        }

        // Ensure the file belongs to this version
        if ($file->contract_version_id !== $version->id) {
            abort(404, 'File not found.');
        }

        $storedFile = $file->storedFile;

        // Delete the pivot record
        $file->delete();

        // If no other references exist, delete the stored file record and physical file
        if ($storedFile && !ContractVersionFile::where('stored_file_id', $storedFile->id)->exists()) {
            if ($storedFile->file_path && Storage::disk('private')->exists($storedFile->file_path)) {
                Storage::disk('private')->delete($storedFile->file_path);
            }
            $storedFile->delete();
        }

        return redirect()
            ->route('contracts.versions.edit', [$contract, $version])
            ->with('success', 'File deleted successfully!');
    }
}
