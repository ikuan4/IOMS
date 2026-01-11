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
        Schema::create('contract_version_files', function (Blueprint $table) {
            $table->id();

            $table->foreignId('contract_version_id')
                ->constrained('contract_versions')
                ->cascadeOnUpdate()
                ->cascadeOnDelete();

            $table->foreignId('stored_file_id')
                ->constrained('stored_files')
                ->cascadeOnUpdate()
                ->cascadeOnDelete();

            // Display order for attachments (Attachment 1, 2, 3...)
            $table->unsignedInteger('display_order')->default(1);

            $table->timestamps();

            $table->index(['contract_version_id', 'display_order']);
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('contract_version_files');
    }
};
