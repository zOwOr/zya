<?php

namespace Tests\Feature;

use App\Models\Branch;
use App\Models\CashFlow;
use App\Models\Customer;
use App\Models\FinBrand;
use App\Models\FinDevice;
use App\Models\FinFinanciera;
use App\Models\FinSale;
use App\Models\FinSalePayment;
use App\Models\Order;
use App\Models\OrderDetails;
use App\Models\PosTransaction;
use App\Models\Product;
use App\Models\Supplier;
use App\Models\User;
use Carbon\Carbon;
use Illuminate\Foundation\Testing\DatabaseTransactions;
use Spatie\Permission\Models\Role;
use Tests\TestCase;

class ZeroAmountAndDiscountTest extends TestCase
{
    use DatabaseTransactions;

    protected User $user;
    protected Branch $branch;
    protected Customer $customer;
    protected FinFinanciera $financiera;
    protected FinBrand $brand;

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
            'username' => 'esme_zero_' . uniqid(),
            'email' => 'esme_zero_' . uniqid() . '@zyacelulares.com',
            'password' => bcrypt('password123'),
            'branch_id' => $this->branch->id,
        ]);
        $this->user->assignRole($role);

        $this->customer = Customer::create([
            'tit_name' => 'Juan Pérez Cliente',
            'tit_email' => 'juan_' . uniqid() . '@example.com',
            'tit_phone' => '8261112233',
            'tit_address' => 'Allende NL',
            'tit_status' => 'Activo',
        ]);

        $this->financiera = FinFinanciera::firstOrCreate(
            ['name' => 'PayJoy'],
            ['is_active' => true]
        );

        $this->brand = FinBrand::firstOrCreate(
            ['name' => 'Samsung'],
            ['is_active' => true]
        );
    }

    /**
     * Criterio 1: Registrar una venta con artículo en stock y artículo libre en $0 sin error de validación.
     */
    public function test_criterion_1_register_sale_with_zero_amount_article(): void
    {
        $this->actingAs($this->user);

        // Crear proveedor
        $supplier = Supplier::create([
            'name' => 'Proveedor Accesorios',
            'email' => 'proveedor_' . uniqid() . '@example.com',
            'phone' => '8261110000',
            'address' => 'Monterrey',
            'shopname' => 'Distribuidora',
        ]);

        // Crear producto de inventario con stock
        $product = Product::create([
            'product_name' => 'Mica de Cristal Templado',
            'category_id' => 1,
            'supplier_id' => $supplier->id,
            'product_code' => 'MICA-' . rand(1000, 9999),
            'stock_quantity' => 10,
            'buying_price' => 20.00,
            'selling_price' => 100.00,
        ]);

        $payload = [
            'branch_id' => $this->branch->id,
            'customer_id' => $this->customer->id,
            'customer_name' => $this->customer->tit_name,
            'payment_method' => 'efectivo',
            'cash_amount' => 0,
            'cash_received' => 0,
            'items' => [
                [
                    'section' => 'producto_inventario',
                    'product_id' => $product->id,
                    'title' => $product->product_name,
                    'quantity' => 1,
                    'amount' => 0.00, // Se vende en $0.00 (de cortesía o promoción)
                    'original_amount' => 100.00,
                    'commission' => 0,
                ],
                [
                    'section' => 'producto_dinamico',
                    'name' => 'Mano de Obra Instalación',
                    'title' => 'Mano de Obra Instalación (Servicio)',
                    'quantity' => 1,
                    'amount' => 0.00, // Libre en $0.00
                    'original_amount' => 0.00,
                    'category_status' => 'Servicio',
                    'commission' => 0,
                ],
            ],
        ];

        $response = $this->postJson(route('central-pos.checkout'), $payload);

        $response->assertStatus(200);
        $response->assertJson(['success' => true]);

        // Verificar PosTransaction
        $txId = $response->json('transaction_id');
        $tx = PosTransaction::find($txId);
        $this->assertNotNull($tx);
        $this->assertEquals(0.00, (float) $tx->total_amount);
        $this->assertEquals(0.00, (float) $tx->paid_amount);

        // Verificar Order creada y que no hubo división por cero
        $order = Order::find($tx->order_id);
        $this->assertNotNull($order);
        $this->assertEquals(0.00, (float) $order->total);
        $this->assertEquals(0.00, (float) $order->pay);
        $this->assertEquals(0.00, (float) $order->due);
        $this->assertEquals('complete', $order->order_status);

        // Verificar OrderDetails
        $details = OrderDetails::where('order_id', $order->id)->get();
        $this->assertCount(2, $details);
        foreach ($details as $d) {
            $this->assertEquals(0.00, (float) $d->unitcost);
            $this->assertEquals(0.00, (float) $d->total);
        }

        // Verificar descuento de stock real
        $product->refresh();
        $this->assertEquals(9, $product->stock_quantity);

        // Verificar ticket de cobro
        $receiptResponse = $this->get(route('central-pos.receipt', $tx->id));
        $receiptResponse->assertStatus(200);
        $receiptResponse->assertSee('$0.00');
    }

    /**
     * Criterio 1 y 2: Venta de financiera con enganche $700 en plataforma pero el negocio no cobra enganche ($0.00).
     */
    public function test_criterion_1_and_2_financiera_sale_with_zero_down_payment_and_discount(): void
    {
        $this->actingAs($this->user);

        $imei = '3589' . rand(10000000000, 99999999999);
        $device = FinDevice::create([
            'brand_id' => $this->brand->id,
            'model' => 'Galaxy A15',
            'imei' => $imei,
            'color' => 'Azul',
            'status' => 'disponible',
            'branch_id' => $this->branch->id,
        ]);

        $tagContrato = 'PAY-' . rand(10000, 99999);

        // Caso real: En la plataforma PayJoy el enganche es $700, pero el negocio no lo cobra (enganche_descuento = 0)
        $saleData = [
            'device_id' => $device->id,
            'imei' => $imei,
            'model' => 'Galaxy A15',
            'brand_id' => $this->brand->id,
            'color' => 'Azul',
            'financiera_id' => $this->financiera->id,
            'branch_id' => $this->branch->id,
            'seller_id' => $this->user->id,
            'price' => 5000.00,
            'down_payment' => 700.00, // Enganche en plataforma
            'enganche_descuento' => 0.00, // Negocio no cobra enganche (descuento 100%)
            'credit_amount' => 4300.00,
            'abono_semanal' => 250.00,
            'term_weeks' => 24,
            'term_months' => 6,
            'tag_contrato' => $tagContrato,
            'sale_date' => Carbon::now()->format('Y-m-d H:i:s'),
            'customer_name' => 'María López',
            'customer_phone' => '8269998877',
            'customer_email' => 'maria@example.com',
            'customer_ine' => 'INE-ML-1234',
            'customer_address' => 'Col. Zaragoza, Allende',
            'customer_chip' => 'Telcel 8261110000',
            'customer_facebook' => 'maria.lopez.zya',
            'ref1_name' => 'Hermana López',
            'ref1_phone' => '8261113333',
            'ref2_name' => 'Tía López',
            'ref2_phone' => '8261114444',
            'ref3_name' => 'Vecino López',
            'ref3_phone' => '8261115555',
            'initial_note' => 'Venta PayJoy con enganche subsidiado $0 por promoción',
        ];

        $response = $this->post(route('financieras.ventas.store'), $saleData);

        $response->assertSessionHasNoErrors();
        $this->assertDatabaseHas('fin_sales', [
            'tag_contrato' => $tagContrato,
            'price' => 5000.00,
            'down_payment' => 700.00,
            'enganche_descuento' => 0.00,
        ]);

        $sale = FinSale::where('tag_contrato', $tagContrato)->first();

        // Criterio 3: El descuento aplicado se ve reflejado en los montos (show, table y pdf)
        $showResponse = $this->get(route('financieras.ventas.show', $sale->id));
        $showResponse->assertStatus(200);
        // Debe mostrar el enganche de plataforma $700.00 y el cobrado c/ descuento en $0.00
        $showResponse->assertSee('$700.00');
        $showResponse->assertSee('$0.00');
        $showResponse->assertSee('Descuento aplicado: $700.00');

        // Lookup en POS central devuelve enganche_descuento = 0.00 y effective_down_payment = 0
        $lookupResponse = $this->getJson(route('central-pos.lookup-financiera', ['q' => $tagContrato]));
        $lookupResponse->assertStatus(200);
        $results = $lookupResponse->json('results');
        $this->assertNotEmpty($results);
        $this->assertEquals(0.00, $results[0]['enganche_descuento']);
        $this->assertEquals(0.00, $results[0]['effective_down_payment']);
        $this->assertEquals(700.00, $results[0]['down_payment']);

        // Vista de tabla
        $indexResponse = $this->get(route('financieras.ventas.index'));
        $indexResponse->assertStatus(200);
        $indexResponse->assertSee('$0.00');
    }

    /**
     * Criterio 2: Registrar abono de financiera en $0.00 sin error de validación (refleja 0 y no 0.01).
     */
    public function test_criterion_2_financiera_abono_with_zero_amount(): void
    {
        $this->actingAs($this->user);

        $device = FinDevice::create([
            'brand_id' => $this->brand->id,
            'model' => 'Moto G54',
            'imei' => '3589' . rand(10000000000, 99999999999),
            'status' => 'vendido',
            'branch_id' => $this->branch->id,
        ]);

        $sale = FinSale::create([
            'sale_code' => 'FIN-' . date('Ymd') . '-TEST',
            'device_id' => $device->id,
            'financiera_id' => $this->financiera->id,
            'branch_id' => $this->branch->id,
            'price' => 4000.00,
            'down_payment' => 0.00,
            'enganche_descuento' => 0.00,
            'credit_amount' => 4000.00,
            'abono_semanal' => 200.00,
            'total_abonos_paid' => 0.00,
            'abonos_count' => 0,
            'term_weeks' => 20,
            'term_months' => 5,
            'tag_contrato' => 'TAG-TEST-' . rand(1000, 9999),
            'sale_date' => now(),
            'status' => 'activa',
            'customer_name' => 'Roberto Garza',
            'customer_phone' => '8261119999',
            'customer_email' => 'roberto@example.com',
            'customer_ine' => 'INE-RG-999',
            'customer_address' => 'Allende Centro',
            'customer_chip' => '8261119999',
            'customer_facebook' => 'roberto.garza',
            'ref1_name' => 'Ref 1',
            'ref1_phone' => '8261111111',
            'ref2_name' => 'Ref 2',
            'ref2_phone' => '8261112222',
            'ref3_name' => 'Ref 3',
            'ref3_phone' => '8261113333',
        ]);

        // 1. Probar en registerDirectFinancieraAbono con amount = 0
        $response = $this->post(route('financieras.ventas.abonos.store', $sale->id), [
            'amount' => 0,
            'commission_amount' => 0,
            'payment_method' => 'efectivo',
            'notes' => 'Abono en $0 de prueba',
        ]);

        $response->assertSessionHasNoErrors();

        // Verificar que FinSalePayment se registró con $0.00 y no $0.01
        $payment = FinSalePayment::where('fin_sale_id', $sale->id)->first();
        $this->assertNotNull($payment);
        $this->assertEquals(0.00, (float) $payment->amount);
        $this->assertEquals(0.00, (float) $payment->total_amount);

        // Verificar CashFlow
        $cf = CashFlow::where('reference', $payment->receipt_code)->first();
        $this->assertNotNull($cf);
        $this->assertEquals(0.00, (float) $cf->amount);

        // 2. Probar en checkout de Central POS con item financiera_abono en $0.00
        $posResponse = $this->postJson(route('central-pos.checkout'), [
            'branch_id' => $this->branch->id,
            'customer_name' => $sale->customer_name,
            'payment_method' => 'efectivo',
            'cash_amount' => 0,
            'items' => [
                [
                    'section' => 'financiera_abono',
                    'sale_id' => $sale->id,
                    'title' => "Abono Financiera TAG: {$sale->tag_contrato}",
                    'quantity' => 1,
                    'amount' => 0.00,
                    'commission' => 0,
                ],
            ],
        ]);

        $posResponse->assertStatus(200);
        $posResponse->assertJson(['success' => true]);
    }

    /**
     * Criterio 4: Las validaciones de montos negativos o vacíos siguen funcionando.
     */
    public function test_criterion_4_negative_or_empty_amounts_are_rejected(): void
    {
        $this->actingAs($this->user);

        // A) En registerDirectFinancieraAbono
        $device = FinDevice::create([
            'brand_id' => $this->brand->id,
            'model' => 'Moto G54',
            'imei' => '3589' . rand(10000000000, 99999999999),
            'status' => 'vendido',
            'branch_id' => $this->branch->id,
        ]);

        $sale = FinSale::create([
            'sale_code' => 'FIN-' . date('Ymd') . '-VAL',
            'device_id' => $device->id,
            'financiera_id' => $this->financiera->id,
            'branch_id' => $this->branch->id,
            'price' => 3000.00,
            'down_payment' => 0.00,
            'enganche_descuento' => 0.00,
            'credit_amount' => 3000.00,
            'abono_semanal' => 150.00,
            'term_weeks' => 20,
            'term_months' => 5,
            'tag_contrato' => 'TAG-VAL-' . rand(1000, 9999),
            'sale_date' => now(),
            'status' => 'activa',
            'customer_name' => 'Cliente Validación',
            'customer_phone' => '8261119999',
            'customer_email' => 'val@example.com',
            'customer_ine' => 'INE-VAL-999',
            'customer_address' => 'Allende Centro',
            'customer_chip' => '8261119999',
            'customer_facebook' => 'cliente.val',
            'ref1_name' => 'Ref 1',
            'ref1_phone' => '8261111111',
            'ref2_name' => 'Ref 2',
            'ref2_phone' => '8261112222',
            'ref3_name' => 'Ref 3',
            'ref3_phone' => '8261113333',
        ]);

        // Monto negativo debe ser rechazado
        $negResponse = $this->post(route('financieras.ventas.abonos.store', $sale->id), [
            'amount' => -50.00,
            'payment_method' => 'efectivo',
        ]);
        $negResponse->assertSessionHasErrors('amount');

        // Monto vacío debe ser rechazado
        $emptyResponse = $this->post(route('financieras.ventas.abonos.store', $sale->id), [
            'amount' => '',
            'payment_method' => 'efectivo',
        ]);
        $emptyResponse->assertSessionHasErrors('amount');

        // B) En Central POS checkout
        $negPosResponse = $this->postJson(route('central-pos.checkout'), [
            'branch_id' => $this->branch->id,
            'payment_method' => 'efectivo',
            'cash_amount' => -100, // Negativo
            'items' => [
                [
                    'section' => 'producto_dinamico',
                    'title' => 'Producto Prueba',
                    'quantity' => 1,
                    'amount' => 100,
                ],
            ],
        ]);
        $negPosResponse->assertStatus(422);

        // C) En FinSaleController store: enganche negativo o precio negativo
        $negSaleResponse = $this->post(route('financieras.ventas.store'), [
            'price' => -500,
            'down_payment' => -100,
            'enganche_descuento' => -50,
        ]);
        $negSaleResponse->assertSessionHasErrors(['price', 'down_payment', 'enganche_descuento']);
    }
}
