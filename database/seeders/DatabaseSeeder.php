<?php

namespace Database\Seeders;

// use Illuminate\Database\Console\Seeds\WithoutModelEvents;

use App\Models\Product;
use App\Models\Category;
use App\Models\Customer;
use App\Models\Employee;
use App\Models\Supplier;
use App\Models\AdvanceSalary;
use Illuminate\Database\Seeder;
use Spatie\Permission\Models\Role;
use Spatie\Permission\Models\Permission;
use Haruncpi\LaravelIdGenerator\IdGenerator;

class DatabaseSeeder extends Seeder
{
    /**
     * Seed the application's database.
     */
    public function run(): void
    {
        // \App\Models\User::factory(10)->create();

        $admin = \App\Models\User::factory()->create([
            'name' => 'Admin',
            'username' => 'admin',
            'email' => 'admin@gmail.com',
        ]);

        $user = \App\Models\User::factory()->create([
            'name' => 'User',
            'username' => 'user',
            'email' => 'user@gmail.com',
        ]);

        Employee::factory(5)->create();
        // AdvanceSalary::factory(25)->create();

        Customer::factory(25)->create();
        Supplier::factory(10)->create();

        for ($i=0; $i < 10; $i++) {
            Product::factory()->create([
                'product_code' => IdGenerator::generate([
                    'table' => 'products',
                    'field' => 'product_code',
                    'length' => 4,
                    'prefix' => 'PC'
                ])
            ]);
        }
        Category::factory(5)->create();

        $permissionGroups = [
            'pos',
            'employee',
            'customer',
            'supplier',
            'salary',
            'attendence',
            'category',
            'product',
            'orders',
            'stock',
            'roles',
            'permissions',
            'user',
            'branch',
            'database',
            'cash',
            'repairs',
            'tandas',
        ];

        $actions = ['menu', 'read', 'create', 'edit', 'delete'];

        foreach ($permissionGroups as $group) {
            foreach ($actions as $action) {
                Permission::firstOrCreate(
                    ['name' => "{$group}.{$action}"],
                    ['group_name' => $group]
                );
            }
        }

        Role::create(['name' => 'SuperAdmin'])->givePermissionTo(Permission::all());
        Role::create(['name' => 'Admin'])->givePermissionTo([
            'customer.menu', 'customer.read', 'customer.create', 'customer.edit', 'customer.delete',
            'user.menu', 'user.read', 'user.create', 'user.edit', 'user.delete',
            'supplier.menu', 'supplier.read', 'supplier.create', 'supplier.edit', 'supplier.delete',
        ]);
        Role::create(['name' => 'Account'])->givePermissionTo([
            'customer.menu', 'customer.read',
            'user.menu', 'user.read',
            'supplier.menu', 'supplier.read',
        ]);
        Role::create(['name' => 'Manager'])->givePermissionTo([
            'stock.menu', 'stock.read',
            'orders.menu', 'orders.read', 'orders.create', 'orders.edit', 'orders.delete',
            'product.menu', 'product.read', 'product.create', 'product.edit', 'product.delete',
            'salary.menu', 'salary.read',
            'employee.menu', 'employee.read',
        ]);

        $admin->assignRole('SuperAdmin');
        $user->assignRole('Account');
    }
}
