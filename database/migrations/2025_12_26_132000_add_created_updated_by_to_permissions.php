<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        if (Schema::hasTable('permissions')) {
            Schema::table('permissions', function (Blueprint $table) {
                if (! Schema::hasColumn('permissions', 'created_by')) {
                    $table->unsignedBigInteger('created_by')->nullable()->index();
                }
                if (! Schema::hasColumn('permissions', 'updated_by')) {
                    $table->unsignedBigInteger('updated_by')->nullable()->index();
                }
            });
        }
    }

    public function down(): void
    {
        if (Schema::hasTable('permissions')) {
            Schema::table('permissions', function (Blueprint $table) {
                if (Schema::hasColumn('permissions', 'updated_by')) {
                    $table->dropColumn('updated_by');
                }
                if (Schema::hasColumn('permissions', 'created_by')) {
                    $table->dropColumn('created_by');
                }
            });
        }
    }
};
