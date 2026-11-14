<?php

namespace Tests\Unit;

use App\Models\Branch;
use App\Models\Order;
use App\Models\PosTransaction;
use App\Models\User;
use Carbon\Carbon;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Blade;
use Illuminate\Support\Facades\View;
use Tests\TestCase;

class PosAutoPendingPaymentFlowTest extends TestCase
{
    public function test_pos_view_removes_manual_partial_toggle_and_implements_automatic_pending_ux(): void
    {
        $source = file_get_contents(resource_path('views/central-pos/index.blade.php'));
        $compiled = Blade::compileString($source);
        $this->assertNotEmpty($compiled);

        // Toggle manual y campos duplicados obsoletos eliminados
        $this->assertStringNotContainsString('id="posPartialPayCheck"', $source);
        $this->assertStringNotContainsString('id="posPaidTodayInput"', $source);
        $this->assertStringNotContainsString('id="posDueBalanceText"', $source);
        $this->assertStringNotContainsString('id="posCashReceivedInput"', $source);

        // Controles unificados para los 4 métodos + estado automático
        $this->assertStringContainsString('id="posCashAmountInput"', $source);
        $this->assertStringContainsString('id="posCashChangeContainer"', $source);
        $this->assertStringContainsString('id="posCardAmountInput"', $source);
        $this->assertStringContainsString('id="posTransferAmountInput"', $source);
        $this->assertStringContainsString('id="mixCashInput"', $source);
        $this->assertStringContainsString('id="mixCardInput"', $source);
        $this->assertStringContainsString('id="mixTransferInput"', $source);
        $this->assertStringContainsString('id="posMixCashDrawerRow"', $source);
        $this->assertStringContainsString('id="posAutoPaymentStatusBox"', $source);

        // Escenario A vs Escenario B y validación de cliente específico
        $this->assertStringContainsString('evaluateTabPaymentState', $source);
        $this->assertStringContainsString('refreshLivePaymentUI', $source);
        $this->assertStringContainsString('resetActiveMethodToSaleTotal', $source);
        $this->assertStringContainsString('Saldo Pendiente Restante:', $source);
        $this->assertStringContainsString('Pagar y Dejar Saldo Pendiente', $source);
        $this->assertStringContainsString('Cerrar Cobro y Generar Ticket', $source);
        $this->assertStringContainsString('btn-checkout-pending', $source);
        $this->assertStringContainsString('isGenericCustomerName', $source);
        $this->assertStringContainsString('hasRegisteredSpecificCustomer', $source);
        $this->assertStringContainsString('confirmPendingSaleDialog', $source);
        $this->assertStringContainsString('showPendingCustomerRequiredAlert', $source);
        $this->assertStringContainsString('btnRefreshPosCustomers', $source);
        $this->assertStringContainsString('central-pos.lookup-customers', $source);

        // Botones de notas pendientes: "Ir a Saldos pendientes" y "Añadir abono" al carrito (sin "Ver nota" ni "Abonar / Ver en Notas")
        $this->assertStringNotContainsString('Abonar / Ver en Notas', $source);
        $this->assertStringNotContainsString('>Ver nota</a>', $source);
        $this->assertStringContainsString('Ir a Saldos pendientes', $source);
        $this->assertStringContainsString('btn-add-pending-note-abono', $source);
        $this->assertStringContainsString('Añadir abono', $source);
        $this->assertStringContainsString('tabHasPendingNoteAbonoConcept', $source);
        $this->assertStringContainsString('No se permite pago pendiente sobre pago pendiente', $source);
    }

    public function test_receipt_renders_full_and_updated_partial_mixto_balances(): void
    {
        $branch = new Branch(['name' => 'Allende']);
        $user = new User(['name' => 'Cajero POS']);

        // 1. Venta parcial mixta ($1000 total, $300 efectivo + $200 tarjeta = $500 hoy, orden actualizada a $350 tras un abono posterior)
        $order = new Order(['due' => 350.00]);
        $partialTx = new PosTransaction([
            'transaction_code' => 'MOV-20261113-PART1',
            'customer_name' => 'Carlos Ramirez',
            'payment_method' => 'mixto',
            'cash_amount' => 300.00,
            'card_amount' => 200.00,
            'transfer_amount' => 0.00,
            'payment_references' => ['card_ref' => 'AUTH-8821'],
            'subtotal' => 1000.00,
            'commission_total' => 0.00,
            'total_amount' => 1000.00,
            'paid_amount' => 500.00,
            'change_amount' => 0.00,
            'items_payload' => [
                [
                    'section' => 'producto_inventario',
                    'title' => 'Smartphone Demo',
                    'quantity' => 1,
                    'amount' => 1000.00,
                    'commission' => 0.00,
                ],
            ],
        ]);
        $partialTx->created_at = Carbon::parse('2026-11-13 15:00:00');
        $partialTx->setRelation('branch', $branch);
        $partialTx->setRelation('user', $user);
        $partialTx->setRelation('order', $order);
        $partialTx->setRelation('financieraPayments', collect());

        $this->app->instance('request', Request::create('/central-pos/receipt/1', 'GET', ['paper' => '80']));
        $partialHtml = View::make('central-pos.receipt', ['transaction' => $partialTx])->render();

        $this->assertStringContainsString('TOTAL DE LA VENTA:', $partialHtml);
        $this->assertStringContainsString('$1,000.00 MXN', $partialHtml);
        $this->assertStringContainsString('PAGO INICIAL / ABONADO HOY:', $partialHtml);
        $this->assertStringContainsString('$500.00 MXN', $partialHtml);
        $this->assertStringContainsString('SALDO PENDIENTE ACTUALIZADO:', $partialHtml);
        $this->assertStringContainsString('$350.00 MXN', $partialHtml);
        $this->assertStringContainsString('$500.00 MXN', $partialHtml);
        $this->assertStringContainsString('MIXTO', $partialHtml);
        $this->assertStringContainsString('AUTH-8821', $partialHtml);

        // 2. Venta liquidada al 100% en efectivo con cambio
        $fullTx = new PosTransaction([
            'transaction_code' => 'MOV-20261113-FULL1',
            'customer_name' => 'Cliente Mostrador',
            'payment_method' => 'efectivo',
            'cash_amount' => 1000.00,
            'card_amount' => 0.00,
            'transfer_amount' => 0.00,
            'subtotal' => 1000.00,
            'commission_total' => 0.00,
            'total_amount' => 1000.00,
            'paid_amount' => 1500.00,
            'change_amount' => 500.00,
            'items_payload' => [
                [
                    'section' => 'producto_dinamico',
                    'title' => 'Funda Premium',
                    'quantity' => 1,
                    'amount' => 1000.00,
                    'commission' => 0.00,
                ],
            ],
        ]);
        $fullTx->created_at = Carbon::parse('2026-11-13 15:10:00');
        $fullTx->setRelation('branch', $branch);
        $fullTx->setRelation('user', $user);
        $fullTx->setRelation('financieraPayments', collect());

        $fullHtml = View::make('central-pos.receipt', ['transaction' => $fullTx])->render();
        $this->assertStringContainsString('TOTALMENTE PAGADO', $fullHtml);
        $this->assertStringContainsString('Cambio Entregado:', $fullHtml);
        $this->assertStringContainsString('$500.00', $fullHtml);
        $this->assertStringNotContainsString('SALDO PENDIENTE ACTUALIZADO:', $fullHtml);
    }

    public function test_backend_controller_and_routes_enforce_generic_customer_block_and_exact_payment_history(): void
    {
        $routes = file_get_contents(base_path('routes/web.php'));
        $this->assertStringContainsString('/lookup-customers', $routes);
        $this->assertStringContainsString("->name('lookup-customers')", $routes);

        $controller = file_get_contents(app_path('Http/Controllers/Dashboard/UnifiedPosController.php'));
        $this->assertStringContainsString('function isGenericWalkinName', $controller);
        $this->assertStringContainsString('function lookupCustomers', $controller);
        $this->assertStringContainsString('function recordOrderPaymentHistories', $controller);
        $this->assertStringContainsString("'publico general'", $controller);
        $this->assertStringContainsString("'amount' => \$orderPay,", $controller);
        $this->assertStringContainsString('if ($orderPay <= 0)', $controller);
        $this->assertStringContainsString("'order', 'financieraPayments.financiera'", $controller);
        $this->assertStringContainsString('containsPendingNoteAbonoConcept', $controller);
        $this->assertStringContainsString("if (\$section === 'nota_abono')", $controller);
        $this->assertStringContainsString('No se permite pago pendiente sobre pago pendiente', $controller);
    }

    public function test_pending_note_abono_detection_and_receipt_render(): void
    {
        $this->assertFalse(PosTransaction::containsPendingNoteAbonoConcept([
            ['section' => 'producto_inventario', 'title' => 'Funda', 'amount' => 100],
        ]));
        $this->assertTrue(PosTransaction::containsPendingNoteAbonoConcept([
            ['section' => 'producto_inventario', 'title' => 'Funda', 'amount' => 100],
            ['section' => 'nota_abono', 'order_id' => 7, 'title' => 'Abono Nota INV-000007', 'amount' => 702],
        ]));

        $branch = new Branch(['name' => 'Allende']);
        $user = new User(['name' => 'Cajero POS']);
        $abonoTx = new PosTransaction([
            'transaction_code' => 'MOV-20261113-ABN01',
            'customer_name' => 'Celestino Kozey',
            'payment_method' => 'efectivo',
            'cash_amount' => 702.00,
            'card_amount' => 0.00,
            'transfer_amount' => 0.00,
            'subtotal' => 702.00,
            'commission_total' => 0.00,
            'total_amount' => 702.00,
            'paid_amount' => 702.00,
            'change_amount' => 0.00,
            'items_payload' => [
                [
                    'section' => 'nota_abono',
                    'order_id' => 7,
                    'title' => 'Abono Nota INV-000007',
                    'subtitle' => 'Suc. Allende | test, prueba 3 | Nota INV-000007 LIQUIDADA ($0.00 restante)',
                    'quantity' => 1,
                    'amount' => 702.00,
                    'commission' => 0.00,
                ],
            ],
        ]);
        $abonoTx->created_at = Carbon::parse('2026-11-13 18:30:00');
        $abonoTx->setRelation('branch', $branch);
        $abonoTx->setRelation('user', $user);
        $abonoTx->setRelation('financieraPayments', collect());

        $this->app->instance('request', Request::create('/central-pos/receipt/2', 'GET', ['paper' => '80']));
        $html = View::make('central-pos.receipt', ['transaction' => $abonoTx])->render();

        $this->assertStringContainsString('Abono Nota INV-000007', $html);
        $this->assertStringContainsString('LIQUIDADA ($0.00 restante)', $html);
        $this->assertStringContainsString('$702.00 MXN', $html);
        $this->assertStringContainsString('TOTALMENTE PAGADO', $html);
    }
}

