<?php

namespace Tests\Feature;

use App\Models\Branch;
use App\Models\Customer;
use App\Models\FinBrand;
use App\Models\FinDevice;
use App\Models\FinFinanciera;
use App\Models\FinSale;
use App\Models\FinSalePayment;
use App\Models\Loan;
use App\Models\LoanType;
use App\Models\Order;
use App\Models\PosTransaction;
use App\Models\User;
use Carbon\Carbon;
use Illuminate\Foundation\Testing\DatabaseTransactions;
use Spatie\Permission\Models\Role;
use Tests\TestCase;

class TicketPrintAndDirectPrintTest extends TestCase
{
    use DatabaseTransactions;

    protected User $userMorelos;
    protected User $userAllende;
    protected User $userCercado;
    protected Branch $branchMorelos;
    protected Branch $branchAllende;
    protected Branch $branchCercado;
    protected Customer $customerRosa;
    protected FinFinanciera $payjoy;

    protected function setUp(): void
    {
        parent::setUp();

        $role = Role::firstOrCreate(['name' => 'SuperAdmin', 'guard_name' => 'web']);

        $this->branchMorelos = Branch::firstOrCreate(
            ['name' => 'Montemorelos'],
            ['address' => 'Montemorelos, NL', 'phone' => '8260000001', 'is_active' => true]
        );
        $this->branchAllende = Branch::firstOrCreate(
            ['name' => 'Allende'],
            ['address' => 'Allende, NL', 'phone' => '8260000002', 'is_active' => true]
        );
        $this->branchCercado = Branch::firstOrCreate(
            ['name' => 'El Cercado'],
            ['address' => 'El Cercado, NL', 'phone' => '8260000003', 'is_active' => true]
        );

        $this->userMorelos = User::create([
            'name' => 'Jimena Morelos',
            'username' => 'jimena_' . uniqid(),
            'email' => 'jimena_' . uniqid() . '@zyacelulares.com',
            'password' => bcrypt('password123'),
            'branch_id' => $this->branchMorelos->id,
        ]);
        $this->userMorelos->assignRole($role);

        $this->userAllende = User::create([
            'name' => 'Verónica Acosta',
            'username' => 'vero_' . uniqid(),
            'email' => 'vero_' . uniqid() . '@zyacelulares.com',
            'password' => bcrypt('password123'),
            'branch_id' => $this->branchAllende->id,
        ]);
        $this->userAllende->assignRole($role);

        $this->userCercado = User::create([
            'name' => 'Monse Cercado',
            'username' => 'monse_' . uniqid(),
            'email' => 'monse_' . uniqid() . '@zyacelulares.com',
            'password' => bcrypt('password123'),
            'branch_id' => $this->branchCercado->id,
        ]);
        $this->userCercado->assignRole($role);

        $this->customerRosa = Customer::create([
            'tit_name' => 'Rosa Isela Torres',
            'tit_email' => 'rosa_' . uniqid() . '@example.com',
            'tit_phone' => '8261112233',
            'branch_id' => $this->branchMorelos->id,
        ]);

        $this->payjoy = FinFinanciera::firstOrCreate(
            ['name' => 'PayJoy'],
            ['slug' => 'payjoy', 'is_active' => true]
        );
    }

    /**
     * P8: Verificar ticket tipo MOV-20261109:
     * - Sucursal Morelos
     * - Cliente: Rosa Isela Torres en negritas
     * - Financiera: PayJoy en negritas
     * - Total e importes en negritas
     * - Ancho 70mm para evitar cortes a la derecha en impresoras térmicas
     */
    public function test_ticket_mov_20261109_has_bold_fields_and_no_right_crop(): void
    {
        $this->actingAs($this->userMorelos);

        $brand = FinBrand::firstOrCreate(['name' => 'Motorola']);
        $device = FinDevice::create([
            'brand_id' => $brand->id,
            'model' => 'Moto G54',
            'imei' => '3589' . rand(10000000000, 99999999999),
            'status' => 'vendido',
            'branch_id' => $this->branchMorelos->id,
        ]);

        // Crear venta financiada previa
        $finSale = FinSale::create([
            'branch_id' => $this->branchMorelos->id,
            'device_id' => $device->id,
            'financiera_id' => $this->payjoy->id,
            'user_id' => $this->userMorelos->id,
            'sale_code' => 'FIN-' . uniqid(),
            'customer_name' => $this->customerRosa->tit_name,
            'customer_phone' => $this->customerRosa->tit_phone,
            'tag_contrato' => 'TAG-PJ-9988',
            'price' => 5000.00,
            'down_payment' => 0.00,
            'total_financed' => 5000.00,
            'abonos_count' => 1,
            'total_abonos_paid' => 523.00,
            'sale_date' => Carbon::parse('2026-11-09'),
            'status' => 'activa',
        ]);

        // Crear transacción POS con los datos exactos del caso reportado
        $transaction = PosTransaction::create([
            'transaction_code' => 'MOV-20261109',
            'branch_id' => $this->branchMorelos->id,
            'user_id' => $this->userMorelos->id,
            'customer_id' => $this->customerRosa->id,
            'customer_name' => $this->customerRosa->tit_name,
            'customer_phone' => $this->customerRosa->tit_phone,
            'subtotal' => 523.00,
            'commission_total' => 0.00,
            'total_amount' => 523.00,
            'paid_amount' => 523.00,
            'change_amount' => 0.00,
            'payment_method' => 'efectivo',
            'cash_amount' => 523.00,
            'card_amount' => 0.00,
            'transfer_amount' => 0.00,
            'items_payload' => [
                [
                    'section' => 'financiera_abono',
                    'sale_id' => $finSale->id,
                    'title' => 'Abono Financiera (PayJoy) TAG: TAG-PJ-9988',
                    'subtitle' => 'Equipo Teléfono',
                    'financiera_name' => 'PayJoy',
                    'quantity' => 1,
                    'amount' => 523.00,
                    'commission' => 0.00,
                ],
            ],
            'drawer_opened' => true,
            'status' => 'completado',
            'created_at' => Carbon::parse('2026-11-09 19:37:00'),
        ]);

        FinSalePayment::create([
            'fin_sale_id' => $finSale->id,
            'financiera_id' => $this->payjoy->id,
            'branch_id' => $this->branchMorelos->id,
            'user_id' => $this->userMorelos->id,
            'pos_transaction_id' => $transaction->id,
            'receipt_code' => 'ABFIN-20261109-1234',
            'tag_contrato' => 'TAG-PJ-9988',
            'customer_name' => $this->customerRosa->tit_name,
            'installment_number' => 1,
            'amount' => 523.00,
            'commission_amount' => 0.00,
            'total_amount' => 523.00,
            'payment_method' => 'efectivo',
            'payment_date' => Carbon::parse('2026-11-09 19:37:00'),
        ]);

        $response = $this->get(route('central-pos.receipt', $transaction->id));
        $response->assertStatus(200);

        $content = $response->getContent();

        // 1. Verificación de Ancho térmico de seguridad (70mm) para no cortarse a la derecha
        $this->assertStringContainsString('width: 70mm', $content);
        $this->assertStringContainsString('max-width: 70mm', $content);
        $this->assertStringNotContainsString('width: 76mm', $content);

        // 2. Cliente en negritas
        $this->assertStringContainsString('ROSA ISELA TORRES', $content);
        $this->assertMatchesRegularExpression('/Cliente:.*<span class="bold"[^>]*>ROSA ISELA TORRES<\/span>/s', $content);

        // 3. Financiera en negritas
        $this->assertStringContainsString('Financiera:', $content);
        $this->assertMatchesRegularExpression('/Financiera:.*<span class="bold"[^>]*>PAYJOY<\/span>/s', $content);

        // 4. Importe en negritas
        $this->assertMatchesRegularExpression('/Importe:.*<span class="bold[^"]*"[^>]*>\$523\.00<\/span>/s', $content);

        // 5. Total en negritas
        $this->assertMatchesRegularExpression('/TOTAL DE LA NOTA:.*<span class="bold"[^>]*>\$523\.00 MXN<\/span>/s', $content);

        // 6. Botones de retorno al POS y Menú
        $this->assertStringContainsString(route('central-pos.index'), $content);
        $this->assertStringContainsString(route('dashboard'), $content);
    }

    /**
     * P9: Verificar que la pantalla de POS Central NO abre una nueva pestaña del navegador,
     * sino que imprime de manera silenciosa/directa vía iframe y ofrece reimpresión.
     */
    public function test_pos_central_uses_direct_print_without_opening_new_tab(): void
    {
        $this->actingAs($this->userAllende);

        $response = $this->get(route('central-pos.index'));
        $response->assertStatus(200);

        $content = $response->getContent();

        // Verificar que no se invoca window.open(res.receipt_url, '_blank') en el flujo de cobro
        $this->assertStringNotContainsString("window.open(res.receipt_url, '_blank')", $content);

        // Verificar función de impresión directa por iframe
        $this->assertStringContainsString('printTicketDirectly', $content);
        $this->assertStringContainsString('posDirectPrintFrame', $content);

        // Verificar banner de venta exitosa con botón para reimprimir
        $this->assertStringContainsString('posSaleSuccessAlert', $content);
        $this->assertStringContainsString('posBtnReimprimir', $content);
        $this->assertStringContainsString('Reimprimir Ticket', $content);
    }

    /**
     * P9: Verificar que las vistas de documentos/facturas/contratos/pagarés cuentan con
     * botones visibles de retorno al punto de venta y al menú principal.
     */
    public function test_document_and_contract_views_have_return_navigation(): void
    {
        $this->actingAs($this->userCercado);

        // 1. Pagaré de Préstamo
        $loanType = LoanType::firstOrCreate(
            ['slug' => 'personal'],
            ['name' => 'Personal', 'is_active' => true]
        );
        $loan = Loan::create([
            'loan_code' => 'PREST-CER-001',
            'branch_id' => $this->branchCercado->id,
            'customer_id' => $this->customerRosa->id,
            'loan_type_id' => $loanType->id,
            'user_id' => $this->userCercado->id,
            'principal_amount' => 2000,
            'interest_rate' => 0,
            'total_amount' => 2000,
            'term_periods' => 4,
            'installment_amount' => 500,
            'payment_frequency' => 'quincenal',
            'start_date' => now()->toDateString(),
            'due_date' => now()->addMonths(2)->toDateString(),
            'status' => 'activo',
        ]);

        $pagareRes = $this->get(route('loans.pagare.print', $loan->id));
        $pagareRes->assertStatus(200);
        $pagareContent = $pagareRes->getContent();
        $this->assertStringContainsString(route('central-pos.index'), $pagareContent);
        $this->assertStringContainsString(route('dashboard'), $pagareContent);
        $this->assertStringContainsString('Volver al Préstamo', $pagareContent);

        // 2. Orden / Factura
        $order = Order::create([
            'customer_id' => $this->customerRosa->id,
            'branch_id' => $this->branchCercado->id,
            'user_id' => $this->userCercado->id,
            'order_date' => now()->toDateString(),
            'order_status' => 'complete',
            'total_products' => 1,
            'sub_total' => 100,
            'vat' => 0,
            'total' => 100,
            'invoice_no' => 'INV-TEST-01',
            'payment_status' => 'HandCash',
            'pay' => 100,
            'due' => 0,
        ]);

        $invoiceRes = $this->get(route('order.invoiceDownload', $order->id));
        $invoiceRes->assertStatus(200);
        $invoiceContent = $invoiceRes->getContent();
        $this->assertStringContainsString(route('central-pos.index'), $invoiceContent);
        $this->assertStringContainsString(route('dashboard'), $invoiceContent);

        // 3. Contrato
        $contractRes = $this->get(route('order.contract', $order->id));
        $contractRes->assertStatus(200);
        $contractContent = $contractRes->getContent();
        $this->assertStringContainsString(route('central-pos.index'), $contractContent);
        $this->assertStringContainsString(route('dashboard'), $contractContent);
    }
}
