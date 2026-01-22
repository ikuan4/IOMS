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
        if (!Schema::hasTable('tickets')) {
            return;
        }

        if (Schema::hasColumn('tickets', 'ticket_module_id')) {
            return;
        }

        Schema::table('tickets', function (Blueprint $table) {
            $table->foreignId('ticket_module_id')
                ->after('ticket_type_id')
                ->constrained('ticket_modules')
                ->cascadeOnUpdate()
                ->restrictOnDelete();

            $table->index(['branch_id', 'ticket_module_id']);
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        if (!Schema::hasTable('tickets') || !Schema::hasColumn('tickets', 'ticket_module_id')) {
            return;
        }

        Schema::table('tickets', function (Blueprint $table) {
            $table->dropConstrainedForeignId('ticket_module_id');
        });
    }
};
