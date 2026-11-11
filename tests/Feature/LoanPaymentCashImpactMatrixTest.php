<?php

namespace Tests\Feature;

use App\Models\Branch;
use App\Models\CashFlow;
use App\Models\Customer;
use App\Models\DailyCut;
use App\Models\Loan;
use App\Models\LoanInstallment;
use App\Models\User;
use Carbon\Carbon;
use Illuminate\Foundation\Testing\DatabaseTransactions;
use Spatie\Permission\Models\Role;
use Tests\TestCase;

class LoanPaymentCashImpactMatrixTest extends TestCase
{
    use DatabaseTransactions;

    protected User $user;
    protected Branch $branch;
    protected Customer $customer;

    protected function setUp(): void
    {
        parent::setUp();

        Carbon::setTestNow(Carbon::parse('2026-11-10 12:00:00'));

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
            'username' => 'vero_matrix_' . uniqid(),
            'email' => 'vero_' . uniqid() . '@zyacelulares.com',
            'password' => bcrypt('password123'),
            'branch_id' => $this->branch->id,
        ]);
        $this->user->assignRole($role);

        $this->customer = Customer::create([
            'tit_name' => 'Jesús Alberto Telles Izaguirre',
            'tit_email' => 'jesus_' . uniqid() . '@example.com',
            'tit_phone' => '8261230000',
            'branch_id' => $this->branch->id,
        ]);
    }

    protected function tearDown(): void
    {
        Carbon::setTestNow();
        parent::tearDown();
    }

    /**
     * Helper para obtener el efectivo esperado en caja actual de la sucursal
     */
    protected function getExpectedCash(string $date): float
    {
        $opening = DailyCut::resolveOpeningBalance($this->branch->id, $date);
        $flows = CashFlow::where('branch_id', $this->branch->id)
            ->whereDate('created_at', $date)
            ->get();

        $cashIncome = (float) $flows->where('type', 'income')->sum(fn($f) => $f->effective_cash_amount);
        $cashExpense = (float) $flows->where('type', 'expense')->sum(fn($f) => $f->effective_cash_amount);

        return round($opening + $cashIncome - $cashExpense, 2);
    }

    /**
     * Prueba de la MATRIZ COMPLETA según las reglas de Esme:
     * 1. Cerrar un préstamo por transferencia NO tiene impacto en caja.
     * 2. Cerrar un préstamo en efectivo SÍ tiene impacto en caja.
     * 3. Los abonos en efectivo SÍ tienen impacto en caja.
     * 4. Abono por transferencia NO tiene impacto en caja.
     * 5. Los cierres manuales no afectan la caja, salvo que el pago haya sido en efectivo.
     *
     * Caso real de evidencia: liquidar préstamo de $6,832.00 por transferencia NO altera la caja física.
     */
    public function test_full_matrix_loan_payments_and_closings_cash_impact(): void
    {
        $testDate = '2026-11-10';
        $prevDate = '2026-11-09';

        // 0. Fondo inicial dejado el día anterior: $1,000.00
        DailyCut::create([
            'branch_id' => $this->branch->id,
            'date' => $prevDate,
            'cut_type' => 'corte_z',
            'cut_mode' => 'manual',
            'user_id' => $this->user->id,
            'opening_balance' => 1000.00,
            'total_income' => 0.00,
            'cash_income' => 0.00,
            'card_income' => 0.00,
            'transfer_income' => 0.00,
            'total_expense' => 0.00,
            'balance' => 1000.00,
            'declared_cash' => 1000.00,
            'difference_amount' => 0.00,
            'closed_at' => Carbon::parse($prevDate . ' 21:00:00'),
        ]);

        $this->assertEquals(1000.00, $this->getExpectedCash($testDate), 'Saldo base inicial debe ser $1,000.00');

        // =========================================================================
        // ESCENARIO 1: Abono en efectivo -> SÍ TIENE IMPACTO EN CAJA (+ $601.00)
        // =========================================================================
        $loan1 = Loan::create([
            'loan_code' => 'PREST-TEST-001',
            'branch_id' => $this->branch->id,
            'customer_id' => $this->customer->id,
            'user_id' => $this->user->id,
            'principal_amount' => 3000.00,
            'interest_rate' => 60.27,
            'interest_amount' => 1808.00,
            'total_amount' => 4808.00,
            'opening_commission' => 0.00,
            'paid_amount' => 0.00,
            'balance_amount' => 4808.00,
            'payment_frequency' => '12_26',
            'term_periods' => 8,
            'installment_amount' => 601.00,
            'start_date' => $testDate,
            'due_date' => '2027-03-26',
            'status' => 'activo',
        ]);

        $inst1 = LoanInstallment::create([
            'loan_id' => $loan1->id,
            'installment_number' => 1,
            'scheduled_date' => '2026-11-26',
            'scheduled_amount' => 601.00,
            'paid_amount' => 0.00,
            'status' => 'pendiente',
        ]);

        // Registrar abono en efectivo de $601.00
        $resAbonoEfectivo = $this->actingAs($this->user)->post(route('loans.payments.store', $loan1->id), [
            'amount' => 601.00,
            'payment_method' => 'efectivo',
            'paid_date' => $testDate,
            'installment_id' => $inst1->id,
        ]);
        $resAbonoEfectivo->assertSessionHasNoErrors();

        // Saldo de caja esperado: $1,000 + $601 = $1,601.00
        $this->assertEquals(1601.00, $this->getExpectedCash($testDate), 'Abono en efectivo DEBE impactar la caja (+ $601.00)');

        // =========================================================================
        // ESCENARIO 2: Abono por transferencia -> NO TIENE IMPACTO EN CAJA ($0.00 impacto)
        // =========================================================================
        $inst2 = LoanInstallment::create([
            'loan_id' => $loan1->id,
            'installment_number' => 2,
            'scheduled_date' => '2026-12-12',
            'scheduled_amount' => 601.00,
            'paid_amount' => 0.00,
            'status' => 'pendiente',
        ]);

        $resAbonoTransf = $this->actingAs($this->user)->post(route('loans.payments.store', $loan1->id), [
            'amount' => 601.00,
            'payment_method' => 'transferencia',
            'paid_date' => $testDate,
            'installment_id' => $inst2->id,
        ]);
        $resAbonoTransf->assertSessionHasNoErrors();

        // Saldo de caja esperado: SIGUE en $1,601.00 (sin impacto)
        $this->assertEquals(1601.00, $this->getExpectedCash($testDate), 'Abono por transferencia NO debe alterar el saldo de caja física');

        // =========================================================================
        // ESCENARIO 3: Cerrar/Liquidar préstamo por transferencia -> NO TIENE IMPACTO EN CAJA
        // Evidencia real reportada: $6,832.00 por transferencia NO deben afectar la caja
        // =========================================================================
        $loanEvidencia = Loan::create([
            'loan_code' => 'PREST-EVIDENCIA-6832',
            'branch_id' => $this->branch->id,
            'customer_id' => $this->customer->id,
            'user_id' => $this->user->id,
            'principal_amount' => 5000.00,
            'interest_rate' => 36.64,
            'interest_amount' => 1832.00,
            'total_amount' => 6832.00,
            'opening_commission' => 0.00,
            'paid_amount' => 0.00,
            'balance_amount' => 6832.00,
            'payment_frequency' => '12_26',
            'term_periods' => 8,
            'installment_amount' => 854.00,
            'start_date' => $testDate,
            'due_date' => '2027-03-26',
            'status' => 'activo',
        ]);

        // Se liquida el préstamo de prueba con $6,832.00 vía transferencia
        $resLiquidacionTransf = $this->actingAs($this->user)->post(route('loans.payments.store', $loanEvidencia->id), [
            'amount' => 6832.00,
            'payment_method' => 'transferencia',
            'paid_date' => $testDate,
            'notes' => 'Liquidación total anticipada por transferencia',
        ]);
        $resLiquidacionTransf->assertSessionHasNoErrors();

        $loanEvidencia->refresh();
        $this->assertEquals('liquidado', $loanEvidencia->status, 'El préstamo debe quedar con estatus liquidado');
        $this->assertEquals(0.00, (float) $loanEvidencia->balance_amount);

        // REGLA CRÍTICA: La caja física NO debe haberse alterado (sigue exactamente en $1,601.00)
        $this->assertEquals(1601.00, $this->getExpectedCash($testDate), 'Liquidación de $6,832.00 por transferencia NO debe afectar la caja física');

        // =========================================================================
        // ESCENARIO 4: Cierre manual sin pago en efectivo (adicional = $0) -> SIN IMPACTO
        // =========================================================================
        $loanManualZero = Loan::create([
            'loan_code' => 'PREST-MANUAL-001',
            'branch_id' => $this->branch->id,
            'customer_id' => $this->customer->id,
            'user_id' => $this->user->id,
            'principal_amount' => 4000.00,
            'total_amount' => 5000.00,
            'paid_amount' => 2000.00,
            'balance_amount' => 3000.00,
            'payment_frequency' => 'semanal',
            'term_periods' => 4,
            'installment_amount' => 1250.00,
            'start_date' => $testDate,
            'due_date' => '2026-12-10',
            'status' => 'activo',
        ]);

        $resManualZero = $this->actingAs($this->user)->post(route('loans.close-manual', $loanManualZero->id), [
            'additional_payment' => 0.00,
            'payment_method' => 'efectivo',
            'closing_reason' => 'Cierre por convenio sin pago adicional hoy',
        ]);
        $resManualZero->assertSessionHasNoErrors();

        $loanManualZero->refresh();
        $this->assertEquals('cerrado_manual', $loanManualZero->status);

        // Saldo de caja esperado: SIGUE en $1,601.00 (sin impacto)
        $this->assertEquals(1601.00, $this->getExpectedCash($testDate), 'Cierre manual con $0 de pago NO debe alterar la caja');

        // =========================================================================
        // ESCENARIO 5: Cierre manual con pago por transferencia -> SIN IMPACTO EN CAJA
        // =========================================================================
        $loanManualTransf = Loan::create([
            'loan_code' => 'PREST-MANUAL-TRF',
            'branch_id' => $this->branch->id,
            'customer_id' => $this->customer->id,
            'user_id' => $this->user->id,
            'principal_amount' => 4000.00,
            'total_amount' => 5000.00,
            'paid_amount' => 1000.00,
            'balance_amount' => 4000.00,
            'payment_frequency' => 'semanal',
            'term_periods' => 4,
            'installment_amount' => 1250.00,
            'start_date' => $testDate,
            'due_date' => '2026-12-10',
            'status' => 'activo',
        ]);

        $resManualTrf = $this->actingAs($this->user)->post(route('loans.close-manual', $loanManualTransf->id), [
            'additional_payment' => 1500.00,
            'payment_method' => 'transferencia',
            'closing_reason' => 'Liquidación negociada recibida por transferencia',
        ]);
        $resManualTrf->assertSessionHasNoErrors();

        $loanManualTransf->refresh();
        $this->assertEquals('cerrado_manual', $loanManualTransf->status);

        // Saldo de caja esperado: SIGUE en $1,601.00 (la transferencia no entra al cajón físico)
        $this->assertEquals(1601.00, $this->getExpectedCash($testDate), 'Cierre manual pagado por transferencia NO debe alterar la caja física');

        // =========================================================================
        // ESCENARIO 6: Cierre manual con pago en efectivo -> SÍ TIENE IMPACTO EN CAJA (+ $800.00)
        // =========================================================================
        $loanManualCash = Loan::create([
            'loan_code' => 'PREST-MANUAL-CSH',
            'branch_id' => $this->branch->id,
            'customer_id' => $this->customer->id,
            'user_id' => $this->user->id,
            'principal_amount' => 2000.00,
            'total_amount' => 2500.00,
            'paid_amount' => 500.00,
            'balance_amount' => 2000.00,
            'payment_frequency' => 'semanal',
            'term_periods' => 4,
            'installment_amount' => 625.00,
            'start_date' => $testDate,
            'due_date' => '2026-12-10',
            'status' => 'activo',
        ]);

        $resManualCash = $this->actingAs($this->user)->post(route('loans.close-manual', $loanManualCash->id), [
            'additional_payment' => 800.00,
            'payment_method' => 'efectivo',
            'closing_reason' => 'Liquidación en efectivo con quita autorizada',
        ]);
        $resManualCash->assertSessionHasNoErrors();

        $loanManualCash->refresh();
        $this->assertEquals('cerrado_manual', $loanManualCash->status);

        // Saldo de caja esperado: $1,601.00 + $800.00 = $2,401.00
        $this->assertEquals(2401.00, $this->getExpectedCash($testDate), 'Cierre manual con pago en efectivo SÍ debe impactar la caja (+ $800.00)');

        // =========================================================================
        // ESCENARIO 7: Cerrar/Liquidar préstamo en efectivo -> SÍ TIENE IMPACTO EN CAJA (+ $3,000.00)
        // =========================================================================
        $loanLiquidacionEfectivo = Loan::create([
            'loan_code' => 'PREST-LIQUIDAR-CSH',
            'branch_id' => $this->branch->id,
            'customer_id' => $this->customer->id,
            'user_id' => $this->user->id,
            'principal_amount' => 2500.00,
            'interest_rate' => 20.00,
            'interest_amount' => 500.00,
            'total_amount' => 3000.00,
            'opening_commission' => 0.00,
            'paid_amount' => 0.00,
            'balance_amount' => 3000.00,
            'payment_frequency' => 'mensual',
            'term_periods' => 1,
            'installment_amount' => 3000.00,
            'start_date' => $testDate,
            'due_date' => '2026-12-10',
            'status' => 'activo',
        ]);

        $resLiquidacionCash = $this->actingAs($this->user)->post(route('loans.payments.store', $loanLiquidacionEfectivo->id), [
            'amount' => 3000.00,
            'payment_method' => 'efectivo',
            'paid_date' => $testDate,
            'notes' => 'Liquidación total en efectivo en sucursal',
        ]);
        $resLiquidacionCash->assertSessionHasNoErrors();

        $loanLiquidacionEfectivo->refresh();
        $this->assertEquals('liquidado', $loanLiquidacionEfectivo->status);

        // Saldo de caja esperado: $2,401.00 + $3,000.00 = $5,401.00
        $this->assertEquals(5401.00, $this->getExpectedCash($testDate), 'Cierre en efectivo SÍ debe impactar la caja (+ $3,000.00)');

        // =========================================================================
        // VERIFICACIÓN FINAL EN CORTE DIARIO (VISTA WEB Y REPORTE Z IMPRESO)
        // =========================================================================
        // Ingresos en efectivo totales = $601 (abono) + $800 (cierre manual) + $3,000 (liquidación) = $4,401.00
        // Fondo inicial = $1,000.00
        // Egresos en efectivo = $0.00
        // Efectivo esperado en caja = $1,000.00 + $4,401.00 = $5,401.00
        // Transferencias acumuladas = $601 (abono) + $6,832 (liquidación) + $1,500 (cierre manual) = $8,933.00

        $webRes = $this->actingAs($this->user)->get(route('cash.dailyCut', [
            'date' => $testDate,
            'branch_id' => $this->branch->id,
        ]));
        $webRes->assertStatus(200);

        $this->assertEquals(5401.00, (float) $webRes->viewData('expectedCash'));
        $this->assertEquals(4401.00, (float) $webRes->viewData('handCash'));
        $this->assertEquals(8933.00, (float) $webRes->viewData('cheque')); // transferencias

        // Al aplicar Corte Z con el conteo físico exacto de $5,401.00:
        $applyRes = $this->actingAs($this->user)->post(route('cash.applyCut'), [
            'date' => $testDate,
            'branch_id' => $this->branch->id,
            'declared_cash' => 5401.00,
            'notes' => 'Corte Z con matriz completa probada',
        ]);
        $applyRes->assertSessionHasNoErrors();

        $corteZ = DailyCut::where('branch_id', $this->branch->id)
            ->whereDate('date', $testDate)
            ->where('cut_type', 'corte_z')
            ->first();

        $this->assertNotNull($corteZ);
        $this->assertEquals(1000.00, (float) $corteZ->opening_balance);
        $this->assertEquals(4401.00, (float) $corteZ->cash_income);
        $this->assertEquals(8933.00, (float) $corteZ->transfer_income);
        $this->assertEquals(5401.00, (float) $corteZ->declared_cash);
        $this->assertEquals(0.00, (float) $corteZ->difference_amount, 'La caja debe estar perfectamente cuadrada ($0.00 de diferencia)');
    }
}

