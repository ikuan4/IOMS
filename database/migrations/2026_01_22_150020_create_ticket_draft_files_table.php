<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        if (Schema::hasTable('ticket_draft_files')) {
            return;
        }

        Schema::create('ticket_draft_files', function (Blueprint $table) {
            $table->id();

            $table->string('draft_key', 64);

            $table->foreignId('stored_file_id')
                ->constrained('stored_files')
                ->cascadeOnUpdate()
                ->cascadeOnDelete();

            $table->timestamps();

            $table->index(['draft_key', 'created_at']);
            $table->unique(['draft_key', 'stored_file_id']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('ticket_draft_files');
    }
};
