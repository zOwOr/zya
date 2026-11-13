<?php

use App\Models\Permission;
use Database\Seeders\ExternalCustomerPermissionSeeder;
use Illuminate\Database\Migrations\Migration;
use Spatie\Permission\PermissionRegistrar;

return new class extends Migration
{
    public function up(): void
    {
        (new ExternalCustomerPermissionSeeder())->run();
    }

    public function down(): void
    {
        $names = array_column(ExternalCustomerPermissionSeeder::PERMISSIONS, 'name');
        Permission::whereIn('name', $names)->delete();
        app()[PermissionRegistrar::class]->forgetCachedPermissions();
    }
};

