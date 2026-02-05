<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        $tableNames = config('permission.table_names', []);
        $columnNames = config('permission.column_names', []);

        $rolesTable = $tableNames['roles'] ?? 'roles';
        $permissionsTable = $tableNames['permissions'] ?? 'permissions';
        $roleHasPermissionsTable = $tableNames['role_has_permissions'] ?? 'role_has_permissions';
        $modelHasRolesTable = $tableNames['model_has_roles'] ?? 'model_has_roles';
        $modelHasPermissionsTable = $tableNames['model_has_permissions'] ?? 'model_has_permissions';

        $pivotRoleKey = $columnNames['role_pivot_key'] ?? 'role_id';
        $pivotPermissionKey = $columnNames['permission_pivot_key'] ?? 'permission_id';
        $modelMorphKey = $columnNames['model_morph_key'] ?? 'model_id';

        // If the core tables are missing, other app migrations are expected to create them.
        // We only create pivots when the referenced tables exist.
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

        if (!Schema::hasTable($modelHasRolesTable)) {
            Schema::create($modelHasRolesTable, static function (Blueprint $table) use ($rolesTable, $pivotRoleKey, $modelMorphKey) {
                $table->foreignId($pivotRoleKey);
                $table->string('model_type');
                $table->bigInteger($modelMorphKey);
                $table->index([$modelMorphKey, 'model_type'], 'model_has_roles_model_id_model_type_index');

                $table->foreign($pivotRoleKey)
                    ->references('id')
                    ->on($rolesTable)
                    ->onDelete('cascade');

                $table->primary([$pivotRoleKey, $modelMorphKey, 'model_type'], 'model_has_roles_role_model_type_primary');
                $table->timestamps();
            });
        }

        if (!Schema::hasTable($modelHasPermissionsTable)) {
            Schema::create($modelHasPermissionsTable, static function (Blueprint $table) use ($permissionsTable, $pivotPermissionKey, $modelMorphKey) {
                $table->foreignId($pivotPermissionKey);
                $table->string('model_type');
                $table->bigInteger($modelMorphKey);
                $table->index([$modelMorphKey, 'model_type'], 'model_has_permissions_model_id_model_type_index');

                $table->foreign($pivotPermissionKey)
                    ->references('id')
                    ->on($permissionsTable)
                    ->onDelete('cascade');

                $table->primary([$pivotPermissionKey, $modelMorphKey, 'model_type'], 'model_has_permissions_permission_model_type_primary');
                $table->timestamps();
            });
        }

        // Your codebase also uses this pivot (created in the original permission migration);
        // ensure it exists for older DBs.
        if (!Schema::hasTable('user_role')) {
            Schema::create('user_role', static function (Blueprint $table) use ($rolesTable) {
                $table->id();
                $table->foreignId('user_id');
                $table->foreignId('role_id');
                $table->timestamps();

                $table->unique(['user_id', 'role_id']);
                $table->index('user_id');
                $table->index('role_id');

                $table->foreign('user_id')->references('id')->on('users')->onDelete('cascade');
                $table->foreign('role_id')->references('id')->on($rolesTable)->onDelete('cascade');
            });
        }
    }

    public function down(): void
    {
        $tableNames = config('permission.table_names', []);

        $roleHasPermissionsTable = $tableNames['role_has_permissions'] ?? 'role_has_permissions';
        $modelHasRolesTable = $tableNames['model_has_roles'] ?? 'model_has_roles';
        $modelHasPermissionsTable = $tableNames['model_has_permissions'] ?? 'model_has_permissions';

        Schema::dropIfExists('user_role');
        Schema::dropIfExists($roleHasPermissionsTable);
        Schema::dropIfExists($modelHasRolesTable);
        Schema::dropIfExists($modelHasPermissionsTable);
    }
};
