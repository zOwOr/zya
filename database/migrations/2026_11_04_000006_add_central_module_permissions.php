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
            // Módulo Centralizado de Cobros (POS 4 en 1)
            'cobros.menu' => ['group' => 'cobros', 'display_name' => 'Cobros Centralizados: Acceso al Menú'],
            'cobros.read' => ['group' => 'cobros', 'display_name' => 'Cobros Centralizados: Abrir Consola Multi-Tab'],
            'cobros.create' => ['group' => 'cobros', 'display_name' => 'Cobros Centralizados: Procesar Cobros y Ventas'],
            'cobros.financieras' => ['group' => 'cobros', 'display_name' => 'Cobros Centralizados: Cobrar Abonos de Financieras (TAG/IMEI)'],
            'cobros.prestamos' => ['group' => 'cobros', 'display_name' => 'Cobros Centralizados: Crear y Abonar Préstamos'],
            'cobros.tandas' => ['group' => 'cobros', 'display_name' => 'Cobros Centralizados: Cobrar Aportaciones de Tandas'],
            'cobros.productos' => ['group' => 'cobros', 'display_name' => 'Cobros Centralizados: Vender Productos (Stock y Dinámicos)'],
            'cobros.open_drawer' => ['group' => 'cobros', 'display_name' => 'Cobros Centralizados: Acción Física de Abrir Cajón'],
            'cobros.cancel_transaction' => ['group' => 'cobros', 'display_name' => 'Cobros Centralizados: Cancelar / Gestionar Movimiento Diario'],
            'cobros.all_branches' => ['group' => 'cobros', 'display_name' => 'Cobros Centralizados: Operar en Todas las Sucursales'],

            // Módulo de Préstamos
            'prestamos.menu' => ['group' => 'prestamos', 'display_name' => 'Préstamos: Acceso al Menú'],
            'prestamos.read' => ['group' => 'prestamos', 'display_name' => 'Préstamos: Ver Préstamos y Tabla de Pagos Pendientes'],
            'prestamos.create' => ['group' => 'prestamos', 'display_name' => 'Préstamos: Crear Nuevo Préstamo y Subir Pagaré'],
            'prestamos.edit' => ['group' => 'prestamos', 'display_name' => 'Préstamos: Editar Préstamo y Expediente'],
            'prestamos.delete' => ['group' => 'prestamos', 'display_name' => 'Préstamos: Cancelar / Eliminar Préstamo'],
            'prestamos.abonar' => ['group' => 'prestamos', 'display_name' => 'Préstamos: Registrar Abono de Préstamo'],
            'prestamos.close_manual' => ['group' => 'prestamos', 'display_name' => 'Préstamos: Cerrar Préstamo Manualmente (Cualquier Monto)'],
            'prestamos.catalogs' => ['group' => 'prestamos', 'display_name' => 'Préstamos: Configurar Tipos de Préstamo'],
            'prestamos.export' => ['group' => 'prestamos', 'display_name' => 'Préstamos: Exportar Reportes (Excel / PDF)'],
            'prestamos.all_branches' => ['group' => 'prestamos', 'display_name' => 'Préstamos: Ver Préstamos de Todas las Sucursales'],
            'prestamos.video.superadmin' => ['group' => 'prestamos', 'display_name' => 'Préstamos: Ver y Editar Video de Evidencia (Solo SuperAdmin)'],

            // Ampliaciones Módulo de Financieras
            'financieras.ventas.abonar' => ['group' => 'financieras', 'display_name' => 'Financieras: Registrar Abono por Device ID / TAG'],
            'financieras.dashboard_payments' => ['group' => 'financieras', 'display_name' => 'Financieras: Consultar y Exportar Pagos en Dashboard'],

            // Ampliaciones Módulo de Tandas
            'tandas.abonar' => ['group' => 'tandas', 'display_name' => 'Tandas: Registrar Pago / Aportación de Participante'],
            'tandas.all_branches' => ['group' => 'tandas', 'display_name' => 'Tandas: Ver Tandas de Todas las Sucursales'],

            // Ampliaciones Módulo de Caja y Cortes
            'cash.pre_cut' => ['group' => 'cash', 'display_name' => 'Caja: Generar Pre-Corte (Corte X)'],
            'cash.final_cut' => ['group' => 'cash', 'display_name' => 'Caja: Aplicar Corte Final (Corte Z)'],
            'cash.open_drawer' => ['group' => 'cash', 'display_name' => 'Caja: Abrir Cajón de Dinero Físicamente'],
            'cash.all_branches' => ['group' => 'cash', 'display_name' => 'Caja: Auditar Cajas y Cortes de Todas las Sucursales'],

            // Módulo de Gifts y Promocionales por Proveedor
            'gifts.menu' => ['group' => 'gifts', 'display_name' => 'Gifts: Acceso al Menú'],
            'gifts.read' => ['group' => 'gifts', 'display_name' => 'Gifts: Ver Inventario y Bitácora de Entregas'],
            'gifts.create' => ['group' => 'gifts', 'display_name' => 'Gifts: Registrar Gift y Entrada de Proveedor'],
            'gifts.deliver' => ['group' => 'gifts', 'display_name' => 'Gifts: Registrar Entrega de Gift a Cliente'],
            'gifts.edit' => ['group' => 'gifts', 'display_name' => 'Gifts: Editar / Ajustar Inventario de Gifts'],
            'gifts.delete' => ['group' => 'gifts', 'display_name' => 'Gifts: Eliminar Gift'],
            'gifts.export' => ['group' => 'gifts', 'display_name' => 'Gifts: Exportar Inventario y Entregas (Excel / PDF)'],
            'gifts.all_branches' => ['group' => 'gifts', 'display_name' => 'Gifts: Ver Inventario de Gifts de Todas las Sucursales'],
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
            'cobros.menu', 'cobros.read', 'cobros.create', 'cobros.financieras', 'cobros.prestamos',
            'cobros.tandas', 'cobros.productos', 'cobros.open_drawer', 'cobros.cancel_transaction', 'cobros.all_branches',
            'prestamos.menu', 'prestamos.read', 'prestamos.create', 'prestamos.edit', 'prestamos.delete',
            'prestamos.abonar', 'prestamos.close_manual', 'prestamos.catalogs', 'prestamos.export', 'prestamos.all_branches', 'prestamos.video.superadmin',
            'financieras.ventas.abonar', 'financieras.dashboard_payments',
            'tandas.abonar', 'tandas.all_branches',
            'cash.pre_cut', 'cash.final_cut', 'cash.open_drawer', 'cash.all_branches',
            'gifts.menu', 'gifts.read', 'gifts.create', 'gifts.deliver', 'gifts.edit', 'gifts.delete', 'gifts.export', 'gifts.all_branches',
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

