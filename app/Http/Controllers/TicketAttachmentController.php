<?php

namespace App\Http\Controllers;

use App\Models\StoredFile;
use App\Models\Ticket;
use App\Models\TicketComment;
use App\Models\TicketCommentDraftFile;
use App\Models\TicketCommentFile;
use App\Models\TicketDraftFile;
use App\Models\TicketFile;
use Illuminate\Http\Request;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Schema;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Str;

class TicketAttachmentController extends Controller
{
    public function uploadDraft(Request $request): mixed
    {
        $user = Auth::user();
        if (!$user) {
            abort(403, 'Unauthorized action.');
        }

        $data = $request->validate([
            'draft_key' => ['required', 'string', 'max:64'],
            'file' => ['required', 'file', 'max:20480'],
        ]);

        $branchId = (int) ($user->branch_id ?? 0);
        if (!$user->isSuperAdmin() && !$branchId) {
            abort(422, 'User branch is not set.');
        }

        /** @var UploadedFile $file */
        $file = $request->file('file');

        $storedFile = $this->storeUploadedFile($file, $branchId, "branches/{$branchId}/tickets/drafts/{$data['draft_key']}");

        if (Schema::hasTable('ticket_draft_files')) {
            TicketDraftFile::firstOrCreate([
                'draft_key' => (string) $data['draft_key'],
                'stored_file_id' => (int) $storedFile->getKey(),
            ]);
        }

        return response()->json([
            'stored_file_id' => (int) $storedFile->getKey(),
            'filename' => (string) $storedFile->original_filename,
            'mime_type' => (string) ($storedFile->mime_type ?? ''),
            'url' => null,
        ]);
    }

    public function deleteDraft(Request $request): mixed
    {
        $user = Auth::user();
        if (!$user) {
            abort(403, 'Unauthorized action.');
        }

        $data = $request->validate([
            'draft_key' => ['required', 'string', 'max:64'],
            'stored_file_id' => ['required', 'integer'],
        ]);

        if (!Schema::hasTable('ticket_draft_files')) {
            return response()->json(['ok' => true]);
        }

        TicketDraftFile::query()
            ->where('draft_key', (string) $data['draft_key'])
            ->where('stored_file_id', (int) $data['stored_file_id'])
            ->delete();

        return response()->json(['ok' => true]);
    }

    public function uploadTicket(Request $request, Ticket $ticket): mixed
    {
        $user = Auth::user();
        if (!$user) {
            abort(403, 'Unauthorized action.');
        }

        $canViewAll = $user->isSuperAdmin() || $user->hasPermission('tickets.view');
        $canViewMine = $user->isSuperAdmin() || $user->hasPermission('tickets.pending.view');
        if (!$canViewAll && !$canViewMine) {
            abort(403, 'Unauthorized action.');
        }

        if (!$user->isSuperAdmin() && (int) $ticket->branch_id !== (int) $user->branch_id) {
            abort(403, 'Unauthorized action.');
        }

        $data = $request->validate([
            'file' => ['required', 'file', 'max:20480'],
        ]);

        /** @var UploadedFile $file */
        $file = $request->file('file');
        $branchId = (int) $ticket->branch_id;

        $storedFile = $this->storeUploadedFile($file, $branchId, "branches/{$branchId}/tickets/{$ticket->getKey()}");

        if (Schema::hasTable('ticket_files')) {
            TicketFile::firstOrCreate([
                'ticket_id' => (int) $ticket->getKey(),
                'stored_file_id' => (int) $storedFile->getKey(),
            ]);
        }

        $inlineUrl = route('tickets.files.inline', ['ticket' => $ticket->getKey(), 'storedFile' => $storedFile->getKey()]);

        return response()->json([
            'stored_file_id' => (int) $storedFile->getKey(),
            'filename' => (string) $storedFile->original_filename,
            'mime_type' => (string) ($storedFile->mime_type ?? ''),
            'url' => $inlineUrl,
        ]);
    }

    public function uploadCommentDraft(Request $request, Ticket $ticket): mixed
    {
        $user = Auth::user();
        if (!$user) {
            abort(403, 'Unauthorized action.');
        }

        $canViewAll = $user->isSuperAdmin() || $user->hasPermission('tickets.view');
        $canViewMine = $user->isSuperAdmin() || $user->hasPermission('tickets.pending.view');
        if (!$canViewAll && !$canViewMine) {
            abort(403, 'Unauthorized action.');
        }

        if (!$user->isSuperAdmin() && (int) $ticket->branch_id !== (int) $user->branch_id) {
            abort(403, 'Unauthorized action.');
        }

        $data = $request->validate([
            'draft_key' => ['required', 'string', 'max:64'],
            'file' => ['required', 'file', 'max:20480'],
        ]);

        /** @var UploadedFile $file */
        $file = $request->file('file');
        $branchId = (int) $ticket->branch_id;

        $storedFile = $this->storeUploadedFile($file, $branchId, "branches/{$branchId}/tickets/{$ticket->getKey()}/comment-drafts/{$data['draft_key']}");

        if (Schema::hasTable('ticket_comment_draft_files')) {
            TicketCommentDraftFile::firstOrCreate([
                'ticket_id' => (int) $ticket->getKey(),
                'draft_key' => (string) $data['draft_key'],
                'stored_file_id' => (int) $storedFile->getKey(),
            ]);
        }

        return response()->json([
            'stored_file_id' => (int) $storedFile->getKey(),
            'filename' => (string) $storedFile->original_filename,
            'mime_type' => (string) ($storedFile->mime_type ?? ''),
            'url' => null,
        ]);
    }

    public function deleteCommentDraft(Request $request, Ticket $ticket): mixed
    {
        $user = Auth::user();
        if (!$user) {
            abort(403, 'Unauthorized action.');
        }

        $canViewAll = $user->isSuperAdmin() || $user->hasPermission('tickets.view');
        $canViewMine = $user->isSuperAdmin() || $user->hasPermission('tickets.pending.view');
        if (!$canViewAll && !$canViewMine) {
            abort(403, 'Unauthorized action.');
        }

        if (!$user->isSuperAdmin() && (int) $ticket->branch_id !== (int) $user->branch_id) {
            abort(403, 'Unauthorized action.');
        }

        $data = $request->validate([
            'draft_key' => ['required', 'string', 'max:64'],
            'stored_file_id' => ['required', 'integer'],
        ]);

        if (!Schema::hasTable('ticket_comment_draft_files')) {
            return response()->json(['ok' => true]);
        }

        TicketCommentDraftFile::query()
            ->where('ticket_id', (int) $ticket->getKey())
            ->where('draft_key', (string) $data['draft_key'])
            ->where('stored_file_id', (int) $data['stored_file_id'])
            ->delete();

        return response()->json(['ok' => true]);
    }

    public function inline(Request $request, Ticket $ticket, StoredFile $storedFile): mixed
    {
        $user = Auth::user();
        if (!$user) {
            abort(403, 'Unauthorized action.');
        }

        $canViewAll = $user->isSuperAdmin() || $user->hasPermission('tickets.view');
        $canViewMine = $user->isSuperAdmin() || $user->hasPermission('tickets.pending.view');
        if (!$canViewAll && !$canViewMine) {
            abort(403, 'Unauthorized action.');
        }

        if (!$user->isSuperAdmin() && (int) $ticket->branch_id !== (int) $user->branch_id) {
            abort(403, 'Unauthorized action.');
        }

        // Ensure file is linked to ticket (ticket_files or any comment file)
        $linked = false;
        if (Schema::hasTable('ticket_files')) {
            $linked = TicketFile::query()
                ->where('ticket_id', (int) $ticket->getKey())
                ->where('stored_file_id', (int) $storedFile->getKey())
                ->exists();
        }

        if (!$linked && Schema::hasTable('ticket_comment_files')) {
            $linked = TicketCommentFile::query()
                ->where('stored_file_id', (int) $storedFile->getKey())
                ->whereHas('comment', function ($q) use ($ticket) {
                    $q->where('ticket_id', (int) $ticket->getKey());
                })
                ->exists();
        }

        if (!$linked) {
            abort(404);
        }

        $disk = Storage::disk($storedFile->disk);
        if (!$storedFile->path || !$disk->exists($storedFile->path)) {
            abort(404);
        }

        $path = $disk->path($storedFile->path);
        $headers = [];
        if (!empty($storedFile->mime_type)) {
            $headers['Content-Type'] = $storedFile->mime_type;
        }
        $headers['Content-Disposition'] = 'inline; filename="' . addslashes($storedFile->download_name) . '"';

        return response()->file($path, $headers);
    }

    public function download(Request $request, Ticket $ticket, StoredFile $storedFile): mixed
    {
        $inlineResponse = $this->inline($request, $ticket, $storedFile);
        // Replace inline disposition with attachment
        $disk = Storage::disk($storedFile->disk);
        $path = $disk->path($storedFile->path);

        return response()->download($path, $storedFile->download_name, [
            'Content-Type' => $storedFile->mime_type ?? 'application/octet-stream',
        ]);
    }

    private function storeUploadedFile(UploadedFile $file, int $branchId, string $folder): StoredFile
    {
        $sha256 = hash_file('sha256', $file->getRealPath());

        $existing = StoredFile::query()
            ->where('branch_id', $branchId)
            ->where('sha256', $sha256)
            ->first();

        if ($existing) {
            $disk = Storage::disk($existing->disk);
            if ($existing->path && $disk->exists($existing->path)) {
                return $existing;
            }
        }

        $diskName = 'local';
        $path = $file->store($folder, $diskName);
        if ($path === false) {
            throw new \RuntimeException('Unable to store uploaded file');
        }

        if ($existing) {
            $existing->disk = $diskName;
            $existing->path = $path;
            $existing->original_filename = $file->getClientOriginalName();
            $existing->mime_type = $file->getClientMimeType();
            $existing->size_bytes = $file->getSize();
            $existing->save();
            return $existing;
        }

        // Use create but guard against unique constraint races
        try {
            return StoredFile::create([
                'branch_id' => $branchId,
                'disk' => $diskName,
                'path' => $path,
                'original_filename' => $file->getClientOriginalName(),
                'mime_type' => $file->getClientMimeType(),
                'size_bytes' => $file->getSize(),
                'sha256' => $sha256,
            ]);
        } catch (\Throwable $e) {
            $found = StoredFile::query()
                ->where('branch_id', $branchId)
                ->where('sha256', $sha256)
                ->first();

            if ($found) {
                return $found;
            }

            throw $e;
        }
    }
}
