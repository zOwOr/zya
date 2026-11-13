<?php

namespace Tests\Feature;

use App\Models\Branch;
use App\Models\CashFlow;
use App\Models\Customer;
use App\Models\ExternalCustomer;
use App\Models\FinFinanciera;
use App\Models\FinSalePayment;
use App\Models\PosTransaction;
use App\Models\User;
use Illuminate\Foundation\Testing\DatabaseTransactions;
use Spatie\Permission\Models\Role;
use Tests\TestCase;

class ExternalCustomerPosFlowTest extends TestCase
{
    use DatabaseTransactions;

    protected Branch $branchAllende;
    protected User $superAdmin;
    protected User $posCashierWithExt;
    protected User $cashierWithoutExt;
    protected FinFinanciera $financiera;

    protected function setUp(): void
    {
        parent::setUp();

        $superRole = Role::firstOrCreate(['name' => 'SuperAdmin', 'guard_name' => 'web']);

        $this->branchAllende = Branch::firstOrCreate(
            ['name' => 'Allende'],
            ['address' => 'Centro', 'phone' => '8261000001', 'is_active' => true, 'auto_cut_enabled' => false]
        );

        $this->superAdmin = User::create([
            'name' => 'Esmeralda Supervisor',
            'username' => 'esme_ext_' . uniqid(),
            'email' => 'esme_ext_' . uniqid() . '@zyacelulares.com',
            'password' => bcrypt('secret123'),
            'branch_id' => $this->branchAllende->id,
        ]);
        $this->superAdmin->assignRole($superRole);

        $this->posCashierWithExt = User::create([
            'name' => 'Verónica Cajera',
            'username' => 'vero_ext_' . uniqid(),
            'email' => 'vero_ext_' . uniqid() . '@zyacelulares.com',
            'password' => bcrypt('secret123'),
            'branch_id' => $this->branchAllende->id,
        ]);
        $this->posCashierWithExt->givePermissionTo([
            'cobros.menu',
            'cobros.read',
            'cobros.create',
            'cobros.financieras',
            'cobros.productos',
            'cobros.external_customers',
            'external_customer.read',
            'external_customer.create',
            'external_customer.convert',
            'cash.read',
            'cash.pre_cut',
            'cash.final_cut',
        ]);

        $this->cashierWithoutExt = User::create([
            'name' => 'Usuario Sin Permiso Externo',
            'username' => 'noperm_ext_' . uniqid(),
            'email' => 'noperm_ext_' . uniqid() . '@zyacelulares.com',
            'password' => bcrypt('secret123'),
            'branch_id' => $this->branchAllende->id,
        ]);
        $this->cashierWithoutExt->givePermissionTo([
            'cobros.read',
            'cobros.create',
            'cobros.financieras',
            'cobros.productos',
        ]);

        $this->financiera = FinFinanciera::firstOrCreate(['name' => 'PayJoy'], ['is_active' => true]);
    }

    public function test_pos_rejects_external_customer_when_cart_has_no_financiera_concept(): void
    {
        $external = ExternalCustomer::create([
            'tit_name' => 'Externo Solo Producto',
            'tit_email' => 'solo@externo.com',
            'tit_phone' => '8261001122',
            'tit_facebook' => 'fb.com/solo',
            'tit_status' => 'aprobado',
            'tit_city' => 'Allende',
            'tit_address' => 'Calle Morelos 10',
            'branch_id' => $this->branchAllende->id,
            'created_by' => $this->superAdmin->id,
        ]);

        $res = $this->actingAs($this->posCashierWithExt)->postJson(route('central-pos.checkout'), [
            'branch_id' => $this->branchAllende->id,
            'external_customer_id' => $external->id,
            'payment_method' => 'efectivo',
            'cash_amount' => 200,
            'items' => [[
                'section' => 'producto_dinamico',
                'name' => 'Funda Uso Rudo',
                'title' => 'Funda Uso Rudo',
                'quantity' => 1,
                'amount' => 200,
                'commission' => 0,
            ]],
        ]);

        $res->assertStatus(422)
            ->assertJson(['success' => false]);
    }

    public function test_external_customer_financiera_checkout_creates_traceable_cash_flow_and_promotes_cleanly(): void
    {
        // 1. Alta rápida de Cliente Externo desde POS
        $createRes = $this->actingAs($this->posCashierWithExt)->postJson(route('central-pos.external-customers.store'), [
            'tit_name' => 'Carlos Walkin PayJoy',
            'tit_email' => 'carlos.walkin@example.com',
            'tit_phone' => '8268889900',
            'tit_facebook' => 'fb.com/carloswalkin',
            'tit_status' => 'aprobado',
            'tit_city' => 'Montemorelos',
            'tit_address' => 'Av. Capitán Alonso de León #450',
            'branch_id' => $this->branchAllende->id,
        ]);

        $createRes->assertStatus(201)->assertJson(['success' => true]);
        $extId = $createRes->json('external_customer.id');

        // 2. Cobro de financiera por equipo externo usando cliente externo
        $checkoutRes = $this->actingAs($this->posCashierWithExt)->postJson(route('central-pos.checkout'), [
            'branch_id' => $this->branchAllende->id,
            'external_customer_id' => $extId,
            'payment_method' => 'efectivo',
            'cash_amount' => 475,
            'items' => [[
                'section' => 'financiera_externa',
                'financiera_id' => $this->financiera->id,
                'tag_contrato' => 'EXT-PJ-4421',
                'imei' => '351234567890123',
                'title' => 'Abono Financiera Externa (PayJoy) Ref: EXT-PJ-4421',
                'quantity' => 1,
                'amount' => 450,
                'commission' => 25,
            ]],
        ]);

        $checkoutRes->assertOk()->assertJson(['success' => true]);
        $txId = $checkoutRes->json('transaction_id');

        $tx = PosTransaction::findOrFail($txId);
        $this->assertNull($tx->customer_id);
        $this->assertSame($extId, (int) $tx->external_customer_id);
        $this->assertSame('Carlos Walkin PayJoy', $tx->customer_name);

        $flow = CashFlow::where('pos_transaction_id', $tx->id)->firstOrFail();
        $this->assertNull($flow->customer_id);
        $this->assertSame($extId, (int) $flow->external_customer_id);

        $finPayment = FinSalePayment::where('pos_transaction_id', $tx->id)->firstOrFail();
        $this->assertNull($finPayment->fin_sale_id);
        $this->assertTrue((bool) $finPayment->is_external_company);
        $this->assertSame($extId, (int) $finPayment->external_customer_id);

        // 3. Promover Cliente Externo a Cliente Interno
        $convertRes = $this->actingAs($this->posCashierWithExt)
            ->post(route('external-customers.convert', $extId));
        $convertRes->assertRedirect();

        $external = ExternalCustomer::findOrFail($extId);
        $this->assertSame(ExternalCustomer::STATUS_CONVERTED, $external->status);
        $this->assertNotNull($external->converted_customer_id);

        $internal = Customer::findOrFail($external->converted_customer_id);
        $this->assertSame('Carlos Walkin PayJoy', $internal->tit_name);
        $this->assertSame('8268889900', $internal->tit_phone);

        // El historial previo permanece íntegro vinculado al cliente externo
        $this->assertSame($extId, (int) $flow->fresh()->external_customer_id);
        $this->assertSame($extId, (int) $tx->fresh()->external_customer_id);

        // Re-convertir no crea duplicados
        $secondConvertRes = $this->actingAs($this->posCashierWithExt)
            ->post(route('external-customers.convert', $extId));
        $secondConvertRes->assertRedirect();
        $this->assertSame(1, Customer::where('tit_phone', '8268889900')->count());

        // Verificar visibilidad en ticket, caja, corte Z e impresión
        $this->actingAs($this->posCashierWithExt)
            ->get(route('central-pos.receipt', $txId))
            ->assertOk()
            ->assertSee('Cliente Externo')
            ->assertSee('CARLOS WALKIN PAYJOY');

        $this->actingAs($this->posCashierWithExt)
            ->get(route('cash.index', ['branch_id' => $this->branchAllende->id]))
            ->assertOk()
            ->assertSee('Carlos Walkin PayJoy')
            ->assertSee('Cliente Externo');

        $this->actingAs($this->posCashierWithExt)
            ->get(route('cash.dailyCut', ['branch_id' => $this->branchAllende->id]))
            ->assertOk()
            ->assertSee('Carlos Walkin PayJoy');
    }

    public function test_unauthorized_users_cannot_access_external_customer_catalog_conversion_or_pos_feature(): void
    {
        $external = ExternalCustomer::create([
            'tit_name' => 'Externo Protegido',
            'tit_email' => 'prot@externo.com',
            'tit_phone' => '8265556677',
            'tit_facebook' => 'fb.com/prot',
            'tit_status' => 'aprobado',
            'tit_city' => 'El Cercado',
            'tit_address' => 'Calle Zaragoza 55',
        ]);

        $this->actingAs($this->cashierWithoutExt)
            ->get(route('external-customers.index'))
            ->assertStatus(403);

        $this->actingAs($this->cashierWithoutExt)
            ->post(route('external-customers.convert', $external))
            ->assertStatus(403);

        $this->actingAs($this->cashierWithoutExt)
            ->postJson(route('central-pos.external-customers.store'), [
                'tit_name' => 'Bloqueado',
                'tit_email' => 'b@example.com',
                'tit_phone' => '8264443322',
                'tit_facebook' => 'fb',
                'tit_status' => 'aprobado',
                'tit_city' => 'Allende',
                'tit_address' => 'Centro',
            ])
            ->assertStatus(403);

        $this->actingAs($this->cashierWithoutExt)
            ->postJson(route('central-pos.checkout'), [
                'branch_id' => $this->branchAllende->id,
                'external_customer_id' => $external->id,
                'payment_method' => 'efectivo',
                'cash_amount' => 300,
                'items' => [[
                    'section' => 'financiera_externa',
                    'financiera_id' => $this->financiera->id,
                    'tag_contrato' => 'REF-1',
                    'title' => 'Abono PayJoy',
                    'quantity' => 1,
                    'amount' => 300,
                    'commission' => 0,
                ]],
            ])
            ->assertStatus(403);
    }
}
