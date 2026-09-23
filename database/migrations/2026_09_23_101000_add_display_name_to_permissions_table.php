<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;
use Illuminate\Support\Facades\DB;

return new class extends Migration
{
    /**
     * Run the migrations.
     */
    public function up(): void
    {
        if (!Schema::hasColumn('permissions', 'display_name')) {
            Schema::table('permissions', function (Blueprint $table) {
                $table->string('display_name')->nullable()->after('name');
            });
        }

        $aliases = [
            // POS
            'pos.menu' => 'POS: Acceso al Menú',
            'pos.read' => 'POS: Ver Ventas',
            'pos.create' => 'POS: Realizar Venta',
            'pos.edit' => 'POS: Modificar Venta',
            'pos.delete' => 'POS: Cancelar Venta',

            // Empleados
            'employee.menu' => 'Empleados: Acceso al Menú',
            'employee.read' => 'Empleados: Ver Lista',
            'employee.create' => 'Empleados: Registrar Empleado',
            'employee.edit' => 'Empleados: Editar Empleado',
            'employee.delete' => 'Empleados: Eliminar Empleado',

            // Clientes
            'customer.menu' => 'Clientes: Acceso al Menú',
            'customer.read' => 'Clientes: Ver Lista',
            'customer.create' => 'Clientes: Registrar Cliente',
            'customer.edit' => 'Clientes: Editar Cliente',
            'customer.delete' => 'Clientes: Eliminar Cliente',

            // Proveedores
            'supplier.menu' => 'Proveedores: Acceso al Menú',
            'supplier.read' => 'Proveedores: Ver Lista',
            'supplier.create' => 'Proveedores: Registrar Proveedor',
            'supplier.edit' => 'Proveedores: Editar Proveedor',
            'supplier.delete' => 'Proveedores: Eliminar Proveedor',

            // Salarios
            'salary.menu' => 'Salarios: Acceso al Menú',
            'salary.read' => 'Salarios: Ver Pagos',
            'salary.create' => 'Salarios: Registrar Pago',
            'salary.edit' => 'Salarios: Editar Pago',
            'salary.delete' => 'Salarios: Eliminar Pago',

            // Asistencias
            'attendence.menu' => 'Asistencias: Acceso al Menú',
            'attendence.read' => 'Asistencias: Ver Registro',
            'attendence.create' => 'Asistencias: Registrar Asistencia',
            'attendence.edit' => 'Asistencias: Editar Asistencia',
            'attendence.delete' => 'Asistencias: Eliminar Asistencia',

            // Categorías
            'category.menu' => 'Categorías: Acceso al Menú',
            'category.read' => 'Categorías: Ver Lista',
            'category.create' => 'Categorías: Crear Categoría',
            'category.edit' => 'Categorías: Editar Categoría',
            'category.delete' => 'Categorías: Eliminar Categoría',

            // Productos
            'product.menu' => 'Productos: Acceso al Menú',
            'product.read' => 'Productos: Ver Catálogo',
            'product.create' => 'Productos: Crear Producto',
            'product.edit' => 'Productos: Editar Producto',
            'product.delete' => 'Productos: Eliminar Producto',

            // Pedidos
            'orders.menu' => 'Pedidos: Acceso al Menú',
            'orders.read' => 'Pedidos: Ver Pedidos',
            'orders.create' => 'Pedidos: Crear Pedido',
            'orders.edit' => 'Pedidos: Editar Pedido',
            'orders.delete' => 'Pedidos: Cancelar Pedido',

            // Stock
            'stock.menu' => 'Stock: Acceso al Menú',
            'stock.read' => 'Stock: Ver Existencias',
            'stock.create' => 'Stock: Registrar Entrada Stock',
            'stock.edit' => 'Stock: Ajustar Stock',
            'stock.delete' => 'Stock: Eliminar Stock',

            // Roles
            'roles.menu' => 'Roles: Acceso al Menú',
            'roles.read' => 'Roles: Ver Lista',
            'roles.create' => 'Roles: Crear Rol',
            'roles.edit' => 'Roles: Editar Rol',
            'roles.delete' => 'Roles: Eliminar Rol',

            // Permisos
            'permissions.menu' => 'Permisos: Acceso al Menú',
            'permissions.read' => 'Permisos: Ver Lista',
            'permissions.create' => 'Permisos: Crear Permiso',
            'permissions.edit' => 'Permisos: Editar Permiso',
            'permissions.delete' => 'Permisos: Eliminar Permiso',

            // Usuarios
            'user.menu' => 'Usuarios: Acceso al Menú',
            'user.read' => 'Usuarios: Ver Usuarios',
            'user.create' => 'Usuarios: Crear Usuario',
            'user.edit' => 'Usuarios: Editar Usuario',
            'user.delete' => 'Usuarios: Eliminar Usuario',

            // Sucursales
            'branch.menu' => 'Sucursales: Acceso al Menú',
            'branch.read' => 'Sucursales: Ver Sucursales',
            'branch.create' => 'Sucursales: Crear Sucursal',
            'branch.edit' => 'Sucursales: Editar Sucursal',
            'branch.delete' => 'Sucursales: Eliminar Sucursal',

            // Base de datos
            'database.menu' => 'Base de Datos: Acceso al Menú',
            'database.read' => 'Base de Datos: Ver Respaldos',
            'database.create' => 'Base de Datos: Generar Respaldo',
            'database.edit' => 'Base de Datos: Restaurar / Configurar',
            'database.delete' => 'Base de Datos: Eliminar Respaldo',

            // Caja
            'cash.menu' => 'Caja: Acceso al Menú',
            'cash.read' => 'Caja: Ver Flujos y Cortes',
            'cash.create' => 'Caja: Registrar Apertura / Movimiento',
            'cash.edit' => 'Caja: Editar Movimiento',
            'cash.delete' => 'Caja: Eliminar Movimiento',

            // Reparaciones
            'repairs.menu' => 'Reparaciones: Acceso al Menú',
            'repairs.read' => 'Reparaciones: Ver Reparaciones',
            'repairs.create' => 'Reparaciones: Recibir Equipo',
            'repairs.edit' => 'Reparaciones: Actualizar Estado',
            'repairs.delete' => 'Reparaciones: Eliminar Registro',
            'test' => 'Reparaciones: Prueba Técnica',

            // Tandas
            'tandas.menu' => 'Tandas: Acceso al Menú',
            'tandas.read' => 'Tandas: Ver Tandas',
            'tandas.create' => 'Tandas: Crear Tanda',
            'tandas.edit' => 'Tandas: Modificar Tanda',
            'tandas.delete' => 'Tandas: Eliminar Tanda',

            // Financieras
            'financieras.menu' => 'Financieras: Menú Principal',
            'financieras.read' => 'Financieras: Ver Catálogo Financieras',
            'financieras.create' => 'Financieras: Crear Financiera',
            'financieras.edit' => 'Financieras: Editar Financiera',
            'financieras.delete' => 'Financieras: Eliminar Financiera',

            'financieras.ventas.menu' => 'Financieras: Menú Ventas',
            'financieras.ventas.read' => 'Financieras: Ver Ventas / Clientes',
            'financieras.ventas.create' => 'Financieras: Registrar Venta',
            'financieras.ventas.edit' => 'Financieras: Editar Venta',
            'financieras.ventas.delete' => 'Financieras: Cancelar Venta',
            'financieras.ventas.assign_seller' => 'Financieras: Asignar Vendedor',

            'financieras.inventario.menu' => 'Financieras: Menú Inventario',
            'financieras.inventario.read' => 'Financieras: Ver Inventario Equipos',
            'financieras.inventario.create' => 'Financieras: Registrar Equipo',
            'financieras.inventario.edit' => 'Financieras: Editar Equipo',
            'financieras.inventario.delete' => 'Financieras: Eliminar Equipo',
            'financieras.inventario.transfer' => 'Financieras: Transferir Equipo',

            'financieras.garantias.menu' => 'Financieras: Menú Garantías',
            'financieras.garantias.read' => 'Financieras: Ver Garantías',
            'financieras.garantias.create' => 'Financieras: Iniciar Garantía',
            'financieras.garantias.edit' => 'Financieras: Actualizar Garantía',
            'financieras.garantias.delete' => 'Financieras: Eliminar Garantía',

            'financieras.robos.menu' => 'Financieras: Menú Reporte Robos',
            'financieras.robos.read' => 'Financieras: Ver Reportes de Robo',
            'financieras.robos.create' => 'Financieras: Registrar Reporte de Robo',
            'financieras.robos.edit' => 'Financieras: Actualizar Reporte de Robo',
            'financieras.robos.delete' => 'Financieras: Eliminar Reporte de Robo',

            'financieras.catalogos.menu' => 'Financieras: Menú Catálogos',
            'financieras.catalogos.read' => 'Financieras: Ver Catálogos (Marcas/Proveedores)',
            'financieras.catalogos.create' => 'Financieras: Crear en Catálogos',
            'financieras.catalogos.edit' => 'Financieras: Editar en Catálogos',
            'financieras.catalogos.delete' => 'Financieras: Eliminar en Catálogos',
        ];

        foreach ($aliases as $name => $displayName) {
            DB::table('permissions')
                ->where('name', $name)
                ->update(['display_name' => $displayName]);
        }
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        if (Schema::hasColumn('permissions', 'display_name')) {
            Schema::table('permissions', function (Blueprint $table) {
                $table->dropColumn('display_name');
            });
        }
    }
};
