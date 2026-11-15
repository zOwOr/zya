<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;
use Spatie\Permission\PermissionRegistrar;

return new class extends Migration
{
    public function up(): void
    {
        if (Schema::hasTable('products') && !Schema::hasColumn('products', 'branch_id')) {
            Schema::table('products', function (Blueprint $table) {
                if (Schema::hasTable('branches')) {
                    $table->foreignId('branch_id')
                        ->nullable()
                        ->after('id')
                        ->constrained('branches')
                        ->nullOnDelete();
                } else {
                    $table->unsignedBigInteger('branch_id')->nullable()->after('id');
                }

                $table->index('branch_id', 'products_branch_id_idx');
            });
        }

        if (Schema::hasTable('products') && Schema::hasTable('branches') && Schema::hasColumn('products', 'branch_id')) {
            $defaultBranchId = DB::table('branches')->orderBy('id')->value('id');
            if ($defaultBranchId) {
                DB::table('products')
                    ->whereNull('branch_id')
                    ->update(['branch_id' => $defaultBranchId]);
            }
        }

        if (Schema::hasTable('permissions') && Schema::hasTable('roles')) {
            if (app()->bound(PermissionRegistrar::class)) {
                app()[PermissionRegistrar::class]->forgetCachedPermissions();
            }

            $permissions = [
                'product.import' => [
                    'display_name' => 'Productos: Importar Catálogo desde Excel y Descargar Plantilla',
                    'group_name' => 'product',
                    'copy_from' => ['product.create'],
                ],
                'product.all_branches' => [
                    'display_name' => 'Productos: Ver y Gestionar Productos de Todas las Sucursales',
                    'group_name' => 'product',
                    'copy_from' => [],
                ],
                'product.export' => [
                    'display_name' => 'Productos: Exportar Catálogo a Excel',
                    'group_name' => 'product',
                    'copy_from' => [],
                ],
            ];

            $superAdminRoleIds = DB::table('roles')
                ->whereIn('name', ['SuperAdmin', 'Super Administrador', 'superadmin', 'Superadmin'])
                ->pluck('id')
                ->all();

            foreach ($permissions as $name => $meta) {
                $existing = DB::table('permissions')->where('name', $name)->first();
                if (!$existing) {
                    $permissionId = DB::table('permissions')->insertGetId([
                        'name' => $name,
                        'display_name' => $meta['display_name'],
                        'guard_name' => 'web',
                        'group_name' => $meta['group_name'],
                        'created_at' => now(),
                        'updated_at' => now(),
                    ]);
                } else {
                    $permissionId = $existing->id;
                    $updateData = ['group_name' => $meta['group_name'], 'updated_at' => now()];
                    if (empty($existing->display_name)) {
                        $updateData['display_name'] = $meta['display_name'];
                    }
                    DB::table('permissions')->where('id', $permissionId)->update($updateData);
                }

                $targetRoleIds = $superAdminRoleIds;
                if (!empty($meta['copy_from'])) {
                    $copiedRoleIds = DB::table('role_has_permissions')
                        ->join('permissions', 'permissions.id', '=', 'role_has_permissions.permission_id')
                        ->whereIn('permissions.name', $meta['copy_from'])
                        ->pluck('role_has_permissions.role_id')
                        ->all();
                    $targetRoleIds = array_unique(array_merge($targetRoleIds, $copiedRoleIds));
                }

                foreach ($targetRoleIds as $roleId) {
                    $alreadyAssigned = DB::table('role_has_permissions')
                        ->where('permission_id', $permissionId)
                        ->where('role_id', $roleId)
                        ->exists();

                    if (!$alreadyAssigned) {
                        DB::table('role_has_permissions')->insert([
                            'permission_id' => $permissionId,
                            'role_id' => $roleId,
                        ]);
                    }
                }
            }

            if (app()->bound(PermissionRegistrar::class)) {
                app()[PermissionRegistrar::class]->forgetCachedPermissions();
            }
        }
    }

    public function down(): void
    {
        if (Schema::hasTable('permissions')) {
            $permIds = DB::table('permissions')
                ->whereIn('name', ['product.import', 'product.all_branches'])
                ->pluck('id')
                ->all();

            if (!empty($permIds)) {
                DB::table('role_has_permissions')->whereIn('permission_id', $permIds)->delete();
                DB::table('model_has_permissions')->whereIn('permission_id', $permIds)->delete();
                DB::table('permissions')->whereIn('id', $permIds)->delete();
            }

            if (app()->bound(PermissionRegistrar::class)) {
                app()[PermissionRegistrar::class]->forgetCachedPermissions();
            }
        }

        if (Schema::hasTable('products') && Schema::hasColumn('products', 'branch_id')) {
            Schema::table('products', function (Blueprint $table) {
                if (DB::getDriverName() === 'mysql' && Schema::hasTable('branches')) {
                    $table->dropForeign(['branch_id']);
                }
                if (Schema::hasIndex('products', 'products_branch_id_idx')) {
                    $table->dropIndex('products_branch_id_idx');
                }
                $table->dropColumn('branch_id');
            });
        }
    }
};

