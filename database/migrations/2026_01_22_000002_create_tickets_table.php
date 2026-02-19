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
        if (Schema::hasTable('tickets')) {
            return;
        }

        Schema::create('tickets', function (Blueprint $table) {
            $table->id();

            $table->foreignId('branch_id')
                ->constrained('branches')
                ->cascadeOnUpdate()
                ->restrictOnDelete();

            $table->foreignId('ticket_type_id')
                ->constrained('ticket_types')
                ->cascadeOnUpdate()
                ->restrictOnDelete();

            $table->foreignId('ticket_module_id')
                ->constrained('ticket_modules')
                ->cascadeOnUpdate()
                ->restrictOnDelete();

            $table->string('subject');
            $table->text('description')->nullable();

            $table->string('status', 32)->default('open');
            $table->string('priority', 32)->default('medium');

            $table->foreignId('assigned_to')->nullable()
                ->constrained('users')
                ->nullOnDelete();

            $table->timestamp('due_at')->nullable();

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

            $table->index(['branch_id', 'ticket_type_id']);
            $table->index(['branch_id', 'ticket_module_id']);
            $table->index(['branch_id', 'status']);
            $table->index(['branch_id', 'priority']);
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('tickets');
    }
};
