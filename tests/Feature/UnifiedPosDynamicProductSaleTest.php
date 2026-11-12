<?php

namespace Tests\Feature;

use App\Models\Branch;
use App\Models\CashFlow;
use App\Models\Customer;
use App\Models\Order;
use App\Models\OrderDetails;
use App\Models\PosTransaction;
use App\Models\Product;
use App\Models\User;
use Illuminate\Foundation\Testing\DatabaseTransactions;
use Spatie\Permission\Models\Role;
use Tests\TestCase;

class UnifiedPosDynamicProductSaleTest extends TestCase
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
            ['address' => 'Allende Centro', 'phone' => '8261234567', 'is_active' => true]
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
            'tit_name' => 'Cliente Mostrador Prueba',
            'tit_email' => 'cliente_' . uniqid() . '@example.com',
            'tit_phone' => '8261119988',
            'branch_id' => $this->branch->id,
        ]);
    }

    /**
     * Test 1: Verificar que el index de POS 4 en 1 contiene los elementos de Producto Libre,
     * renderActiveCart corregido, enlaces defensivos y botón de Ver Ticket.
     */
    public function test_pos_4_in_1_view_contains_all_dynamic_product_elements_and_fixes(): void
    {
        $this->actingAs($this->user);

        $response = $this->get(route('central-pos.index'));
        $response->assertStatus(200);

        $content = $response->getContent();

        // 1. Subpestaña y formulario de Producto Libre
        $this->assertStringContainsString('subtab-dynamic', $content);
        $this->assertStringContainsString('dynProdName', $content);
        $this->assertStringContainsString('dynProdPrice', $content);
        $this->assertStringContainsString('dynProdQty', $content);
        $this->assertStringContainsString('btnAddDynamicProductToTab', $content);

        // 2. Botón de checkout y generación de ticket
        $this->assertStringContainsString('btnCompleteUnifiedSale', $content);
        $this->assertStringContainsString('Cerrar Cobro y Generar Ticket', $content);

        // 3. Ver / Abrir ticket en el banner de éxito
        $this->assertStringContainsString('posBtnVerTicket', $content);
        $this->assertStringContainsString('Ver Ticket', $content);

        // 4. Corrección de función inexistente renderCurrentTabCart
        $this->assertStringNotContainsString('renderCurrentTabCart()', $content);
        $this->assertStringContainsString('renderActiveCart()', $content);
    }

    /**
     * Test 2: Venta exitosa en POS 4 en 1 agregando un Producto Libre (Sin Stock).
     */
    public function test_pos_4_in_1_checkout_with_dynamic_product_succeeds_and_generates_sale_and_ticket(): void
    {
        $this->actingAs($this->user);

        $initialProductsCount = Product::count();

        $payload = [
            'branch_id' => $this->branch->id,
            'customer_id' => $this->customer->id,
            'customer_name' => $this->customer->tit_name,
            'customer_phone' => $this->customer->tit_phone,
            'payment_method' => 'efectivo',
            'cash_amount' => 180.00,
            'paid_amount' => 180.00,
            'cash_received' => 200.00,
            'open_drawer' => 1,
            'items' => [
                [
                    'section' => 'producto_dinamico',
                    'name' => 'Mica Hidrogel Mate',
                    'brand' => 'Genérica',
                    'model' => 'Universal',
                    'imei' => null,
                    'category_status' => 'Nuevo',
                    'warranty_time' => '30 días',
                    'observations' => 'Colocada en mostrador',
                    'title' => 'Mica Hidrogel Mate (Nuevo)',
                    'subtitle' => 'Sin impacto en inventario',
                    'quantity' => 1,
                    'amount' => 180.00,
                    'commission' => 0.00,
                ],
            ],
        ];

        $response = $this->postJson(route('central-pos.checkout'), $payload);
        $response->assertStatus(200);

        $data = $response->json();
        $this->assertTrue($data['success']);
        $this->assertNotEmpty($data['transaction_id']);
        $this->assertNotEmpty($data['transaction_code']);
        $this->assertNotEmpty($data['receipt_url']);

        // 1. Verificar transacción en PosTransaction
        $tx = PosTransaction::find($data['transaction_id']);
        $this->assertNotNull($tx);
        $this->assertEquals(180.00, (float) $tx->total_amount);
        $this->assertEquals(200.00, (float) $tx->paid_amount);
        $this->assertEquals(20.00, (float) $tx->change_amount);
        $this->assertEquals('efectivo', $tx->payment_method);
        $this->assertEquals('completado', $tx->status);

        // 2. Verificar que NO alteró el catálogo físico de productos
        $this->assertEquals($initialProductsCount, Product::count());

        // 3. Verificar Order y OrderDetails con is_dynamic = true
        $this->assertNotNull($tx->order_id);
        $order = Order::find($tx->order_id);
        $this->assertNotNull($order);
        $this->assertEquals(180.00, (float) $order->total);
        $this->assertEquals('complete', $order->order_status);

        $detail = OrderDetails::where('order_id', $order->id)->first();
        $this->assertNotNull($detail);
        $this->assertTrue((bool) $detail->is_dynamic);
        $this->assertNull($detail->product_id);
        $this->assertEquals('Mica Hidrogel Mate', $detail->dynamic_product_name);
        $this->assertEquals(1, $detail->quantity);
        $this->assertEquals(180.00, (float) $detail->unitcost);
        $this->assertEquals(180.00, (float) $detail->total);

        // 4. Verificar CashFlow registrado
        $cashFlow = CashFlow::where('pos_transaction_id', $tx->id)->first();
        $this->assertNotNull($cashFlow);
        $this->assertEquals(180.00, (float) $cashFlow->amount);
        $this->assertEquals('income', $cashFlow->type);

        // 5. Verificar que el endpoint de impresión de ticket renderiza sin errores
        $receiptResponse = $this->get(route('central-pos.receipt', $tx->id));
        $receiptResponse->assertStatus(200);
        $receiptHtml = $receiptResponse->getContent();
        $this->assertStringContainsString('Mica Hidrogel Mate', $receiptHtml);
        $this->assertStringContainsString('TOTAL DE LA NOTA', $receiptHtml);
        $this->assertStringContainsString('180.00', $receiptHtml);
        $this->assertStringContainsString('EFECTIVO', $receiptHtml);
    }

    /**
     * Test 3: Venta mixta de Producto Libre y Producto de Catálogo con stock.
     */
    public function test_mixed_stock_and_dynamic_product_sale(): void
    {
        $this->actingAs($this->user);

        $category = \App\Models\Category::firstOrCreate(
            ['name' => 'Accesorios'],
            ['slug' => 'accesorios']
        );
        $supplier = \App\Models\Supplier::firstOrCreate(
            ['name' => 'Proveedor Test'],
            ['email' => 'prov_' . uniqid() . '@example.com', 'phone' => '8260000000']
        );

        $stockProduct = Product::create([
            'product_name' => 'Cargador Original C',
            'product_code' => 'CHG-' . rand(1000, 9999),
            'category_id' => $category->id,
            'supplier_id' => $supplier->id,
            'buying_price' => 100.00,
            'selling_price' => 250.00,
            'stock_quantity' => 10,
            'branch_id' => $this->branch->id,
            'category_status' => 'Nuevo',
        ]);

        $payload = [
            'branch_id' => $this->branch->id,
            'customer_name' => 'Cliente Mostrador',
            'payment_method' => 'tarjeta',
            'card_amount' => 350.00,
            'paid_amount' => 350.00,
            'payment_references' => ['card_ref' => 'AUTH-8899'],
            'open_drawer' => 0,
            'items' => [
                [
                    'section' => 'producto_inventario',
                    'product_id' => $stockProduct->id,
                    'title' => $stockProduct->product_name,
                    'quantity' => 1,
                    'amount' => 250.00,
                    'commission' => 0.00,
                ],
                [
                    'section' => 'producto_dinamico',
                    'name' => 'Servicio Limpieza Puerto',
                    'title' => 'Servicio Limpieza Puerto (Servicio)',
                    'quantity' => 1,
                    'amount' => 100.00,
                    'commission' => 0.00,
                ],
            ],
        ];

        $response = $this->postJson(route('central-pos.checkout'), $payload);
        $response->assertStatus(200);

        // Descontó 1 de stock del producto físico
        $stockProduct->refresh();
        $this->assertEquals(9, $stockProduct->stock_quantity);

        $tx = PosTransaction::find($response->json('transaction_id'));
        $this->assertEquals(350.00, (float) $tx->total_amount);
        $this->assertEquals('tarjeta', $tx->payment_method);

        // Ambos detalles existen en OrderDetails
        $details = OrderDetails::where('order_id', $tx->order_id)->get();
        $this->assertCount(2, $details);
        $this->assertTrue($details->contains('product_id', $stockProduct->id));
        $this->assertTrue($details->contains('is_dynamic', true));
    }
}
