<?php

namespace Tests\Feature;

use App\Models\Branch;
use App\Models\CashFlow;
use App\Models\Customer;
use App\Models\DailyCut;
use App\Models\PosTransaction;
use App\Models\User;
use Carbon\Carbon;
use Illuminate\Foundation\Testing\DatabaseTransactions;
use Spatie\Permission\Models\Role;
use Tests\TestCase;

class DailyCutZReportTest extends TestCase
{
    use DatabaseTransactions;

    protected User $user;
    protected Branch $branch;
    protected Customer $customer;

    protected function setUp(): void
    {
        parent::setUp();

        Carbon::setTestNow(Carbon::parse('2026-11-10 21:00:00'));

        $role = Role::firstOrCreate(['name' => 'SuperAdmin', 'guard_name' => 'web']);

        $this->branch = Branch::firstOrCreate(
            ['name' => 'Allende'],
            [
                'address' => 'Centro Allende, NL',
                'phone' => '8261234567',
                'is_active' => true,
            ]
        );

        $this->user = User::create([
            'name' => 'Verónica Acosta',
            'username' => 'vero_allende_' . uniqid(),
            'email' => 'vero_' . uniqid() . '@zyacelulares.com',
            'password' => bcrypt('password123'),
            'branch_id' => $this->branch->id,
        ]);
        $this->user->assignRole($role);

        $this->customer = Customer::create([
            'tit_name' => 'Yahaira Sofía',
            'tit_email' => 'yahaira_' . uniqid() . '@example.com',
            'tit_phone' => '8261112233',
            'branch_id' => $this->branch->id,
        ]);
    }

    protected function tearDown(): void
    {
        Carbon::setTestNow();
        parent::tearDown();
    }

    /**
     * Criterio 1: Generar el Corte Z de Allende del 10/11/2026 y verificar orden y cálculos exactos:
     * - saldo inicial: $2,049.00
     * - total ingresos: $10,666.60
     * - total egresos/retiros: $3,310.00
     * - saldo final en caja (contable global): $9,405.60
     * - ingresos en efectivo: $3,534.60
     * - tarjeta: $0.00
     * - transferencia: $7,132.00
     * - efectivo físico declarado: $2,273.50
     * - efectivo esperado en caja: $2,049.00 + $3,534.60 − $3,310.00 = $2,273.60. Diferencia: −$0.10 (Faltante).
     */
    public function test_allende_corte_z_calculations_and_exact_order(): void
    {
        $testDate = '2026-11-10';
        $prevDate = '2026-11-09';

        // 1. Corte Z anterior (fondo de caja / saldo inicial que se dejó la noche anterior) = $2,049.00
        DailyCut::create([
            'branch_id' => $this->branch->id,
            'date' => $prevDate,
            'cut_type' => 'corte_z',
            'cut_mode' => 'manual',
            'user_id' => $this->user->id,
            'opening_balance' => 1500.00,
            'total_income' => 5000.00,
            'cash_income' => 2049.00,
            'card_income' => 0.00,
            'transfer_income' => 0.00,
            'total_expense' => 0.00,
            'balance' => 2049.00,
            'declared_cash' => 2049.00,
            'difference_amount' => 0.00,
            'closed_at' => Carbon::parse($prevDate . ' 21:00:00'),
        ]);

        // 2. Ingresos del 10/11/2026:
        // Efectivo: $3,534.60
        CashFlow::create([
            'branch_id' => $this->branch->id,
            'user_id' => $this->user->id,
            'type' => 'income',
            'payment_method' => 'efectivo',
            'amount' => 3534.60,
            'cash_amount' => 3534.60,
            'card_amount' => 0.00,
            'transfer_amount' => 0.00,
            'customer_name' => 'Yahaira Sofía',
            'description' => 'Cobros varios en efectivo',
            'module' => 'POS',
            'reference' => 'POS-20261110-001',
            'created_at' => Carbon::parse($testDate . ' 11:30:00'),
        ]);

        // Transferencia: $7,132.00
        CashFlow::create([
            'branch_id' => $this->branch->id,
            'user_id' => $this->user->id,
            'type' => 'income',
            'payment_method' => 'transferencia',
            'amount' => 7132.00,
            'cash_amount' => 0.00,
            'card_amount' => 0.00,
            'transfer_amount' => 7132.00,
            'customer_name' => 'Cliente Transferencia Allende',
            'description' => 'Pago por transferencia electrónica',
            'module' => 'Financieras',
            'reference' => 'TRF-20261110-99',
            'created_at' => Carbon::parse($testDate . ' 14:15:00'),
        ]);

        // Egresos del 10/11/2026:
        // Egresos en efectivo: $3,310.00
        CashFlow::create([
            'branch_id' => $this->branch->id,
            'user_id' => $this->user->id,
            'type' => 'expense',
            'payment_method' => 'efectivo',
            'amount' => 3310.00,
            'cash_amount' => 3310.00,
            'card_amount' => 0.00,
            'transfer_amount' => 0.00,
            'customer_name' => 'Gastos Sucursal',
            'description' => 'Retiros de efectivo y gastos de operación',
            'module' => 'Caja',
            'reference' => 'GAS-20261110-01',
            'created_at' => Carbon::parse($testDate . ' 17:45:00'),
        ]);

        // 3. Aplicar Corte Z con desglose de denominaciones que suma $2,273.50:
        // $1000 x 2 = $2000
        // $100 x 2 = $200
        // $50 x 1 = $50
        // $20 x 1 = $20
        // monedas = $3.50
        // Total físico = $2,273.50
        $response = $this->actingAs($this->user)->post(route('cash.applyCut'), [
            'date' => $testDate,
            'branch_id' => $this->branch->id,
            'withdraw_amount' => 0.00,
            'notes' => 'Corte Z de Allende 10/11/2026',
            'denominations' => [
                '1000' => 2,
                '500' => 0,
                '200' => 0,
                '100' => 2,
                '50' => 1,
                '20' => 1,
                'coins' => 3.50,
            ],
        ]);

        $response->assertRedirect(route('cash.dailyCut', ['date' => $testDate, 'branch_id' => $this->branch->id]));

        // 4. Verificar en base de datos los cálculos de Corte Z
        $cut = DailyCut::where('branch_id', $this->branch->id)
            ->whereDate('date', $testDate)
            ->where('cut_type', 'corte_z')
            ->first();

        $this->assertNotNull($cut, 'El Corte Z debe haberse creado.');
        $this->assertEquals(2049.00, (float) $cut->opening_balance, 'Saldo inicial debe ser $2,049.00');
        $this->assertEquals(10666.60, (float) $cut->total_income, 'Total ingresos debe ser $10,666.60');
        $this->assertEquals(3534.60, (float) $cut->cash_income, 'Ingresos en efectivo deben ser $3,534.60');
        $this->assertEquals(0.00, (float) $cut->card_income, 'Tarjeta debe ser $0.00');
        $this->assertEquals(7132.00, (float) $cut->transfer_income, 'Transferencia debe ser $7,132.00');
        $this->assertEquals(3310.00, (float) $cut->total_expense, 'Total egresos debe ser $3,310.00');
        $this->assertEquals(9405.60, (float) $cut->balance, 'Saldo contable global debe ser $9,405.60 (2049 + 10666.60 - 3310)');
        $this->assertEquals(2273.50, (float) $cut->declared_cash, 'Efectivo físico declarado debe ser $2,273.50');
        $this->assertEquals(-0.10, (float) $cut->difference_amount, 'Diferencia contra esperado (2273.60) debe ser -0.10');

        $this->assertIsArray($cut->denomination_breakdown);
        $this->assertEquals(2, $cut->denomination_breakdown['b1000']);
        $this->assertEquals(2, $cut->denomination_breakdown['b100']);
        $this->assertEquals(1, $cut->denomination_breakdown['b50']);
        $this->assertEquals(1, $cut->denomination_breakdown['b20']);
        $this->assertEquals(3.50, (float) $cut->denomination_breakdown['coins']);
        $this->assertEquals(2273.50, (float) $cut->denomination_breakdown['total']);

        // 5. Verificar reporte de impresión (printCut)
        $printRes = $this->actingAs($this->user)->get(route('cash.printCut', [
            'cut_id' => $cut->id,
            'branch_id' => $this->branch->id,
            'date' => $testDate,
        ]));

        $printRes->assertStatus(200);
        $printContent = $printRes->getContent();

        // Verificar que los 3 bloques principales existen en el orden exacto:
        $posSec1 = strpos($printContent, 'Resumen de Caja');
        $posSec2 = strpos($printContent, 'Conteo Físico por Denominación');
        $posSec3 = strpos($printContent, 'Bitácora Cronológica con Detalle por Operación del Cliente');

        $this->assertNotFalse($posSec1, 'Debe contener la Sección 1: Resumen de Caja');
        $this->assertNotFalse($posSec2, 'Debe contener la Sección 2: Conteo Físico por Denominación');
        $this->assertNotFalse($posSec3, 'Debe contener la Sección 3: Bitácora Cronológica con Detalle por Operación del Cliente');
        $this->assertTrue($posSec1 < $posSec2 && $posSec2 < $posSec3, 'Las 3 secciones deben aparecer en el orden exacto 1 -> 2 -> 3');

        // Verificar valores en Sección 1
        $this->assertStringContainsString('2,049.00', $printContent, 'Debe contener el saldo inicial de 2,049.00');
        $this->assertStringContainsString('3,534.60', $printContent, 'Debe contener los ingresos en efectivo de 3,534.60');
        $this->assertStringContainsString('3,310.00', $printContent, 'Debe contener los egresos en efectivo de 3,310.00');
        $this->assertStringContainsString('2,273.60', $printContent, 'Debe contener el efectivo esperado de 2,273.60');
        $this->assertStringContainsString('7,132.00', $printContent, 'Debe mostrar transferencias de 7,132.00 por separado');

        // Verificar Sección 2: Denominaciones y Diferencia
        $this->assertStringContainsString('2,273.50', $printContent, 'Debe contener el efectivo físico declarado de 2,273.50');
        $this->assertStringContainsStringIgnoringCase('Faltante', $printContent, 'Debe marcar el resultado como Faltante');
        $this->assertStringContainsString('0.10', $printContent, 'Debe mostrar la diferencia de 0.10');

        // Verificar que NO existe la sección duplicada
        $this->assertStringNotContainsString('Desglose de Movimientos por Cliente', $printContent, 'No debe existir el apartado separado Desglose de Movimientos por Cliente');
    }

    /**
     * Criterio 2 y 3: Conteo físico calcula sobrante o faltante comparado contra el efectivo esperado
     * y la bitácora agrupa los conceptos que el cliente paga en una misma operación con viñetas/bullets.
     */
    public function test_chronological_log_groups_concepts_with_bullets_and_no_duplication(): void
    {
        $testDate = '2026-11-10';

        // Crear una transacción POS con 3 conceptos en una misma visita
        $itemsPayload = [
            [
                'title' => 'Abono a PayJoy (Semana 4)',
                'amount' => 700.00,
                'quantity' => 1,
                'commission' => 20.00,
                'section' => 'financiera_abono',
            ],
            [
                'title' => 'Compra de funda reforzada',
                'amount' => 300.00,
                'quantity' => 1,
                'commission' => 0.00,
                'section' => 'accesorio',
            ],
            [
                'title' => 'Abono a tanda #3',
                'amount' => 500.00,
                'quantity' => 1,
                'commission' => 0.00,
                'section' => 'tanda_abono',
            ],
        ];

        $posTx = PosTransaction::create([
            'transaction_code' => 'POS-TX-' . uniqid(),
            'branch_id' => $this->branch->id,
            'user_id' => $this->user->id,
            'customer_id' => $this->customer->id,
            'total_amount' => 1500.00,
            'paid_amount' => 1500.00,
            'change_amount' => 0.00,
            'payment_method' => 'efectivo',
            'status' => 'completado',
            'items_payload' => $itemsPayload,
            'notes' => 'Operación combinada Yahaira Sofía',
            'created_at' => Carbon::parse($testDate . ' 16:30:00'),
        ]);

        $cashFlow = CashFlow::create([
            'branch_id' => $this->branch->id,
            'user_id' => $this->user->id,
            'pos_transaction_id' => $posTx->id,
            'type' => 'income',
            'payment_method' => 'efectivo',
            'amount' => 1500.00,
            'cash_amount' => 1500.00,
            'card_amount' => 0.00,
            'transfer_amount' => 0.00,
            'customer_name' => 'Yahaira Sofía',
            'description' => 'Cobro Central POS: Abono a PayJoy, Compra de funda, Abono a tanda',
            'module' => 'POS',
            'reference' => 'POS-VISITA-001',
            'created_at' => Carbon::parse($testDate . ' 16:30:00'),
        ]);

        // Consultar la vista web de Corte Diario
        $webRes = $this->actingAs($this->user)->get(route('cash.dailyCut', [
            'date' => $testDate,
            'branch_id' => $this->branch->id,
        ]));

        $webRes->assertStatus(200);
        $webContent = $webRes->getContent();

        // 1. Verificar que Yahaira Sofía aparece con el total de la operación ($1,500.00)
        $this->assertStringContainsString('Yahaira Sofía', $webContent);
        $this->assertStringContainsString('1,500.00', $webContent);

        // 2. Verificar que los 3 conceptos aparecen individualmente con sus montos:
        $this->assertStringContainsString('Abono a PayJoy (Semana 4)', $webContent);
        $this->assertStringContainsString('700.00', $webContent);
        $this->assertStringContainsString('Compra de funda reforzada', $webContent);
        $this->assertStringContainsString('300.00', $webContent);
        $this->assertStringContainsString('Abono a tanda #3', $webContent);
        $this->assertStringContainsString('500.00', $webContent);

        // 3. Verificar que NO existe la sección duplicada "Desglose por Cliente de Todo el Movimiento del Día"
        $this->assertStringNotContainsString('Desglose por Cliente de Todo el Movimiento del Día', $webContent);

        // Consultar la vista de impresión
        $printRes = $this->actingAs($this->user)->get(route('cash.printCut', [
            'date' => $testDate,
            'branch_id' => $this->branch->id,
        ]));

        $printRes->assertStatus(200);
        $printContent = $printRes->getContent();

        $this->assertStringContainsString('Yahaira Sofía', $printContent);
        $this->assertStringContainsString('1,500.00', $printContent);
        $this->assertStringContainsString('Abono a PayJoy (Semana 4)', $printContent);
        $this->assertStringContainsString('700.00', $printContent);
        $this->assertStringContainsString('Compra de funda reforzada', $printContent);
        $this->assertStringContainsString('300.00', $printContent);
        $this->assertStringContainsString('Abono a tanda #3', $printContent);
        $this->assertStringContainsString('500.00', $printContent);
        $this->assertStringNotContainsString('Desglose de Movimientos por Cliente', $printContent);
    }

    /**
     * Criterio 4: Transferencias y pagos con tarjeta quedan FUERA del cálculo del efectivo esperado en cajón.
     */
    public function test_transfers_and_cards_are_excluded_from_expected_cash(): void
    {
        $testDate = '2026-11-10';

        // Ingreso en efectivo: $500
        CashFlow::create([
            'branch_id' => $this->branch->id,
            'user_id' => $this->user->id,
            'type' => 'income',
            'payment_method' => 'efectivo',
            'amount' => 500.00,
            'cash_amount' => 500.00,
            'created_at' => Carbon::parse($testDate . ' 10:00:00'),
        ]);

        // Ingreso en transferencia muy grande: $50,000.00
        CashFlow::create([
            'branch_id' => $this->branch->id,
            'user_id' => $this->user->id,
            'type' => 'income',
            'payment_method' => 'transferencia',
            'amount' => 50000.00,
            'cash_amount' => 0.00,
            'transfer_amount' => 50000.00,
            'created_at' => Carbon::parse($testDate . ' 11:00:00'),
        ]);

        // Ingreso con tarjeta: $10,000.00
        CashFlow::create([
            'branch_id' => $this->branch->id,
            'user_id' => $this->user->id,
            'type' => 'income',
            'payment_method' => 'tarjeta',
            'amount' => 10000.00,
            'cash_amount' => 0.00,
            'card_amount' => 10000.00,
            'created_at' => Carbon::parse($testDate . ' 12:00:00'),
        ]);

        // Egreso en efectivo: $200.00
        CashFlow::create([
            'branch_id' => $this->branch->id,
            'user_id' => $this->user->id,
            'type' => 'expense',
            'payment_method' => 'efectivo',
            'amount' => 200.00,
            'cash_amount' => 200.00,
            'created_at' => Carbon::parse($testDate . ' 13:00:00'),
        ]);

        // Saldo inicial = 0.
        // Efectivo esperado en caja debe ser ÚNICAMENTE:
        // 0 + 500 (efectivo) - 200 (egreso) = 300.00
        // Los $50,000 de transferencia y $10,000 de tarjeta NO deben entrar.
        $webRes = $this->actingAs($this->user)->get(route('cash.dailyCut', [
            'date' => $testDate,
            'branch_id' => $this->branch->id,
        ]));

        $webRes->assertStatus(200);
        $expectedCashInView = $webRes->viewData('expectedCash');
        $this->assertEquals(300.00, $expectedCashInView, 'El efectivo esperado debe ser estrictamente $300.00, excluyendo transferencias y tarjetas');

        $printRes = $this->actingAs($this->user)->get(route('cash.printCut', [
            'date' => $testDate,
            'branch_id' => $this->branch->id,
        ]));

        $printRes->assertStatus(200);
        $expectedCashInPrint = $printRes->viewData('expectedCash');
        $this->assertEquals(300.00, $expectedCashInPrint, 'El efectivo esperado en la impresión debe ser estrictamente $300.00');
    }
}
