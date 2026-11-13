<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\DB;
use Spatie\Permission\PermissionRegistrar;

return new class extends Migration
{
    public function up(): void
    {
        if (app()->bound(PermissionRegistrar::class)) {
            app()[PermissionRegistrar::class]->forgetCachedPermissions();
        }

        $permissions = [
            // Dashboard (Cobros a Financieras, Ventas POS e Histórico de Fechas)
            'dashboard.financieras.export_pdf' => [
                'group' => 'dashboard',
                'display_name' => 'Dashboard - Cobros a Financieras: Exportar PDF',
            ],
            'dashboard.financieras.export_excel' => [
                'group' => 'dashboard',
                'display_name' => 'Dashboard - Cobros a Financieras: Exportar Excel',
            ],
            'dashboard.ventas.export_pdf' => [
                'group' => 'dashboard',
                'display_name' => 'Dashboard - Ventas / Movimientos POS: Exportar PDF',
            ],
            'dashboard.ventas.export_excel' => [
                'group' => 'dashboard',
                'display_name' => 'Dashboard - Ventas / Movimientos POS: Exportar Excel',
            ],
            'dashboard.filter_all_dates' => [
                'group' => 'dashboard',
                'display_name' => 'Dashboard: Filtrar Fechas Históricas (> 2 días) y Todos los Registros',
            ],

            // Financieras (Bitácora Abonos y Enganches, Ventas e Histórico de Fechas)
            'financieras.bitacora.read' => [
                'group' => 'financieras',
                'display_name' => 'Financieras - Bitácora de Abonos y Enganches: Ver / Consultar',
            ],
            'financieras.bitacora.export_pdf' => [
                'group' => 'financieras',
                'display_name' => 'Financieras - Bitácora de Abonos y Enganches: Exportar PDF',
            ],
            'financieras.bitacora.export_excel' => [
                'group' => 'financieras',
                'display_name' => 'Financieras - Bitácora de Abonos y Enganches: Exportar Excel',
            ],
            'financieras.ventas.export_pdf' => [
                'group' => 'financieras',
                'display_name' => 'Financieras - Ventas: Exportar Listado PDF',
            ],
            'financieras.ventas.export_excel' => [
                'group' => 'financieras',
                'display_name' => 'Financieras - Ventas: Exportar Listado Excel',
            ],
            'financieras.filter_all_dates' => [
                'group' => 'financieras',
                'display_name' => 'Financieras: Filtrar Fechas Históricas (> 2 días) y Todos los Registros',
            ],

            // Caja (Cortes X/Z, Reportes de Gastos e Histórico de Fechas)
            'cash.cortes.export_pdf' => [
                'group' => 'cash',
                'display_name' => 'Caja - Cortes (X / Z): Exportar PDF',
            ],
            'cash.cortes.export_excel' => [
                'group' => 'cash',
                'display_name' => 'Caja - Cortes (X / Z): Exportar Excel',
            ],
            'cash.gastos.export_pdf' => [
                'group' => 'cash',
                'display_name' => 'Caja - Reportes de Gastos: Exportar PDF',
            ],
            'cash.gastos.export_excel' => [
                'group' => 'cash',
                'display_name' => 'Caja - Reportes de Gastos: Exportar Excel',
            ],
            'cash.filter_all_dates' => [
                'group' => 'cash',
                'display_name' => 'Caja (Cortes y Gastos): Filtrar Fechas Históricas (> 2 días) y Todos los Registros',
            ],

            // Órdenes y Ventas POS
            'orders.export_pdf' => [
                'group' => 'orders',
                'display_name' => 'Órdenes y Ventas: Exportar Listado PDF',
            ],
            'orders.export_excel' => [
                'group' => 'orders',
                'display_name' => 'Órdenes y Ventas: Exportar Listado Excel',
            ],
            'orders.filter_all_dates' => [
                'group' => 'orders',
                'display_name' => 'Órdenes y Ventas: Filtrar Fechas Históricas (> 2 días) y Todos los Registros',
            ],
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
            'dashboard.financieras.export_pdf',
            'dashboard.financieras.export_excel',
            'dashboard.ventas.export_pdf',
            'dashboard.ventas.export_excel',
            'dashboard.filter_all_dates',
            'financieras.bitacora.read',
            'financieras.bitacora.export_pdf',
            'financieras.bitacora.export_excel',
            'financieras.ventas.export_pdf',
            'financieras.ventas.export_excel',
            'financieras.filter_all_dates',
            'cash.cortes.export_pdf',
            'cash.cortes.export_excel',
            'cash.gastos.export_pdf',
            'cash.gastos.export_excel',
            'cash.filter_all_dates',
            'orders.export_pdf',
            'orders.export_excel',
            'orders.filter_all_dates',
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

