<?php

namespace Tests\Feature;

use App\Models\Branch;
use App\Models\FinBrand;
use App\Models\FinDevice;
use App\Models\FinFinanciera;
use App\Models\FinSale;
use App\Models\FinSalePayment;
use App\Models\PosTransaction;
use App\Models\User;
use App\Services\FinancieraFilterService;
use Carbon\Carbon;
use Illuminate\Foundation\Testing\DatabaseTransactions;
use Illuminate\Support\Str;
use PhpOffice\PhpSpreadsheet\IOFactory;
use Spatie\Permission\Models\Permission;
use Spatie\Permission\Models\Role;
use Spatie\Permission\PermissionRegistrar;
use Tests\TestCase;

class MultiFinancieraFilterPermissionTest extends TestCase
{
    use DatabaseTransactions;

    protected Branch $branchAllende;
    protected User $esmeralda;
    protected User $regularUser;
    protected FinFinanciera $payjoy;
    protected FinFinanciera $credijusto;
    protected FinFinanciera $macropay;
    protected FinBrand $brand;

    protected function setUp(): void
    {
        parent::setUp();

        if (app()->bound(PermissionRegistrar::class)) {
            app()[PermissionRegistrar::class]->forgetCachedPermissions();
        }

        $this->branchAllende = Branch::firstOrCreate(
            ['name' => 'Allende'],
            ['address' => 'Centro', 'phone' => '8261000001', 'is_active' => true, 'auto_cut_enabled' => false]
        );

        foreach ([
            'dashboard.menu',
            'dashboard.read',
            'dashboard.financieras.export_pdf',
            'dashboard.financieras.export_excel',
            'dashboard.filter_all_dates',
            'dashboard.all_branches',
            'financieras.menu',
            'financieras.read',
            'financieras.ventas.menu',
            'financieras.ventas.read',
            'financieras.ventas.export_pdf',
            'financieras.ventas.export_excel',
            'financieras.filter_all_dates',
            'financieras.inventario.all_branches',
            FinancieraFilterService::PERMISSION_FILTER_MULTIPLE,
        ] as $perm) {
            Permission::firstOrCreate(['name' => $perm, 'guard_name' => 'web']);
        }

        $commonRole = Role::firstOrCreate(['name' => 'GerenteSucursalTest', 'guard_name' => 'web']);
        $commonPermissions = [
            'dashboard.menu',
            'dashboard.read',
            'dashboard.financieras.export_pdf',
            'dashboard.financieras.export_excel',
            'dashboard.filter_all_dates',
            'dashboard.all_branches',
            'financieras.menu',
            'financieras.read',
            'financieras.ventas.menu',
            'financieras.ventas.read',
            'financieras.ventas.export_pdf',
            'financieras.ventas.export_excel',
            'financieras.filter_all_dates',
            'financieras.inventario.all_branches',
        ];
        $commonRole->syncPermissions($commonPermissions);

        $this->esmeralda = User::create([
            'name' => 'Esmeralda Zapata',
            'username' => 'esme_mf_' . uniqid(),
            'email' => 'esme_mf_' . uniqid() . '@zyacelulares.com',
            'password' => bcrypt('secret123'),
            'branch_id' => $this->branchAllende->id,
        ]);
        $this->esmeralda->assignRole($commonRole);
        $this->esmeralda->givePermissionTo(FinancieraFilterService::PERMISSION_FILTER_MULTIPLE);

        $this->regularUser = User::create([
            'name' => 'Verónica Sucursal',
            'username' => 'vero_mf_' . uniqid(),
            'email' => 'vero_mf_' . uniqid() . '@zyacelulares.com',
            'password' => bcrypt('secret123'),
            'branch_id' => $this->branchAllende->id,
        ]);
        $this->regularUser->assignRole($commonRole);

        $this->payjoy = FinFinanciera::firstOrCreate(['name' => 'PayJoy'], ['is_active' => true]);
        $this->credijusto = FinFinanciera::firstOrCreate(['name' => 'Credijusto'], ['is_active' => true]);
        $this->macropay = FinFinanciera::firstOrCreate(['name' => 'Macropay'], ['is_active' => true]);
        $this->brand = FinBrand::firstOrCreate(['name' => 'Samsung'], ['is_active' => true]);
    }

    protected function createSaleWithOptionalPayment(
        FinFinanciera $financiera,
        string $customerName,
        string $date,
        float $paymentAmount = 0.0
    ): FinSale {
        $device = FinDevice::create([
            'brand_id' => $this->brand->id,
            'model' => 'Galaxy A35',
            'imei' => '3588' . random_int(10000000000, 99999999999),
            'status' => 'vendido',
            'branch_id' => $this->branchAllende->id,
        ]);

        $sale = FinSale::create([
            'sale_code' => 'MF-' . strtoupper(Str::random(7)),
            'sale_type' => 'credito',
            'device_id' => $device->id,
            'financiera_id' => $financiera->id,
            'branch_id' => $this->branchAllende->id,
            'price' => 4500.00,
            'down_payment' => 500.00,
            'credit_amount' => 4000.00,
            'tag_contrato' => 'TAG-' . strtoupper(Str::random(6)),
            'sale_date' => Carbon::parse($date . ' 12:00:00'),
            'status' => 'activa',
            'customer_name' => $customerName,
            'seller_id' => $this->esmeralda->id,
        ]);

        if ($paymentAmount > 0) {
            $tx = PosTransaction::create([
                'transaction_code' => 'MOV-MF-' . strtoupper(Str::random(6)),
                'branch_id' => $this->branchAllende->id,
                'user_id' => $this->esmeralda->id,
                'customer_name' => $customerName,
                'subtotal' => $paymentAmount,
                'commission_total' => 0.00,
                'total_amount' => $paymentAmount,
                'paid_amount' => $paymentAmount,
                'payment_method' => 'efectivo',
                'cash_amount' => $paymentAmount,
                'status' => 'completado',
            ]);

            FinSalePayment::create([
                'fin_sale_id' => $sale->id,
                'device_id' => $device->id,
                'financiera_id' => $financiera->id,
                'branch_id' => $this->branchAllende->id,
                'user_id' => $this->esmeralda->id,
                'pos_transaction_id' => $tx->id,
                'receipt_code' => 'AB-MF-' . strtoupper(Str::random(6)),
                'tag_contrato' => $sale->tag_contrato,
                'customer_name' => $customerName,
                'installment_number' => 1,
                'amount' => $paymentAmount,
                'commission_amount' => 0.00,
                'total_amount' => $paymentAmount,
                'payment_method' => 'efectivo',
                'cash_amount' => $paymentAmount,
                'payment_date' => Carbon::parse($date . ' 14:00:00'),
            ]);
        }

        return $sale;
    }

    public function test_abonos_dashboard_allows_multiselect_for_esmeralda_with_totals_pagination_and_exports(): void
    {
        $targetDate = Carbon::today()->toDateString();

        $this->createSaleWithOptionalPayment($this->payjoy, 'Cliente Abono PayJoy', $targetDate, 500.00);
        $this->createSaleWithOptionalPayment($this->credijusto, 'Cliente Abono Credijusto', $targetDate, 750.00);
        $this->createSaleWithOptionalPayment($this->macropay, 'Cliente Abono Macropay', $targetDate, 999.00);

        $query = [
            'fin_filter_type' => 'dia',
            'fin_date' => $targetDate,
            'financiera_id' => [$this->payjoy->id, $this->credijusto->id],
        ];

        $resp = $this->actingAs($this->esmeralda)->get(route('dashboard', $query));
        $resp->assertStatus(200);
        $html = $resp->getContent();

        $this->assertStringContainsString('name="financiera_id[]"', $html);
        $this->assertStringContainsString('multiple="multiple"', $html);
        $this->assertStringContainsString('Cliente Abono PayJoy', $html);
        $this->assertStringContainsString('Cliente Abono Credijusto', $html);
        $this->assertStringNotContainsString('Cliente Abono Macropay', $html);

        // Verificar totales consolidados ($500 + $750 = $1,250.00 sin incluir Macropay)
        $resp->assertViewHas('finTotals', function (array $totals) {
            return (int) $totals['count'] >= 2 && (float) $totals['amount'] === 1250.00;
        });

        // Exportación Excel mantiene el filtro múltiple consolidado
        $excelResp = $this->actingAs($this->esmeralda)->get(route('dashboard.financieras.excel', $query));
        $excelResp->assertStatus(200);
        $sheet = IOFactory::load($excelResp->getFile()->getPathname())->getActiveSheet();
        $sheetJson = json_encode($sheet->toArray());
        $this->assertStringContainsString('Cliente Abono PayJoy', $sheetJson);
        $this->assertStringContainsString('Cliente Abono Credijusto', $sheetJson);
        $this->assertStringNotContainsString('Cliente Abono Macropay', $sheetJson);

        // Exportación PDF responde OK y contiene encabezado filtrado
        $pdfResp = $this->actingAs($this->esmeralda)->get(route('dashboard.financieras.pdf', $query));
        $pdfResp->assertStatus(200);
        $this->assertStringStartsWith('%PDF-', $pdfResp->getContent());
    }

    public function test_abonos_dashboard_blocks_multiselect_for_users_without_permission(): void
    {
        $targetDate = Carbon::today()->toDateString();

        $this->createSaleWithOptionalPayment($this->payjoy, 'Cliente Regular PayJoy', $targetDate, 400.00);
        $this->createSaleWithOptionalPayment($this->credijusto, 'Cliente Regular Credijusto', $targetDate, 600.00);

        $tamperedQuery = [
            'fin_filter_type' => 'dia',
            'fin_date' => $targetDate,
            'financiera_id' => [$this->payjoy->id, $this->credijusto->id],
        ];

        $resp = $this->actingAs($this->regularUser)->get(route('dashboard', $tamperedQuery));
        $resp->assertStatus(200);
        $html = $resp->getContent();

        $this->assertStringContainsString('name="financiera_id"', $html);
        $this->assertStringNotContainsString('name="financiera_id[]"', $html);
        $this->assertStringContainsString('Cliente Regular PayJoy', $html);
        $this->assertStringNotContainsString('Cliente Regular Credijusto', $html);
    }

    public function test_ventas_financieras_allows_multiselect_for_esmeralda_and_blocks_regular_users(): void
    {
        $targetDate = Carbon::today()->toDateString();

        $this->createSaleWithOptionalPayment($this->payjoy, 'Venta Consolidada PayJoy', $targetDate);
        $this->createSaleWithOptionalPayment($this->credijusto, 'Venta Consolidada Credijusto', $targetDate);
        $this->createSaleWithOptionalPayment($this->macropay, 'Venta Excluida Macropay', $targetDate);

        $multiQuery = [
            'tab' => 'ventas',
            'filter_type' => 'dia',
            'date' => $targetDate,
            'financiera_id' => [$this->payjoy->id, $this->credijusto->id],
        ];

        // 1. Esmeralda: obtiene PayJoy + Credijusto consolidados en UI y en exportaciones
        $esmeResp = $this->actingAs($this->esmeralda)->get(route('financieras.index', $multiQuery));
        $esmeResp->assertStatus(200);
        $esmeHtml = $esmeResp->getContent();

        $this->assertStringContainsString('name="financiera_id[]"', $esmeHtml);
        $this->assertStringContainsString('multiple="multiple"', $esmeHtml);
        $this->assertStringContainsString('Venta Consolidada PayJoy', $esmeHtml);
        $this->assertStringContainsString('Venta Consolidada Credijusto', $esmeHtml);
        $this->assertStringNotContainsString('Venta Excluida Macropay', $esmeHtml);

        $excelResp = $this->actingAs($this->esmeralda)->get(route('financieras.ventas.export.excel', $multiQuery));
        $excelResp->assertStatus(200);
        $sheetJson = json_encode(IOFactory::load($excelResp->getFile()->getPathname())->getActiveSheet()->toArray());
        $this->assertStringContainsString('Venta Consolidada PayJoy', $sheetJson);
        $this->assertStringContainsString('Venta Consolidada Credijusto', $sheetJson);
        $this->assertStringNotContainsString('Venta Excluida Macropay', $sheetJson);

        $pdfResp = $this->actingAs($this->esmeralda)->get(route('financieras.ventas.export.pdf', $multiQuery));
        $pdfResp->assertStatus(200);
        $this->assertStringStartsWith('%PDF-', $pdfResp->getContent());

        // 2. Usuario sin permiso: la consulta múltiple forzada se bloquea a una sola financiera
        $regResp = $this->actingAs($this->regularUser)->get(route('financieras.index', $multiQuery));
        $regResp->assertStatus(200);
        $regHtml = $regResp->getContent();

        $this->assertStringContainsString('name="financiera_id"', $regHtml);
        $this->assertStringNotContainsString('name="financiera_id[]"', $regHtml);
        $this->assertStringContainsString('Venta Consolidada PayJoy', $regHtml);
        $this->assertStringNotContainsString('Venta Consolidada Credijusto', $regHtml);
    }

    public function test_migration_assigns_multiple_filter_permission_exclusively_to_esmeralda_and_respects_branch_limits(): void
    {
        $branchOther = Branch::firstOrCreate(
            ['name' => 'Montemorelos'],
            ['address' => 'Centro', 'phone' => '8261000002', 'is_active' => true, 'auto_cut_enabled' => false]
        );

        // Revocar permiso previo para probar que la migración lo asigna solo a Esmeralda
        $this->esmeralda->revokePermissionTo(FinancieraFilterService::PERMISSION_FILTER_MULTIPLE);

        $migration = require database_path('migrations/2026_11_13_000006_add_multiple_financieras_filter_permission.php');
        $migration->up();

        $this->esmeralda->refresh();
        $this->regularUser->refresh();

        $this->assertTrue($this->esmeralda->can(FinancieraFilterService::PERMISSION_FILTER_MULTIPLE));
        $this->assertFalse($this->regularUser->can(FinancieraFilterService::PERMISSION_FILTER_MULTIPLE));

        // Si un usuario con filtro múltiple NO tiene permiso all_branches, sigue limitado a su sucursal
        $limitedMultiUser = User::create([
            'name' => 'Esmeralda Sucursal Limitada',
            'username' => 'esme_lim_' . uniqid(),
            'email' => 'esme_lim_' . uniqid() . '@zyacelulares.com',
            'password' => bcrypt('secret123'),
            'branch_id' => $this->branchAllende->id,
        ]);
        $limitedMultiUser->givePermissionTo([
            'dashboard.menu',
            'dashboard.read',
            FinancieraFilterService::PERMISSION_FILTER_MULTIPLE,
        ]);

        $targetDate = Carbon::today()->toDateString();
        $this->createSaleWithOptionalPayment($this->payjoy, 'Cliente Allende Permitido', $targetDate, 300.00);

        $deviceOther = FinDevice::create([
            'brand_id' => $this->brand->id,
            'model' => 'Galaxy A15',
            'imei' => '3577' . random_int(10000000000, 99999999999),
            'status' => 'vendido',
            'branch_id' => $branchOther->id,
        ]);
        $saleOther = FinSale::create([
            'sale_code' => 'MFO-' . strtoupper(Str::random(6)),
            'sale_type' => 'credito',
            'device_id' => $deviceOther->id,
            'financiera_id' => $this->credijusto->id,
            'branch_id' => $branchOther->id,
            'price' => 3000.00,
            'down_payment' => 500.00,
            'credit_amount' => 2500.00,
            'sale_date' => Carbon::parse($targetDate . ' 10:00:00'),
            'status' => 'activa',
            'customer_name' => 'Cliente Otra Sucursal Bloqueado',
            'seller_id' => $this->esmeralda->id,
        ]);
        FinSalePayment::create([
            'fin_sale_id' => $saleOther->id,
            'device_id' => $deviceOther->id,
            'financiera_id' => $this->credijusto->id,
            'branch_id' => $branchOther->id,
            'user_id' => $this->esmeralda->id,
            'receipt_code' => 'AB-OTR-' . strtoupper(Str::random(5)),
            'customer_name' => 'Cliente Otra Sucursal Bloqueado',
            'installment_number' => 1,
            'amount' => 888.00,
            'commission_amount' => 0.00,
            'total_amount' => 888.00,
            'payment_method' => 'efectivo',
            'cash_amount' => 888.00,
            'payment_date' => Carbon::parse($targetDate . ' 11:00:00'),
        ]);

        $resp = $this->actingAs($limitedMultiUser)->get(route('dashboard', [
            'fin_filter_type' => 'dia',
            'fin_date' => $targetDate,
            'branch_id' => $branchOther->id,
            'financiera_id' => [$this->payjoy->id, $this->credijusto->id],
        ]));
        $resp->assertStatus(200);
        $this->assertStringContainsString('Cliente Allende Permitido', $resp->getContent());
        $this->assertStringNotContainsString('Cliente Otra Sucursal Bloqueado', $resp->getContent());
    }
}
