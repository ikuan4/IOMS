<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        if (!Schema::hasTable('ticket_comment_draft_files')) {
            Schema::create('ticket_comment_draft_files', function (Blueprint $table) {
                $table->id();

                $table->foreignId('ticket_id')
                    ->constrained('tickets')
                    ->cascadeOnUpdate()
                    ->cascadeOnDelete();

                $table->string('draft_key', 64);

                $table->foreignId('stored_file_id')
                    ->constrained('stored_files')
                    ->cascadeOnUpdate()
                    ->cascadeOnDelete();

                $table->timestamps();

                // Explicit short names: MySQL identifier limit is 64 chars.
                $table->index(['ticket_id', 'draft_key', 'created_at'], 'tcdraft_t_dk_ca_idx');
                $table->unique(['ticket_id', 'draft_key', 'stored_file_id'], 'tcdraft_t_dk_sf_uq');
            });
            return;
        }

        // If a previous run partially created the table but failed on the constraint name,
        // ensure the missing indexes exist (ignore if already present).
        try {
            Schema::table('ticket_comment_draft_files', function (Blueprint $table) {
                $table->index(['ticket_id', 'draft_key', 'created_at'], 'tcdraft_t_dk_ca_idx');
            });
        } catch (Throwable $e) {
            $msg = $e->getMessage();
            if (!is_string($msg) || stripos($msg, 'Duplicate key name') === false) {
                throw $e;
            }
        }

        try {
            Schema::table('ticket_comment_draft_files', function (Blueprint $table) {
                $table->unique(['ticket_id', 'draft_key', 'stored_file_id'], 'tcdraft_t_dk_sf_uq');
            });
        } catch (Throwable $e) {
            $msg = $e->getMessage();
            if (!is_string($msg) || stripos($msg, 'Duplicate key name') === false) {
                throw $e;
            }
        }
    }

    public function down(): void
    {
        Schema::dropIfExists('ticket_comment_draft_files');
    }
};
