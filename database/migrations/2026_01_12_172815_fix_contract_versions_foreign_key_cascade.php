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
        Schema::table('contract_versions', function (Blueprint $table) {
            // Drop the existing foreign key
            $table->dropForeign(['contract_id']);

            // Re-add it with RESTRICT instead of CASCADE on delete
            $table->foreign('contract_id')
                ->references('id')
                ->on('contracts')
                ->cascadeOnUpdate()
                ->restrictOnDelete();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('contract_versions', function (Blueprint $table) {
            // Drop the foreign key
            $table->dropForeign(['contract_id']);

            // Re-add it with the old CASCADE behavior
            $table->foreign('contract_id')
                ->references('id')
                ->on('contracts')
                ->cascadeOnUpdate()
                ->cascadeOnDelete();
        });
    }
};
