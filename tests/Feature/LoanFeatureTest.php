<?php

namespace Tests\Feature;

use App\Models\Branch;
use App\Models\CashFlow;
use App\Models\Customer;
use App\Models\DailyCut;
use App\Models\Loan;
use App\Models\LoanInstallment;
use App\Models\LoanType;
use App\Models\User;
use Carbon\Carbon;
use Illuminate\Foundation\Testing\DatabaseTransactions;
use Spatie\Permission\Models\Role;
use Tests\TestCase;

class LoanFeatureTest extends TestCase
{
    use DatabaseTransactions;

    protected User $user;
    protected Branch $branch;
    protected Customer $customer;

    protected function setUp(): void
    {
        parent::setUp();

        $role = Role::firstOrCreate(['name' => 'SuperAdmin', 'guard_name' => 'web']);

        $this->branch = Branch::firstOrCreate(
            ['name' => 'Allende'],
            [
                'address' => 'Centro Allende',
                'phone' => '8261234567',
                'is_active' => true,
            ]
        );

        $this->user = User::create([
            'name' => 'Esmeralda',
            'username' => 'esme_' . uniqid(),
            'email' => 'esme_' . uniqid() . '@zyacelulares.com',
            'password' => bcrypt('password123'),
            'branch_id' => $this->branch->id,
        ]);
        $this->user->assignRole($role);

        $this->customer = Customer::create([
            'tit_name' => 'Juan Pérez Gómez',
            'tit_email' => 'juan_' . uniqid() . '@example.com',
            'tit_phone' => '826' . rand(1000000, 9999999),
            'tit_address' => 'Calle Hidalgo 123, Allende',
        ]);
    }

    /**
     * Criterio 3: La pantalla ya no exige elegir un tipo de préstamo pre-hecho.
     * Criterio 4: Tipos predefinidos con 20%, 40%, 50% y 60% disponibles.
     */
    public function test_create_screen_renders_successfully_with_flexible_flow_and_presets(): void
    {
        $response = $this->actingAs($this->user)->get(route('loans.create'));

        $response->assertStatus(200);
        $response->assertSee('Crear Nuevo Préstamo');
        $response->assertSee('Abono por Pago / Cuota');
        $response->assertSee('12 y 26 (Días 12 y 26)');
        $response->assertSee('20%');
        $response->assertSee('40%');
        $response->assertSee('50%');
        $response->assertSee('60%');
        $response->assertSee('Sin tipo predefinido (Captura Directa / Personalizado)');

        // Verificar que los tipos predefinidos están en la base de datos
        $this->assertTrue(LoanType::where('default_interest_rate', 20.00)->exists());
        $this->assertTrue(LoanType::where('default_interest_rate', 40.00)->exists());
        $this->assertTrue(LoanType::where('default_interest_rate', 50.00)->exists());
        $this->assertTrue(LoanType::where('default_interest_rate', 60.00)->exists());
    }

    /**
     * Criterio 1:
     * Crear un préstamo de ejemplo: capital $5,000, abono $854, 8 pagos, frecuencia 12 y 26.
     * El sistema calcula interés y total, y genera la amortización correcta sin tipo de préstamo pre-hecho.
     */
    public function test_criterion_1_create_loan_5000_installment_854_8_periods_frequency_12_26(): void
    {
        $payload = [
            'customer_id' => $this->customer->id,
            'branch_id' => $this->branch->id,
            'loan_type_id' => '', // Sin tipo de préstamo pre-hecho
            'principal_amount' => 5000.00,
            'installment_amount' => 854.00,
            'term_periods' => 8,
            'payment_frequency' => '12_26',
            'start_date' => '2026-11-11',
            'opening_commission' => 150.00,
            'commission_payment_method' => 'efectivo',
        ];

        $response = $this->actingAs($this->user)->post(route('loans.store'), $payload);

        $response->assertSessionHasNoErrors();
        $response->assertRedirect();

        $loan = Loan::where('customer_id', $this->customer->id)->latest()->first();
        $this->assertNotNull($loan);

        // Verificación de cálculos financieros
        $this->assertEquals(5000.00, (float) $loan->principal_amount);
        $this->assertEquals(854.00, (float) $loan->installment_amount);
        $this->assertEquals(8, $loan->term_periods);
        $this->assertEquals(6832.00, (float) $loan->total_amount); // 854 * 8
        $this->assertEquals(1832.00, (float) $loan->interest_amount); // 6832 - 5000
        $this->assertEquals(36.64, (float) $loan->interest_rate); // 1832 / 5000 * 100
        $this->assertEquals('12_26', $loan->payment_frequency);
        $this->assertEquals('12 y 26', $loan->payment_frequency_label);
        $this->assertNull($loan->loan_type_id);

        // Verificación de cuotas de amortización generadas
        $installments = $loan->installments;
        $this->assertCount(8, $installments);
        $this->assertEquals(6832.00, (float) $installments->sum('scheduled_amount'));

        // Comprobar las fechas exactas de la amortización en días 12 y 26
        $expectedDates = [
            '2026-11-12',
            '2026-11-26',
            '2026-12-12',
            '2026-12-26',
            '2027-01-12',
            '2027-01-26',
            '2027-02-12',
            '2027-02-26',
        ];

        foreach ($installments as $idx => $inst) {
            $this->assertEquals($expectedDates[$idx], $inst->scheduled_date->format('Y-m-d'));
            $this->assertEquals(854.00, (float) $inst->scheduled_amount);
        }

        // Verificación de impacto en caja de la comisión de apertura ($150)
        $cashFlow = CashFlow::where('reference', $loan->loan_code)->first();
        $this->assertNotNull($cashFlow);
        $this->assertEquals(150.00, (float) $cashFlow->amount);
        $this->assertEquals('income', $cashFlow->type);
        $this->assertEquals('Préstamos (Comisión Apertura)', $cashFlow->module);
    }

    /**
     * Criterio 2:
     * Crear el préstamo de $3,000 en 8 pagos de $601 y verificar que la tasa calculada es consistente.
     */
    public function test_criterion_2_create_loan_3000_installment_601_8_periods(): void
    {
        $payload = [
            'customer_id' => $this->customer->id,
            'branch_id' => $this->branch->id,
            'loan_type_id' => null,
            'principal_amount' => 3000.00,
            'installment_amount' => 601.00,
            'term_periods' => 8,
            'payment_frequency' => 'quincenal',
            'start_date' => '2026-11-11',
            'opening_commission' => 0,
        ];

        $response = $this->actingAs($this->user)->post(route('loans.store'), $payload);

        $response->assertSessionHasNoErrors();
        $response->assertRedirect();

        $loan = Loan::where('customer_id', $this->customer->id)->latest()->first();
        $this->assertNotNull($loan);

        $this->assertEquals(3000.00, (float) $loan->principal_amount);
        $this->assertEquals(601.00, (float) $loan->installment_amount);
        $this->assertEquals(4808.00, (float) $loan->total_amount); // 601 * 8
        $this->assertEquals(1808.00, (float) $loan->interest_amount); // 4808 - 3000
        $this->assertEquals(60.27, (float) $loan->interest_rate); // 1808 / 3000 * 100
        $this->assertEquals(8, $loan->installments()->count());
    }

    /**
     * Criterio 4:
     * Los tipos predefinidos con 20%, 40%, 50% y 60% son editables en el catálogo.
     */
    public function test_criterion_4_predefined_types_are_editable(): void
    {
        $loanType40 = LoanType::where('default_interest_rate', 40.00)->first();
        $this->assertNotNull($loanType40);

        // Actualizar el tipo de préstamo (por ejemplo ajustar tasa o descripción)
        $response = $this->actingAs($this->user)->put(route('loans.types.update', $loanType40->id), [
            'name' => 'Préstamo Tasa 40% (Modificado)',
            'description' => 'Tasa actualizada por Esme',
            'default_interest_rate' => 42.50,
            'default_opening_fee' => 120.00,
            'default_frequency' => '12_26',
            'default_periods' => 10,
            'is_active' => true,
        ]);

        $response->assertSessionHasNoErrors();
        $loanType40->refresh();

        $this->assertEquals('Préstamo Tasa 40% (Modificado)', $loanType40->name);
        $this->assertEquals(42.50, (float) $loanType40->default_interest_rate);
        $this->assertEquals('12_26', $loanType40->default_frequency);
        $this->assertEquals(10, $loanType40->default_periods);
    }

    /**
     * Creación de préstamo desde Módulo Centralizado POS con abono directo y frecuencia 12 y 26.
     */
    public function test_central_pos_loan_creation_with_direct_installment(): void
    {
        $payload = [
            'customer_id' => $this->customer->id,
            'customer_name' => $this->customer->tit_name,
            'branch_id' => $this->branch->id,
            'payment_method' => 'efectivo',
            'cash_amount' => 150.00,
            'card_amount' => 0,
            'transfer_amount' => 0,
            'items' => [
                [
                    'section' => 'prestamo_apertura',
                    'customer_id' => $this->customer->id,
                    'principal_amount' => 5000.00,
                    'installment_amount' => 854.00,
                    'term_periods' => 8,
                    'payment_frequency' => '12_26',
                    'commission' => 150.00,
                    'amount' => 0,
                    'quantity' => 1,
                ]
            ],
        ];

        $response = $this->actingAs($this->user)->postJson(route('central-pos.checkout'), $payload);

        $response->assertStatus(200);
        $response->assertJson(['success' => true]);

        $loan = Loan::where('customer_id', $this->customer->id)->latest()->first();
        $this->assertNotNull($loan);
        $this->assertEquals(5000.00, (float) $loan->principal_amount);
        $this->assertEquals(854.00, (float) $loan->installment_amount);
        $this->assertEquals(6832.00, (float) $loan->total_amount);
        $this->assertEquals(1832.00, (float) $loan->interest_amount);
        $this->assertEquals(36.64, (float) $loan->interest_rate);
        $this->assertEquals('12_26', $loan->payment_frequency);
        $this->assertEquals(8, $loan->installments()->count());
    }

    /**
     * Verificación de compatibilidad con commit 59185b0 (Fondo Inicial y Cuadre de Efectivo).
     * Asegura que:
     * 1) DailyCut::resolveOpeningBalance() resuelve el fondo inicial en efectivo físico.
     * 2) Las comisiones y abonos de préstamos respetan effective_cash_amount, effective_card_amount y effective_transfer_amount.
     * 3) Solo los pagos en efectivo físico alteran el efectivo esperado en el cajón de corte de caja.
     */
    public function test_loan_cash_flows_integrate_with_daily_cut_and_effective_cash_balancing(): void
    {
        $today = Carbon::today()->toDateString();
        $yesterday = Carbon::yesterday()->toDateString();

        // 1. Simular un Corte Z del día anterior con efectivo físico declarado ($1,200.00)
        DailyCut::create([
            'branch_id' => $this->branch->id,
            'user_id' => $this->user->id,
            'date' => $yesterday,
            'cut_type' => 'corte_z',
            'cut_mode' => 'manual',
            'opening_balance' => 1000.00,
            'cash_income' => 500.00,
            'card_income' => 300.00,
            'transfer_income' => 200.00,
            'total_income' => 1000.00,
            'total_expense' => 300.00,
            'balance' => 1200.00,
            'declared_cash' => 1200.00,
            'difference_amount' => 0.00,
        ]);

        // Verificar que el Fondo Inicial resuelto para hoy sea exactamente el efectivo físico declarado
        $openingBalance = DailyCut::resolveOpeningBalance($this->branch->id, $today);
        $this->assertEquals(1200.00, $openingBalance);

        // 2. Crear préstamo con comisión en efectivo ($150)
        $this->actingAs($this->user)->post(route('loans.store'), [
            'customer_id' => $this->customer->id,
            'branch_id' => $this->branch->id,
            'principal_amount' => 5000.00,
            'installment_amount' => 854.00,
            'term_periods' => 8,
            'payment_frequency' => '12_26',
            'start_date' => $today,
            'opening_commission' => 150.00,
            'commission_payment_method' => 'efectivo',
        ]);

        // 3. Crear préstamo con comisión por transferencia ($200)
        $this->actingAs($this->user)->post(route('loans.store'), [
            'customer_id' => $this->customer->id,
            'branch_id' => $this->branch->id,
            'principal_amount' => 3000.00,
            'installment_amount' => 601.00,
            'term_periods' => 8,
            'payment_frequency' => '12_26',
            'start_date' => $today,
            'opening_commission' => 200.00,
            'commission_payment_method' => 'transferencia',
        ]);

        // Obtener los flujos de caja generados hoy para la sucursal
        $flows = CashFlow::where('branch_id', $this->branch->id)
            ->whereDate('created_at', $today)
            ->get();

        $cashIncome = round((float) $flows->where('type', 'income')->sum(fn($f) => $f->effective_cash_amount), 2);
        $transferIncome = round((float) $flows->where('type', 'income')->sum(fn($f) => $f->effective_transfer_amount), 2);
        $cardIncome = round((float) $flows->where('type', 'income')->sum(fn($f) => $f->effective_card_amount), 2);
        $totalExpenses = round((float) $flows->where('type', 'expense')->sum(fn($f) => $f->effective_cash_amount), 2);

        // La comisión en efectivo suma al efectivo de caja ($150)
        $this->assertEquals(150.00, $cashIncome);

        // La comisión por transferencia NO altera el efectivo, se clasifica en transferencias ($200)
        $this->assertEquals(200.00, $transferIncome);
        $this->assertEquals(0.00, $cardIncome);

        // El desembolso de capital no genera egreso en cajón (regla de ZYA)
        $this->assertEquals(0.00, $totalExpenses);

        // El efectivo esperado en el cajón (Fondo Inicial + Efectivo Ingresado - Egresos Efectivo)
        $expectedCashInDrawer = round($openingBalance + $cashIncome - $totalExpenses, 2);
        $this->assertEquals(1350.00, $expectedCashInDrawer);
    }

    /**
     * Verificación de correcciones en el Pagaré (Defectos 1 a 5 y Criterios 1 a 6).
     * Caso de prueba:
     * Préstamo de $3,000 en 8 pagos de $601.01,
     * deudor Jesús Alberto Telles Izaguirre, aval Fernando Gabriel Telles Izaguirre.
     */
    public function test_promissory_note_defects_corrected(): void
    {
        $debtor = Customer::create([
            'tit_name' => 'Jesús Alberto Telles Izaguirre',
            'tit_email' => 'jesus.telles.' . uniqid() . '@example.com',
            'tit_phone' => '826' . rand(1000000, 9999999),
            'tit_address' => 'Allende Centro, N.L.',
            'tit_city' => 'Allende',
            'tit_status' => 'active',
            'aval_name' => 'Fernando Gabriel Telles Izaguirre',
            'aval_phone' => '8261002031',
            'aval_address' => 'Allende Centro, N.L.',
        ]);

        $payload = [
            'customer_id' => $debtor->id,
            'branch_id' => $this->branch->id,
            'loan_type_id' => null,
            'principal_amount' => 3000.00,
            'installment_amount' => 601.01,
            'term_periods' => 8,
            'payment_frequency' => '12_26',
            'start_date' => '2026-11-10',
            'aval_name' => 'Fernando Gabriel Telles Izaguirre',
            'opening_commission' => 150.00,
            'commission_payment_method' => 'efectivo',
        ];

        $response = $this->actingAs($this->user)->post(route('loans.store'), $payload);
        $loan = Loan::where('customer_id', $debtor->id)->latest()->first();
        $this->assertNotNull($loan);
        $response->assertRedirect(route('loans.show', $loan->id));

        // Criterio 1: 8 cuotas, todas en días 12 y 26
        $this->assertEquals(8, $loan->installments()->count());
        foreach ($loan->installments as $inst) {
            $day = $inst->scheduled_date->day;
            $this->assertTrue(in_array($day, [12, 26]), "La fecha {$inst->scheduled_date->toDateString()} debe caer en día 12 o 26");
            $this->assertEquals(601.01, (float) $inst->scheduled_amount);
        }

        // Consultar la vista del pagaré
        $pdfResponse = $this->actingAs($this->user)->get(route('loans.pagare.print', $loan->id));
        $pdfResponse->assertStatus(200);
        $content = $pdfResponse->getContent();

        // Criterio 2: No debe aparecer fecha de vencimiento final
        $this->assertStringNotContainsString('Vencimiento Final', $content);

        // Criterio 3: Fecha de suscripción es la fecha del préstamo (10/11/2026)
        $this->assertStringContainsString('Fecha de Suscripción:</strong> 10/11/2026', $content);

        // Criterio 4: Dice "a la orden de Nancy Esmeralda Zapata Medina"
        $this->assertStringContainsString('a la orden de <strong>Nancy Esmeralda Zapata Medina</strong>', $content);

        // Criterio 5: Solo aparece la firma del aval (sin "testigo ni autorización")
        $this->assertStringContainsString('FIRMA DEL AVAL', $content);
        $this->assertStringNotContainsString('TESTIGO O AUTORIZACIÓN', $content);
        $this->assertStringNotContainsString('TESTIGO', $content);
        $this->assertStringNotContainsString('AUTORIZACIÓN', $content);
        $this->assertStringContainsString('Fernando Gabriel Telles Izaguirre', $content);

        // Criterio 6: Resto del pagaré (Bueno por, deudor, valor recibido, calendario)
        $this->assertStringContainsString('BUENO POR: $4,808.08 M.N.', $content);
        $this->assertStringContainsString('Jesús Alberto Telles Izaguirre', $content);
        $this->assertStringContainsString('CUATRO MIL OCHOCIENTOS OCHO PESOS 08/100 M.N.', $content);
    }
}
