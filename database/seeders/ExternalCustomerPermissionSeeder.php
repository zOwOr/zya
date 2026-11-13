<?php

namespace Database\Seeders;

use App\Models\Permission;
use Illuminate\Database\Seeder;
use Spatie\Permission\Models\Role;
use Spatie\Permission\PermissionRegistrar;

class ExternalCustomerPermissionSeeder extends Seeder
{
    public const PERMISSIONS = [
        [
            'name' => 'external_customer.menu',
            'display_name' => 'Ver Catálogo de Clientes Externos en Menú',
            'group_name' => 'external_customer',
        ],
        [
            'name' => 'external_customer.read',
            'display_name' => 'Consultar Clientes Externos e Historial',
            'group_name' => 'external_customer',
        ],
        [
            'name' => 'external_customer.create',
            'display_name' => 'Registrar Clientes Externos',
            'group_name' => 'external_customer',
        ],
        [
            'name' => 'external_customer.edit',
            'display_name' => 'Editar Clientes Externos',
            'group_name' => 'external_customer',
        ],
        [
            'name' => 'external_customer.delete',
            'display_name' => 'Eliminar Clientes Externos',
            'group_name' => 'external_customer',
        ],
        [
            'name' => 'external_customer.convert',
            'display_name' => 'Promover / Convertir Cliente Externo a Cliente Interno',
            'group_name' => 'external_customer',
        ],
        [
            'name' => 'cobros.external_customers',
            'display_name' => 'Capturar y Usar Clientes Externos en POS (Financieras)',
            'group_name' => 'cobros',
        ],
    ];

    public function run(): void
    {
        $createdNames = [];

        foreach (self::PERMISSIONS as $permData) {
            Permission::updateOrCreate(
                [
                    'name' => $permData['name'],
                    'guard_name' => 'web',
                ],
                [
                    'display_name' => $permData['display_name'],
                    'group_name' => $permData['group_name'],
                ]
            );
            $createdNames[] = $permData['name'];
        }

        $superAdminRoles = Role::whereIn('name', [
            'SuperAdmin',
            'Super Administrador',
            'superadmin',
            'Superadmin',
        ])->get();

        foreach ($superAdminRoles as $role) {
            $role->givePermissionTo($createdNames);
        }

        app()[PermissionRegistrar::class]->forgetCachedPermissions();
    }
}

