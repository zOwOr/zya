<?php

namespace Tests\Feature;

use App\Models\Branch;
use App\Models\CashFlow;
use App\Models\DailyCut;
use App\Models\ExpenseType;
use App\Models\User;
use Illuminate\Foundation\Testing\DatabaseTransactions;
use Spatie\Permission\Models\Permission;
use Spatie\Permission\Models\Role;
use Tests\TestCase;

class ExpenseTypesAndCorteZTest extends TestCase
{
    use DatabaseTransactions;

    protected Branch $branchA;
    protected Branch $branchB;
    protected User $adminUser;
    protected User $cashierUser;

    protected function setUp(): void
    {
        parent::setUp();

        $superRole = Role::firstOrCreate(['name' => 'SuperAdmin', 'guard_name' => 'web']);
        $cajeroRole = Role::firstOrCreate(['name' => 'CajeroEgresoTest_' . uniqid(), 'guard_name' => 'web']);

        foreach (['cash.menu', 'cash.read', 'cash.create'] as $permName) {
            $perm = Permission::firstOrCreate(['name' => $permName, 'guard_name' => 'web']);
            $cajeroRole->givePermissionTo($perm);
        }

        $this->branchA = Branch::create([
            'name' => 'Sucursal Allende Test ' . uniqid(),
            'address' => 'Centro',
            'phone' => '8260001111',
            'is_active' => true,
            'auto_cut_enabled' => false,
        ]);

        $this->branchB = Branch::create([
            'name' => 'Sucursal Montemorelos Test ' . uniqid(),
            'address' => 'Zaragoza',
            'phone' => '8260002222',
            'is_active' => true,
            'auto_cut_enabled' => false,
        ]);

        $this->adminUser = User::create([
            'name' => 'Esmeralda Catalogo Test',
            'username' => 'esme_exp_' . uniqid(),
            'email' => 'esme_exp_' . uniqid() . '@zyacelulares.com',
            'password' => bcrypt('secret123'),
            'branch_id' => $this->branchA->id,
        ]);
        $this->adminUser->assignRole($superRole);

        $this->cashierUser = User::create([
            'name' => 'Cajera Test Egresos',
            'username' => 'cajera_exp_' . uniqid(),
            'email' => 'cajera_exp_' . uniqid() . '@zyacelulares.com',
            'password' => bcrypt('secret123'),
            'branch_id' => $this->branchB->id,
        ]);
        $this->cashierUser->assignRole($cajeroRole);
    }

    public function test_default_expense_types_catalog_is_seeded_in_exact_order_with_otro_at_the_end(): void
    {
        $ordered = ExpenseType::ordered()->pluck('name')->all();

        $expectedPrefix = [
            'Mobiliario y equipo',
            'Limpieza',
            'Papelería',
            'Servicios',
            'Renta del local',
            'Mercancía',
            'Refacciones',
            'Transporte',
            'Envíos y paquetería',
            'Alimentos y viáticos',
            'Sueldos, adelantos y comisiones',
            'Mantenimiento del local o equipo',
            'Publicidad',
            'Sistemas y trámites',
            'Retiros y gastos personales',
            'Préstamos internos',
            'Depósitos y traspasos de efectivo',
        ];

        foreach ($expectedPrefix as $idx => $name) {
            $this->assertContains($name, $ordered);
            $this->assertSame($name, $ordered[$idx]);
        }

        $this->assertSame('Otro', end($ordered));
        $otro = ExpenseType::where('is_other', true)->firstOrFail();
        $this->assertTrue($otro->requires_reason);
    }

    public function test_most_used_expense_types_are_ranked_per_branch_above_full_list_with_otro_last(): void
    {
        $servicios = ExpenseType::where('slug', 'servicios')->firstOrFail();
        $limpieza = ExpenseType::where('slug', 'limpieza')->firstOrFail();
        $transporte = ExpenseType::where('slug', 'transporte')->firstOrFail();
        $otro = ExpenseType::where('is_other', true)->firstOrFail();

        // Sucursal A: 3 veces Servicios, 1 vez Limpieza, 4 veces Otro (Otro no debe subir a Más usados)
        for ($i = 0; $i < 3; $i++) {
            CashFlow::create([
                'branch_id' => $this->branchA->id,
                'user_id' => $this->adminUser->id,
                'type' => 'expense',
                'amount' => 100,
                'description' => "Luz {$i}",
                'module' => 'Egresos / Gastos',
                'expense_type_id' => $servicios->id,
                'expense_type_name' => $servicios->name,
                'payment_method' => 'efectivo',
                'cash_amount' => 100,
            ]);
        }
        CashFlow::create([
            'branch_id' => $this->branchA->id,
            'user_id' => $this->adminUser->id,
            'type' => 'expense',
            'amount' => 50,
            'description' => 'Pinol y cloro',
            'module' => 'Egresos / Gastos',
            'expense_type_id' => $limpieza->id,
            'expense_type_name' => $limpieza->name,
            'payment_method' => 'efectivo',
            'cash_amount' => 50,
        ]);
        for ($i = 0; $i < 4; $i++) {
            CashFlow::create([
                'branch_id' => $this->branchA->id,
                'user_id' => $this->adminUser->id,
                'type' => 'expense',
                'amount' => 30,
                'description' => "Gasto vario {$i}",
                'module' => 'Egresos / Gastos',
                'expense_type_id' => $otro->id,
                'expense_type_name' => $otro->name,
                'payment_method' => 'efectivo',
                'cash_amount' => 30,
            ]);
        }

        // Sucursal B: 2 veces Transporte
        for ($i = 0; $i < 2; $i++) {
            CashFlow::create([
                'branch_id' => $this->branchB->id,
                'user_id' => $this->cashierUser->id,
                'type' => 'expense',
                'amount' => 200,
                'description' => "Gasolina {$i}",
                'module' => 'Egresos / Gastos',
                'expense_type_id' => $transporte->id,
                'expense_type_name' => $transporte->name,
                'payment_method' => 'efectivo',
                'cash_amount' => 200,
            ]);
        }

        $selectorA = ExpenseType::forBranchSelector($this->branchA->id);
        $this->assertEquals(['Servicios', 'Limpieza'], $selectorA['mostUsed']->pluck('name')->all());
        $this->assertSame('Otro', $selectorA['allTypes']->last()->name);

        $selectorB = ExpenseType::forBranchSelector($this->branchB->id);
        $this->assertEquals(['Transporte'], $selectorB['mostUsed']->pluck('name')->all());
        $this->assertSame('Otro', $selectorB['allTypes']->last()->name);

        // Validar HTML en Corte y Caja para Sucursal A
        $this->actingAs($this->adminUser);
        $respA = $this->get(route('cash.index', ['branch_id' => $this->branchA->id]));
        $respA->assertStatus(200);
        $htmlA = $respA->getContent();
        $this->assertStringContainsString('Más usados en esta sucursal', $htmlA);
        $this->assertStringContainsString('Lista completa', $htmlA);
        $this->assertStringContainsString('Otro (motivo obligatorio)', $htmlA);

        $posTop = strpos($htmlA, 'Servicios (luz, agua, internet) (3 usos)');
        $posFull = strpos($htmlA, 'optgroup label="Lista completa"');
        $posOtro = strpos($htmlA, 'Otro (motivo obligatorio)');
        $this->assertNotFalse($posTop);
        $this->assertNotFalse($posFull);
        $this->assertNotFalse($posOtro);
        $this->assertLessThan($posFull, $posTop);
        $this->assertGreaterThan($posFull, $posOtro);
    }

    public function test_otro_expense_requires_reason_while_typed_expenses_save_type_reason_and_show_in_corte_z(): void
    {
        $otro = ExpenseType::where('is_other', true)->firstOrFail();
        $servicios = ExpenseType::where('slug', 'servicios')->firstOrFail();

        // Ingreso inicial en Sucursal B para tener saldo de caja
        CashFlow::create([
            'branch_id' => $this->branchB->id,
            'user_id' => $this->cashierUser->id,
            'type' => 'income',
            'amount' => 2000.00,
            'description' => 'Venta de apertura test',
            'module' => 'Cobro Centralizado',
            'payment_method' => 'efectivo',
            'cash_amount' => 2000.00,
        ]);

        $this->actingAs($this->cashierUser);

        // 1) "Otro" sin motivo debe fallar validación
        $failOtro = $this->from(route('cash.index'))->post(route('cash.store'), [
            'branch_id' => $this->branchB->id,
            'type' => 'expense',
            'payment_method' => 'efectivo',
            'expense_type_id' => $otro->id,
            'amount' => 150.00,
            'description' => '   ',
        ]);
        $failOtro->assertRedirect(route('cash.index'));
        $failOtro->assertSessionHasErrors('description');
        $this->assertEquals(0, CashFlow::where('branch_id', $this->branchB->id)->where('type', 'expense')->count());

        // 2) "Otro" CON motivo sí se registra
        $okOtro = $this->post(route('cash.store'), [
            'branch_id' => $this->branchB->id,
            'type' => 'expense',
            'payment_method' => 'efectivo',
            'expense_type_id' => $otro->id,
            'amount' => 240.00,
            'description' => 'Compra urgente de extensión eléctrica',
        ]);
        $okOtro->assertRedirect(route('cash.index', ['branch_id' => $this->branchB->id]));

        // 3) Tipo específico ("Servicios") con importe y motivo
        $okServ = $this->post(route('cash.store'), [
            'branch_id' => $this->branchB->id,
            'type' => 'expense',
            'payment_method' => 'efectivo',
            'expense_type_id' => $servicios->id,
            'amount' => 890.50,
            'description' => 'Recibo de luz CFE noviembre',
        ]);
        $okServ->assertRedirect(route('cash.index', ['branch_id' => $this->branchB->id]));

        $this->assertDatabaseHas('cash_flows', [
            'branch_id' => $this->branchB->id,
            'type' => 'expense',
            'expense_type_id' => $servicios->id,
            'expense_type_name' => 'Servicios',
            'description' => 'Recibo de luz CFE noviembre',
            'amount' => 890.50,
        ]);

        // Verificar impacto en saldo de caja: 2000 - (240 + 890.50) = 869.50
        $this->get(route('cash.index'))
            ->assertStatus(200)
            ->assertViewHas('cashBalance', 869.50);

        // Aplicar Corte Z como Admin para Sucursal B y revisar vista en pantalla + impresión de Corte Z
        $this->actingAs($this->adminUser);
        $this->post(route('cash.applyCut'), [
            'branch_id' => $this->branchB->id,
            'date' => now()->toDateString(),
            'declared_cash' => 869.50,
        ])->assertRedirect();

        $corteZ = DailyCut::where('branch_id', $this->branchB->id)
            ->where('cut_type', 'corte_z')
            ->latest('id')
            ->firstOrFail();

        $this->assertEquals(1130.50, (float) $corteZ->total_expense);

        $cutScreenResp = $this->get(route('cash.dailyCut', [
            'branch_id' => $this->branchB->id,
            'date' => now()->toDateString(),
        ]));
        $cutScreenResp->assertStatus(200)
            ->assertSee('Detalle de Egresos / Salidas de Caja')
            ->assertSee('Servicios')
            ->assertSee('Recibo de luz CFE noviembre')
            ->assertSee('890.50')
            ->assertSee('Otro')
            ->assertSee('Compra urgente de extensión eléctrica')
            ->assertSee('240.00');

        $cutPrintResp = $this->get(route('cash.printCut', [
            'cut_id' => $corteZ->id,
            'branch_id' => $this->branchB->id,
        ]));
        $cutPrintResp->assertStatus(200)
            ->assertSee('Detalle de Egresos / Salidas de Caja')
            ->assertSee('Servicios')
            ->assertSee('Recibo de luz CFE noviembre')
            ->assertSee('890.50')
            ->assertSee('Otro')
            ->assertSee('Compra urgente de extensión eléctrica')
            ->assertSee('240.00');
    }

    public function test_expense_types_crud_and_permissions_preserve_history(): void
    {
        // Cajera sin cash.edit no puede modificar catálogo CRUD
        $this->actingAs($this->cashierUser);
        $this->post(route('cash.expenseTypes.store'), [
            'name' => 'Intento Cajera',
        ])->assertStatus(403);

        // Admin / Esmeralda tiene CRUD completo
        $this->actingAs($this->adminUser);
        $this->get(route('cash.expenseTypes.index'))
            ->assertStatus(200)
            ->assertSee('Mobiliario y equipo');

        // Create
        $this->post(route('cash.expenseTypes.store'), [
            'name' => 'Gastos Bancarios',
            'examples' => 'comisión terminal, transferencias',
            'sort_order' => 25,
            'requires_reason' => '1',
            'is_active' => '1',
        ])->assertRedirect();

        $custom = ExpenseType::where('name', 'Gastos Bancarios')->firstOrFail();
        $this->assertTrue($custom->requires_reason);
        $this->assertTrue($custom->is_active);

        // Registrar un movimiento con ese tipo personalizado
        $this->post(route('cash.store'), [
            'branch_id' => $this->branchA->id,
            'type' => 'expense',
            'payment_method' => 'efectivo',
            'expense_type_id' => $custom->id,
            'amount' => 115.00,
            'description' => 'Renta terminal clip',
        ])->assertRedirect();

        // Update
        $this->put(route('cash.expenseTypes.update', $custom->id), [
            'name' => 'Comisiones Bancarias',
            'examples' => 'terminal, banco',
            'sort_order' => 26,
            'is_active' => '1',
        ])->assertRedirect();

        $this->assertEquals('Comisiones Bancarias', $custom->fresh()->name);

        // Toggle inactive -> ya no aparece en el selector activo
        $this->patch(route('cash.expenseTypes.toggle', $custom->id))->assertRedirect();
        $this->assertFalse($custom->fresh()->is_active);
        $this->assertFalse(
            ExpenseType::forBranchSelector($this->branchA->id)['allTypes']->contains('id', $custom->id)
        );

        // Delete -> elimina del catálogo pero mantiene el nombre histórico en CashFlow para Corte Z
        $this->delete(route('cash.expenseTypes.destroy', $custom->id))->assertRedirect();
        $this->assertDatabaseMissing('expense_types', ['id' => $custom->id]);
        $this->assertDatabaseHas('cash_flows', [
            'branch_id' => $this->branchA->id,
            'description' => 'Renta terminal clip',
            'expense_type_id' => null,
            'expense_type_name' => 'Gastos Bancarios',
        ]);

        // Protección: "Otro" no puede eliminarse ni desactivarse
        $otro = ExpenseType::where('is_other', true)->firstOrFail();
        $this->delete(route('cash.expenseTypes.destroy', $otro->id))->assertRedirect();
        $this->patch(route('cash.expenseTypes.toggle', $otro->id))->assertRedirect();
        $this->assertTrue($otro->fresh()->is_active);
    }
}

