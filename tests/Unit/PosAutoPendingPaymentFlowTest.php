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

        // Orden visual en pantalla de cobro: Transferencia junto a Efectivo, antes de Tarjeta y Mixto
        $efectivoPos = strpos($source, 'data-method="efectivo"');
        $transfPos = strpos($source, 'data-method="transferencia"');
        $tarjetaPos = strpos($source, 'data-method="tarjeta"');
        $mixtoPos = strpos($source, 'data-method="mixto"');
        $this->assertNotFalse($efectivoPos);
        $this->assertNotFalse($transfPos);
        $this->assertNotFalse($tarjetaPos);
        $this->assertNotFalse($mixtoPos);
        $this->assertLessThan($transfPos, $efectivoPos);
        $this->assertLessThan($tarjetaPos, $transfPos);
        $this->assertLessThan($mixtoPos, $tarjetaPos);

        // También en desglose Mixto y pantallas secundarias de cobro
        $this->assertLessThan(strpos($source, 'id="mixCardInput"'), strpos($source, 'id="mixTransferInput"'));

        $pendingDue = file_get_contents(resource_path('views/orders/pending-due.blade.php'));
        $this->assertNotEmpty(Blade::compileString($pendingDue));
        $this->assertLessThan(strpos($pendingDue, 'data-val="transferencia"'), strpos($pendingDue, 'data-val="efectivo"'));
        $this->assertLessThan(strpos($pendingDue, 'data-val="tarjeta"'), strpos($pendingDue, 'data-val="transferencia"'));
        $this->assertLessThan(strpos($pendingDue, 'data-val="mixto"'), strpos($pendingDue, 'data-val="tarjeta"'));

        $cashForm = file_get_contents(resource_path('views/cash/partials/form.blade.php'));
        $this->assertNotEmpty(Blade::compileString($cashForm));
        $this->assertLessThan(strpos($cashForm, 'value="transferencia"'), strpos($cashForm, 'value="efectivo"'));
        $this->assertLessThan(strpos($cashForm, 'value="tarjeta"'), strpos($cashForm, 'value="transferencia"'));

        $finShow = file_get_contents(resource_path('views/financieras/ventas/show.blade.php'));
        $this->assertNotEmpty(Blade::compileString($finShow));
        $this->assertLessThan(strpos($finShow, 'name="card_amount"'), strpos($finShow, 'name="transfer_amount"'));
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
        $this->assertStringContainsString('Saldo inicial de esta nota:', $partialHtml);
        $this->assertStringContainsString('NOTA CON SALDO PENDIENTE POR LIQUIDAR', $partialHtml);
        $this->assertStringNotContainsString('(LIQUIDADO)', $partialHtml);
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
        $this->assertStringContainsString('sanitizePendingNoteSubtitle', $controller);
        $this->assertStringContainsString("if (\$section === 'nota_abono')", $controller);
        $this->assertStringContainsString('No se permite pago pendiente sobre pago pendiente', $controller);
        $this->assertStringNotContainsString('Resta en nota {', $controller);
        $this->assertStringNotContainsString('$0.00 restante', $controller);
    }

    public function test_pending_note_abono_detection_and_hides_pending_balance_on_tickets(): void
    {
        $this->assertFalse(PosTransaction::containsPendingNoteAbonoConcept([
            ['section' => 'producto_inventario', 'title' => 'Funda', 'amount' => 100],
        ]));
        $this->assertTrue(PosTransaction::containsPendingNoteAbonoConcept([
            ['section' => 'producto_inventario', 'title' => 'Funda', 'amount' => 100],
            ['section' => 'nota_abono', 'order_id' => 7, 'title' => 'Abono Nota INV-000007', 'amount' => 702],
        ]));

        // Sanitización directa de subtítulos históricos / nuevos de abonos a notas pendientes
        $this->assertSame(
            'Suc. Sucursal Matriz | Producto',
            PosTransaction::sanitizePendingNoteSubtitle('Suc. Sucursal Matriz | Producto | Resta en nota INV-000281: $4,408.00')
        );
        $this->assertSame(
            'Suc. Allende | test, prueba 3',
            PosTransaction::sanitizePendingNoteSubtitle('Suc. Allende | test, prueba 3 | Nota INV-000007 LIQUIDADA ($0.00 restante)')
        );
        $this->assertNull(
            PosTransaction::sanitizePendingNoteSubtitle('Resta en nota INV-000281: $4,408.00')
        );

        $branch = new Branch(['name' => 'Allende']);
        $user = new User(['name' => 'Cajero POS']);
        $abonoTx = new PosTransaction([
            'transaction_code' => 'MOV-20261114-2UICU',
            'customer_name' => 'ALDO GAEL GAMEZ ZÁRATE',
            'payment_method' => 'transferencia',
            'cash_amount' => 0.00,
            'card_amount' => 0.00,
            'transfer_amount' => 400.00,
            'subtotal' => 400.00,
            'commission_total' => 0.00,
            'total_amount' => 400.00,
            'paid_amount' => 400.00,
            'change_amount' => 0.00,
            'items_payload' => [
                [
                    'section' => 'nota_abono',
                    'order_id' => 281,
                    'title' => 'Abono Nota INV-000281',
                    'subtitle' => 'Suc. Sucursal Matriz | Producto | Resta en nota INV-000281: $4,408.00',
                    'quantity' => 1,
                    'amount' => 400.00,
                    'commission' => 0.00,
                ],
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
        $abonoTx->created_at = Carbon::parse('2026-11-14 08:55:00');
        $abonoTx->setRelation('branch', $branch);
        $abonoTx->setRelation('user', $user);
        $abonoTx->setRelation('financieraPayments', collect());

        $this->app->instance('request', Request::create('/central-pos/receipt/2', 'GET', ['paper' => '80']));
        $html = View::make('central-pos.receipt', ['transaction' => $abonoTx])->render();

        $this->assertStringContainsString('Abono Nota INV-000281', $html);
        $this->assertStringContainsString('Suc. Sucursal Matriz | Producto', $html);
        $this->assertStringContainsString('Suc. Allende | test, prueba 3', $html);
        $this->assertStringNotContainsString('Resta en nota INV-000281', $html);
        $this->assertStringNotContainsString('$4,408.00', $html);
        $this->assertStringNotContainsString('LIQUIDADA ($0.00 restante)', $html);
        $this->assertStringContainsString('$400.00 MXN', $html);
        $this->assertStringContainsString('TOTALMENTE PAGADO', $html);

        // Ticket de abono en Módulo de Órdenes / Saldos Pendientes (orders/abono-ticket.blade.php) tampoco imprime saldo pendiente ni saldo anterior
        $customer = new \App\Models\Customer();
        $customer->tit_name = 'Carlos Ramirez';
        $order = new Order();
        $order->invoice_no = 'INV-000281';
        $order->order_date = '2026-11-01';
        $order->total = 5000.00;
        $order->due = 4408.00;
        $order->setRelation('customer', $customer);

        $history = new \App\Models\PaymentHistory([
            'id' => 99,
            'amount' => 400.00,
            'method' => 'Cheque',
            'cash_amount' => 0,
            'card_amount' => 0,
            'transfer_amount' => 400,
        ]);
        $history->id = 99;
        $history->created_at = Carbon::parse('2026-11-14 08:55:00');
        $history->setRelation('user', $user);

        $this->app->instance('request', Request::create('/orders/281/ticket-abono/99', 'GET', ['paper' => '80']));
        $abonoTicketHtml = View::make('orders.abono-ticket', [
            'order' => $order,
            'history' => $history,
            'branch' => $branch,
        ])->render();

        $this->assertStringContainsString('INV-000281', $abonoTicketHtml);
        $this->assertStringContainsString('$400.00 MXN', $abonoTicketHtml);
        $this->assertStringContainsString('TRANSFERENCIA', $abonoTicketHtml);
        $this->assertStringNotContainsString('Saldo Anterior:', $abonoTicketHtml);
        $this->assertStringNotContainsString('SALDO PENDIENTE:', $abonoTicketHtml);
        $this->assertStringNotContainsString('NUEVO SALDO PENDIENTE:', $abonoTicketHtml);
        $this->assertStringNotContainsString('$4,408.00', $abonoTicketHtml);
        $this->assertStringNotContainsString('$4,808.00', $abonoTicketHtml);
        $this->assertStringNotContainsString('COMPLETAMENTE LIQUIDADA', $abonoTicketHtml);

        // Nota de venta imprimible (orders/invoice-order.blade.php) tampoco imprime Debido
        $invoiceOrderSource = file_get_contents(resource_path('views/orders/invoice-order.blade.php'));
        $this->assertNotEmpty(Blade::compileString($invoiceOrderSource));
        $this->assertStringNotContainsString('Debido:', $invoiceOrderSource);
    }

    public function test_pos_view_and_controller_allow_pending_balance_on_financieras_loans_and_tandas(): void
    {
        $viewSource = file_get_contents(resource_path('views/central-pos/index.blade.php'));
        $this->assertNotEmpty(Blade::compileString($viewSource));

        // Ya NO debe existir el bloqueo antiguo a financieras ni el requisito obligatorio de producto para saldo pendiente
        $this->assertStringNotContainsString('Los abonos a financieras no permiten pago parcial', $viewSource);
        $this->assertStringNotContainsString('Para dejar saldo pendiente en Nota por Cobrar debe existir al menos un producto', $viewSource);
        $this->assertStringContainsString('hasCreditModuleConcept', $viewSource);
        $this->assertStringContainsString('Acreditación completa en módulo:', $viewSource);
        $this->assertStringContainsString('const usePendingButton = payState.isPendingMode && !payState.hasPendingNoteAbono && !payState.isOverpaidNonCash;', $viewSource);

        // Regla "no pago pendiente sobre pago pendiente" se mantiene vigente para abono de nota pendiente
        $this->assertStringContainsString('No se permite pago pendiente sobre pago pendiente', $viewSource);

        $controllerSource = file_get_contents(app_path('Http/Controllers/Dashboard/UnifiedPosController.php'));
        $this->assertStringNotContainsString('Los abonos a financieras no permiten pago parcial', $controllerSource);
        $this->assertStringNotContainsString('Para dejar una Nota con Saldo Pendiente debe existir al menos un producto', $controllerSource);
        $this->assertStringNotContainsString('debe cubrir al menos esos conceptos', $controllerSource);
        $this->assertStringContainsString('$hasProductsInOrder || $dueAmount > 0', $controllerSource);
        $this->assertStringContainsString('$includeCreditItemsInOrder = ($dueAmount > 0);', $controllerSource);
        $this->assertStringContainsString('$resolvedExternalCustomerId || !$selectedCatalogCustomer || $customerLooksGeneric', $controllerSource);
        $this->assertStringContainsString('No se permite pago pendiente sobre pago pendiente', $controllerSource);
    }

    public function test_allocate_payment_breakdown_prevents_phantom_cash_on_zero_and_partial_credit_sales(): void
    {
        $refClass = new \ReflectionClass(\App\Http\Controllers\Dashboard\UnifiedPosController::class);
        $controller = $refClass->newInstanceWithoutConstructor();
        $method = $refClass->getMethod('allocatePaymentBreakdown');
        $method->setAccessible(true);

        // 1. Abono Financiera de $553.00 con $0.00 pagado hoy -> desglose en $0.00 (cero dinero fantasma)
        $poolZero = ['paid' => 0.0, 'cash' => 0.0, 'card' => 0.0, 'transfer' => 0.0];
        $resZero = $method->invokeArgs($controller, [553.00, 0.00, 0.00, 0.00, 0.00, 553.00, &$poolZero]);
        $this->assertSame(['efectivo' => 0.0, 'tarjeta' => 0.0, 'transferencia' => 0.0], $resZero);

        // 2. Abono Financiera único de $553.00 pagado parcialmente con $200.00 Mixto ($120 efectivo + $80 transf) -> NO inflar a $553
        $poolPartialSingle = ['paid' => 200.0, 'cash' => 120.0, 'card' => 0.0, 'transfer' => 80.0];
        $resPartialSingle = $method->invokeArgs($controller, [553.00, 200.00, 120.00, 0.00, 80.00, 553.00, &$poolPartialSingle]);
        $this->assertSame(['efectivo' => 120.0, 'tarjeta' => 0.0, 'transferencia' => 80.0], $resPartialSingle);
        $this->assertEquals(0.0, $poolPartialSingle['paid']);

        // 3. Múltiples conceptos parciales (Financiera $300 + Préstamo $400 = $700 total), cliente paga $200 Mixto ($120 efec + $80 transf)
        $poolMulti = ['paid' => 200.0, 'cash' => 120.0, 'card' => 0.0, 'transfer' => 80.0];
        $finShare = $method->invokeArgs($controller, [300.00, 200.00, 120.00, 0.00, 80.00, 700.00, &$poolMulti]);
        $loanShare = $method->invokeArgs($controller, [400.00, 200.00, 120.00, 0.00, 80.00, 700.00, &$poolMulti]);
        $this->assertEqualsWithDelta(120.00, $finShare['efectivo'] + $loanShare['efectivo'], 0.001);
        $this->assertEqualsWithDelta(0.00, $finShare['tarjeta'] + $loanShare['tarjeta'], 0.001);
        $this->assertEqualsWithDelta(80.00, $finShare['transferencia'] + $loanShare['transferencia'], 0.001);
        $this->assertEqualsWithDelta(0.00, $poolMulti['paid'], 0.001);

        // 4. Combinado Producto ($200) + Financiera ($553) = $753, cliente paga $200 efectivo hoy -> Financiera nunca excede $200 reales
        $poolMixed = ['paid' => 200.0, 'cash' => 200.0, 'card' => 0.0, 'transfer' => 0.0];
        $finMixed = $method->invokeArgs($controller, [553.00, 200.00, 200.00, 0.00, 0.00, 753.00, &$poolMixed]);
        $this->assertLessThanOrEqual(200.00, $finMixed['efectivo'] + $finMixed['tarjeta'] + $finMixed['transferencia']);
        $this->assertEquals(0.0, $finMixed['tarjeta']);
        $this->assertEquals(0.0, $finMixed['transferencia']);
    }

    public function test_receipt_displays_exact_pending_balance_for_zero_and_partial_financiera_loan_tanda_sales(): void
    {
        $branch = new Branch(['name' => 'Allende']);
        $user = new User(['name' => 'Cajero POS']);

        // Caso A: Abono Financiera PayJoy $553.00 ($543 + $10 comisión) con $0.00 inicial -> deuda real $553.00 sin duplicar
        $orderZero = new Order(['invoice_no' => 'INV-000901', 'due' => 553.00]);
        $txZero = new PosTransaction([
            'transaction_code' => 'MOV-20261114-FIN00',
            'customer_name' => 'Maria Fernanda Torres',
            'payment_method' => 'mixto',
            'cash_amount' => 0.00,
            'card_amount' => 0.00,
            'transfer_amount' => 0.00,
            'subtotal' => 543.00,
            'commission_total' => 10.00,
            'total_amount' => 553.00,
            'paid_amount' => 0.00,
            'change_amount' => 0.00,
            'items_payload' => [
                [
                    'section' => 'financiera_abono',
                    'title' => 'Abono Financiera (PayJoy)',
                    'financiera_name' => 'PayJoy',
                    'quantity' => 1,
                    'amount' => 543.00,
                    'commission' => 10.00,
                ],
            ],
        ]);
        $txZero->created_at = Carbon::parse('2026-11-14 13:05:00');
        $txZero->setRelation('branch', $branch);
        $txZero->setRelation('user', $user);
        $txZero->setRelation('order', $orderZero);
        $txZero->setRelation('financieraPayments', collect());

        $this->app->instance('request', Request::create('/central-pos/receipt/10', 'GET', ['paper' => '80']));
        $htmlZero = View::make('central-pos.receipt', ['transaction' => $txZero])->render();

        $this->assertStringContainsString('TOTAL DE LA VENTA:', $htmlZero);
        $this->assertStringContainsString('$553.00 MXN', $htmlZero);
        $this->assertStringContainsString('PAGO INICIAL / ABONADO HOY:', $htmlZero);
        $this->assertStringContainsString('$0.00 MXN', $htmlZero);
        $this->assertStringContainsString('SALDO PENDIENTE:', $htmlZero);
        $this->assertStringContainsString('INV-000901', $htmlZero);
        $this->assertStringContainsString('NOTA CON SALDO PENDIENTE POR LIQUIDAR', $htmlZero);
        $this->assertStringContainsString('(SIN ABONO INICIAL)', $htmlZero);
        $this->assertStringNotContainsString('$1,106.00', $htmlZero);

        // Caso B: Abono Préstamo ($300) + Aportación Tanda ($200) = $500 con abono Mixto $150 ($100 Efec + $50 Transf) -> Resta $350.00
        $orderPartial = new Order(['invoice_no' => 'INV-000902', 'due' => 350.00]);
        $txPartial = new PosTransaction([
            'transaction_code' => 'MOV-20261114-PRTN1',
            'customer_name' => 'Pedro Antonio Morales',
            'payment_method' => 'mixto',
            'cash_amount' => 100.00,
            'card_amount' => 0.00,
            'transfer_amount' => 50.00,
            'subtotal' => 500.00,
            'commission_total' => 0.00,
            'total_amount' => 500.00,
            'paid_amount' => 150.00,
            'change_amount' => 0.00,
            'items_payload' => [
                [
                    'section' => 'prestamo_abono',
                    'title' => 'Abono Préstamo PREST-20261114-AB12 (Pago #2)',
                    'quantity' => 1,
                    'amount' => 300.00,
                    'commission' => 0.00,
                ],
                [
                    'section' => 'tanda_abono',
                    'title' => 'Tanda: Tanda Decembrina (Periodo #3)',
                    'quantity' => 1,
                    'amount' => 200.00,
                    'commission' => 0.00,
                ],
            ],
        ]);
        $txPartial->created_at = Carbon::parse('2026-11-14 13:10:00');
        $txPartial->setRelation('branch', $branch);
        $txPartial->setRelation('user', $user);
        $txPartial->setRelation('order', $orderPartial);
        $txPartial->setRelation('financieraPayments', collect());

        $htmlPartial = View::make('central-pos.receipt', ['transaction' => $txPartial])->render();
        $this->assertStringContainsString('$500.00 MXN', $htmlPartial);
        $this->assertStringContainsString('$150.00 MXN', $htmlPartial);
        $this->assertStringContainsString('SALDO PENDIENTE:', $htmlPartial);
        $this->assertStringContainsString('$350.00 MXN', $htmlPartial);
        $this->assertStringContainsString('INV-000902', $htmlPartial);
        $this->assertStringContainsString('$100.00', $htmlPartial);
        $this->assertStringContainsString('$50.00', $htmlPartial);
    }
}

