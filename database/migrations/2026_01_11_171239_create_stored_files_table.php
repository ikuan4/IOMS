<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Run the migrations.
     */
    public function up(): void
    {
        Schema::create('stored_files', function (Blueprint $table) {
            $table->id();

            // Branch-specific file storage with deduplication per branch
            $table->foreignId('branch_id')
                ->constrained('branches')
                ->cascadeOnUpdate()
                ->restrictOnDelete();

            // Storage disk (e.g., 'local', 's3')
            $table->string('disk')->default('local');

            // Path relative to disk root: branches/{branch_id}/contracts/{filename}
            $table->string('path');

            // Original filename as uploaded
            $table->string('original_filename');

            // MIME type and size
            $table->string('mime_type')->nullable();
            $table->unsignedBigInteger('size_bytes')->nullable();

            // SHA-256 for deduplication within branch
            $table->string('sha256', 64);

            $table->timestamps();

            // Unique SHA-256 per branch (allow same file in different branches)
            $table->unique(['branch_id', 'sha256']);
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('stored_files');
    }
};
