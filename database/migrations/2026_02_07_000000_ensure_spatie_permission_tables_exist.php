<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        // Use Spatie config when available, but fall back to defaults so this migration
        // remains safe even if config is not yet cached/loaded.
        $tableNames = (array) config('permission.table_names', []);
        $columnNames = (array) config('permission.column_names', []);
        $teams = (bool) config('permission.teams', false);

        $rolesTable = $tableNames['roles'] ?? 'roles';
        $permissionsTable = $tableNames['permissions'] ?? 'permissions';
        $roleHasPermissionsTable = $tableNames['role_has_permissions'] ?? 'role_has_permissions';
        $modelHasRolesTable = $tableNames['model_has_roles'] ?? 'model_has_roles';
        $modelHasPermissionsTable = $tableNames['model_has_permissions'] ?? 'model_has_permissions';

        $pivotRoleKey = $columnNames['role_pivot_key'] ?? 'role_id';
        $pivotPermissionKey = $columnNames['permission_pivot_key'] ?? 'permission_id';
        $modelMorphKey = $columnNames['model_morph_key'] ?? 'model_id';
        $teamForeignKey = $columnNames['team_foreign_key'] ?? 'team_id';

        // 1) Ensure permissions table exists (roles may already exist in production).
        if (!Schema::hasTable($permissionsTable)) {
            Schema::create($permissionsTable, static function (Blueprint $table) {
                $table->bigIncrements('id');
                $table->string('name');
                $table->string('guard_name')->default('web');
                $table->string('slug')->nullable();
                $table->string('group')->nullable();
                $table->text('description')->nullable();
                $table->foreignId('created_by')->nullable()->index();
                $table->foreignId('updated_by')->nullable()->index();
                $table->foreignId('deleted_by')->nullable()->index();
                $table->foreignId('restored_by')->nullable()->index();
                $table->timestamps();
                $table->softDeletes();
                $table->timestamp('restored_at')->nullable();

                $table->unique(['name', 'guard_name']);
            });
        }

        // 2) Ensure pivot tables exist.
        // Only create pivots once referenced core tables exist.
        $hasCore = Schema::hasTable($rolesTable) && Schema::hasTable($permissionsTable);
        if (!$hasCore) {
            return;
        }

        if (!Schema::hasTable($roleHasPermissionsTable)) {
            Schema::create($roleHasPermissionsTable, static function (Blueprint $table) use ($rolesTable, $permissionsTable, $pivotRoleKey, $pivotPermissionKey) {
                $table->foreignId($pivotPermissionKey);
                $table->foreignId($pivotRoleKey);

                $table->foreign($pivotPermissionKey)
                    ->references('id')
                    ->on($permissionsTable)
                    ->onDelete('cascade');

                $table->foreign($pivotRoleKey)
                    ->references('id')
                    ->on($rolesTable)
                    ->onDelete('cascade');

                $table->primary([$pivotPermissionKey, $pivotRoleKey], 'role_has_permissions_permission_id_role_id_primary');
                $table->timestamps();
            });
        }

        if (!Schema::hasTable($modelHasPermissionsTable)) {
            Schema::create($modelHasPermissionsTable, static function (Blueprint $table) use ($permissionsTable, $pivotPermissionKey, $modelMorphKey, $teams, $teamForeignKey) {
                $table->foreignId($pivotPermissionKey);

                $table->string('model_type');
                $table->bigInteger($modelMorphKey);
                $table->index([$modelMorphKey, 'model_type'], 'model_has_permissions_model_id_model_type_index');

                $table->foreign($pivotPermissionKey)
                    ->references('id')
                    ->on($permissionsTable)
                    ->onDelete('cascade');

                if ($teams) {
                    $table->foreignId($teamForeignKey);
                    $table->index($teamForeignKey, 'model_has_permissions_team_foreign_key_index');

                    $table->primary([$teamForeignKey, $pivotPermissionKey, $modelMorphKey, 'model_type'], 'model_has_permissions_permission_model_type_primary');
                } else {
                    $table->primary([$pivotPermissionKey, $modelMorphKey, 'model_type'], 'model_has_permissions_permission_model_type_primary');
                }

                $table->timestamps();
            });
        }

        if (!Schema::hasTable($modelHasRolesTable)) {
            Schema::create($modelHasRolesTable, static function (Blueprint $table) use ($rolesTable, $pivotRoleKey, $modelMorphKey, $teams, $teamForeignKey) {
                $table->foreignId($pivotRoleKey);

                $table->string('model_type');
                $table->bigInteger($modelMorphKey);
                $table->index([$modelMorphKey, 'model_type'], 'model_has_roles_model_id_model_type_index');

                $table->foreign($pivotRoleKey)
                    ->references('id')
                    ->on($rolesTable)
                    ->onDelete('cascade');

                if ($teams) {
                    $table->foreignId($teamForeignKey);
                    $table->index($teamForeignKey, 'model_has_roles_team_foreign_key_index');

                    $table->primary([$teamForeignKey, $pivotRoleKey, $modelMorphKey, 'model_type'], 'model_has_roles_role_model_type_primary');
                } else {
                    $table->primary([$pivotRoleKey, $modelMorphKey, 'model_type'], 'model_has_roles_role_model_type_primary');
                }

                $table->timestamps();
            });
        }
    }

    public function down(): void
    {
        // Intentionally non-destructive. Never drop tables in production.
    }
};
