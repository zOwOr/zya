<?php

namespace Tests\Feature;

use App\Models\Branch;
use App\Models\Category;
use App\Models\Customer;
use App\Models\Permission;
use App\Models\Product;
use App\Models\Supplier;
use App\Models\User;
use App\Services\ProductImportExportService;
use Gloudemans\Shoppingcart\Facades\Cart;
use Illuminate\Foundation\Testing\DatabaseTransactions;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\View;
use PhpOffice\PhpSpreadsheet\Cell\DataType;
use PhpOffice\PhpSpreadsheet\IOFactory;
use PhpOffice\PhpSpreadsheet\Spreadsheet;
use PhpOffice\PhpSpreadsheet\Writer\Xlsx;
use Spatie\Permission\Models\Role;
use Spatie\Permission\PermissionRegistrar;
use Tests\TestCase;

class ProductModuleAndImportTest extends TestCase
{
    use DatabaseTransactions;

    protected Branch $branchA;
    protected Branch $branchB;
    protected Category $catPhones;
    protected Category $catAcc;
    protected Supplier $supMain;
    protected Supplier $supAlt;

    protected function setUp(): void
    {
        parent::setUp();

        if (app()->bound(PermissionRegistrar::class)) {
            app()[PermissionRegistrar::class]->forgetCachedPermissions();
        }

        $this->branchA = Branch::firstOrCreate(
            ['name' => 'Allende'],
            ['address' => 'Centro', 'phone' => '8261000001', 'is_active' => true]
        );
        $this->branchB = Branch::firstOrCreate(
            ['name' => 'Montemorelos'],
            ['address' => 'Centro', 'phone' => '8261000002', 'is_active' => true]
        );

        $this->catPhones = Category::firstOrCreate(
            ['name' => 'Teléfonos'],
            ['slug' => 'telefonos']
        );
        $this->catAcc = Category::firstOrCreate(
            ['name' => 'Accesorios Electrónicos'],
            ['slug' => 'accesorios-electronicos']
        );

        $this->supMain = Supplier::create([
            'name' => 'Distribuidora Ágil ' . uniqid(),
            'shopname' => 'Mayorista Móvil ' . uniqid(),
            'email' => 'sup1_' . uniqid() . '@test.local',
            'phone' => '8110000001',
            'address' => 'Centro',
            'type' => 'Distributor',
        ]);
        $this->supAlt = Supplier::create([
            'name' => 'Importadora Beta ' . uniqid(),
            'shopname' => 'Tech Beta ' . uniqid(),
            'email' => 'sup2_' . uniqid() . '@test.local',
            'phone' => '8110000002',
            'address' => 'Norte',
            'type' => 'Whole Seller',
        ]);
    }

    protected function makeUserWithPermissions(array $permissionNames, ?int $branchId = null, ?string $roleName = null): User
    {
        $role = $roleName
            ? Role::firstOrCreate(['name' => $roleName, 'guard_name' => 'web'])
            : Role::create(['name' => 'TestProdRole_' . uniqid(), 'guard_name' => 'web']);

        if (!$roleName && !empty($permissionNames)) {
            $role->syncPermissions($permissionNames);
        }

        $user = User::create([
            'branch_id' => $branchId,
            'name' => 'User ' . $role->name,
            'username' => 'u_' . uniqid(),
            'email' => 'u_' . uniqid() . '@test.local',
            'password' => bcrypt('secret123'),
        ]);
        $user->assignRole($role);
        app()[PermissionRegistrar::class]->forgetCachedPermissions();

        return $user;
    }

    protected function saveSpreadsheetToTemp(Spreadsheet $spreadsheet): string
    {
        $tmpPath = tempnam(sys_get_temp_dir(), 'prod_imp_') . '.xlsx';
        (new Xlsx($spreadsheet))->save($tmpPath);

        return $tmpPath;
    }

    public function test_migration_permissions_exist_and_are_correctly_assigned_to_roles(): void
    {
        $this->assertTrue(Permission::where('name', 'product.import')->exists());
        $this->assertTrue(Permission::where('name', 'product.all_branches')->exists());
        $this->assertTrue(Permission::where('name', 'product.export')->exists());

        $superAdminRole = Role::where('name', 'SuperAdmin')->firstOrFail();
        $this->assertTrue($superAdminRole->hasPermissionTo('product.import'));
        $this->assertTrue($superAdminRole->hasPermissionTo('product.all_branches'));
        $this->assertTrue($superAdminRole->hasPermissionTo('product.export'));

        if ($adminRole = Role::where('name', 'Admin')->first()) {
            $this->assertTrue($adminRole->hasPermissionTo('product.import'));
        }

        $this->assertStringContainsString('Importar', Permission::defaultAlias('product.import'));
    }

    public function test_template_generation_includes_structure_text_formats_and_branch_scoped_catalogs(): void
    {
        $service = app(ProductImportExportService::class);
        $restrictedUser = $this->makeUserWithPermissions(
            ['product.menu', 'product.read', 'product.create', 'product.import'],
            $this->branchB->id
        );

        $spreadsheet = $service->generateTemplateSpreadsheet($restrictedUser);

        $this->assertSame(2, $spreadsheet->getSheetCount());
        $templateSheet = $spreadsheet->getSheet(0);
        $this->assertSame('Plantilla Productos', $templateSheet->getTitle());
        $this->assertSame('NOMBRE DEL PRODUCTO *', $templateSheet->getCell('A1')->getValue());
        $this->assertSame('CATEGORIA *', $templateSheet->getCell('B1')->getValue());
        $this->assertSame('PROVEEDOR *', $templateSheet->getCell('C1')->getValue());
        $this->assertSame('SUCURSAL', $templateSheet->getCell('D1')->getValue());
        $this->assertSame('CODIGO / SKU', $templateSheet->getCell('E1')->getValue());
        $this->assertSame($this->branchB->name, $templateSheet->getCell('D2')->getValue());
        $this->assertSame('@', $templateSheet->getStyle('E5')->getNumberFormat()->getFormatCode());
        $this->assertSame('@', $templateSheet->getStyle('I5')->getNumberFormat()->getFormatCode());

        // Hoja 2 solo expone sucursal asignada cuando el usuario no tiene product.all_branches
        $guideSheet = $spreadsheet->getSheet(1);
        $this->assertSame('Guía y Catálogos', $guideSheet->getTitle());
        $colAValues = implode(' | ', array_map(fn($row) => (string) ($row[0] ?? ''), $guideSheet->toArray()));
        $this->assertStringContainsString($this->branchB->name, $colAValues);
        $this->assertStringNotContainsString($this->branchA->name, $colAValues);
    }

    public function test_import_parses_spanish_headers_accent_insensitive_catalogs_dates_prices_and_auto_codes(): void
    {
        $service = app(ProductImportExportService::class);
        $superAdmin = $this->makeUserWithPermissions([], $this->branchA->id, 'SuperAdmin');

        $uniqueSku = '00' . random_int(1000, 9999);
        $uniqueImei = '866' . random_int(100000000000, 999999999999);

        $ss = new Spreadsheet();
        $sheet = $ss->getActiveSheet();
        $sheet->fromArray([[
            'NOMBRE DEL PRODUCTO *', 'CATEGORIA *', 'PROVEEDOR *', 'SUCURSAL',
            'CODIGO / SKU', 'PRECIO COMPRA *', 'PRECIO VENTA *', 'STOCK *',
            'IMEI / SERIE', 'MARCA', 'MODELO', 'CONDICION', 'GARANTIA',
            'ALMACEN / PASILLO', 'SECCION / TIENDA', 'FECHA COMPRA', 'FECHA EXPIRACION', 'OBSERVACIONES',
        ]], null, 'A1');

        // Fila 2: Sin acentos ("telefonos"), shopname proveedor, "$3,250.50", IMEI numérico largo, fecha d/m/Y
        $sheet->setCellValue('A2', 'Redmi Note 14 Pro Test');
        $sheet->setCellValue('B2', 'telefonos');
        $sheet->setCellValue('C2', mb_strtolower($this->supMain->shopname, 'UTF-8'));
        $sheet->setCellValue('D2', mb_strtolower($this->branchB->name, 'UTF-8'));
        $sheet->setCellValueExplicit('E2', $uniqueSku, DataType::TYPE_STRING);
        $sheet->setCellValue('F2', '$3,250.50');
        $sheet->setCellValue('G2', '$4,999.00');
        $sheet->setCellValue('H2', 4);
        $sheet->setCellValueExplicit('I2', $uniqueImei, DataType::TYPE_STRING);
        $sheet->setCellValue('J2', 'Xiaomi');
        $sheet->setCellValue('K2', 'Note 14');
        $sheet->setCellValue('L2', 'Nuevo');
        $sheet->setCellValue('M2', '12 meses');
        $sheet->setCellValue('N2', 'Vitrina 1');
        $sheet->setCellValue('O2', 'Piso Venta');
        $sheet->setCellValue('P2', '15/11/2026');
        $sheet->setCellValue('Q2', '2027-11-15');
        $sheet->setCellValue('R2', 'Equipo nuevo sellado');

        // Fila 3: Sin SKU y sin sucursal -> autogenera código PCxxxx y usa defaultBranchId (branchA), IMEI numérico sin notación científica
        $sheet->setCellValue('A3', 'Cable USB-C Test AutoSKU');
        $sheet->setCellValue('B3', (string) $this->catAcc->id);
        $sheet->setCellValue('C3', $this->supAlt->name);
        $sheet->setCellValue('F3', '45');
        $sheet->setCellValue('G3', '120');
        $sheet->setCellValue('H3', 20);
        $sheet->setCellValue('I3', 123456789012);

        // Fila 4: Vacía (debe omitirse sin error)
        $sheet->setCellValue('A4', '');

        $tmpPath = $this->saveSpreadsheetToTemp($ss);

        try {
            $result = $service->importFromPath($tmpPath, $superAdmin, $this->branchA->id, 'reject');
            $this->assertTrue($result['ok'], implode(' | ', $result['errors']));
            $this->assertSame(2, $result['inserted']);
            $this->assertSame(1, $result['skipped_empty']);

            $redmi = Product::where('product_code', $uniqueSku)->where('branch_id', $this->branchB->id)->firstOrFail();
            $this->assertSame('Redmi Note 14 Pro Test', $redmi->product_name);
            $this->assertSame($this->catPhones->id, $redmi->category_id);
            $this->assertSame($this->supMain->id, $redmi->supplier_id);
            $this->assertSame(3250.50, $redmi->buying_price);
            $this->assertSame(4999.00, $redmi->selling_price);
            $this->assertSame(4, $redmi->stock_quantity);
            $this->assertSame($uniqueImei, $redmi->imei);
            $this->assertSame('Xiaomi', $redmi->brand);
            $this->assertSame('Note 14', $redmi->model);
            $this->assertSame('Nuevo', $redmi->category_status);
            $this->assertSame('12 meses', $redmi->warranty_time);
            $this->assertSame('2026-11-15', $redmi->buying_date);
            $this->assertSame('2027-11-15', $redmi->expire_date);
            $this->assertSame('Equipo nuevo sellado', $redmi->observations);

            $cable = Product::where('product_name', 'Cable USB-C Test AutoSKU')->firstOrFail();
            $this->assertSame($this->branchA->id, $cable->branch_id);
            $this->assertMatchesRegularExpression('/^PC\d{4,}$/', $cable->product_code);
            $this->assertSame('123456789012', $cable->imei);
        } finally {
            @unlink($tmpPath);
        }
    }

    public function test_import_rejects_cross_branch_for_restricted_user_and_rolls_back_atomically(): void
    {
        $service = app(ProductImportExportService::class);
        $branchAUser = $this->makeUserWithPermissions(
            ['product.menu', 'product.read', 'product.create', 'product.import'],
            $this->branchA->id
        );

        $ss = new Spreadsheet();
        $ss->getActiveSheet()->fromArray([
            ['NOMBRE DEL PRODUCTO *', 'CATEGORIA *', 'PROVEEDOR *', 'SUCURSAL', 'PRECIO COMPRA *', 'PRECIO VENTA *', 'STOCK *'],
            ['Funda Válida Atomic', $this->catAcc->name, $this->supMain->name, $this->branchA->name, 30, 80, 10],
            ['Funda Otra Sucursal', $this->catAcc->name, $this->supMain->name, $this->branchB->name, 30, 80, 10],
            ['Funda Categoría Mala', 'Categoria Inventada Inexistente', $this->supMain->name, $this->branchA->name, -5, 80, 2],
        ], null, 'A1');

        $tmpPath = $this->saveSpreadsheetToTemp($ss);
        $beforeCount = Product::count();

        try {
            $result = $service->importFromPath($tmpPath, $branchAUser, null, 'reject');
            $this->assertFalse($result['ok']);
            $this->assertSame($beforeCount, Product::count(), 'No debe persistir registros parciales si falla cualquier fila');

            $joinedErrors = implode("\n", $result['errors']);
            $this->assertStringContainsString('Fila 3:', $joinedErrors);
            $this->assertStringContainsString('No tienes permiso para importar productos a la sucursal', $joinedErrors);
            $this->assertStringContainsString('Fila 4:', $joinedErrors);
            $this->assertStringContainsString('no existe en el catálogo', $joinedErrors);
            $this->assertStringContainsString('PRECIO COMPRA', $joinedErrors);
        } finally {
            @unlink($tmpPath);
        }
    }

    public function test_import_duplicate_mode_update_and_multi_branch_sku_independence(): void
    {
        $service = app(ProductImportExportService::class);
        $superAdmin = $this->makeUserWithPermissions([], $this->branchA->id, 'SuperAdmin');

        $sharedSku = 'SKU-' . uniqid();
        Product::create([
            'branch_id' => $this->branchA->id,
            'product_name' => 'Base Matriz',
            'category_id' => $this->catAcc->id,
            'supplier_id' => $this->supMain->id,
            'product_code' => $sharedSku,
            'buying_price' => 50,
            'selling_price' => 100,
            'stock_quantity' => 5,
        ]);

        $ss = new Spreadsheet();
        $ss->getActiveSheet()->fromArray([
            ['NOMBRE DEL PRODUCTO *', 'CATEGORIA *', 'PROVEEDOR *', 'SUCURSAL', 'CODIGO / SKU', 'PRECIO COMPRA *', 'PRECIO VENTA *', 'STOCK *'],
            ['Base Matriz Actualizada', $this->catAcc->name, $this->supMain->name, $this->branchA->name, $sharedSku, 60, 125, 18],
            ['Mismo SKU Sucursal B', $this->catAcc->name, $this->supAlt->name, $this->branchB->name, $sharedSku, 62, 130, 9],
        ], null, 'A1');

        $tmpPath = $this->saveSpreadsheetToTemp($ss);

        try {
            $result = $service->importFromPath($tmpPath, $superAdmin, null, 'update');
            $this->assertTrue($result['ok'], implode(' | ', $result['errors']));
            $this->assertSame(1, $result['updated']);
            $this->assertSame(1, $result['inserted']);

            $inA = Product::where('branch_id', $this->branchA->id)->where('product_code', $sharedSku)->firstOrFail();
            $this->assertSame('Base Matriz Actualizada', $inA->product_name);
            $this->assertSame(18, $inA->stock_quantity);

            $inB = Product::where('branch_id', $this->branchB->id)->where('product_code', $sharedSku)->firstOrFail();
            $this->assertSame('Mismo SKU Sucursal B', $inB->product_name);
            $this->assertSame(9, $inB->stock_quantity);
        } finally {
            @unlink($tmpPath);
        }
    }

    public function test_http_routes_permissions_multibranch_crud_and_pos_checkout_branch_guard(): void
    {
        $prodA = Product::create([
            'branch_id' => $this->branchA->id,
            'product_name' => 'Producto Matriz Visibilidad ' . uniqid(),
            'category_id' => $this->catAcc->id,
            'supplier_id' => $this->supMain->id,
            'product_code' => 'PCA-' . uniqid(),
            'buying_price' => 50,
            'selling_price' => 90,
            'stock_quantity' => 5,
        ]);

        $prodB = Product::create([
            'branch_id' => $this->branchB->id,
            'product_name' => 'Producto Exclusivo Norte ' . uniqid(),
            'category_id' => $this->catPhones->id,
            'supplier_id' => $this->supMain->id,
            'product_code' => 'PCB-' . uniqid(),
            'imei' => '3599' . random_int(10000000000, 99999999999),
            'buying_price' => 1000,
            'selling_price' => 1800,
            'stock_quantity' => 7,
        ]);

        // 1. Usuario solo lectura NO puede abrir import, plantilla ni CRUD de escritura
        $reader = $this->makeUserWithPermissions(
            ['product.menu', 'product.read', 'cobros.read', 'cobros.create', 'cobros.productos', 'stock.read', 'orders.read'],
            $this->branchA->id
        );
        $this->actingAs($reader)
            ->get(route('products.index'))
            ->assertOk()
            ->assertSee($prodA->product_name)
            ->assertDontSee($prodB->product_name);

        $this->actingAs($reader)
            ->get(route('order.stockManage'))
            ->assertOk()
            ->assertSee($prodA->product_name)
            ->assertDontSee($prodB->product_name);

        $this->actingAs($reader)->get(route('products.importView'))->assertStatus(403);
        $this->actingAs($reader)->get(route('products.downloadTemplate'))->assertStatus(403);
        $this->actingAs($reader)->get(route('products.create'))->assertStatus(403);

        // 2. Usuario de Sucursal A con create + import NO puede acceder a producto de Sucursal B
        $branchAWorker = $this->makeUserWithPermissions(
            ['product.menu', 'product.read', 'product.create', 'product.edit', 'product.delete', 'product.import'],
            $this->branchA->id
        );
        $this->actingAs($branchAWorker)->get(route('products.importView'))
            ->assertOk()
            ->assertSee('Estructura Esperada de Columnas')
            ->assertSee('Descargar Plantilla Excel (.xlsx)');

        $tplResp = $this->actingAs($branchAWorker)->get(route('products.downloadTemplate'));
        $tplResp->assertOk();
        $this->assertStringStartsWith('PK', $tplResp->streamedContent(), 'Debe descargar archivo XLSX válido');

        $this->actingAs($branchAWorker)->get(route('products.show', $prodB->id))->assertStatus(403);
        $this->actingAs($branchAWorker)->get(route('products.edit', $prodB->id))->assertStatus(403);
        $this->actingAs($branchAWorker)->delete(route('products.destroy', $prodB->id))->assertStatus(403);

        // Envío malicioso intentando inyectar branch_id de Sucursal B -> debe forzarse a Sucursal A
        $uniqueImei = '3512' . random_int(10000000000, 99999999999);
        $this->actingAs($branchAWorker)->post(route('products.store'), [
            'branch_id' => $this->branchB->id,
            'product_name' => 'Moto G85 HTTP ' . uniqid(),
            'category_id' => $this->catPhones->id,
            'supplier_id' => $this->supMain->id,
            'buying_price' => 3500,
            'selling_price' => 4800,
            'stock_quantity' => 3,
            'imei' => $uniqueImei,
            'brand' => 'Motorola',
            'model' => 'XT2427',
            'category_status' => 'Nuevo',
            'warranty_time' => '12 meses',
            'observations' => 'Sellado de fábrica',
        ])->assertRedirect(route('products.index'));

        $created = Product::where('imei', $uniqueImei)->firstOrFail();
        $this->assertSame($this->branchA->id, $created->branch_id);
        $this->assertSame('Motorola', $created->brand);
        $this->assertSame('12 meses', $created->warranty_time);

        // 3. POS lookupProducts y checkout bloquean stock de otra sucursal
        $posLookupA = $this->actingAs($reader)
            ->getJson(route('central-pos.lookup-products', ['q' => $prodB->imei, 'branch_id' => $this->branchB->id]))
            ->assertOk()
            ->json('products');
        $this->assertCount(0, $posLookupA, 'Cajero de Sucursal A no debe encontrar productos de Sucursal B');

        $customer = Customer::create([
            'tit_name' => 'Cliente Test POS Productos',
            'tit_email' => 'cli_' . uniqid() . '@test.local',
            'tit_phone' => '8260001122',
            'branch_id' => $this->branchA->id,
        ]);

        $crossCheckout = $this->actingAs($reader)->postJson(route('central-pos.checkout'), [
            'branch_id' => $this->branchA->id,
            'customer_id' => $customer->id,
            'customer_name' => $customer->tit_name,
            'customer_phone' => $customer->tit_phone,
            'payment_method' => 'efectivo',
            'cash_amount' => 1800,
            'paid_amount' => 1800,
            'items' => [[
                'section' => 'producto_inventario',
                'product_id' => $prodB->id,
                'quantity' => 1,
                'amount' => 1800,
            ]],
        ]);
        $crossCheckout->assertStatus(422);
        $this->assertStringContainsString('otra sucursal', (string) $crossCheckout->json('message'));

        // 4. SuperAdmin consulta en Sucursal B y exporta filtrado por sucursal
        $superAdmin = $this->makeUserWithPermissions([], $this->branchA->id, 'SuperAdmin');
        $posLookupSuper = $this->actingAs($superAdmin)
            ->getJson(route('central-pos.lookup-products', ['q' => $prodB->imei, 'branch_id' => $this->branchB->id]))
            ->assertOk()
            ->json('products');
        $this->assertCount(1, $posLookupSuper);
        $this->assertSame($prodB->id, $posLookupSuper[0]['id']);

        $httpSku = 'HTTP-' . uniqid();
        $ss = new Spreadsheet();
        $ss->getActiveSheet()->fromArray([
            ['NOMBRE DEL PRODUCTO *', 'CATEGORIA *', 'PROVEEDOR *', 'SUCURSAL', 'CODIGO / SKU', 'PRECIO COMPRA *', 'PRECIO VENTA *', 'STOCK *'],
            ['Cargador HTTP Importado', $this->catAcc->name, $this->supMain->name, $this->branchA->name, $httpSku, 90, 199, 6],
        ], null, 'A1');
        $tmpUpload = $this->saveSpreadsheetToTemp($ss);

        try {
            $uploadFile = new UploadedFile($tmpUpload, 'carga.xlsx', 'application/vnd.openxmlformats-officedocument.spreadsheetml.sheet', null, true);
            $this->actingAs($branchAWorker)->post(route('products.importStore'), [
                'upload_file' => $uploadFile,
                'duplicate_mode' => 'reject',
            ])->assertRedirect(route('products.index'));

            $this->assertTrue(Product::where('product_code', $httpSku)->where('branch_id', $this->branchA->id)->exists());
        } finally {
            @unlink($tmpUpload);
        }

        $exportResp = $this->actingAs($superAdmin)->get(route('products.exportData', ['branch_id' => $this->branchB->id, 'search' => $prodB->product_code]));
        $exportResp->assertOk();
        $tmpExp = tempnam(sys_get_temp_dir(), 'exp_verify_') . '.xlsx';
        file_put_contents($tmpExp, $exportResp->streamedContent());
        try {
            $loadedExport = IOFactory::load($tmpExp);
            $rows = $loadedExport->getActiveSheet()->toArray();
            $this->assertCount(2, $rows); // Header + prodB
            $this->assertSame($this->branchB->name, $rows[1][3]);
            $this->assertSame($prodB->product_code, $rows[1][4]);
        } finally {
            @unlink($tmpExp);
        }
    }

    public function test_product_and_stock_blade_views_display_real_error_flash_and_fields(): void
    {
        $superAdmin = $this->makeUserWithPermissions([], $this->branchA->id, 'SuperAdmin');
        $this->actingAs($superAdmin);

        session()->flash('error', 'Error real visible de prueba');

        $products = Product::with(['branch', 'category', 'supplier'])->paginate(10);

        $indexHtml = View::make('products.index', [
            'products' => $products,
            'branches' => Branch::all(),
            'categories' => Category::all(),
            'canAllBranches' => true,
            'selectedBranchId' => null,
        ])->render();
        $this->assertStringContainsString('Error real visible de prueba', $indexHtml);

        $stockHtml = View::make('stock.index', [
            'products' => $products,
            'branches' => Branch::all(),
            'canAllBranches' => true,
            'selectedBranchId' => null,
        ])->render();
        $this->assertStringContainsString('Error real visible de prueba', $stockHtml);
    }

    public function test_product_code_generation_scales_beyond_99_and_enforces_barcode_safe_ascii(): void
    {
        $reserved = ['PC0098' => true, 'PC0099' => true];
        $max = 99;
        $this->assertSame('PC0100', Product::generateUniqueCode($reserved, $max));
        $this->assertSame('PC0101', Product::generateUniqueCode($reserved, $max));

        $dbReserved = [];
        $dbMax = 0;
        $auto1 = Product::generateUniqueCode($dbReserved, $dbMax);
        $auto2 = Product::generateUniqueCode($dbReserved, $dbMax);
        $this->assertMatchesRegularExpression('/^PC\d{4,}$/', $auto1);
        $this->assertNotSame($auto1, $auto2);

        $this->assertTrue(Product::isBarcodeSafe('PC0100'));
        $this->assertTrue(Product::isBarcodeSafe('SKU-2026_A/1'));
        $this->assertFalse(Product::isBarcodeSafe('CÓDIGO-1'));
        $this->assertFalse(Product::isBarcodeSafe('SKU-Ñ01'));
        $this->assertSame('CODIGO-N1', Product::toBarcodeSafeText('CÓDIGO-Ñ1'));

        $creator = $this->makeUserWithPermissions(
            ['product.menu', 'product.read', 'product.create', 'product.import'],
            $this->branchA->id
        );

        // El formulario HTTP debe rechazar códigos con caracteres no compatibles con Code-128
        $this->actingAs($creator)->post(route('products.store'), [
            'product_name' => 'Audífono Inválido',
            'category_id' => $this->catAcc->id,
            'supplier_id' => $this->supMain->id,
            'product_code' => 'CÁMARA-Ñ01',
            'buying_price' => 100,
            'selling_price' => 200,
            'stock_quantity' => 5,
        ])->assertSessionHasErrors('product_code');

        // La importación Excel también debe rechazar códigos no ASCII imprimibles
        $service = app(ProductImportExportService::class);
        $ss = new Spreadsheet();
        $ss->getActiveSheet()->fromArray([
            ['NOMBRE DEL PRODUCTO *', 'CATEGORIA *', 'PROVEEDOR *', 'SUCURSAL', 'CODIGO / SKU', 'PRECIO COMPRA *', 'PRECIO VENTA *', 'STOCK *'],
            ['Funda Código Inválido', $this->catAcc->name, $this->supMain->name, $this->branchA->name, 'CÓDIGO-Ñ99', 50, 120, 4],
        ], null, 'A1');
        $tmpPath = $this->saveSpreadsheetToTemp($ss);

        try {
            $result = $service->importFromPath($tmpPath, $creator, $this->branchA->id, 'reject');
            $this->assertFalse($result['ok']);
            $this->assertNotEmpty($result['errors']);
            $this->assertStringContainsString('no compatibles con código de barras', implode(' ', $result['errors']));
        } finally {
            @unlink($tmpPath);
        }
    }

    public function test_product_show_persists_missing_barcode_and_barcode_labels_supports_stock_manual_and_branch_isolation(): void
    {
        $prodMissingCode = Product::create([
            'branch_id' => $this->branchA->id,
            'product_name' => 'Cable USB-C Sin Codigo ' . uniqid(),
            'category_id' => $this->catAcc->id,
            'supplier_id' => $this->supMain->id,
            'product_code' => '',
            'buying_price' => 30,
            'selling_price' => 99,
            'stock_quantity' => 4,
        ]);

        $prodZeroStock = Product::create([
            'branch_id' => $this->branchA->id,
            'product_name' => 'Producto Sin Existencia ' . uniqid(),
            'category_id' => $this->catAcc->id,
            'supplier_id' => $this->supMain->id,
            'product_code' => 'ZERO-' . random_int(10000, 99999),
            'buying_price' => 40,
            'selling_price' => 120,
            'stock_quantity' => 0,
        ]);

        $prodOtherBranch = Product::create([
            'branch_id' => $this->branchB->id,
            'product_name' => 'Producto Sucursal B ' . uniqid(),
            'category_id' => $this->catAcc->id,
            'supplier_id' => $this->supMain->id,
            'product_code' => 'BRB-' . random_int(10000, 99999),
            'buying_price' => 50,
            'selling_price' => 150,
            'stock_quantity' => 3,
        ]);

        $readerA = $this->makeUserWithPermissions(['product.menu', 'product.read'], $this->branchA->id);
        $noProductPerm = $this->makeUserWithPermissions(['pos.menu', 'pos.read'], $this->branchA->id);

        // Al ver detalle del producto sin SKU, debe autogenerar y persistir un PCxxxx válido + SVG
        $showResp = $this->actingAs($readerA)->get(route('products.show', $prodMissingCode->id));
        $showResp->assertOk();
        $prodMissingCode->refresh();
        $this->assertMatchesRegularExpression('/^PC\d{4,}$/', (string) $prodMissingCode->product_code);
        $this->assertTrue(Product::isBarcodeSafe($prodMissingCode->product_code));
        $showResp->assertSee($prodMissingCode->product_code)
            ->assertSee('<svg', false)
            ->assertSee('Imprimir Etiquetas');

        // Aislamiento de permisos y sucursal en etiquetas
        $this->actingAs($noProductPerm)->get(route('products.barcodeLabels', $prodMissingCode->id))->assertStatus(403);
        $this->actingAs($readerA)->get(route('products.barcodeLabels', $prodOtherBranch->id))->assertStatus(403);

        // Modo stock: prepara vista previa con 4 etiquetas según stock_quantity = 4 y plantilla clonar
        $stockLabelsResp = $this->actingAs($readerA)->get(route('products.barcodeLabels', [
            'product' => $prodMissingCode->id,
            'mode' => 'stock',
        ]));
        $stockLabelsResp->assertOk()
            ->assertViewHas('initialMode', 'stock')
            ->assertViewHas('initialQuantity', 4)
            ->assertViewHas('stockQty', 4)
            ->assertSee('modeStockRadio')
            ->assertSee('modeManualRadio')
            ->assertSee('labelQuantityInput')
            ->assertSee('labelPresetSelect')
            ->assertSee('singleBarcodeLabelTemplate')
            ->assertSee('<strong id="previewCountLabel">4</strong>', false)
            ->assertSee('<svg', false)
            ->assertSee($prodMissingCode->product_code);

        // Modo manual con cantidad explícita (12) y preset térmico 50x25mm
        $manualLabelsResp = $this->actingAs($readerA)->get(route('products.barcodeLabels', [
            'product' => $prodMissingCode->id,
            'mode' => 'manual',
            'quantity' => 12,
            'preset' => 'thermal_50x25',
        ]));
        $manualLabelsResp->assertOk()
            ->assertViewHas('initialMode', 'manual')
            ->assertViewHas('initialQuantity', 12)
            ->assertViewHas('initialPreset', 'thermal_50x25')
            ->assertSee('<strong id="previewCountLabel">12</strong>', false)
            ->assertSee('50mm 25mm');

        // Producto con stock 0 en modo stock muestra alerta #zeroStockAlert
        $zeroResp = $this->actingAs($readerA)->get(route('products.barcodeLabels', [
            'product' => $prodZeroStock->id,
            'mode' => 'stock',
        ]));
        $zeroResp->assertOk()->assertSee('zeroStockAlert');
    }

    public function test_pos_barcode_lookup_ranking_id_fallback_scanner_view_and_legacy_scan_barcode(): void
    {
        $prefix = 'SCN' . random_int(10000, 99999);
        $partialImei = '3588' . random_int(10000000000, 99999999999);

        // Producto alfabéticamente primero ('Alpha...') con código que sólo contiene el prefijo
        $partialMatch = Product::create([
            'branch_id' => $this->branchA->id,
            'product_name' => 'Alpha Prefijo ' . uniqid(),
            'category_id' => $this->catAcc->id,
            'supplier_id' => $this->supMain->id,
            'product_code' => $prefix . '9',
            'buying_price' => 100,
            'selling_price' => 250,
            'stock_quantity' => 5,
        ]);

        // Producto alfabéticamente después ('Zulu...') cuyo product_code ES exacto
        $exactMatch = Product::create([
            'branch_id' => $this->branchA->id,
            'product_name' => 'Zulu Exacto ' . uniqid(),
            'category_id' => $this->catPhones->id,
            'supplier_id' => $this->supMain->id,
            'product_code' => $prefix,
            'imei' => $partialImei,
            'buying_price' => 1500,
            'selling_price' => 2400,
            'stock_quantity' => 2,
        ]);

        $zeroStockProd = Product::create([
            'branch_id' => $this->branchA->id,
            'product_name' => 'Agotado Escaner ' . uniqid(),
            'category_id' => $this->catAcc->id,
            'supplier_id' => $this->supMain->id,
            'product_code' => 'OUT' . random_int(10000, 99999),
            'buying_price' => 50,
            'selling_price' => 100,
            'stock_quantity' => 0,
        ]);

        $otherBranchProd = Product::create([
            'branch_id' => $this->branchB->id,
            'product_name' => 'Ajeno Sucursal B ' . uniqid(),
            'category_id' => $this->catAcc->id,
            'supplier_id' => $this->supMain->id,
            'product_code' => 'BRB' . random_int(10000, 99999),
            'buying_price' => 50,
            'selling_price' => 120,
            'stock_quantity' => 10,
        ]);

        $cashier = $this->makeUserWithPermissions(
            ['cobros.menu', 'cobros.read', 'cobros.create', 'cobros.productos', 'pos.menu', 'pos.read', 'pos.create'],
            $this->branchA->id
        );

        // 1. UnifiedPOS lookup-products prioriza coincidencia exacta sobre coincidencia por prefijo aunque alfabéticamente vaya después
        $lookup = $this->actingAs($cashier)
            ->getJson(route('central-pos.lookup-products', ['q' => $prefix]))
            ->assertOk()
            ->json('products');

        $this->assertGreaterThanOrEqual(2, count($lookup));
        $this->assertSame($exactMatch->id, $lookup[0]['id']);
        $this->assertSame($partialMatch->id, $lookup[1]['id']);

        // 2. Soporta resolución por formato legado ID-<id>
        $idFallback = $this->actingAs($cashier)
            ->getJson(route('central-pos.lookup-products', ['q' => 'ID-' . $exactMatch->id]))
            ->assertOk()
            ->json('products');
        $this->assertSame($exactMatch->id, $idFallback[0]['id']);

        // 3. La vista central-pos contiene la integración de escaneo directo al carrito y consulta remota
        $posHtml = $this->actingAs($cashier)->get(route('central-pos.index'));
        $posHtml->assertOk()
            ->assertSee('posProductSearchInput')
            ->assertSee('posScanStatus')
            ->assertSee('PRODUCTS_LOOKUP_URL')
            ->assertSee('INITIAL_CATALOG_PRODUCTS')
            ->assertSee('handleBarcodeScan');

        // 4. PosController::scanBarcode (flujo clásico) escanea case-insensitive por código o IMEI, valida stock y sucursal
        Cart::instance('default')->destroy();

        // Escaneo en minúsculas añade 1 unidad al carrito
        $this->actingAs($cashier)
            ->post(route('pos.scanBarcode'), ['barcode' => strtolower($prefix)])
            ->assertRedirect()
            ->assertSessionHas('success');
        $this->assertSame(1, (int) Cart::instance('default')->count());

        // Segundo escaneo usando IMEI del mismo producto incrementa a 2 (su stock máximo)
        $this->actingAs($cashier)
            ->post(route('pos.scanBarcode'), ['barcode' => $partialImei])
            ->assertRedirect()
            ->assertSessionHas('success');
        $this->assertSame(2, (int) Cart::instance('default')->count());

        // Tercer escaneo excede stock_quantity (=2) -> warning sin incrementar carrito
        $this->actingAs($cashier)
            ->post(route('pos.scanBarcode'), ['barcode' => $prefix])
            ->assertRedirect()
            ->assertSessionHas('warning');
        $this->assertSame(2, (int) Cart::instance('default')->count());

        // Producto con stock 0 -> warning
        $this->actingAs($cashier)
            ->post(route('pos.scanBarcode'), ['barcode' => $zeroStockProd->product_code])
            ->assertRedirect()
            ->assertSessionHas('warning');

        // Producto de otra sucursal -> warning
        $this->actingAs($cashier)
            ->post(route('pos.scanBarcode'), ['barcode' => $otherBranchProd->product_code])
            ->assertRedirect()
            ->assertSessionHas('warning');

        Cart::instance('default')->destroy();
    }
}
