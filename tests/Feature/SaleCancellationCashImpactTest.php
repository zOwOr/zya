<?php

namespace Tests\Feature;

use App\Models\Branch;
use App\Models\CashFlow;
use App\Models\Category;
use App\Models\Customer;
use App\Models\DailyCut;
use App\Models\Order;
use App\Models\PaymentHistory;
use App\Models\PosTransaction;
use App\Models\Product;
use App\Models\Supplier;
use App\Models\User;
use App\Services\CashFlowService;
use Carbon\Carbon;
use Gloudemans\Shoppingcart\Facades\Cart;
use Illuminate\Foundation\Testing\DatabaseTransactions;
use Spatie\Permission\Models\Role;
use Tests\TestCase;

class SaleCancellationCashImpactTest extends TestCase
{
    use DatabaseTransactions;

    protected Branch $branch;
    protected Branch $otherBranch;
    protected User $superAdmin;
    protected Customer $customer;
    protected Category $category;
    protected Supplier $supplier;

    protected function setUp(): void
    {
        parent::setUp();

        $superRole = Role::firstOrCreate(['name' => 'SuperAdmin', 'guard_name' => 'web']);

        $this->branch = Branch::create([
            'name' => 'Sucursal Caja Cancel Test ' . uniqid(),
            'address' => 'Calle Prueba 100',
            'phone' => '8260000001',
            'is_active' => true,
            'auto_cut_enabled' => false,
        ]);

        $this->otherBranch = Branch::create([
            'name' => 'Sucursal Esme Casa ' . uniqid(),
            'address' => 'Calle Prueba 200',
            'phone' => '8260000002',
            'is_active' => true,
            'auto_cut_enabled' => false,
        ]);

        // SuperAdmin con sucursal distinta para validar que la cancelación impacte la sucursal de la venta
        $this->superAdmin = User::create([
            'name' => 'Esmeralda Cancel Test',
            'username' => 'esme_cancel_' . uniqid(),
            'email' => 'esme_cancel_' . uniqid() . '@zyacelulares.com',
            'password' => bcrypt('secret123'),
            'branch_id' => $this->otherBranch->id,
        ]);
        $this->superAdmin->assignRole($superRole);

        $this->customer = Customer::create([
            'tit_name' => 'Cliente Cancelación Test',
            'tit_email' => 'cliente_cancel_' . uniqid() . '@example.com',
            'tit_phone' => '826' . rand(1000000, 9999999),
            'branch_id' => $this->branch->id,
        ]);

        $this->category = Category::first() ?? Category::create([
            'name' => 'Cat Test Cancel ' . uniqid(),
            'slug' => 'cat-test-cancel-' . uniqid(),
        ]);

        $this->supplier = Supplier::first() ?? Supplier::create([
            'name' => 'Prov Test Cancel ' . uniqid(),
            'email' => 'prov_' . uniqid() . '@example.com',
            'phone' => '8269990000',
        ]);
    }

    protected function createProduct(string $name, float $price, int $stock = 10): Product
    {
        return Product::create([
            'product_name' => $name,
            'category_id' => $this->category->id,
            'supplier_id' => $this->supplier->id,
            'product_code' => 'PRD-' . strtoupper(uniqid()),
            'branch_id' => $this->branch->id,
            'stock_quantity' => $stock,
            'buying_price' => max(1, $price * 0.6),
            'selling_price' => $price,
        ]);
    }

    public function test_cancelling_efectivo_pos_sale_reduces_physical_cash_in_register_and_cuts_idempotently(): void
    {
        $this->actingAs($this->superAdmin);

        $product = $this->createProduct('Funda Premium Test', 500.00, 5);

        $checkoutResp = $this->postJson(route('central-pos.checkout'), [
            'branch_id' => $this->branch->id,
            'customer_id' => $this->customer->id,
            'customer_name' => $this->customer->tit_name,
            'payment_method' => 'efectivo',
            'cash_amount' => 500.00,
            'cash_received' => 500.00,
            'items' => [[
                'section' => 'producto_inventario',
                'product_id' => $product->id,
                'title' => $product->product_name,
                'amount' => 500.00,
                'quantity' => 1,
            ]],
        ]);
        $checkoutResp->assertStatus(200)->assertJson(['success' => true]);

        $tx = PosTransaction::findOrFail($checkoutResp->json('transaction_id'));

        $this->get(route('cash.index', ['branch_id' => $this->branch->id]))
            ->assertStatus(200)
            ->assertViewHas('cashBalance', 500.0)
            ->assertViewHas('totalExpense', 0.0);

        $this->get(route('cash.dailyCut', ['branch_id' => $this->branch->id, 'filter_type' => 'dia', 'date' => now()->toDateString()]))
            ->assertStatus(200)
            ->assertViewHas('expectedCash', 500.0)
            ->assertViewHas('handCash', 500.0)
            ->assertViewHas('totalExpense', 0.0);

        // Cancelar desde el Dashboard (usuario de otra sucursal -> debe impactar la sucursal de la venta)
        $cancelResp = $this->post(route('dashboard.transactions.cancel', $tx->id));
        $cancelResp->assertRedirect();

        $tx->refresh();
        $this->assertEquals('cancelado', $tx->status);
        $this->assertEquals('cancelado', $tx->order->order_status);

        $reversals = CashFlow::where('pos_transaction_id', $tx->id)
            ->where('type', 'expense')
            ->get();

        $this->assertCount(1, $reversals);
        $reversal = $reversals->first();
        $this->assertEquals($this->branch->id, (int) $reversal->branch_id);
        $this->assertEquals('efectivo', $reversal->payment_method);
        $this->assertEquals(500.00, (float) $reversal->amount);
        $this->assertEquals(500.00, (float) $reversal->cash_amount);
        $this->assertEquals(0.00, (float) $reversal->card_amount);
        $this->assertEquals(0.00, (float) $reversal->transfer_amount);
        $this->assertEquals(CashFlowService::CANCELLATION_MODULE, $reversal->module);

        // Validar caja física y corte X/Z tras cancelación
        $this->get(route('cash.index', ['branch_id' => $this->branch->id]))
            ->assertStatus(200)
            ->assertViewHas('cashBalance', 0.0)
            ->assertViewHas('totalExpense', 500.0);

        $cutViewResp = $this->get(route('cash.dailyCut', ['branch_id' => $this->branch->id, 'filter_type' => 'dia', 'date' => now()->toDateString()]));
        $cutViewResp->assertStatus(200)
            ->assertViewHas('expectedCash', 0.0)
            ->assertViewHas('handCash', 500.0)
            ->assertViewHas('totalExpense', 500.0)
            ->assertSee('Devolución en efectivo por cancelación de venta');

        // Confirmar Pre-Corte X refleja $0.00 esperado en caja física
        $this->post(route('cash.preCut'), [
            'branch_id' => $this->branch->id,
            'date' => now()->toDateString(),
            'declared_cash' => 0.00,
        ])->assertRedirect();

        $this->assertDatabaseHas('daily_cuts', [
            'branch_id' => $this->branch->id,
            'cut_type' => 'pre_corte',
            'cash_income' => 500.00,
            'total_expense' => 500.00,
            'declared_cash' => 0.00,
            'difference_amount' => 0.00,
        ]);

        // Reintentar cancelación no duplica egresos en caja
        $this->post(route('dashboard.transactions.cancel', $tx->id))->assertRedirect();
        $this->assertEquals(1, CashFlow::where('pos_transaction_id', $tx->id)->where('type', 'expense')->count());
    }

    public function test_cancelling_tarjeta_and_transferencia_pos_sales_does_not_affect_physical_cash(): void
    {
        $this->actingAs($this->superAdmin);

        // Venta base en efectivo de $100 para confirmar que la caja física permanece intacta en $100
        $cashResp = $this->postJson(route('central-pos.checkout'), [
            'branch_id' => $this->branch->id,
            'customer_id' => $this->customer->id,
            'customer_name' => $this->customer->tit_name,
            'payment_method' => 'efectivo',
            'cash_amount' => 100.00,
            'cash_received' => 100.00,
            'items' => [[
                'section' => 'producto_dinamico',
                'title' => 'Cable USB',
                'amount' => 100.00,
                'quantity' => 1,
            ]],
        ]);
        $cashResp->assertStatus(200)->assertJson(['success' => true]);

        // Venta con Tarjeta ($650)
        $cardResp = $this->postJson(route('central-pos.checkout'), [
            'branch_id' => $this->branch->id,
            'customer_id' => $this->customer->id,
            'customer_name' => $this->customer->tit_name,
            'payment_method' => 'tarjeta',
            'card_amount' => 650.00,
            'payment_references' => ['card_ref' => 'VISA-1234'],
            'items' => [[
                'section' => 'producto_dinamico',
                'title' => 'Mica Vidrio',
                'amount' => 650.00,
                'quantity' => 1,
            ]],
        ]);
        $cardResp->assertStatus(200)->assertJson(['success' => true]);

        // Venta con Transferencia ($420)
        $transferResp = $this->postJson(route('central-pos.checkout'), [
            'branch_id' => $this->branch->id,
            'customer_id' => $this->customer->id,
            'customer_name' => $this->customer->tit_name,
            'payment_method' => 'transferencia',
            'transfer_amount' => 420.00,
            'payment_references' => ['transfer_ref' => 'SPEI-9988'],
            'items' => [[
                'section' => 'producto_dinamico',
                'title' => 'Cargador Rápido',
                'amount' => 420.00,
                'quantity' => 1,
            ]],
        ]);
        $transferResp->assertStatus(200)->assertJson(['success' => true]);

        $cardTx = PosTransaction::findOrFail($cardResp->json('transaction_id'));
        $transferTx = PosTransaction::findOrFail($transferResp->json('transaction_id'));

        $this->post(route('dashboard.transactions.cancel', $cardTx->id))->assertRedirect();
        $this->post(route('dashboard.transactions.cancel', $transferTx->id))->assertRedirect();

        $this->assertEquals('cancelado', $cardTx->fresh()->status);
        $this->assertEquals('cancelado', $transferTx->fresh()->status);

        // No debe haberse creado ningún egreso en efectivo
        $this->assertEquals(
            0,
            CashFlow::where('branch_id', $this->branch->id)->where('type', 'expense')->count()
        );

        // La caja física sigue mostrando exactamente los $100.00 del cobro en efectivo
        $this->get(route('cash.index', ['branch_id' => $this->branch->id]))
            ->assertStatus(200)
            ->assertViewHas('cashBalance', 100.0)
            ->assertViewHas('totalExpense', 0.0);

        $this->get(route('cash.dailyCut', ['branch_id' => $this->branch->id, 'filter_type' => 'dia', 'date' => now()->toDateString()]))
            ->assertStatus(200)
            ->assertViewHas('expectedCash', 100.0)
            ->assertViewHas('handCash', 100.0)
            ->assertViewHas('totalExpense', 0.0);
    }

    public function test_cancelling_mixto_pos_sale_reduces_physical_cash_only_by_cash_portion(): void
    {
        $this->actingAs($this->superAdmin);

        $mixResp = $this->postJson(route('central-pos.checkout'), [
            'branch_id' => $this->branch->id,
            'customer_id' => $this->customer->id,
            'customer_name' => $this->customer->tit_name,
            'payment_method' => 'mixto',
            'cash_amount' => 300.00,
            'card_amount' => 200.00,
            'transfer_amount' => 100.00,
            'cash_received' => 300.00,
            'items' => [[
                'section' => 'producto_dinamico',
                'title' => 'Equipo Seminuevo Mixto',
                'amount' => 600.00,
                'quantity' => 1,
            ]],
        ]);
        $mixResp->assertStatus(200)->assertJson(['success' => true]);

        $mixTx = PosTransaction::findOrFail($mixResp->json('transaction_id'));

        $this->get(route('cash.dailyCut', ['branch_id' => $this->branch->id, 'filter_type' => 'dia', 'date' => now()->toDateString()]))
            ->assertStatus(200)
            ->assertViewHas('expectedCash', 300.0);

        $this->post(route('dashboard.transactions.cancel', $mixTx->id))->assertRedirect();

        $reversals = CashFlow::where('pos_transaction_id', $mixTx->id)
            ->where('type', 'expense')
            ->get();

        $this->assertCount(1, $reversals);
        $this->assertEquals(300.00, (float) $reversals->first()->amount);
        $this->assertEquals(300.00, (float) $reversals->first()->cash_amount);
        $this->assertEquals(0.00, (float) $reversals->first()->card_amount);
        $this->assertEquals(0.00, (float) $reversals->first()->transfer_amount);

        // Caja física debe bajar de $300 a $0.00 (no descontar tarjeta ni transferencia)
        $this->get(route('cash.index', ['branch_id' => $this->branch->id]))
            ->assertStatus(200)
            ->assertViewHas('cashBalance', 0.0)
            ->assertViewHas('totalExpense', 300.0);

        $this->get(route('cash.dailyCut', ['branch_id' => $this->branch->id, 'filter_type' => 'dia', 'date' => now()->toDateString()]))
            ->assertStatus(200)
            ->assertViewHas('expectedCash', 0.0)
            ->assertViewHas('handCash', 300.0)
            ->assertViewHas('totalExpense', 300.0);
    }

    public function test_order_cancellation_reverts_only_cash_and_avoids_double_deduction(): void
    {
        $this->actingAs($this->superAdmin);

        // 1. Orden legacy creada por transferencia (Cheque) NO debe tocar caja ni al crear ni al cancelar
        Cart::instance('default')->destroy();
        $productTransfer = $this->createProduct('Audífono Transfer', 350.00, 4);
        Cart::instance('default')->add($productTransfer->id, $productTransfer->product_name, 1, 350.00);

        $this->post(route('pos.storeOrder'), [
            'customer_id' => $this->customer->id,
            'payment_status' => 'Cheque',
            'pay' => 350.00,
            'branch_id' => $this->branch->id,
        ])->assertRedirect();

        $transferOrder = Order::latest('id')->firstOrFail();
        $this->assertEquals(0.0, (float) CashFlow::where('branch_id', $this->branch->id)->where('type', 'income')->get()->sum(fn ($f) => $f->effective_cash_amount));

        $this->delete(route('order.delete', $transferOrder->id))->assertRedirect();
        $this->assertEquals(0, CashFlow::where('branch_id', $this->branch->id)->where('type', 'expense')->count());
        $this->assertEquals(4, (int) $productTransfer->fresh()->stock_quantity);

        // 2. Orden legacy en efectivo ($200 inicial + abono posterior mixto $100 cash + $50 card)
        Cart::instance('default')->destroy();
        $productCash = $this->createProduct('Bocina Efectivo', 350.00, 4);
        Cart::instance('default')->add($productCash->id, $productCash->product_name, 1, 350.00);

        $this->post(route('pos.storeOrder'), [
            'customer_id' => $this->customer->id,
            'payment_status' => 'HandCash',
            'pay' => 200.00,
            'branch_id' => $this->branch->id,
        ])->assertRedirect();

        $cashOrder = Order::latest('id')->firstOrFail();
        $this->assertEquals(3, (int) $productCash->fresh()->stock_quantity);

        $this->post(route('order.updateDue'), [
            'order_id' => $cashOrder->id,
            'due' => 150.00,
            'payment_method' => 'mixto',
            'cash_amount' => 100.00,
            'card_amount' => 50.00,
            'transfer_amount' => 0,
            'branch_id' => $this->branch->id,
        ])->assertRedirect();

        $this->assertTrue(PaymentHistory::where('order_id', $cashOrder->id)->exists());

        $this->get(route('cash.index', ['branch_id' => $this->branch->id]))
            ->assertViewHas('cashBalance', 300.0);

        $this->delete(route('order.delete', $cashOrder->id))->assertRedirect();

        $this->assertEquals(4, (int) $productCash->fresh()->stock_quantity);
        $this->get(route('cash.index', ['branch_id' => $this->branch->id]))
            ->assertViewHas('cashBalance', 0.0)
            ->assertViewHas('totalExpense', 300.0);

        // 3. Orden vinculada a PosTransaction ya cancelada NO se duplica en egreso al borrar la orden
        $posProduct = $this->createProduct('Powerbank POS', 400.00, 5);
        $posResp = $this->postJson(route('central-pos.checkout'), [
            'branch_id' => $this->branch->id,
            'customer_id' => $this->customer->id,
            'customer_name' => $this->customer->tit_name,
            'payment_method' => 'efectivo',
            'cash_amount' => 400.00,
            'cash_received' => 400.00,
            'items' => [[
                'section' => 'producto_inventario',
                'product_id' => $posProduct->id,
                'title' => $posProduct->product_name,
                'amount' => 400.00,
                'quantity' => 1,
            ]],
        ]);
        $posResp->assertStatus(200);
        $posTx = PosTransaction::findOrFail($posResp->json('transaction_id'));

        $this->post(route('dashboard.transactions.cancel', $posTx->id))->assertRedirect();
        $this->delete(route('order.delete', $posTx->order_id))->assertRedirect();

        // Total acumulado en la sucursal de prueba: $300 (orden legacy) + $400 (POS) = $700, nunca $1,100
        $this->assertEquals(
            700.00,
            round((float) CashFlow::where('branch_id', $this->branch->id)->where('type', 'expense')->sum('amount'), 2)
        );
        $this->get(route('cash.index', ['branch_id' => $this->branch->id]))
            ->assertViewHas('cashBalance', 0.0);

        // 4. Si se elimina primero la orden POS, revierte su efectivo y marca cancelado el PosTransaction sin duplicar después
        $posProduct2 = $this->createProduct('Cargador C POS', 250.00, 3);
        $posResp2 = $this->postJson(route('central-pos.checkout'), [
            'branch_id' => $this->branch->id,
            'customer_id' => $this->customer->id,
            'customer_name' => $this->customer->tit_name,
            'payment_method' => 'efectivo',
            'cash_amount' => 250.00,
            'cash_received' => 250.00,
            'items' => [[
                'section' => 'producto_inventario',
                'product_id' => $posProduct2->id,
                'title' => $posProduct2->product_name,
                'amount' => 250.00,
                'quantity' => 1,
            ]],
        ]);
        $posResp2->assertStatus(200);
        $posTx2 = PosTransaction::findOrFail($posResp2->json('transaction_id'));

        $this->delete(route('order.delete', $posTx2->order_id))->assertRedirect();
        $this->assertEquals('cancelado', $posTx2->fresh()->status);

        $this->post(route('dashboard.transactions.cancel', $posTx2->id))->assertRedirect();
        $this->assertEquals(1, CashFlow::where('pos_transaction_id', $posTx2->id)->where('type', 'expense')->count());
        $this->get(route('cash.index', ['branch_id' => $this->branch->id]))
            ->assertViewHas('cashBalance', 0.0);
    }

    public function test_cancelling_zero_paid_credit_pos_sale_creates_no_cash_expense(): void
    {
        $this->actingAs($this->superAdmin);

        $creditProduct = $this->createProduct('Tablet Crédito Cero Hoy', 800.00, 3);
        $creditResp = $this->postJson(route('central-pos.checkout'), [
            'branch_id' => $this->branch->id,
            'customer_id' => $this->customer->id,
            'customer_name' => $this->customer->tit_name,
            'payment_method' => 'efectivo',
            'cash_amount' => 0.00,
            'paid_amount' => 0.00,
            'items' => [[
                'section' => 'producto_inventario',
                'product_id' => $creditProduct->id,
                'title' => $creditProduct->product_name,
                'amount' => 800.00,
                'quantity' => 1,
            ]],
        ]);
        $creditResp->assertStatus(200)->assertJson(['success' => true]);

        $creditTx = PosTransaction::findOrFail($creditResp->json('transaction_id'));
        $this->post(route('dashboard.transactions.cancel', $creditTx->id))->assertRedirect();

        $this->assertEquals('cancelado', $creditTx->fresh()->status);
        $this->assertEquals(0, CashFlow::where('branch_id', $this->branch->id)->where('type', 'expense')->count());
        $this->get(route('cash.index', ['branch_id' => $this->branch->id]))
            ->assertViewHas('cashBalance', 0.0)
            ->assertViewHas('totalExpense', 0.0);
    }

    public function test_corte_z_today_restricts_pos_and_order_cancellations_by_branch_while_allowing_previous_day_when_today_is_open(): void
    {
        $this->actingAs($this->superAdmin);

        $yesterday = Carbon::yesterday()->toDateString();

        // Venta de AYER con Corte Z de AYER ya aplicado, pero caja de HOY aún abierta
        $yesterdayTx = PosTransaction::create([
            'transaction_code' => 'MOV-YEST-' . strtoupper(uniqid()),
            'branch_id' => $this->branch->id,
            'user_id' => $this->superAdmin->id,
            'customer_id' => $this->customer->id,
            'customer_name' => $this->customer->tit_name,
            'subtotal' => 300.00,
            'commission_total' => 0,
            'total_amount' => 300.00,
            'paid_amount' => 300.00,
            'change_amount' => 0,
            'payment_method' => 'efectivo',
            'cash_amount' => 300.00,
            'card_amount' => 0,
            'transfer_amount' => 0,
            'items_payload' => [['section' => 'producto_dinamico', 'title' => 'Producto Ayer', 'amount' => 300, 'quantity' => 1]],
            'status' => 'completado',
        ]);
        $yesterdayFlow = CashFlow::create([
            'type' => 'income',
            'amount' => 300.00,
            'description' => 'Venta Ayer',
            'reference' => $yesterdayTx->transaction_code,
            'module' => 'Cobro Centralizado',
            'branch_id' => $this->branch->id,
            'user_id' => $this->superAdmin->id,
            'pos_transaction_id' => $yesterdayTx->id,
            'customer_id' => $this->customer->id,
            'customer_name' => $this->customer->tit_name,
            'payment_method' => 'efectivo',
            'cash_amount' => 300.00,
        ]);
        CashFlow::where('id', $yesterdayFlow->id)->update(['created_at' => Carbon::yesterday()->setTime(14, 0)]);

        DailyCut::create([
            'branch_id' => $this->branch->id,
            'date' => $yesterday,
            'cut_type' => 'corte_z',
            'cut_mode' => 'manual',
            'user_id' => $this->superAdmin->id,
            'opening_balance' => 0,
            'total_income' => 300.00,
            'cash_income' => 300.00,
            'card_income' => 0,
            'transfer_income' => 0,
            'total_expense' => 0,
            'withdraw_amount' => 0,
            'balance' => 300.00,
            'declared_cash' => 300.00,
            'difference_amount' => 0,
            'closed_at' => Carbon::yesterday()->setTime(21, 0),
        ]);

        // Opción 2: Mientras el Corte Z de HOY siga abierto en esa sucursal, sí se puede cancelar la venta de ayer
        $this->post(route('dashboard.transactions.cancel', $yesterdayTx->id))
            ->assertRedirect()
            ->assertSessionHas('success');
        $this->assertEquals('cancelado', $yesterdayTx->fresh()->status);
        $this->assertEquals(1, CashFlow::where('pos_transaction_id', $yesterdayTx->id)->where('type', 'expense')->count());

        // Venta de HOY en sucursal A ($this->branch) y venta de HOY en sucursal B ($this->otherBranch)
        $respBranchA = $this->postJson(route('central-pos.checkout'), [
            'branch_id' => $this->branch->id,
            'customer_id' => $this->customer->id,
            'customer_name' => $this->customer->tit_name,
            'payment_method' => 'efectivo',
            'cash_amount' => 500.00,
            'items' => [[
                'section' => 'producto_dinamico',
                'title' => 'Producto Hoy Sucursal A',
                'amount' => 500.00,
                'quantity' => 1,
            ]],
        ])->assertStatus(200);
        $txBranchA = PosTransaction::findOrFail($respBranchA->json('transaction_id'));

        $respBranchB = $this->postJson(route('central-pos.checkout'), [
            'branch_id' => $this->otherBranch->id,
            'customer_id' => $this->customer->id,
            'customer_name' => $this->customer->tit_name,
            'payment_method' => 'efectivo',
            'cash_amount' => 200.00,
            'items' => [[
                'section' => 'producto_dinamico',
                'title' => 'Producto Hoy Sucursal B',
                'amount' => 200.00,
                'quantity' => 1,
            ]],
        ])->assertStatus(200);
        $txBranchB = PosTransaction::findOrFail($respBranchB->json('transaction_id'));

        // Orden pendiente en sucursal A
        Cart::instance('default')->destroy();
        $orderProduct = $this->createProduct('Producto Orden Corte Z', 150.00, 5);
        Cart::instance('default')->add($orderProduct->id, $orderProduct->product_name, 1, 150.00);
        $this->post(route('pos.storeOrder'), [
            'customer_id' => $this->customer->id,
            'payment_status' => 'HandCash',
            'pay' => 50.00,
            'branch_id' => $this->branch->id,
        ])->assertRedirect();
        $pendingOrder = Order::latest('id')->firstOrFail();

        // Antes del Corte Z de hoy: SuperAdmin SÍ ve cancelar orden y cancelar movimiento
        $this->get(route('order.orderDetails', $pendingOrder->id))
            ->assertStatus(200)
            ->assertSee('cancel-form', false);
        $this->get(route('dashboard', ['branch_id' => $this->branch->id]))
            ->assertStatus(200)
            ->assertSee(route('dashboard.transactions.cancel', $txBranchA->id), false);

        // Aplicar Corte Z de HOY solo en sucursal A ($this->branch)
        $this->post(route('cash.applyCut'), [
            'branch_id' => $this->branch->id,
            'date' => Carbon::today()->toDateString(),
            'declared_cash' => 550.00,
        ])->assertRedirect();

        // UI oculta el botón activo de cancelar y muestra bloqueo en Dashboard y Detalle de Orden para sucursal A
        $this->get(route('dashboard', ['branch_id' => $this->branch->id]))
            ->assertStatus(200)
            ->assertDontSee(route('dashboard.transactions.cancel', $txBranchA->id), false)
            ->assertSee('la-lock', false);

        $this->get(route('order.orderDetails', $pendingOrder->id))
            ->assertStatus(200)
            ->assertDontSee('cancel-form', false)
            ->assertSee('Cancelación bloqueada (Corte Z aplicado)');

        // Intentos directos al backend quedan bloqueados en sucursal A
        $this->post(route('dashboard.transactions.cancel', $txBranchA->id))
            ->assertRedirect()
            ->assertSessionHas('error');
        $this->postJson(route('dashboard.transactions.cancel', $txBranchA->id))
            ->assertStatus(422)
            ->assertJson(['success' => false]);
        $this->assertEquals('completado', $txBranchA->fresh()->status);
        $this->assertEquals(0, CashFlow::where('pos_transaction_id', $txBranchA->id)->where('type', 'expense')->count());

        $this->delete(route('order.delete', $pendingOrder->id))
            ->assertRedirect()
            ->assertSessionHas('error');
        $this->assertNotNull(Order::find($pendingOrder->id));
        $this->assertEquals(4, (int) $orderProduct->fresh()->stock_quantity);

        // Sucursal B sigue con su Corte Z de hoy abierto -> su botón sigue visible y cancela sin problemas
        $this->get(route('dashboard', ['branch_id' => $this->otherBranch->id]))
            ->assertStatus(200)
            ->assertSee(route('dashboard.transactions.cancel', $txBranchB->id), false);
        $this->post(route('dashboard.transactions.cancel', $txBranchB->id))
            ->assertRedirect()
            ->assertSessionHas('success');
        $this->assertEquals('cancelado', $txBranchB->fresh()->status);
    }

    public function test_cancel_visibility_and_execution_strictly_respect_roles_and_permissions(): void
    {
        // Crear dos ventas y una orden pendiente en sucursal abierta
        $this->actingAs($this->superAdmin);
        $checkout1 = $this->postJson(route('central-pos.checkout'), [
            'branch_id' => $this->branch->id,
            'customer_id' => $this->customer->id,
            'customer_name' => $this->customer->tit_name,
            'payment_method' => 'efectivo',
            'cash_amount' => 250.00,
            'items' => [[
                'section' => 'producto_dinamico',
                'title' => 'Producto Permisos 1',
                'amount' => 250.00,
                'quantity' => 1,
            ]],
        ])->assertStatus(200);
        $tx1 = PosTransaction::findOrFail($checkout1->json('transaction_id'));

        Cart::instance('default')->destroy();
        $prod = $this->createProduct('Producto Orden Permisos', 120.00, 3);
        Cart::instance('default')->add($prod->id, $prod->product_name, 1, 120.00);
        $this->post(route('pos.storeOrder'), [
            'customer_id' => $this->customer->id,
            'payment_status' => 'HandCash',
            'pay' => 60.00,
            'branch_id' => $this->branch->id,
        ])->assertRedirect();
        $order = Order::latest('id')->firstOrFail();

        // 1. Usuario con lectura de Dashboard y Órdenes pero SIN permisos de cancelación
        $readOnlyRole = Role::create(['name' => 'SoloLecturaCancel_' . uniqid(), 'guard_name' => 'web']);
        $readOnlyRole->givePermissionTo(['dashboard.menu', 'dashboard.read', 'dashboard.all_branches', 'orders.menu', 'orders.read']);
        $readOnlyUser = User::create([
            'name' => 'Cajera Sin Cancelar',
            'username' => 'nocancel_' . uniqid(),
            'email' => 'nocancel_' . uniqid() . '@zyacelulares.com',
            'password' => bcrypt('secret123'),
            'branch_id' => $this->branch->id,
        ]);
        $readOnlyUser->assignRole($readOnlyRole);

        $this->actingAs($readOnlyUser);
        $this->get(route('dashboard', ['branch_id' => $this->branch->id]))
            ->assertStatus(200)
            ->assertDontSee(route('dashboard.transactions.cancel', $tx1->id), false)
            ->assertDontSee('la-lock', false);

        $this->get(route('order.orderDetails', $order->id))
            ->assertStatus(200)
            ->assertDontSee('cancel-form', false)
            ->assertDontSee('Cancelación bloqueada');

        $this->post(route('dashboard.transactions.cancel', $tx1->id))->assertStatus(403);
        $this->delete(route('order.delete', $order->id))->assertStatus(403);

        // Incluso después de aplicar Corte Z, usuario sin permiso sigue recibiendo 403 y sin ver botones
        $this->actingAs($this->superAdmin);
        DailyCut::create([
            'branch_id' => $this->branch->id,
            'date' => Carbon::today()->toDateString(),
            'cut_type' => 'corte_z',
            'cut_mode' => 'manual',
            'user_id' => $this->superAdmin->id,
            'opening_balance' => 0,
            'total_income' => 310.00,
            'cash_income' => 310.00,
            'card_income' => 0,
            'transfer_income' => 0,
            'total_expense' => 0,
            'withdraw_amount' => 0,
            'balance' => 310.00,
            'declared_cash' => 310.00,
            'difference_amount' => 0,
            'closed_at' => now(),
        ]);

        $this->actingAs($readOnlyUser);
        $this->get(route('dashboard', ['branch_id' => $this->branch->id]))
            ->assertStatus(200)
            ->assertDontSee('la-lock', false);
        $this->get(route('order.orderDetails', $order->id))
            ->assertStatus(200)
            ->assertDontSee('Cancelación bloqueada');
        $this->post(route('dashboard.transactions.cancel', $tx1->id))->assertStatus(403);
        $this->delete(route('order.delete', $order->id))->assertStatus(403);

        // 2. Usuario CON cobros.cancel_transaction (sin dashboard.cancel_transaction) y CON orders.delete en sucursal abierta ($this->otherBranch)
        $cancellerRole = Role::create(['name' => 'PuedeCancelar_' . uniqid(), 'guard_name' => 'web']);
        $cancellerRole->givePermissionTo([
            'dashboard.menu',
            'dashboard.read',
            'dashboard.all_branches',
            'cobros.cancel_transaction',
            'orders.menu',
            'orders.read',
            'orders.delete',
        ]);
        $cancellerUser = User::create([
            'name' => 'Supervisor Cancelador',
            'username' => 'canceller_' . uniqid(),
            'email' => 'canceller_' . uniqid() . '@zyacelulares.com',
            'password' => bcrypt('secret123'),
            'branch_id' => $this->otherBranch->id,
        ]);
        $cancellerUser->assignRole($cancellerRole);

        $this->actingAs($this->superAdmin);
        $checkoutOpen = $this->postJson(route('central-pos.checkout'), [
            'branch_id' => $this->otherBranch->id,
            'customer_id' => $this->customer->id,
            'customer_name' => $this->customer->tit_name,
            'payment_method' => 'efectivo',
            'cash_amount' => 180.00,
            'items' => [[
                'section' => 'producto_dinamico',
                'title' => 'Producto Cancelador Abierto',
                'amount' => 180.00,
                'quantity' => 1,
            ]],
        ])->assertStatus(200);
        $txOpen = PosTransaction::findOrFail($checkoutOpen->json('transaction_id'));

        Cart::instance('default')->destroy();
        Cart::instance('default')->add($prod->id, $prod->product_name, 1, 120.00);
        $this->post(route('pos.storeOrder'), [
            'customer_id' => $this->customer->id,
            'payment_status' => 'HandCash',
            'pay' => 60.00,
            'branch_id' => $this->otherBranch->id,
        ])->assertRedirect();
        $openOrder = Order::latest('id')->firstOrFail();

        $this->actingAs($cancellerUser);
        $this->get(route('dashboard', ['branch_id' => $this->otherBranch->id]))
            ->assertStatus(200)
            ->assertSee(route('dashboard.transactions.cancel', $txOpen->id), false);
        $this->post(route('dashboard.transactions.cancel', $txOpen->id))
            ->assertRedirect()
            ->assertSessionHas('success');
        $this->assertEquals('cancelado', $txOpen->fresh()->status);

        $this->get(route('order.orderDetails', $openOrder->id))
            ->assertStatus(200)
            ->assertSee('cancel-form', false);
        $this->delete(route('order.delete', $openOrder->id))
            ->assertRedirect()
            ->assertSessionHas('success');
        $this->assertNull(Order::find($openOrder->id));
    }
}
