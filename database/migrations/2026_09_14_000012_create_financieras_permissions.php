<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\DB;
use Carbon\Carbon;

return new class extends Migration
{
    public function up(): void
    {
        $now = Carbon::now()->toDateTimeString();

        $permissions = [
            // Module entry permission
            ['name' => 'financieras.menu', 'guard_name' => 'web', 'group_name' => 'financieras', 'created_at' => $now, 'updated_at' => $now],
            ['name' => 'financieras.read', 'guard_name' => 'web', 'group_name' => 'financieras', 'created_at' => $now, 'updated_at' => $now],
            ['name' => 'financieras.create', 'guard_name' => 'web', 'group_name' => 'financieras', 'created_at' => $now, 'updated_at' => $now],
            ['name' => 'financieras.edit', 'guard_name' => 'web', 'group_name' => 'financieras', 'created_at' => $now, 'updated_at' => $now],
            ['name' => 'financieras.delete', 'guard_name' => 'web', 'group_name' => 'financieras', 'created_at' => $now, 'updated_at' => $now],
            
            // Ventas
            ['name' => 'financieras.ventas.menu', 'guard_name' => 'web', 'group_name' => 'financieras', 'created_at' => $now, 'updated_at' => $now],
            ['name' => 'financieras.ventas.read', 'guard_name' => 'web', 'group_name' => 'financieras', 'created_at' => $now, 'updated_at' => $now],
            ['name' => 'financieras.ventas.create', 'guard_name' => 'web', 'group_name' => 'financieras', 'created_at' => $now, 'updated_at' => $now],
            ['name' => 'financieras.ventas.edit', 'guard_name' => 'web', 'group_name' => 'financieras', 'created_at' => $now, 'updated_at' => $now],
            ['name' => 'financieras.ventas.delete', 'guard_name' => 'web', 'group_name' => 'financieras', 'created_at' => $now, 'updated_at' => $now],
            
            // Inventario
            ['name' => 'financieras.inventario.menu', 'guard_name' => 'web', 'group_name' => 'financieras', 'created_at' => $now, 'updated_at' => $now],
            ['name' => 'financieras.inventario.read', 'guard_name' => 'web', 'group_name' => 'financieras', 'created_at' => $now, 'updated_at' => $now],
            ['name' => 'financieras.inventario.create', 'guard_name' => 'web', 'group_name' => 'financieras', 'created_at' => $now, 'updated_at' => $now],
            ['name' => 'financieras.inventario.edit', 'guard_name' => 'web', 'group_name' => 'financieras', 'created_at' => $now, 'updated_at' => $now],
            ['name' => 'financieras.inventario.delete', 'guard_name' => 'web', 'group_name' => 'financieras', 'created_at' => $now, 'updated_at' => $now],
            ['name' => 'financieras.inventario.transfer', 'guard_name' => 'web', 'group_name' => 'financieras', 'created_at' => $now, 'updated_at' => $now],
            
            // Garantias
            ['name' => 'financieras.garantias.menu', 'guard_name' => 'web', 'group_name' => 'financieras', 'created_at' => $now, 'updated_at' => $now],
            ['name' => 'financieras.garantias.read', 'guard_name' => 'web', 'group_name' => 'financieras', 'created_at' => $now, 'updated_at' => $now],
            ['name' => 'financieras.garantias.create', 'guard_name' => 'web', 'group_name' => 'financieras', 'created_at' => $now, 'updated_at' => $now],
            ['name' => 'financieras.garantias.edit', 'guard_name' => 'web', 'group_name' => 'financieras', 'created_at' => $now, 'updated_at' => $now],
            ['name' => 'financieras.garantias.delete', 'guard_name' => 'web', 'group_name' => 'financieras', 'created_at' => $now, 'updated_at' => $now],
            
            // Robos
            ['name' => 'financieras.robos.menu', 'guard_name' => 'web', 'group_name' => 'financieras', 'created_at' => $now, 'updated_at' => $now],
            ['name' => 'financieras.robos.read', 'guard_name' => 'web', 'group_name' => 'financieras', 'created_at' => $now, 'updated_at' => $now],
            ['name' => 'financieras.robos.create', 'guard_name' => 'web', 'group_name' => 'financieras', 'created_at' => $now, 'updated_at' => $now],
            ['name' => 'financieras.robos.edit', 'guard_name' => 'web', 'group_name' => 'financieras', 'created_at' => $now, 'updated_at' => $now],
            ['name' => 'financieras.robos.delete', 'guard_name' => 'web', 'group_name' => 'financieras', 'created_at' => $now, 'updated_at' => $now],
            
            // Catalogos
            ['name' => 'financieras.catalogos.menu', 'guard_name' => 'web', 'group_name' => 'financieras', 'created_at' => $now, 'updated_at' => $now],
            ['name' => 'financieras.catalogos.read', 'guard_name' => 'web', 'group_name' => 'financieras', 'created_at' => $now, 'updated_at' => $now],
            ['name' => 'financieras.catalogos.create', 'guard_name' => 'web', 'group_name' => 'financieras', 'created_at' => $now, 'updated_at' => $now],
            ['name' => 'financieras.catalogos.edit', 'guard_name' => 'web', 'group_name' => 'financieras', 'created_at' => $now, 'updated_at' => $now],
            ['name' => 'financieras.catalogos.delete', 'guard_name' => 'web', 'group_name' => 'financieras', 'created_at' => $now, 'updated_at' => $now],
        ];

        DB::table('permissions')->insertOrIgnore($permissions);

        // Assign all to SuperAdmin role if it exists
        $superAdmin = DB::table('roles')->where('name', 'SuperAdmin')->first();
        if ($superAdmin) {
            $permissionNames = array_column($permissions, 'name');
            $pids = DB::table('permissions')->whereIn('name', $permissionNames)->pluck('id');
            $rolePerms = [];
            foreach ($pids as $pid) {
                $rolePerms[] = [
                    'permission_id' => $pid,
                    'role_id' => $superAdmin->id,
                ];
            }
            if (!empty($rolePerms)) {
                DB::table('role_has_permissions')->insertOrIgnore($rolePerms);
            }
        }

        // Clear Spatie cache
        if (app()->bound('cache')) {
            app('cache')->forget(config('permission.cache.key', 'spatie.permission.cache'));
        }
    }

    public function down(): void
    {
        $names = [
            'financieras.menu',
            'financieras.ventas.menu', 'financieras.ventas.read', 'financieras.ventas.create', 'financieras.ventas.edit', 'financieras.ventas.delete',
            'financieras.inventario.menu', 'financieras.inventario.read', 'financieras.inventario.create', 'financieras.inventario.edit', 'financieras.inventario.delete', 'financieras.inventario.transfer',
            'financieras.garantias.menu', 'financieras.garantias.read', 'financieras.garantias.create', 'financieras.garantias.edit', 'financieras.garantias.delete',
            'financieras.robos.menu', 'financieras.robos.read', 'financieras.robos.create', 'financieras.robos.edit', 'financieras.robos.delete',
            'financieras.catalogos.menu', 'financieras.catalogos.read', 'financieras.catalogos.create', 'financieras.catalogos.edit', 'financieras.catalogos.delete',
        ];

        DB::table('permissions')->whereIn('name', $names)->delete();
        
        if (app()->bound('cache')) {
            app('cache')->forget(config('permission.cache.key', 'spatie.permission.cache'));
        }
    }
};
