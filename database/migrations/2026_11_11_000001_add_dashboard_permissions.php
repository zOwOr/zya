<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\DB;
use Spatie\Permission\Models\Role;
use Spatie\Permission\PermissionRegistrar;

return new class extends Migration
{
    public function up(): void
    {
        if (app()->bound(PermissionRegistrar::class)) {
            app()[PermissionRegistrar::class]->forgetCachedPermissions();
        }

        $permissions = [
            'dashboard.menu' => ['group' => 'dashboard', 'display_name' => 'Dashboard: Acceso al Menú'],
            'dashboard.read' => ['group' => 'dashboard', 'display_name' => 'Dashboard: Ver Métricas y Panel Central'],
            'dashboard.export' => ['group' => 'dashboard', 'display_name' => 'Dashboard: Exportar Reportes (PDF / Excel)'],
            'dashboard.cancel_transaction' => ['group' => 'dashboard', 'display_name' => 'Dashboard: Cancelar Movimientos POS'],
            'dashboard.all_branches' => ['group' => 'dashboard', 'display_name' => 'Dashboard: Ver Datos de Todas las Sucursales'],
        ];

        $superAdminRoles = DB::table('roles')
            ->whereIn('name', ['SuperAdmin', 'Super Administrador', 'superadmin', 'Superadmin'])
            ->get();

        foreach ($permissions as $name => $data) {
            $existing = DB::table('permissions')->where('name', $name)->first();
            if (!$existing) {
                $permissionId = DB::table('permissions')->insertGetId([
                    'name' => $name,
                    'display_name' => $data['display_name'],
                    'guard_name' => 'web',
                    'group_name' => $data['group'],
                    'created_at' => now(),
                    'updated_at' => now(),
                ]);
            } else {
                $permissionId = $existing->id;
                DB::table('permissions')->where('id', $permissionId)->update([
                    'display_name' => $data['display_name'],
                    'group_name' => $data['group'],
                    'updated_at' => now(),
                ]);
            }

            foreach ($superAdminRoles as $role) {
                $hasPerm = DB::table('role_has_permissions')
                    ->where('permission_id', $permissionId)
                    ->where('role_id', $role->id)
                    ->exists();

                if (!$hasPerm) {
                    DB::table('role_has_permissions')->insert([
                        'permission_id' => $permissionId,
                        'role_id' => $role->id,
                    ]);
                }
            }
        }

        if (app()->bound(PermissionRegistrar::class)) {
            app()[PermissionRegistrar::class]->forgetCachedPermissions();
        }
    }

    public function down(): void
    {
        $names = [
            'dashboard.menu',
            'dashboard.read',
            'dashboard.export',
            'dashboard.cancel_transaction',
            'dashboard.all_branches',
        ];

        DB::table('role_has_permissions')
            ->whereIn('permission_id', function ($q) use ($names) {
                $q->select('id')->from('permissions')->whereIn('name', $names);
            })->delete();

        DB::table('permissions')->whereIn('name', $names)->delete();

        if (app()->bound(PermissionRegistrar::class)) {
            app()[PermissionRegistrar::class]->forgetCachedPermissions();
        }
    }
};

