<?php

use App\Services\GiftInventoryService;
use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;
use Spatie\Permission\PermissionRegistrar;

return new class extends Migration
{
    public function up(): void
    {
        if (!Schema::hasTable('gift_stocks')) {
            Schema::create('gift_stocks', function (Blueprint $table) {
                $table->id();
                $table->foreignId('gift_id')->constrained('gifts')->cascadeOnDelete();
                $table->foreignId('branch_id')->constrained('branches')->cascadeOnDelete();
                $table->integer('stock_quantity')->default(0);
                $table->integer('min_stock')->default(3);
                $table->timestamps();

                $table->unique(['gift_id', 'branch_id'], 'gift_stocks_gift_branch_unique');
                $table->index(['branch_id', 'stock_quantity'], 'gift_stocks_branch_qty_idx');
            });
        }

        if (!Schema::hasTable('gift_transfers')) {
            Schema::create('gift_transfers', function (Blueprint $table) {
                $table->id();
                $table->string('transfer_code', 60)->nullable()->unique();
                $table->foreignId('gift_id')->constrained('gifts')->cascadeOnDelete();
                $table->foreignId('from_branch_id')->nullable()->constrained('branches')->nullOnDelete();
                $table->foreignId('to_branch_id')->nullable()->constrained('branches')->nullOnDelete();
                $table->integer('quantity');
                $table->integer('from_previous_stock')->default(0);
                $table->integer('from_new_stock')->default(0);
                $table->integer('to_previous_stock')->default(0);
                $table->integer('to_new_stock')->default(0);
                $table->foreignId('user_id')->nullable()->constrained('users')->nullOnDelete();
                $table->text('notes')->nullable();
                $table->timestamp('transferred_at')->useCurrent();
                $table->timestamps();

                $table->index(['gift_id', 'transferred_at'], 'gift_transfers_gift_date_idx');
                $table->index('from_branch_id', 'gift_transfers_from_branch_idx');
                $table->index('to_branch_id', 'gift_transfers_to_branch_idx');
            });
        }

        if (Schema::hasTable('gift_movements')) {
            if (DB::getDriverName() === 'mysql') {
                DB::statement("ALTER TABLE gift_movements MODIFY COLUMN movement_type VARCHAR(40) NOT NULL DEFAULT 'entrada_proveedor'");
            }

            if (!Schema::hasColumn('gift_movements', 'gift_transfer_id')) {
                Schema::table('gift_movements', function (Blueprint $table) {
                    $table->foreignId('gift_transfer_id')
                        ->nullable()
                        ->after('gift_id')
                        ->constrained('gift_transfers')
                        ->nullOnDelete();
                });
            }
        }

        if (Schema::hasTable('gifts')) {
            if (!Schema::hasColumn('gifts', 'normalized_name')) {
                Schema::table('gifts', function (Blueprint $table) {
                    $table->string('normalized_name', 191)->nullable()->after('name');
                });
            }

            // Consolidar registros históricos duplicados por sucursal sin perder existencias ni historial
            GiftInventoryService::consolidateExistingGifts();

            $hasUniqueIndex = false;
            if (DB::getDriverName() === 'mysql') {
                $indexes = DB::select("SHOW INDEX FROM gifts WHERE Key_name = 'gifts_normalized_name_unique'");
                $hasUniqueIndex = count($indexes) > 0;
            } else {
                $hasUniqueIndex = Schema::hasIndex('gifts', 'gifts_normalized_name_unique');
            }

            if (!$hasUniqueIndex) {
                Schema::table('gifts', function (Blueprint $table) {
                    $table->unique('normalized_name', 'gifts_normalized_name_unique');
                });
            }
        }

        // Permiso específico para traspasos de gifts de Inventario General a sucursales
        if (Schema::hasTable('permissions') && Schema::hasTable('roles')) {
            if (app()->bound(PermissionRegistrar::class)) {
                app()[PermissionRegistrar::class]->forgetCachedPermissions();
            }

            $permName = 'gifts.transfer';
            $existing = DB::table('permissions')->where('name', $permName)->first();
            if (!$existing) {
                $permissionId = DB::table('permissions')->insertGetId([
                    'name' => $permName,
                    'display_name' => 'Gifts: Traspasar de Inventario General a Sucursales',
                    'guard_name' => 'web',
                    'group_name' => 'gifts',
                    'created_at' => now(),
                    'updated_at' => now(),
                ]);
            } else {
                $permissionId = $existing->id;
                DB::table('permissions')->where('id', $permissionId)->update([
                    'display_name' => 'Gifts: Traspasar de Inventario General a Sucursales',
                    'group_name' => 'gifts',
                    'updated_at' => now(),
                ]);
            }

            $roleIds = DB::table('roles')
                ->whereIn('name', ['SuperAdmin', 'Super Administrador', 'superadmin', 'Superadmin'])
                ->pluck('id')
                ->all();

            $giftRoleIds = DB::table('role_has_permissions')
                ->join('permissions', 'permissions.id', '=', 'role_has_permissions.permission_id')
                ->whereIn('permissions.name', ['gifts.create', 'gifts.all_branches'])
                ->pluck('role_has_permissions.role_id')
                ->all();

            foreach (array_unique(array_merge($roleIds, $giftRoleIds)) as $roleId) {
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

            if (app()->bound(PermissionRegistrar::class)) {
                app()[PermissionRegistrar::class]->forgetCachedPermissions();
            }
        }
    }

    public function down(): void
    {
        if (Schema::hasTable('permissions')) {
            $perm = DB::table('permissions')->where('name', 'gifts.transfer')->first();
            if ($perm) {
                DB::table('role_has_permissions')->where('permission_id', $perm->id)->delete();
                DB::table('model_has_permissions')->where('permission_id', $perm->id)->delete();
                DB::table('permissions')->where('id', $perm->id)->delete();
            }
            if (app()->bound(PermissionRegistrar::class)) {
                app()[PermissionRegistrar::class]->forgetCachedPermissions();
            }
        }

        if (Schema::hasTable('gifts')) {
            $hasUniqueIndex = false;
            if (DB::getDriverName() === 'mysql') {
                $indexes = DB::select("SHOW INDEX FROM gifts WHERE Key_name = 'gifts_normalized_name_unique'");
                $hasUniqueIndex = count($indexes) > 0;
            } else {
                $hasUniqueIndex = Schema::hasIndex('gifts', 'gifts_normalized_name_unique');
            }

            if ($hasUniqueIndex) {
                Schema::table('gifts', function (Blueprint $table) {
                    $table->dropUnique('gifts_normalized_name_unique');
                });
            }

            if (Schema::hasColumn('gifts', 'normalized_name')) {
                Schema::table('gifts', function (Blueprint $table) {
                    $table->dropColumn('normalized_name');
                });
            }
        }

        if (Schema::hasTable('gift_movements') && Schema::hasColumn('gift_movements', 'gift_transfer_id')) {
            Schema::table('gift_movements', function (Blueprint $table) {
                $table->dropConstrainedForeignId('gift_transfer_id');
            });
        }

        Schema::dropIfExists('gift_transfers');
        Schema::dropIfExists('gift_stocks');
    }
};

