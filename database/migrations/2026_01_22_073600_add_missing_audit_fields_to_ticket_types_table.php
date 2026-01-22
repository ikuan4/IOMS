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
        if (!Schema::hasTable('ticket_types')) {
            return;
        }

        Schema::table('ticket_types', function (Blueprint $table) {
            if (!Schema::hasColumn('ticket_types', 'created_by')) {
                $table->foreignId('created_by')->nullable()
                    ->constrained('users')
                    ->nullOnDelete();
            }

            if (!Schema::hasColumn('ticket_types', 'updated_by')) {
                $table->foreignId('updated_by')->nullable()
                    ->constrained('users')
                    ->nullOnDelete();
            }

            if (!Schema::hasColumn('ticket_types', 'deleted_by')) {
                $table->foreignId('deleted_by')->nullable()
                    ->constrained('users')
                    ->nullOnDelete();
            }

            if (!Schema::hasColumn('ticket_types', 'restored_by')) {
                $table->foreignId('restored_by')->nullable()
                    ->constrained('users')
                    ->nullOnDelete();
            }

            if (!Schema::hasColumn('ticket_types', 'restored_at')) {
                $table->timestamp('restored_at')->nullable();
            }
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        if (!Schema::hasTable('ticket_types')) {
            return;
        }

        Schema::table('ticket_types', function (Blueprint $table) {
            if (Schema::hasColumn('ticket_types', 'restored_at')) {
                $table->dropColumn('restored_at');
            }

            if (Schema::hasColumn('ticket_types', 'restored_by')) {
                $table->dropConstrainedForeignId('restored_by');
            }

            if (Schema::hasColumn('ticket_types', 'deleted_by')) {
                $table->dropConstrainedForeignId('deleted_by');
            }

            if (Schema::hasColumn('ticket_types', 'updated_by')) {
                $table->dropConstrainedForeignId('updated_by');
            }

            if (Schema::hasColumn('ticket_types', 'created_by')) {
                $table->dropConstrainedForeignId('created_by');
            }
        });
    }
};
