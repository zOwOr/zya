<?php

use App\Models\User;
use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;
use Spatie\Permission\Models\Permission;
use Spatie\Permission\PermissionRegistrar;

return new class extends Migration
{
    public const PERMISSION_NAME = 'financieras.filter_multiple';

    /**
     * Run the migrations.
     */
    public function up(): void
    {
        if (!Schema::hasTable('permissions')) {
            return;
        }

        if (app()->bound(PermissionRegistrar::class)) {
            app()[PermissionRegistrar::class]->forgetCachedPermissions();
        }

        $now = now();
        $hasDisplayName = Schema::hasColumn('permissions', 'display_name');
        $hasGroupName = Schema::hasColumn('permissions', 'group_name');

        $existing = DB::table('permissions')
            ->where('name', self::PERMISSION_NAME)
            ->where('guard_name', 'web')
            ->first();

        if (!$existing) {
            $payload = [
                'name' => self::PERMISSION_NAME,
                'guard_name' => 'web',
                'created_at' => $now,
                'updated_at' => $now,
            ];
            if ($hasDisplayName) {
                $payload['display_name'] = 'Financieras: Filtro Múltiple (Abonos y Ventas)';
            }
            if ($hasGroupName) {
                $payload['group_name'] = 'financieras';
            }

            $permissionId = DB::table('permissions')->insertGetId($payload);
        } else {
            $permissionId = $existing->id;
            $updates = ['updated_at' => $now];
            if ($hasDisplayName && empty($existing->display_name)) {
                $updates['display_name'] = 'Financieras: Filtro Múltiple (Abonos y Ventas)';
            }
            if ($hasGroupName && empty($existing->group_name)) {
                $updates['group_name'] = 'financieras';
            }
            if (count($updates) > 1) {
                DB::table('permissions')->where('id', $permissionId)->update($updates);
            }
        }

        // Asignar EXCLUSIVAMENTE a la usuaria Esmeralda (y/o a un rol dedicado con su nombre si existe).
        // Nunca se asigna automáticamente a roles generales (Admin, Manager, Account, etc.).
        if (Schema::hasTable('users') && Schema::hasTable('model_has_permissions')) {
            $esmeraldaUserIds = DB::table('users')
                ->where(function ($q) {
                    $q->whereRaw('LOWER(name) LIKE ?', ['%esmeralda%'])
                        ->orWhereRaw('LOWER(username) LIKE ?', ['%esmeralda%'])
                        ->orWhereRaw('LOWER(email) LIKE ?', ['%esmeralda%']);
                })
                ->pluck('id');

            foreach ($esmeraldaUserIds as $userId) {
                $exists = DB::table('model_has_permissions')
                    ->where('permission_id', $permissionId)
                    ->where('model_type', User::class)
                    ->where('model_id', $userId)
                    ->exists();

                if (!$exists) {
                    DB::table('model_has_permissions')->insert([
                        'permission_id' => $permissionId,
                        'model_type' => User::class,
                        'model_id' => $userId,
                    ]);
                }
            }
        }

        if (Schema::hasTable('roles') && Schema::hasTable('role_has_permissions')) {
            $esmeraldaRoleIds = DB::table('roles')
                ->whereRaw('LOWER(name) LIKE ?', ['%esmeralda%'])
                ->pluck('id');

            foreach ($esmeraldaRoleIds as $roleId) {
                $exists = DB::table('role_has_permissions')
                    ->where('permission_id', $permissionId)
                    ->where('role_id', $roleId)
                    ->exists();

                if (!$exists) {
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

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        if (!Schema::hasTable('permissions')) {
            return;
        }

        if (app()->bound(PermissionRegistrar::class)) {
            app()[PermissionRegistrar::class]->forgetCachedPermissions();
        }

        $permission = Permission::where('name', self::PERMISSION_NAME)
            ->where('guard_name', 'web')
            ->first();

        if ($permission) {
            if (Schema::hasTable('role_has_permissions')) {
                DB::table('role_has_permissions')->where('permission_id', $permission->id)->delete();
            }
            if (Schema::hasTable('model_has_permissions')) {
                DB::table('model_has_permissions')->where('permission_id', $permission->id)->delete();
            }
            $permission->delete();
        }

        if (app()->bound(PermissionRegistrar::class)) {
            app()[PermissionRegistrar::class]->forgetCachedPermissions();
        }
    }
};

