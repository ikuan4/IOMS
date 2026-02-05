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
        Schema::create('contracts', function (Blueprint $table) {
            $table->id();

            // Branch relationship
            $table->foreignId('branch_id')
                ->constrained('branches')
                ->cascadeOnUpdate()
                ->restrictOnDelete();

            // Contract type relationship
            $table->foreignId('contract_type_id')
                ->constrained('contract_types')
                ->cascadeOnUpdate()
                ->restrictOnDelete();

            // Contract number format: CT-{BRANCH_ID}/{TYPE3}/{YYYY}/{id}
            $table->string('contract_number')->unique();

            // Counterparty name
            $table->string('contract_with');

            // Grace period for "Expiring Soon" status
            $table->integer('grace_period_days')->default(30);

            // Active/Inactive flag
            $table->boolean('is_active')->default(true);

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
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('contracts');
    }
};
