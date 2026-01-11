<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        // Roles table
        if (!Schema::hasTable('roles')) {
            Schema::create('roles', function (Blueprint $table) {
                $table->bigIncrements('id');
                $table->string('name')->unique();
                $table->string('slug')->unique()->nullable();
                $table->string('guard_name')->default('web');
                $table->text('description')->nullable();
                $table->boolean('is_active')->default(true);
                $table->integer('priority')->default(1);
                $table->unsignedBigInteger('branch_id')->nullable();
                $table->timestamps();
                $table->softDeletes();
            });
        } else {
            // Ensure columns exist and have sensible defaults
            Schema::table('roles', function (Blueprint $table) {
                if (!Schema::hasColumn('roles', 'guard_name')) {
                    $table->string('guard_name')->default('web')->after('slug');
                }
                if (!Schema::hasColumn('roles', 'priority')) {
                    $table->integer('priority')->default(1)->after('is_active');
                }
                if (!Schema::hasColumn('roles', 'is_active')) {
                    $table->boolean('is_active')->default(true)->after('description');
                }
                if (!Schema::hasColumn('roles', 'branch_id')) {
                    $table->unsignedBigInteger('branch_id')->nullable()->after('priority');
                }
                if (!Schema::hasColumn('roles', 'slug')) {
                    $table->string('slug')->unique()->nullable()->after('name');
                }
            });
        }

        // Permissions table
        if (!Schema::hasTable('permissions')) {
            Schema::create('permissions', function (Blueprint $table) {
                $table->bigIncrements('id');
                $table->string('name')->unique();
                $table->string('slug')->unique()->nullable();
                $table->string('guard_name')->default('web');
                $table->timestamps();
            });
        }

        // role_has_permissions pivot
        if (!Schema::hasTable('role_has_permissions')) {
            Schema::create('role_has_permissions', function (Blueprint $table) {
                $table->unsignedBigInteger('permission_id');
                $table->unsignedBigInteger('role_id');
                $table->primary(['permission_id', 'role_id']);
            });
        }

        // model_has_roles pivot (for spatie)
        if (!Schema::hasTable('model_has_roles')) {
            Schema::create('model_has_roles', function (Blueprint $table) {
                $table->unsignedBigInteger('role_id');
                $table->string('model_type');
                $table->unsignedBigInteger('model_id');
                $table->index(['model_type','model_id']);
                $table->primary(['role_id','model_id','model_type']);
            });
        }

        // audit_logs (ensure exists)
        if (!Schema::hasTable('audit_logs')) {
            Schema::create('audit_logs', function (Blueprint $table) {
                $table->bigIncrements('id');
                $table->unsignedBigInteger('user_id')->nullable();
                $table->string('action');
                $table->string('auditable_type')->nullable();
                $table->unsignedBigInteger('auditable_id')->nullable();
                $table->json('old_values')->nullable();
                $table->json('new_values')->nullable();
                $table->timestamps();
            });
        }
    }

    public function down(): void
    {
        // Downgrade: drop tables created here only if present
        Schema::dropIfExists('model_has_roles');
        Schema::dropIfExists('role_has_permissions');
        Schema::dropIfExists('permissions');
        Schema::dropIfExists('audit_logs');
        // Do not drop roles to avoid accidental data loss when rolling back partial consolidations
    }
};
