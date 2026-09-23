<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\DB;
use Spatie\Permission\Models\Role;
use Spatie\Permission\PermissionRegistrar;

return new class extends Migration
{
    /**
     * Run the migrations.
     */
    public function up(): void
    {
        app()[PermissionRegistrar::class]->forgetCachedPermissions();

        $exportPermissions = [
            'pos.export' => ['group' => 'pos', 'display_name' => 'POS: Exportar Datos (Excel / PDF)'],
            'employee.export' => ['group' => 'employee', 'display_name' => 'Empleados: Exportar Lista (Excel / PDF)'],
            'customer.export' => ['group' => 'customer', 'display_name' => 'Clientes: Exportar Datos (Excel / PDF)'],
            'supplier.export' => ['group' => 'supplier', 'display_name' => 'Proveedores: Exportar Lista (Excel / PDF)'],
            'salary.export' => ['group' => 'salary', 'display_name' => 'Salarios: Exportar Historial de Pagos (Excel / PDF)'],
            'attendence.export' => ['group' => 'attendence', 'display_name' => 'Asistencias: Exportar Registros (Excel / PDF)'],
            'category.export' => ['group' => 'category', 'display_name' => 'Categorías: Exportar Lista (Excel / PDF)'],
            'product.export' => ['group' => 'product', 'display_name' => 'Productos: Exportar Catálogo (Excel / PDF)'],
            'orders.export' => ['group' => 'orders', 'display_name' => 'Pedidos: Exportar Reporte de Pedidos (Excel / PDF)'],
            'stock.export' => ['group' => 'stock', 'display_name' => 'Stock: Exportar Inventario (Excel / PDF)'],
            'roles.export' => ['group' => 'roles', 'display_name' => 'Roles: Exportar Lista de Roles (Excel / PDF)'],
            'permissions.export' => ['group' => 'permissions', 'display_name' => 'Permisos: Exportar Lista de Permisos (Excel / PDF)'],
            'user.export' => ['group' => 'user', 'display_name' => 'Usuarios: Exportar Lista de Usuarios (Excel / PDF)'],
            'branch.export' => ['group' => 'branch', 'display_name' => 'Sucursales: Exportar Lista (Excel / PDF)'],
            'database.export' => ['group' => 'database', 'display_name' => 'Base de Datos: Exportar Lista de Respaldos (Excel / PDF)'],
            'cash.export' => ['group' => 'cash', 'display_name' => 'Caja: Exportar Cortes y Movimientos (Excel / PDF)'],
            'repairs.export' => ['group' => 'repairs', 'display_name' => 'Reparaciones: Exportar Reporte (Excel / PDF)'],
            'tandas.export' => ['group' => 'tandas', 'display_name' => 'Tandas: Exportar Lista de Tandas (Excel / PDF)'],
            'financieras.export' => ['group' => 'financieras', 'display_name' => 'Financieras: Exportar Catálogo de Financieras'],
            'financieras.ventas.export' => ['group' => 'financieras', 'display_name' => 'Financieras: Exportar Ventas a Excel'],
            'financieras.inventario.export' => ['group' => 'financieras', 'display_name' => 'Financieras: Exportar Inventario a Excel'],
            'financieras.garantias.export' => ['group' => 'financieras', 'display_name' => 'Financieras: Exportar Garantías a Excel'],
            'financieras.robos.export' => ['group' => 'financieras', 'display_name' => 'Financieras: Exportar Reportes de Robo a Excel'],
            'financieras.catalogos.export' => ['group' => 'financieras', 'display_name' => 'Financieras: Exportar Catálogos a Excel'],
        ];

        $superAdminRoles = Role::whereIn('name', ['SuperAdmin', 'Super Administrador', 'superadmin'])->get();

        foreach ($exportPermissions as $name => $data) {
            $permission = DB::table('permissions')->where('name', $name)->first();

            if (!$permission) {
                $permissionId = DB::table('permissions')->insertGetId([
                    'name' => $name,
                    'display_name' => $data['display_name'],
                    'guard_name' => 'web',
                    'group_name' => $data['group'],
                    'created_at' => now(),
                    'updated_at' => now(),
                ]);
            } else {
                $permissionId = $permission->id;
                DB::table('permissions')->where('id', $permissionId)->update([
                    'display_name' => $data['display_name'],
                    'group_name' => $data['group'],
                ]);
            }

            foreach ($superAdminRoles as $role) {
                $exists = DB::table('role_has_permissions')
                    ->where('permission_id', $permissionId)
                    ->where('role_id', $role->id)
                    ->exists();

                if (!$exists) {
                    DB::table('role_has_permissions')->insert([
                        'permission_id' => $permissionId,
                        'role_id' => $role->id,
                    ]);
                }
            }
        }

        app()[PermissionRegistrar::class]->forgetCachedPermissions();
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        $permissions = [
            'pos.export', 'employee.export', 'customer.export', 'supplier.export',
            'salary.export', 'attendence.export', 'category.export', 'product.export',
            'orders.export', 'stock.export', 'roles.export', 'permissions.export',
            'user.export', 'branch.export', 'database.export', 'cash.export',
            'repairs.export', 'tandas.export', 'financieras.export',
            'financieras.ventas.export', 'financieras.inventario.export',
            'financieras.garantias.export', 'financieras.robos.export',
            'financieras.catalogos.export',
        ];

        DB::table('role_has_permissions')
            ->whereIn('permission_id', function ($query) use ($permissions) {
                $query->select('id')->from('permissions')->whereIn('name', $permissions);
            })->delete();

        DB::table('permissions')->whereIn('name', $permissions)->delete();

        app()[PermissionRegistrar::class]->forgetCachedPermissions();
    }
};
