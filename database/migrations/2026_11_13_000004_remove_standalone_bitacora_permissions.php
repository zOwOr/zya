<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;
use Spatie\Permission\PermissionRegistrar;

return new class extends Migration
{
    public function up(): void
    {
        if (app()->bound(PermissionRegistrar::class)) {
            app()[PermissionRegistrar::class]->forgetCachedPermissions();
        }

        $tableNames = config('permission.table_names', []);
        $permissionsTable = $tableNames['permissions'] ?? 'permissions';
        $roleHasPermissionsTable = $tableNames['role_has_permissions'] ?? 'role_has_permissions';
        $modelHasPermissionsTable = $tableNames['model_has_permissions'] ?? 'model_has_permissions';
        $pivotPermission = PermissionRegistrar::$pivotPermission ?? 'permission_id';

        if (Schema::hasTable($permissionsTable)) {
            $permissionIds = DB::table($permissionsTable)
                ->whereIn('name', [
                    'financieras.bitacora.read',
                    'financieras.bitacora.export_pdf',
                    'financieras.bitacora.export_excel',
                ])
                ->pluck('id')
                ->all();

            if (!empty($permissionIds)) {
                if (Schema::hasTable($roleHasPermissionsTable)) {
                    DB::table($roleHasPermissionsTable)->whereIn($pivotPermission, $permissionIds)->delete();
                }

                if (Schema::hasTable($modelHasPermissionsTable)) {
                    DB::table($modelHasPermissionsTable)->whereIn($pivotPermission, $permissionIds)->delete();
                }

                DB::table($permissionsTable)->whereIn('id', $permissionIds)->delete();
            }
        }

        if (app()->bound(PermissionRegistrar::class)) {
            app()[PermissionRegistrar::class]->forgetCachedPermissions();
        }
    }

    public function down(): void
    {
        if (app()->bound(PermissionRegistrar::class)) {
            app()[PermissionRegistrar::class]->forgetCachedPermissions();
        }
    }
};

