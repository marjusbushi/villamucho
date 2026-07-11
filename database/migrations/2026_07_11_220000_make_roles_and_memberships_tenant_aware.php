<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        $tenantId = DB::table('tenants')->orderBy('id')->value('id');
        $teamKey = config('permission.column_names.team_foreign_key', 'team_id');
        $tables = config('permission.table_names');
        $columns = config('permission.column_names');
        $rolePivot = $columns['role_pivot_key'] ?? 'role_id';
        $permissionPivot = $columns['permission_pivot_key'] ?? 'permission_id';

        Schema::table('tenant_user', function (Blueprint $table) {
            $table->boolean('is_active')->default(true)->after('is_owner')->index();
        });

        if (! Schema::hasColumn($tables['roles'], $teamKey)) {
            Schema::table($tables['roles'], function (Blueprint $table) use ($teamKey) {
                $table->foreignId($teamKey)->nullable()->after('id');
                $table->index($teamKey, 'roles_team_foreign_key_index');
                $table->dropUnique('roles_name_guard_name_unique');
                $table->unique([$teamKey, 'name', 'guard_name']);
            });
        }

        DB::table($tables['roles'])->whereNull($teamKey)->update([$teamKey => $tenantId]);

        $this->upgradePermissionPivot(
            $tables['model_has_permissions'],
            $teamKey,
            $permissionPivot,
            'model_has_permissions_permission_model_type_primary',
            $tables['permissions'],
            $tenantId,
        );

        $this->upgradePermissionPivot(
            $tables['model_has_roles'],
            $teamKey,
            $rolePivot,
            'model_has_roles_role_model_type_primary',
            $tables['roles'],
            $tenantId,
        );

        Schema::table($tables['roles'], function (Blueprint $table) use ($teamKey) {
            $table->foreign($teamKey)->references('id')->on('tenants')->cascadeOnDelete();
        });

        foreach ([$tables['model_has_permissions'], $tables['model_has_roles']] as $pivotTable) {
            Schema::table($pivotTable, function (Blueprint $table) use ($teamKey) {
                $table->foreign($teamKey)->references('id')->on('tenants')->cascadeOnDelete();
            });
        }

        app('cache')
            ->store(config('permission.cache.store') !== 'default' ? config('permission.cache.store') : null)
            ->forget(config('permission.cache.key'));
    }

    private function upgradePermissionPivot(
        string $tableName,
        string $teamKey,
        string $relatedKey,
        string $primaryName,
        string $relatedTable,
        int $tenantId,
    ): void {
        if (Schema::hasColumn($tableName, $teamKey)) {
            DB::table($tableName)->whereNull($teamKey)->update([$teamKey => $tenantId]);

            return;
        }

        Schema::table($tableName, function (Blueprint $table) use ($teamKey) {
            $table->unsignedBigInteger($teamKey)->nullable();
            $table->index($teamKey, $table->getTable().'_team_foreign_key_index');
        });

        DB::table($tableName)->whereNull($teamKey)->update([$teamKey => $tenantId]);

        Schema::table($tableName, function (Blueprint $table) use ($relatedKey) {
            if (DB::getDriverName() !== 'sqlite') {
                $table->dropForeign([$relatedKey]);
            }
            $table->dropPrimary();
        });

        Schema::table($tableName, function (Blueprint $table) use ($teamKey) {
            $table->unsignedBigInteger($teamKey)->nullable(false)->change();
        });

        Schema::table($tableName, function (Blueprint $table) use ($teamKey, $relatedKey, $primaryName, $relatedTable) {
            $table->primary(
                [$teamKey, $relatedKey, config('permission.column_names.model_morph_key'), 'model_type'],
                $primaryName,
            );

            if (DB::getDriverName() !== 'sqlite') {
                $table->foreign($relatedKey)->references('id')->on($relatedTable)->cascadeOnDelete();
            }
        });
    }

    public function down(): void
    {
        // Intentionally irreversible: collapsing multiple hotels' role assignments
        // into global rows would silently grant cross-hotel access.
    }
};
