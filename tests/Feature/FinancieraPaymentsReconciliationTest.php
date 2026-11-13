<?php

namespace Tests\Feature;

use App\Models\Branch;
use App\Models\CashFlow;
use App\Models\FinBrand;
use App\Models\FinDevice;
use App\Models\FinFinanciera;
use App\Models\FinSale;
use App\Models\FinSaleNote;
use App\Models\FinSalePayment;
use App\Models\PosTransaction;
use App\Models\User;
use Illuminate\Foundation\Testing\DatabaseTransactions;
use Spatie\Permission\Models\Role;
use Tests\TestCase;

class FinancieraPaymentsReconciliationTest extends TestCase
{
    use DatabaseTransactions;

    protected Branch $branchAllende;
    protected Branch $branchMontemorelos;
    protected User $superAdmin;
    protected User $sellerAllende;
    protected FinFinanciera $financiera;
    protected FinBrand $brand;

    protected function setUp(): void
    {
        parent::setUp();

        $superRole = Role::firstOrCreate(['name' => 'SuperAdmin', 'guard_name' => 'web']);

        $this->branchAllende = Branch::firstOrCreate(
            ['name' => 'Allende'],
            ['address' => 'Centro', 'phone' => '8261000001', 'is_active' => true, 'auto_cut_enabled' => false]
        );

        $this->branchMontemorelos = Branch::firstOrCreate(
            ['name' => 'Montemorelos'],
            ['address' => 'Centro', 'phone' => '8261000002', 'is_active' => true, 'auto_cut_enabled' => false]
        );

        $this->superAdmin = User::create([
            'name' => 'Esmeralda Admin',
            'username' => 'esme_rec_' . uniqid(),
            'email' => 'esme_rec_' . uniqid() . '@zyacelulares.com',
            'password' => bcrypt('secret123'),
            'branch_id' => $this->branchAllende->id,
        ]);
        $this->superAdmin->assignRole($superRole);

        $this->sellerAllende = User::create([
            'name' => 'Verónica Allende',
            'username' => 'vero_rec_' . uniqid(),
            'email' => 'vero_rec_' . uniqid() . '@zyacelulares.com',
            'password' => bcrypt('secret123'),
            'branch_id' => $this->branchAllende->id,
        ]);
        $this->sellerAllende->givePermissionTo([
            'cobros.read',
            'cobros.create',
            'cobros.financieras',
            'financieras.ventas.abonar',
            'financieras.trazabilidad.actions',
        ]);

        $this->financiera = FinFinanciera::firstOrCreate(['name' => 'PayJoy'], ['is_active' => true]);
        $this->brand = FinBrand::firstOrCreate(['name' => 'Samsung'], ['is_active' => true]);
    }

    protected function createCreditSale(Branch $branch, string $saleType = 'credito'): FinSale
    {
        $device = FinDevice::create([
            'brand_id' => $this->brand->id,
            'model' => 'Galaxy A55',
            'imei' => '3599' . rand(10000000000, 99999999999),
            'status' => 'vendido',
            'branch_id' => $branch->id,
        ]);

        return FinSale::create([
            'sale_code' => 'FIN-' . date('Ymd') . '-' . strtoupper(substr(uniqid(), -5)),
            'sale_type' => $saleType,
            'device_id' => $device->id,
            'financiera_id' => $this->financiera->id,
            'branch_id' => $branch->id,
            'price' => 5000.00,
            'down_payment' => 1000.00,
            'enganche_descuento' => 1000.00,
            'credit_amount' => 4000.00,
            'abono_semanal' => 250.00,
            'total_abonos_paid' => 0.00,
            'abonos_count' => 0,
            'tag_contrato' => 'TAG-' . strtoupper(substr(uniqid(), -6)),
            'sale_date' => now(),
            'status' => 'activa',
            'customer_name' => 'Cliente Financiera Prueba',
            'customer_phone' => '8261239999',
        ]);
    }

    public function test_daily_cut_shows_all_operations_beyond_15_on_day_filter(): void
    {
        $this->actingAs($this->superAdmin);

        for ($i = 1; $i <= 18; $i++) {
            CashFlow::create([
                'branch_id' => $this->branchAllende->id,
                'user_id' => $this->superAdmin->id,
                'type' => 'income',
                'amount' => 100.00,
                'description' => "Operacion Corte {$i}",
                'reference' => "REF-DAY-{$i}",
                'module' => 'Cobro Centralizado',
                'payment_method' => 'efectivo',
                'cash_amount' => 100.00,
                'card_amount' => 0.00,
                'transfer_amount' => 0.00,
            ]);
        }

        $resp = $this->get(route('cash.dailyCut', [
            'branch_id' => $this->branchAllende->id,
            'filter_type' => 'dia',
            'date' => now()->toDateString(),
        ]));

        $resp->assertStatus(200);
        $ops = $resp->viewData('chronologicalOperations');
        $this->assertGreaterThanOrEqual(18, count($ops));
        $resp->assertSee('Operacion Corte 1');
        $resp->assertSee('Operacion Corte 18');
    }

    public function test_lookup_financiera_and_pos_checkout_exclude_contado_sales(): void
    {
        $this->actingAs($this->superAdmin);

        $contadoSale = $this->createCreditSale($this->branchAllende, 'contado');
        $creditSale = $this->createCreditSale($this->branchAllende, 'credito');

        $lookupResp = $this->getJson(route('central-pos.lookup-financiera', [
            'q' => $contadoSale->tag_contrato,
        ]));
        $lookupResp->assertStatus(200);
        $this->assertCount(0, $lookupResp->json('results'));

        $lookupCreditResp = $this->getJson(route('central-pos.lookup-financiera', [
            'q' => $creditSale->tag_contrato,
        ]));
        $lookupCreditResp->assertStatus(200);
        $this->assertCount(1, $lookupCreditResp->json('results'));
        $this->assertEquals($this->branchAllende->id, $lookupCreditResp->json('results.0.branch_id'));

        $posContado = $this->postJson(route('central-pos.checkout'), [
            'branch_id' => $this->branchAllende->id,
            'customer_name' => $contadoSale->customer_name,
            'payment_method' => 'efectivo',
            'cash_amount' => 250,
            'items' => [[
                'section' => 'financiera_abono',
                'sale_id' => $contadoSale->id,
                'title' => 'Intento abono contado',
                'quantity' => 1,
                'amount' => 250,
                'commission' => 0,
            ]],
        ]);
        $posContado->assertStatus(422);
    }

    public function test_cross_branch_financiera_abono_records_in_physical_receiving_branch(): void
    {
        $sale = $this->createCreditSale($this->branchMontemorelos, 'credito');

        // Cajera física de Allende cobra un contrato creado en Montemorelos
        $this->actingAs($this->sellerAllende);
        $resp = $this->post(route('financieras.ventas.abonos.store', $sale->id), [
            'branch_id' => $this->branchMontemorelos->id, // Intentar forzar otra sucursal sin permiso debe ignorarse
            'amount' => 250.00,
            'commission_amount' => 15.00,
            'payment_method' => 'efectivo',
        ]);
        $resp->assertRedirect();

        $payment = FinSalePayment::where('fin_sale_id', $sale->id)->firstOrFail();
        $this->assertEquals($this->branchAllende->id, (int) $payment->branch_id);

        $cf = CashFlow::where('pos_transaction_id', $payment->pos_transaction_id)->firstOrFail();
        $this->assertEquals($this->branchAllende->id, (int) $cf->branch_id);

        // Usuario multisucursal (SuperAdmin) puede elegir expresamente en qué caja física entró el dinero
        $this->actingAs($this->superAdmin);
        $adminResp = $this->post(route('financieras.ventas.abonos.store', $sale->id), [
            'branch_id' => $this->branchMontemorelos->id,
            'amount' => 200.00,
            'commission_amount' => 0.00,
            'payment_method' => 'transferencia',
        ]);
        $adminResp->assertRedirect();

        $adminPayment = FinSalePayment::where('fin_sale_id', $sale->id)
            ->orderByDesc('id')
            ->firstOrFail();
        $this->assertEquals($this->branchMontemorelos->id, (int) $adminPayment->branch_id);
    }

    public function test_pos_rejects_partial_payment_for_financiera_abono_and_cancel_syncs_fin_sale(): void
    {
        $this->actingAs($this->superAdmin);

        $sale = $this->createCreditSale($this->branchAllende, 'credito');

        // 1. Bloquear pago parcial en abono de financiera
        $partialResp = $this->postJson(route('central-pos.checkout'), [
            'branch_id' => $this->branchAllende->id,
            'customer_name' => $sale->customer_name,
            'payment_method' => 'efectivo',
            'paid_amount' => 100.00,
            'cash_amount' => 100.00,
            'items' => [[
                'section' => 'financiera_abono',
                'sale_id' => $sale->id,
                'title' => "Abono PayJoy TAG: {$sale->tag_contrato}",
                'quantity' => 1,
                'amount' => 250.00,
                'commission' => 0.00,
            ]],
        ]);
        $partialResp->assertStatus(422);

        // 2. Cobro completo mixto + consulta de conceptos y cancelación sincronizada
        $okResp = $this->postJson(route('central-pos.checkout'), [
            'branch_id' => $this->branchAllende->id,
            'customer_name' => $sale->customer_name,
            'payment_method' => 'mixto',
            'cash_amount' => 150.00,
            'card_amount' => 60.00,
            'transfer_amount' => 40.00,
            'items' => [[
                'section' => 'financiera_abono',
                'sale_id' => $sale->id,
                'title' => "Abono PayJoy TAG: {$sale->tag_contrato}",
                'quantity' => 1,
                'amount' => 250.00,
                'commission' => 0.00,
            ]],
        ]);
        $okResp->assertStatus(200)->assertJson(['success' => true]);

        $txId = $okResp->json('transaction_id');
        $sale->refresh();
        $this->assertEquals(250.00, (float) $sale->total_abonos_paid);
        $this->assertEquals(1, (int) $sale->abonos_count);

        // Verificar que recentTransactions devuelve conceptos usando title y filtra por TAG
        $histResp = $this->getJson(route('central-pos.recent-transactions', [
            'branch_id' => $this->branchAllende->id,
            'search' => $sale->tag_contrato,
        ]));
        $histResp->assertStatus(200);
        $this->assertStringContainsString($sale->tag_contrato, (string) $histResp->json('transactions.0.concepts'));

        // Mientras el abono esté activo, FinSaleController::cancel no debe permitir cancelar el contrato
        $cancelSaleBlocked = $this->post(route('financieras.ventas.cancel', $sale->id), [
            'cancellation_reason' => 'Prueba bloqueo con abono activo',
        ]);
        $cancelSaleBlocked->assertRedirect(route('financieras.ventas.show', $sale->id));
        $cancelSaleBlocked->assertSessionHas('error');
        $this->assertEquals('activa', $sale->fresh()->status);

        // Cancelar el PosTransaction sí revierte el abono acumulado en FinSale y deja nota
        $cancelTxResp = $this->post(route('dashboard.transactions.cancel', $txId), [
            'reason' => 'Error de captura',
        ]);
        $cancelTxResp->assertRedirect();

        $sale->refresh();
        $this->assertEquals(0.00, (float) $sale->total_abonos_paid);
        $this->assertEquals(0, (int) $sale->abonos_count);
        $this->assertNull($sale->last_abono_at);
        $this->assertTrue(FinSaleNote::where('sale_id', $sale->id)->where('note', 'like', '%Abono cancelado%')->exists());
        $this->assertEquals('cancelado', PosTransaction::findOrFail($txId)->status);
    }
}

