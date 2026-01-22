<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        if (Schema::hasTable('ticket_comment_files')) {
            return;
        }

        Schema::create('ticket_comment_files', function (Blueprint $table) {
            $table->id();

            $table->foreignId('ticket_comment_id')
                ->constrained('ticket_comments')
                ->cascadeOnUpdate()
                ->cascadeOnDelete();

            $table->foreignId('stored_file_id')
                ->constrained('stored_files')
                ->cascadeOnUpdate()
                ->cascadeOnDelete();

            $table->timestamps();

            $table->unique(['ticket_comment_id', 'stored_file_id']);
            $table->index(['ticket_comment_id', 'created_at']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('ticket_comment_files');
    }
};
