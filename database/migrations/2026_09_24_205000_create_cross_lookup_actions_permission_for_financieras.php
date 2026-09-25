<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\DB;
use Carbon\Carbon;

return new class extends Migration
{
    public function up(): void
    {
        $now = Carbon::now()->toDateTimeString();

        $permissionName = 'financieras.trazabilidad.actions';
        $displayName = 'Financieras: Acciones en Trazabilidad Cruzada';

        // Insertar permiso si no existe
        $existing = DB::table('permissions')->where('name', $permissionName)->first();
        if (!$existing) {
            $permissionId = DB::table('permissions')->insertGetId([
                'name' => $permissionName,
                'display_name' => $displayName,
                'guard_name' => 'web',
                'group_name' => 'financieras',
                'created_at' => $now,
                'updated_at' => $now,
            ]);
        } else {
            $permissionId = $existing->id;
            DB::table('permissions')->where('id', $permissionId)->update([
                'display_name' => $displayName,
                'group_name' => 'financieras',
            ]);
        }

        // Asignar al rol SuperAdmin / Administrador si existe
        $adminRoles = DB::table('roles')->whereIn('name', [
            'SuperAdmin', 'Super Administrador', 'superadmin', 'Superadmin',
            'Admin', 'Administrador', 'admin'
        ])->get();

        foreach ($adminRoles as $role) {
            DB::table('role_has_permissions')->insertOrIgnore([
                'permission_id' => $permissionId,
                'role_id' => $role->id,
            ]);
        }

        // Limpiar caché de Spatie
        if (app()->bound('cache')) {
            app('cache')->forget(config('permission.cache.key', 'spatie.permission.cache'));
        }
    }

    public function down(): void
    {
        $permission = DB::table('permissions')->where('name', 'financieras.trazabilidad.actions')->first();
        if ($permission) {
            DB::table('role_has_permissions')->where('permission_id', $permission->id)->delete();
            DB::table('permissions')->where('id', $permission->id)->delete();
        }

        if (app()->bound('cache')) {
            app('cache')->forget(config('permission.cache.key', 'spatie.permission.cache'));
        }
    }
};
