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
        Schema::create('contract_versions', function (Blueprint $table) {
            $table->id();

            $table->foreignId('contract_id')
                ->constrained('contracts')
                ->cascadeOnUpdate()
                ->restrictOnDelete();

            // Version number: 1, 2, 3, ...
            $table->integer('version_number');

            // Optional description for this version
            $table->text('description')->nullable();

            // Dates stored as UTC, displayed as IST
            $table->dateTime('start_date');
            $table->dateTime('end_date');

            // Audit fields
            $table->foreignId('created_by')->nullable()
                ->constrained('users')
                ->nullOnDelete();

            $table->foreignId('updated_by')->nullable()
                ->constrained('users')
                ->nullOnDelete();

            $table->foreignId('deleted_by')->nullable()
                ->constrained('users')
                ->nullOnDelete();

            $table->foreignId('restored_by')->nullable()
                ->constrained('users')
                ->nullOnDelete();

            $table->timestamps();
            $table->softDeletes();
            $table->timestamp('restored_at')->nullable();

            // Unique version number per contract
            $table->unique(['contract_id', 'version_number']);
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('contract_versions');
    }
};
