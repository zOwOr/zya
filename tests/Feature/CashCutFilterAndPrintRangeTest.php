<?php

namespace Tests\Feature;

use App\Models\Branch;
use App\Models\CashFlow;
use App\Models\Customer;
use App\Models\DailyCut;
use App\Models\User;
use Carbon\Carbon;
use Illuminate\Foundation\Testing\DatabaseTransactions;
use Spatie\Permission\Models\Role;
use Tests\TestCase;

class CashCutFilterAndPrintRangeTest extends TestCase
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
            ['name' => 'Test Sucursal Centro'],
            [
                'address' => 'Av Hidalgo 100',
                'phone' => '8261112233',
                'is_active' => true,
            ]
        );

        $this->user = User::create([
            'name' => 'Cajero Test',
            'username' => 'cajero_' . uniqid(),
            'email' => 'cajero_' . uniqid() . '@example.com',
            'password' => bcrypt('password123'),
            'branch_id' => $this->branch->id,
        ]);
        $this->user->assignRole($role);

        $this->customer = Customer::create([
            'tit_name' => 'Cliente Test',
            'tit_email' => 'cliente_' . uniqid() . '@example.com',
            'tit_phone' => '8265554433',
            'branch_id' => $this->branch->id,
        ]);
    }

    public function test_filter_by_specific_day()
    {
        $cut1 = DailyCut::create([
            'branch_id' => $this->branch->id,
            'date' => '2026-11-03',
            'cut_type' => 'corte_z',
            'cut_mode' => 'manual',
            'user_id' => $this->user->id,
            'opening_balance' => 500.00,
            'total_income' => 1200.00,
            'cash_income' => 1000.00,
            'card_income' => 200.00,
            'transfer_income' => 0.00,
            'total_expense' => 100.00,
            'withdraw_amount' => 0.00,
            'balance' => 1400.00,
            'declared_cash' => 1400.00,
            'difference_amount' => 0.00,
            'notes' => 'Corte del dia 3',
            'closed_at' => Carbon::parse('2026-11-03 21:00:00'),
        ]);

        $cut2 = DailyCut::create([
            'branch_id' => $this->branch->id,
            'date' => '2026-11-08',
            'cut_type' => 'corte_z',
            'cut_mode' => 'manual',
            'user_id' => $this->user->id,
            'opening_balance' => 1400.00,
            'total_income' => 800.00,
            'cash_income' => 800.00,
            'card_income' => 0.00,
            'transfer_income' => 0.00,
            'total_expense' => 50.00,
            'withdraw_amount' => 0.00,
            'balance' => 2150.00,
            'declared_cash' => 2150.00,
            'difference_amount' => 0.00,
            'notes' => 'Corte del dia 8',
            'closed_at' => Carbon::parse('2026-11-08 21:00:00'),
        ]);

        // Filtrar por dia específico 2026-11-03
        $response = $this->actingAs($this->user)->get(route('cash.dailyCut', [
            'filter_type' => 'dia',
            'date' => '2026-11-03',
            'branch_id' => $this->branch->id,
        ]));

        $response->assertStatus(200);
        $response->assertSee('Corte de Caja Diario - 03/11/2026');
        $response->assertSee('Corte del dia 3');
        $cutsHistory = $response->viewData('cutsHistory');
        $this->assertTrue($cutsHistory->contains('id', $cut1->id));
        $this->assertFalse($cutsHistory->contains('id', $cut2->id));
    }

    public function test_filter_by_date_range()
    {
        $cutA = DailyCut::create([
            'branch_id' => $this->branch->id,
            'date' => '2026-11-02',
            'cut_type' => 'corte_z',
            'cut_mode' => 'manual',
            'user_id' => $this->user->id,
            'opening_balance' => 200.00,
            'total_income' => 500.00,
            'cash_income' => 500.00,
            'card_income' => 0.00,
            'transfer_income' => 0.00,
            'total_expense' => 0.00,
            'withdraw_amount' => 0.00,
            'balance' => 700.00,
            'declared_cash' => 700.00,
            'difference_amount' => 0.00,
            'notes' => 'Corte A Nov 2',
            'closed_at' => Carbon::parse('2026-11-02 21:00:00'),
        ]);

        $cutB = DailyCut::create([
            'branch_id' => $this->branch->id,
            'date' => '2026-11-05',
            'cut_type' => 'corte_z',
            'cut_mode' => 'manual',
            'user_id' => $this->user->id,
            'opening_balance' => 700.00,
            'total_income' => 600.00,
            'cash_income' => 600.00,
            'card_income' => 0.00,
            'transfer_income' => 0.00,
            'total_expense' => 0.00,
            'withdraw_amount' => 0.00,
            'balance' => 1300.00,
            'declared_cash' => 1300.00,
            'difference_amount' => 0.00,
            'notes' => 'Corte B Nov 5',
            'closed_at' => Carbon::parse('2026-11-05 21:00:00'),
        ]);

        $cutC = DailyCut::create([
            'branch_id' => $this->branch->id,
            'date' => '2026-11-15',
            'cut_type' => 'corte_z',
            'cut_mode' => 'manual',
            'user_id' => $this->user->id,
            'opening_balance' => 1300.00,
            'total_income' => 400.00,
            'cash_income' => 400.00,
            'card_income' => 0.00,
            'transfer_income' => 0.00,
            'total_expense' => 0.00,
            'withdraw_amount' => 0.00,
            'balance' => 1700.00,
            'declared_cash' => 1700.00,
            'difference_amount' => 0.00,
            'notes' => 'Corte C Nov 15 fuera de rango',
            'closed_at' => Carbon::parse('2026-11-15 21:00:00'),
        ]);

        // Filtrar del 2026-11-01 al 2026-11-07
        $response = $this->actingAs($this->user)->get(route('cash.dailyCut', [
            'filter_type' => 'rango',
            'start_date' => '2026-11-01',
            'end_date' => '2026-11-07',
            'branch_id' => $this->branch->id,
        ]));

        $response->assertStatus(200);
        $response->assertSee('Corte de Caja - Rango del 01/11/2026 al 07/11/2026');
        $response->assertSee('Imprimir Reporte del Rango');

        $cutsHistory = $response->viewData('cutsHistory');
        $this->assertTrue($cutsHistory->contains('id', $cutA->id));
        $this->assertTrue($cutsHistory->contains('id', $cutB->id));
        $this->assertFalse($cutsHistory->contains('id', $cutC->id));
    }

    public function test_filter_by_all_records()
    {
        $cut1 = DailyCut::create([
            'branch_id' => $this->branch->id,
            'date' => '2026-10-15',
            'cut_type' => 'corte_z',
            'cut_mode' => 'manual',
            'user_id' => $this->user->id,
            'opening_balance' => 100.00,
            'total_income' => 200.00,
            'cash_income' => 200.00,
            'card_income' => 0.00,
            'transfer_income' => 0.00,
            'total_expense' => 0.00,
            'withdraw_amount' => 0.00,
            'balance' => 300.00,
            'declared_cash' => 300.00,
            'difference_amount' => 0.00,
            'closed_at' => Carbon::parse('2026-10-15 20:00:00'),
        ]);

        $cut2 = DailyCut::create([
            'branch_id' => $this->branch->id,
            'date' => '2026-11-20',
            'cut_type' => 'corte_z',
            'cut_mode' => 'manual',
            'user_id' => $this->user->id,
            'opening_balance' => 300.00,
            'total_income' => 500.00,
            'cash_income' => 500.00,
            'card_income' => 0.00,
            'transfer_income' => 0.00,
            'total_expense' => 0.00,
            'withdraw_amount' => 0.00,
            'balance' => 800.00,
            'declared_cash' => 800.00,
            'difference_amount' => 0.00,
            'closed_at' => Carbon::parse('2026-11-20 20:00:00'),
        ]);

        $response = $this->actingAs($this->user)->get(route('cash.dailyCut', [
            'filter_type' => 'todos',
            'branch_id' => $this->branch->id,
        ]));

        $response->assertStatus(200);
        $response->assertSee('Corte de Caja - Historial Completo (Todos los Registros)');
        $response->assertSee('Imprimir Todos los Cortes');

        $cutsHistory = $response->viewData('cutsHistory');
        $this->assertTrue($cutsHistory->contains('id', $cut1->id));
        $this->assertTrue($cutsHistory->contains('id', $cut2->id));
    }

    public function test_print_consolidated_range_report()
    {
        DailyCut::create([
            'branch_id' => $this->branch->id,
            'date' => '2026-11-04',
            'cut_type' => 'corte_z',
            'cut_mode' => 'manual',
            'user_id' => $this->user->id,
            'opening_balance' => 300.00,
            'total_income' => 1500.00,
            'cash_income' => 1200.00,
            'card_income' => 300.00,
            'transfer_income' => 0.00,
            'total_expense' => 200.00,
            'withdraw_amount' => 0.00,
            'balance' => 1300.00,
            'declared_cash' => 1300.00,
            'difference_amount' => 0.00,
            'closed_at' => Carbon::parse('2026-11-04 21:00:00'),
        ]);

        $response = $this->actingAs($this->user)->get(route('cash.printCut', [
            'filter_type' => 'rango',
            'start_date' => '2026-11-01',
            'end_date' => '2026-11-07',
            'branch_id' => $this->branch->id,
        ]));

        $response->assertStatus(200);
        $this->assertTrue($response->viewData('isMultiCutReport'));
        $response->assertSee('REPORTE CONSOLIDADO DE CORTES DE CAJA');
        $response->assertSee('01/11/2026');
        $response->assertSee('07/11/2026');
    }

    public function test_print_all_records_report()
    {
        DailyCut::create([
            'branch_id' => $this->branch->id,
            'date' => '2026-11-01',
            'cut_type' => 'corte_z',
            'cut_mode' => 'manual',
            'user_id' => $this->user->id,
            'opening_balance' => 100.00,
            'total_income' => 500.00,
            'cash_income' => 500.00,
            'card_income' => 0.00,
            'transfer_income' => 0.00,
            'total_expense' => 50.00,
            'withdraw_amount' => 0.00,
            'balance' => 550.00,
            'declared_cash' => 550.00,
            'difference_amount' => 0.00,
            'closed_at' => Carbon::parse('2026-11-01 21:00:00'),
        ]);

        $response = $this->actingAs($this->user)->get(route('cash.printCut', [
            'filter_type' => 'todos',
            'branch_id' => $this->branch->id,
        ]));

        $response->assertStatus(200);
        $this->assertTrue($response->viewData('isMultiCutReport'));
        $response->assertSee('REPORTE HISTÓRICO GENERAL DE CORTES DE CAJA');
    }

    public function test_print_individual_cut_by_cut_id()
    {
        $cut = DailyCut::create([
            'branch_id' => $this->branch->id,
            'date' => '2026-11-06',
            'cut_type' => 'corte_z',
            'cut_mode' => 'manual',
            'user_id' => $this->user->id,
            'opening_balance' => 200.00,
            'total_income' => 700.00,
            'cash_income' => 700.00,
            'card_income' => 0.00,
            'transfer_income' => 0.00,
            'total_expense' => 100.00,
            'withdraw_amount' => 0.00,
            'balance' => 800.00,
            'declared_cash' => 800.00,
            'difference_amount' => 0.00,
            'closed_at' => Carbon::parse('2026-11-06 21:00:00'),
        ]);

        $response = $this->actingAs($this->user)->get(route('cash.printCut', [
            'cut_id' => $cut->id,
            'branch_id' => $this->branch->id,
        ]));

        $response->assertStatus(200);
        $this->assertEquals($cut->id, $response->viewData('dailyCut')->id);
        $response->assertSee('REPORTE DE CORTE FINAL DIARIO (CORTE Z)');
        $response->assertSee('#' . $cut->id);
    }
}

