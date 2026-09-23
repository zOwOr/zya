<?php

namespace App\Models;

use Spatie\Permission\Models\Permission as SpatiePermission;

class Permission extends SpatiePermission
{
    protected $fillable = [
        'name',
        'display_name',
        'guard_name',
        'group_name',
    ];

    /**
     * Devuelve el apodo en español o genera uno por defecto.
     */
    public function getDisplayNameAttribute($value): string
    {
        if (!empty($value)) {
            return $value;
        }

        return self::defaultAlias($this->name);
    }

    /**
     * Traducción / Formato por defecto para permisos que no tengan display_name explícito.
     */
    public static function defaultAlias(?string $name): string
    {
        if (empty($name)) {
            return '';
        }

        $parts = explode('.', $name);
        $action = end($parts);
        $prefix = count($parts) > 1 ? array_slice($parts, 0, -1) : [];

        $actionTranslations = [
            'menu' => 'Acceso al Menú',
            'read' => 'Ver / Consultar',
            'create' => 'Crear / Registrar',
            'edit' => 'Editar / Modificar',
            'delete' => 'Eliminar / Cancelar',
            'transfer' => 'Transferir',
            'assign_seller' => 'Asignar Vendedor',
            'export' => 'Exportar Datos',
        ];

        $actionLabel = $actionTranslations[strtolower($action)] ?? ucfirst(str_replace('_', ' ', $action));
        $prefixLabel = !empty($prefix) ? implode(' > ', array_map(fn($p) => ucfirst($p), $prefix)) . ': ' : '';

        return $prefixLabel . $actionLabel;
    }

    /**
     * Devuelve el nombre amigable en español para cada grupo de permisos.
     */
    public static function groupLabel(?string $group_name): string
    {
        if (empty($group_name)) {
            return 'General';
        }

        $labels = [
            'pos' => 'Punto de Venta (POS)',
            'employee' => 'Empleados',
            'customer' => 'Clientes',
            'supplier' => 'Proveedores',
            'salary' => 'Salarios y Nómina',
            'attendence' => 'Asistencias',
            'category' => 'Categorías',
            'product' => 'Productos',
            'orders' => 'Pedidos / Ventas',
            'stock' => 'Inventario / Stock',
            'roles' => 'Roles de Usuario',
            'permissions' => 'Permisos del Sistema',
            'user' => 'Usuarios',
            'branch' => 'Sucursales',
            'database' => 'Base de Datos y Respaldos',
            'cash' => 'Caja y Cortes',
            'repairs' => 'Reparaciones',
            'tandas' => 'Tandas',
            'financieras' => 'Módulo de Financieras',
        ];

        return $labels[strtolower(trim($group_name))] ?? ucfirst($group_name);
    }
}
