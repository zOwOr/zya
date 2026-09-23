<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\DB;
use Carbon\Carbon;

return new class extends Migration
{
    public function up(): void
    {
        $now = Carbon::now()->toDateTimeString();

        $permissionName = 'financieras.ventas.assign_seller';

        // Insert permission if not exists
        $existing = DB::table('permissions')->where('name', $permissionName)->first();
        if (!$existing) {
            $permissionId = DB::table('permissions')->insertGetId([
                'name' => $permissionName,
                'guard_name' => 'web',
                'group_name' => 'financieras',
                'created_at' => $now,
                'updated_at' => $now,
            ]);
        } else {
            $permissionId = $existing->id;
        }

        // Assign to SuperAdmin role if it exists
        $superAdminRoles = DB::table('roles')->whereIn('name', ['SuperAdmin', 'Super Administrador'])->get();
        foreach ($superAdminRoles as $role) {
            DB::table('role_has_permissions')->insertOrIgnore([
                'permission_id' => $permissionId,
                'role_id' => $role->id,
            ]);
        }

        // Clear Spatie cache
        if (app()->bound('cache')) {
            app('cache')->forget(config('permission.cache.key', 'spatie.permission.cache'));
        }
    }

    public function down(): void
    {
        $permission = DB::table('permissions')->where('name', 'financieras.ventas.assign_seller')->first();
        if ($permission) {
            DB::table('role_has_permissions')->where('permission_id', $permission->id)->delete();
            DB::table('permissions')->where('id', $permission->id)->delete();
        }

        if (app()->bound('cache')) {
            app('cache')->forget(config('permission.cache.key', 'spatie.permission.cache'));
        }
    }
};
