<?php

namespace Tests\Feature;

use App\Models\Branch;
use App\Models\CashFlow;
use App\Models\Customer;
use App\Models\DailyCut;
use App\Models\FinDevice;
use App\Models\FinFinanciera;
use App\Models\FinSale;
use App\Models\FinSalePayment;
use App\Models\Gift;
use App\Models\GiftMovement;
use App\Models\Loan;
use App\Models\LoanInstallment;
use App\Models\LoanPayment;
use App\Models\LoanType;
use App\Models\Order;
use App\Models\PosTransaction;
use App\Models\User;
use Barryvdh\DomPDF\Facade\Pdf;
use Carbon\Carbon;
use Illuminate\Foundation\Testing\DatabaseTransactions;
use Illuminate\Support\Str;
use PhpOffice\PhpSpreadsheet\IOFactory;
use Spatie\Permission\Models\Role;
use Tests\TestCase;

class ErpRequestedFeaturesTest extends TestCase
{
    use DatabaseTransactions;

    protected Branch $branchAllende;
    protected Branch $branchMorelos;
    protected Branch $branchCercado;
    protected User $user;
    protected Customer $customer;

    protected function setUp(): void
    {
        parent::setUp();

        $role = Role::firstOrCreate(['name' => 'SuperAdmin', 'guard_name' => 'web']);

        $this->branchAllende = Branch::firstOrCreate(
            ['name' => 'Allende'],
            ['address' => 'Centro Allende', 'phone' => '8261234567']
        );
        $this->branchMorelos = Branch::firstOrCreate(
            ['name' => 'Montemorelos'],
            ['address' => 'Centro Montemorelos', 'phone' => '8267654321']
        );
        $this->branchCercado = Branch::firstOrCreate(
            ['name' => 'El Cercado'],
            ['address' => 'Centro El Cercado', 'phone' => '8181234567']
        );

        $this->user = User::create([
            'name' => 'Esmeralda Operadora Test',
            'username' => 'esme_test_' . uniqid(),
            'email' => 'esme_test_' . uniqid() . '@zyacelulares.com',
            'password' => bcrypt('secret123'),
            'branch_id' => $this->branchAllende->id,
        ]);
        $this->user->assignRole($role);

        $this->customer = Customer::create([
            'tit_name' => 'Rosa Isela Torres Prueba',
            'tit_email' => 'rosa_' . uniqid() . '@example.com',
            'tit_phone' => '826' . rand(1000000, 9999999),
            'branch_id' => $this->branchAllende->id,
        ]);

        // Evitar que auto-corte de días anteriores altere conteos de prueba
        Branch::query()->update(['auto_cut_enabled' => false]);
    }

    /**
     * Extrae texto legible de los streams FlateDecode generados por DomPDF.
     */
    protected function extractTextFromPdfBytes(string $pdfBytes): string
    {
        $text = '';
        if (preg_match_all('/stream\r?\n(.*?)\r?\nendstream/s', $pdfBytes, $matches)) {
            foreach ($matches[1] as $stream) {
                $decoded = @gzuncompress($stream);
                $chunk = $decoded !== false ? $decoded : $stream;
                $text .= str_replace("\x00", '', $chunk) . "\n";
            }
        }
        return $text;
    }

    /**
     * Crea un flujo de caja asegurando la fecha/hora exacta en created_at.
     */
    protected function createCashFlowAt(array $attributes, Carbon $timestamp): CashFlow
    {
        $flow = CashFlow::create($attributes);
        $flow->created_at = $timestamp;
        $flow->updated_at = $timestamp;
        $flow->save();
        return $flow;
    }

    /**
     * Crea una financiera, equipo y venta base para registrar abonos de prueba.
     */
    protected function createFinSaleForFinanciera(FinFinanciera $fin, Branch $branch, string $suffix): FinSale
    {
        $device = FinDevice::create([
            'imei' => '35' . rand(1000000000000, 9999999999999),
            'model' => 'Galaxy A15 ' . $suffix,
            'branch_id' => $branch->id,
            'status' => 'vendido',
        ]);

        return FinSale::create([
            'sale_code' => 'FIN-' . strtoupper(Str::random(6)),
            'sale_type' => 'credito',
            'device_id' => $device->id,
            'financiera_id' => $fin->id,
            'branch_id' => $branch->id,
            'seller_id' => $this->user->id,
            'customer_name' => 'Cliente Fin ' . $suffix,
            'customer_phone' => '8261001122',
            'price' => 4500.00,
            'down_payment' => 500.00,
            'credit_amount' => 4000.00,
            'tag_contrato' => 'TAG-' . strtoupper($suffix),
            'abono_semanal' => 300.00,
            'sale_date' => Carbon::now(),
            'status' => 'activa',
        ]);
    }

    /**
     * Punto 1: Vista previa en pantalla, modos XP 80mm / XP 58mm / Epson Carta,
     * Captura WhatsApp, Historial POS y botón Ver en Dashboard.
     */
    public function test_punto_1_ticket_preview_paper_modes_whatsapp_and_history(): void
    {
        $this->actingAs($this->user);

        $tx = PosTransaction::create([
            'transaction_code' => 'MOV-' . date('Ymd') . '-P1TST',
            'branch_id' => $this->branchAllende->id,
            'user_id' => $this->user->id,
            'customer_id' => $this->customer->id,
            'customer_name' => 'Cliente Ticket Vista',
            'customer_phone' => '8265550001',
            'subtotal' => 250.00,
            'commission_total' => 0.00,
            'total_amount' => 250.00,
            'paid_amount' => 300.00,
            'change_amount' => 50.00,
            'payment_method' => 'efectivo',
            'cash_amount' => 250.00,
            'items_payload' => [[
                'section' => 'producto_dinamico',
                'title' => 'Funda Reforzada',
                'quantity' => 1,
                'amount' => 250.00,
                'commission' => 0.00,
            ]],
            'drawer_opened' => true,
            'status' => 'completado',
        ]);

        // Ticket con parámetro paper=letter&preview=1
        $receiptResp = $this->get(route('central-pos.receipt', $tx->id) . '?paper=letter&preview=1');
        $receiptResp->assertStatus(200);
        $html = $receiptResp->getContent();
        $this->assertStringContainsString('XP 80mm', $html);
        $this->assertStringContainsString('XP 58mm', $html);
        $this->assertStringContainsString('Epson Carta', $html);
        $this->assertStringContainsString('Captura WhatsApp', $html);
        $this->assertStringContainsString('paper-letter', $html);
        $this->assertStringContainsString('toggleCaptureMode', $html);
        $this->assertStringContainsString('dynamicPageRule', $html);

        // POS index: historial de tickets, modal de vista previa y controles de cajón / auto-impresión
        $posResp = $this->get(route('central-pos.index'));
        $posResp->assertStatus(200);
        $posHtml = $posResp->getContent();
        $this->assertStringContainsString('btnPosTicketHistory', $posHtml);
        $this->assertStringContainsString('posHistoryModal', $posHtml);
        $this->assertStringContainsString('openTicketPreviewModal', $posHtml);
        $this->assertStringContainsString('posOpenDrawerCheck', $posHtml);
        $this->assertStringContainsString('posAutoPrintCheck', $posHtml);

        // Endpoint JSON historial POS
        $historyResp = $this->getJson(route('central-pos.recent-transactions', [
            'branch_id' => $this->branchAllende->id,
            'search' => $tx->transaction_code,
        ]));
        $historyResp->assertStatus(200);
        $historyJson = $historyResp->json();
        $found = collect($historyJson['transactions'] ?? $historyJson['results'] ?? $historyJson)
            ->first(fn($row) => is_array($row) && ($row['transaction_code'] ?? '') === $tx->transaction_code);
        $this->assertNotNull($found, 'La transacción recién creada debe aparecer en el historial del POS.');

        // Dashboard: modal global de vista previa y botón Ver
        $dashResp = $this->get(route('dashboard'));
        $dashResp->assertStatus(200);
        $dashHtml = $dashResp->getContent();
        $this->assertStringContainsString('globalTicketPreviewModal', $dashHtml);
        $this->assertStringContainsString('openTicketPreviewModal', $dashHtml);
        $this->assertStringContainsString($tx->transaction_code, $dashHtml);
    }

    /**
     * Punto 8: Apertura automática de cajón de dinero SOLO en pagos con efectivo.
     */
    public function test_punto_8_cash_drawer_opens_only_when_cash_is_used(): void
    {
        $this->actingAs($this->user);

        // 1. Venta con tarjeta: NO debe marcar apertura de cajón
        $cardPayload = [
            'branch_id' => $this->branchAllende->id,
            'customer_id' => $this->customer->id,
            'customer_name' => $this->customer->tit_name,
            'payment_method' => 'tarjeta',
            'card_amount' => 150.00,
            'paid_amount' => 150.00,
            'open_drawer' => 1,
            'items' => [[
                'section' => 'producto_dinamico',
                'name' => 'Cargador USB-C',
                'title' => 'Cargador USB-C (Nuevo)',
                'quantity' => 1,
                'amount' => 150.00,
                'commission' => 0.00,
            ]],
        ];

        $cardResp = $this->postJson(route('central-pos.checkout'), $cardPayload);
        $cardResp->assertStatus(200);
        $this->assertFalse((bool) $cardResp->json('open_drawer'));
        $cardTx = PosTransaction::findOrFail($cardResp->json('transaction_id'));
        $this->assertFalse((bool) $cardTx->drawer_opened);

        // 2. Venta en efectivo: SÍ debe activar apertura de cajón
        $cashPayload = [
            'branch_id' => $this->branchAllende->id,
            'customer_id' => $this->customer->id,
            'customer_name' => $this->customer->tit_name,
            'payment_method' => 'efectivo',
            'cash_amount' => 180.00,
            'paid_amount' => 180.00,
            'cash_received' => 200.00,
            'open_drawer' => 1,
            'items' => [[
                'section' => 'producto_dinamico',
                'name' => 'Audífonos Alámbricos',
                'title' => 'Audífonos Alámbricos (Nuevo)',
                'quantity' => 1,
                'amount' => 180.00,
                'commission' => 0.00,
            ]],
        ];

        $cashResp = $this->postJson(route('central-pos.checkout'), $cashPayload);
        $cashResp->assertStatus(200);
        $this->assertTrue((bool) $cashResp->json('open_drawer'));
        $cashTx = PosTransaction::findOrFail($cashResp->json('transaction_id'));
        $this->assertTrue((bool) $cashTx->drawer_opened);
    }

    /**
     * Puntos 4 y 4A: PDF diario agrupado por financiera con subtotales y total del día,
     * y resumen de abonos a financieras dentro del Corte Z excluyendo ceros y cancelados.
     */
    public function test_puntos_4_and_4a_daily_financieras_pdf_and_corte_z_summary_exclude_zeros_and_cancelled(): void
    {
        $this->actingAs($this->user);

        $targetDate = '2026-11-12';
        $finPay = FinFinanciera::create(['name' => 'FIN_ALPHA_' . strtoupper(Str::random(4)), 'is_active' => true]);
        $finCred = FinFinanciera::create(['name' => 'FIN_BETA_' . strtoupper(Str::random(4)), 'is_active' => true]);
        $finZero = FinFinanciera::create(['name' => 'FIN_CERO_' . strtoupper(Str::random(4)), 'is_active' => true]);

        $sale1 = $this->createFinSaleForFinanciera($finPay, $this->branchAllende, 'A1');
        $sale2 = $this->createFinSaleForFinanciera($finCred, $this->branchAllende, 'B2');

        $txActive1 = PosTransaction::create([
            'transaction_code' => 'MOV-FIN-' . strtoupper(Str::random(5)),
            'branch_id' => $this->branchAllende->id,
            'user_id' => $this->user->id,
            'customer_name' => 'Cliente Alpha',
            'subtotal' => 500.00,
            'commission_total' => 10.00,
            'total_amount' => 510.00,
            'paid_amount' => 510.00,
            'payment_method' => 'efectivo',
            'cash_amount' => 510.00,
            'status' => 'completado',
        ]);

        FinSalePayment::create([
            'fin_sale_id' => $sale1->id,
            'device_id' => $sale1->device_id,
            'financiera_id' => $finPay->id,
            'branch_id' => $this->branchAllende->id,
            'user_id' => $this->user->id,
            'pos_transaction_id' => $txActive1->id,
            'receipt_code' => 'ABFIN-' . strtoupper(Str::random(6)),
            'tag_contrato' => $sale1->tag_contrato,
            'customer_name' => 'Cliente Alpha',
            'installment_number' => 1,
            'amount' => 500.00,
            'commission_amount' => 10.00,
            'total_amount' => 510.00,
            'payment_method' => 'efectivo',
            'payment_date' => Carbon::parse($targetDate . ' 11:15:00'),
        ]);

        $txActive2 = PosTransaction::create([
            'transaction_code' => 'MOV-FIN-' . strtoupper(Str::random(5)),
            'branch_id' => $this->branchAllende->id,
            'user_id' => $this->user->id,
            'customer_name' => 'Cliente Beta',
            'subtotal' => 300.00,
            'commission_total' => 0.00,
            'total_amount' => 300.00,
            'paid_amount' => 300.00,
            'payment_method' => 'efectivo',
            'cash_amount' => 300.00,
            'status' => 'completado',
        ]);

        FinSalePayment::create([
            'fin_sale_id' => $sale2->id,
            'device_id' => $sale2->device_id,
            'financiera_id' => $finCred->id,
            'branch_id' => $this->branchAllende->id,
            'user_id' => $this->user->id,
            'pos_transaction_id' => $txActive2->id,
            'receipt_code' => 'ABFIN-' . strtoupper(Str::random(6)),
            'tag_contrato' => $sale2->tag_contrato,
            'customer_name' => 'Cliente Beta',
            'installment_number' => 1,
            'amount' => 300.00,
            'commission_amount' => 0.00,
            'total_amount' => 300.00,
            'payment_method' => 'efectivo',
            'payment_date' => Carbon::parse($targetDate . ' 12:30:00'),
        ]);

        // Pago asociado a POS cancelado (debe excluirse del PDF y del resumen del Corte Z)
        $txCancelled = PosTransaction::create([
            'transaction_code' => 'MOV-CANC-' . strtoupper(Str::random(5)),
            'branch_id' => $this->branchAllende->id,
            'user_id' => $this->user->id,
            'customer_name' => 'Cliente Cancelado Fantasma',
            'subtotal' => 999.00,
            'commission_total' => 0.00,
            'total_amount' => 999.00,
            'paid_amount' => 999.00,
            'payment_method' => 'efectivo',
            'cash_amount' => 999.00,
            'status' => 'cancelado',
        ]);

        FinSalePayment::create([
            'fin_sale_id' => $sale1->id,
            'device_id' => $sale1->device_id,
            'financiera_id' => $finPay->id,
            'branch_id' => $this->branchAllende->id,
            'user_id' => $this->user->id,
            'pos_transaction_id' => $txCancelled->id,
            'receipt_code' => 'ABFIN-CANC-' . strtoupper(Str::random(5)),
            'tag_contrato' => $sale1->tag_contrato,
            'customer_name' => 'Cliente Cancelado Fantasma',
            'installment_number' => 2,
            'amount' => 999.00,
            'commission_amount' => 0.00,
            'total_amount' => 999.00,
            'payment_method' => 'efectivo',
            'payment_date' => Carbon::parse($targetDate . ' 13:00:00'),
        ]);

        // Punto 4: Descarga de PDF diario agrupado
        $pdfResp = $this->get(route('dashboard.financieras.pdf', ['date' => $targetDate]));
        $pdfResp->assertStatus(200);
        $this->assertStringContainsString('application/pdf', (string) $pdfResp->headers->get('content-type'));
        $pdfBytes = $pdfResp->getContent();
        $this->assertStringStartsWith('%PDF-', $pdfBytes);

        $pdfText = $this->extractTextFromPdfBytes($pdfBytes);
        $this->assertStringContainsString($finPay->name, $pdfText);
        $this->assertStringContainsString($finCred->name, $pdfText);
        $this->assertStringNotContainsString($finZero->name, $pdfText);
        $this->assertStringNotContainsString('Cliente Cancelado Fantasma', $pdfText);

        // Punto 4A: Resumen de financieras dentro de Corte Z (pantalla y versión imprimible)
        $cutResp = $this->get(route('cash.dailyCut', [
            'branch_id' => $this->branchAllende->id,
            'filter_type' => 'dia',
            'date' => $targetDate,
        ]));
        $cutResp->assertStatus(200);
        $cutHtml = $cutResp->getContent();
        $this->assertStringContainsString($finPay->name, $cutHtml);
        $this->assertStringContainsString($finCred->name, $cutHtml);
        $this->assertStringNotContainsString($finZero->name, $cutHtml);
        $this->assertStringNotContainsString('999.00', $cutHtml);

        $printResp = $this->get(route('cash.printCut', [
            'branch_id' => $this->branchAllende->id,
            'filter_type' => 'dia',
            'date' => $targetDate,
        ]));
        $printResp->assertStatus(200);
        $printHtml = $printResp->getContent();
        $this->assertStringContainsString('Resumen de Abonos a Financieras', $printHtml);
        $this->assertStringContainsString($finPay->name, $printHtml);
        $this->assertStringContainsString($finCred->name, $printHtml);
        $this->assertStringNotContainsString($finZero->name, $printHtml);
        $this->assertStringNotContainsString('999.00', $printHtml);
    }

    /**
     * Punto 5: Exportar Cortes Z de todas las sucursales en PDF y Excel (.xlsx).
     */
    public function test_punto_5_multi_branch_cortes_z_export_pdf_and_excel(): void
    {
        $this->actingAs($this->user);

        $date = '2026-11-11';
        foreach ([$this->branchAllende, $this->branchMorelos, $this->branchCercado] as $idx => $branch) {
            DailyCut::create([
                'branch_id' => $branch->id,
                'date' => $date,
                'cut_type' => 'corte_z',
                'cut_mode' => 'manual',
                'user_id' => $this->user->id,
                'opening_balance' => 500.00,
                'total_income' => 1000.00 + ($idx * 100),
                'cash_income' => 1000.00 + ($idx * 100),
                'card_income' => 0.00,
                'transfer_income' => 0.00,
                'total_expense' => 150.00,
                'balance' => 850.00 + ($idx * 100),
                'declared_cash' => 1350.00 + ($idx * 100),
                'difference_amount' => 0.00,
                'closed_at' => Carbon::parse($date . ' 20:00:00'),
                'notes' => 'Corte test ' . $branch->name,
            ]);
        }

        // PDF consolidado multi-sucursal
        $pdfResp = $this->get(route('cash.exportPdf', [
            'branch_id' => 'all',
            'filter_type' => 'dia',
            'date' => $date,
        ]));
        $pdfResp->assertStatus(200);
        $this->assertStringContainsString('application/pdf', (string) $pdfResp->headers->get('content-type'));
        $this->assertStringStartsWith('%PDF-', $pdfResp->getContent());

        $pdfText = $this->extractTextFromPdfBytes($pdfResp->getContent());
        $this->assertStringContainsString('Allende', $pdfText);
        $this->assertStringContainsString('Montemorelos', $pdfText);
        $this->assertStringContainsString('El Cercado', $pdfText);

        // Excel consolidado multi-sucursal
        $excelResp = $this->get(route('cash.exportExcel', [
            'branch_id' => 'all',
            'filter_type' => 'dia',
            'date' => $date,
        ]));
        $excelResp->assertStatus(200);

        $filePath = $excelResp->getFile()->getPathname();
        $spreadsheet = IOFactory::load($filePath);
        $sheet = $spreadsheet->getSheetByName('Cortes Z');
        $this->assertNotNull($sheet);

        $rowsText = json_encode($sheet->toArray());
        $this->assertStringContainsString('Allende', $rowsText);
        $this->assertStringContainsString('Montemorelos', $rowsText);
        $this->assertStringContainsString('El Cercado', $rowsText);
    }

    /**
     * Punto 6: Reporte de gastos por día, semana, mes o rango y descarga PDF/Excel.
     */
    public function test_punto_6_expenses_report_periods_branches_and_exports(): void
    {
        $this->actingAs($this->user);

        $day = '2026-11-12';
        $earlyMonthDay = '2026-11-02';

        $this->createCashFlowAt([
            'branch_id' => $this->branchAllende->id,
            'user_id' => $this->user->id,
            'type' => 'expense',
            'amount' => 125.50,
            'description' => 'GASTO_DIA_ALLENDE_PAPELERIA',
            'reference' => 'GAS-001',
            'module' => 'Gastos Operativos',
            'payment_method' => 'efectivo',
            'cash_amount' => 125.50,
        ], Carbon::parse($day . ' 10:00:00'));

        $this->createCashFlowAt([
            'branch_id' => $this->branchMorelos->id,
            'user_id' => $this->user->id,
            'type' => 'expense',
            'amount' => 240.00,
            'description' => 'GASTO_DIA_MORELOS_LIMPIEZA',
            'reference' => 'GAS-002',
            'module' => 'Gastos Operativos',
            'payment_method' => 'efectivo',
            'cash_amount' => 240.00,
        ], Carbon::parse($day . ' 11:00:00'));

        $this->createCashFlowAt([
            'branch_id' => $this->branchAllende->id,
            'user_id' => $this->user->id,
            'type' => 'expense',
            'amount' => 500.00,
            'description' => 'GASTO_INICIO_MES_INTERNET',
            'reference' => 'GAS-003',
            'module' => 'Servicios',
            'payment_method' => 'efectivo',
            'cash_amount' => 500.00,
        ], Carbon::parse($earlyMonthDay . ' 09:00:00'));

        // Filtro por día todas las sucursales: ve los del 2026-11-12, NO el de inicio de mes
        $dayResp = $this->get(route('cash.expenses', [
            'period' => 'dia',
            'date' => $day,
            'branch_id' => 'all',
        ]));
        $dayResp->assertStatus(200);
        $dayResp->assertSee('GASTO_DIA_ALLENDE_PAPELERIA');
        $dayResp->assertSee('GASTO_DIA_MORELOS_LIMPIEZA');
        $dayResp->assertDontSee('GASTO_INICIO_MES_INTERNET');

        // Filtro por semana solo Allende: ve Allende, NO Montemorelos
        $weekResp = $this->get(route('cash.expenses', [
            'period' => 'semana',
            'date' => $day,
            'branch_id' => $this->branchAllende->id,
        ]));
        $weekResp->assertStatus(200);
        $weekResp->assertSee('GASTO_DIA_ALLENDE_PAPELERIA');
        $weekResp->assertDontSee('GASTO_DIA_MORELOS_LIMPIEZA');

        // Filtro por mes todas las sucursales: ve todos
        $monthResp = $this->get(route('cash.expenses', [
            'period' => 'mes',
            'date' => $day,
            'branch_id' => 'all',
        ]));
        $monthResp->assertStatus(200);
        $monthResp->assertSee('GASTO_DIA_ALLENDE_PAPELERIA');
        $monthResp->assertSee('GASTO_INICIO_MES_INTERNET');

        // Filtro por rango personalizado + PDF + Excel
        $rangeParams = [
            'period' => 'rango',
            'start_date' => $earlyMonthDay,
            'end_date' => $day,
            'branch_id' => 'all',
        ];
        $this->get(route('cash.expenses', $rangeParams))
            ->assertStatus(200)
            ->assertSee('GASTO_INICIO_MES_INTERNET')
            ->assertSee('GASTO_DIA_MORELOS_LIMPIEZA');

        $pdfResp = $this->get(route('cash.expenses.pdf', $rangeParams));
        $pdfResp->assertStatus(200);
        $this->assertStringStartsWith('%PDF-', $pdfResp->getContent());

        $excelResp = $this->get(route('cash.expenses.excel', $rangeParams));
        $excelResp->assertStatus(200);
        $spreadsheet = IOFactory::load($excelResp->getFile()->getPathname());
        $sheet = $spreadsheet->getSheetByName('Gastos');
        $this->assertNotNull($sheet);
        $excelDump = json_encode($sheet->toArray());
        $this->assertStringContainsString('GASTO_DIA_ALLENDE_PAPELERIA', $excelDump);
        $this->assertStringContainsString('TOTAL DEL PER', $excelDump);
    }

    /**
     * Punto 12: Cancelación de préstamos con motivo sin generar CashFlow ni alterar DailyCut.
     */
    public function test_punto_12_loan_cancellation_records_reason_and_preserves_cash_and_cuts(): void
    {
        $this->actingAs($this->user);

        $cut = DailyCut::create([
            'branch_id' => $this->branchAllende->id,
            'date' => '2026-11-10',
            'cut_type' => 'corte_z',
            'cut_mode' => 'manual',
            'user_id' => $this->user->id,
            'opening_balance' => 1000.00,
            'total_income' => 2500.00,
            'cash_income' => 2500.00,
            'total_expense' => 500.00,
            'balance' => 2000.00,
            'closed_at' => Carbon::parse('2026-11-10 21:00:00'),
        ]);

        $loanType = LoanType::firstOrCreate(
            ['slug' => 'efectivo-test'],
            ['name' => 'Préstamo Efectivo Test', 'default_interest_rate' => 20, 'is_active' => true]
        );

        $loan = Loan::create([
            'loan_code' => 'PREST-TEST-' . strtoupper(Str::random(4)),
            'branch_id' => $this->branchAllende->id,
            'customer_id' => $this->customer->id,
            'user_id' => $this->user->id,
            'loan_type_id' => $loanType->id,
            'principal_amount' => 3000.00,
            'interest_rate' => 20.00,
            'interest_amount' => 600.00,
            'total_amount' => 3600.00,
            'paid_amount' => 0.00,
            'balance_amount' => 3600.00,
            'payment_frequency' => 'semanal',
            'term_periods' => 4,
            'installment_amount' => 900.00,
            'start_date' => '2026-11-10',
            'due_date' => '2026-12-08',
            'status' => 'activo',
        ]);

        for ($i = 1; $i <= 4; $i++) {
            LoanInstallment::create([
                'loan_id' => $loan->id,
                'installment_number' => $i,
                'scheduled_date' => Carbon::parse('2026-11-10')->addWeeks($i)->toDateString(),
                'scheduled_amount' => 900.00,
                'paid_amount' => 0.00,
                'status' => 'pendiente',
            ]);
        }

        $cashFlowCountBefore = CashFlow::count();

        // Requiere motivo de cancelación
        $invalidResp = $this->post(route('loans.cancel', $loan->id), []);
        $invalidResp->assertSessionHasErrors('cancellation_reason');

        // Cancelación válida
        $cancelResp = $this->post(route('loans.cancel', $loan->id), [
            'cancellation_reason' => 'Registro de prueba capturado por error',
        ]);
        $cancelResp->assertRedirect(route('loans.show', $loan->id));

        $loan->refresh();
        $this->assertEquals('cancelado', $loan->status);
        $this->assertEquals(0.00, (float) $loan->balance_amount);
        $this->assertEquals('Registro de prueba capturado por error', $loan->cancellation_reason);
        $this->assertEquals($this->user->id, $loan->cancelled_by);
        $this->assertNotNull($loan->cancelled_at);

        $pendingCount = LoanInstallment::where('loan_id', $loan->id)
            ->where('status', '!=', 'cancelado')
            ->count();
        $this->assertEquals(0, $pendingCount);

        // Cero impacto en CashFlow ni en DailyCut
        $this->assertEquals($cashFlowCountBefore, CashFlow::count());
        $cut->refresh();
        $this->assertEquals(2500.00, (float) $cut->total_income);
        $this->assertEquals(500.00, (float) $cut->total_expense);
        $this->assertEquals(2000.00, (float) $cut->balance);

        // Visibilidad en detalle y filtro del listado
        $this->get(route('loans.show', $loan->id))
            ->assertStatus(200)
            ->assertSee('CANCELADO')
            ->assertSee('Registro de prueba capturado por error');

        $this->get(route('loans.index', ['status' => 'cancelado']))
            ->assertStatus(200)
            ->assertSee($loan->loan_code);
    }

    /**
     * Punto 13: Gifts en POS por sucursal, $0.00 forzado en servidor,
     * validación cruzada de sucursal, ticket e inventario/kardex.
     */
    public function test_punto_13_gifts_in_pos_lookup_zero_price_and_atomic_branch_stock_decrement(): void
    {
        $this->actingAs($this->user);

        $giftAllende = Gift::create([
            'code' => 'GIFT-ALL-' . strtoupper(Str::random(4)),
            'name' => 'Funda Regalo Allende Test',
            'brand' => 'ZYA Promo',
            'branch_id' => $this->branchAllende->id,
            'stock_quantity' => 5,
            'min_stock' => 1,
            'unit_cost' => 15.00,
            'status' => 'activo',
        ]);

        $giftZeroStock = Gift::create([
            'code' => 'GIFT-ZERO-' . strtoupper(Str::random(4)),
            'name' => 'Gift Agotado Test',
            'branch_id' => $this->branchAllende->id,
            'stock_quantity' => 0,
            'min_stock' => 1,
            'status' => 'activo',
        ]);

        $giftCercado = Gift::create([
            'code' => 'GIFT-CER-' . strtoupper(Str::random(4)),
            'name' => 'PowerBank Regalo Cercado',
            'branch_id' => $this->branchCercado->id,
            'stock_quantity' => 4,
            'min_stock' => 1,
            'status' => 'activo',
        ]);

        // Lookup filtrado estricto por sucursal activa y stock > 0
        $lookupResp = $this->getJson(route('central-pos.lookup-gifts', [
            'branch_id' => $this->branchAllende->id,
            'q' => 'Test',
        ]));
        $lookupResp->assertStatus(200);
        $lookupJson = json_encode($lookupResp->json());
        $this->assertStringContainsString($giftAllende->name, $lookupJson);
        $this->assertStringNotContainsString($giftZeroStock->name, $lookupJson);
        $this->assertStringNotContainsString($giftCercado->name, $lookupJson);

        // Rechazar entrega de regalo perteneciente a otra sucursal
        $crossBranchResp = $this->postJson(route('central-pos.checkout'), [
            'branch_id' => $this->branchAllende->id,
            'customer_name' => $this->customer->tit_name,
            'payment_method' => 'efectivo',
            'cash_amount' => 100.00,
            'paid_amount' => 100.00,
            'items' => [
                [
                    'section' => 'producto_dinamico',
                    'name' => 'Mica Vidrio',
                    'title' => 'Mica Vidrio',
                    'quantity' => 1,
                    'amount' => 100.00,
                    'commission' => 0.00,
                ],
                [
                    'section' => 'gift',
                    'gift_id' => $giftCercado->id,
                    'title' => $giftCercado->name,
                    'quantity' => 1,
                    'amount' => 0.00,
                ],
            ],
        ]);
        $crossBranchResp->assertStatus(422);
        $this->assertEquals(4, $giftCercado->fresh()->stockInBranch($this->branchCercado->id));
        $this->assertNull($giftCercado->fresh()->branch_id);

        // Cobro válido enviando precio manipulado ($500) en regalo: servidor fuerza $0.00
        $validResp = $this->postJson(route('central-pos.checkout'), [
            'branch_id' => $this->branchAllende->id,
            'customer_id' => $this->customer->id,
            'customer_name' => $this->customer->tit_name,
            'customer_phone' => $this->customer->tit_phone,
            'payment_method' => 'efectivo',
            'cash_amount' => 200.00,
            'paid_amount' => 200.00,
            'cash_received' => 200.00,
            'items' => [
                [
                    'section' => 'producto_dinamico',
                    'name' => 'Mica Cerámica',
                    'title' => 'Mica Cerámica',
                    'quantity' => 1,
                    'amount' => 200.00,
                    'commission' => 0.00,
                ],
                [
                    'section' => 'gift',
                    'gift_id' => $giftAllende->id,
                    'title' => $giftAllende->name,
                    'quantity' => 2,
                    'amount' => 500.00,
                    'commission' => 50.00,
                ],
            ],
        ]);
        $validResp->assertStatus(200);

        $tx = PosTransaction::findOrFail($validResp->json('transaction_id'));
        $this->assertEquals(200.00, (float) $tx->total_amount);
        $this->assertEquals(0.00, (float) $tx->commission_total);

        // Descuento exacto de inventario y kardex GiftMovement (exclusivo por sucursal)
        $this->assertEquals(3, $giftAllende->fresh()->stockInBranch($this->branchAllende->id));
        $this->assertEquals(0, $giftAllende->fresh()->stock_quantity);
        $this->assertEquals(4, $giftCercado->fresh()->stockInBranch($this->branchCercado->id));

        $movement = GiftMovement::where('gift_id', $giftAllende->id)
            ->where('reference_code', $tx->transaction_code)
            ->first();
        $this->assertNotNull($movement);
        $this->assertEquals('entrega_cliente', $movement->movement_type);
        $this->assertEquals('central_pos', $movement->reference_module);
        $this->assertEquals(2, $movement->quantity);
        $this->assertEquals(5, $movement->previous_stock);
        $this->assertEquals(3, $movement->new_stock);

        // Ticket muestra (REGALO) y $0.00
        $this->get(route('central-pos.receipt', $tx->id))
            ->assertStatus(200)
            ->assertSee($giftAllende->name)
            ->assertSee('(REGALO)')
            ->assertSee('$0.00');
    }

    /**
     * Punto 14: Formato compacto del Corte Z impreso (5-6 movimientos vs 35 movimientos de sábado)
     * preservando legibilidad, bitácora, conteo físico, conciliación y resumen de financieras.
     */
    public function test_punto_14_corte_z_compact_print_layout_short_and_saturday_volume(): void
    {
        $this->actingAs($this->user);

        $shortDate = '2026-11-11';
        for ($i = 1; $i <= 6; $i++) {
            $this->createCashFlowAt([
                'branch_id' => $this->branchAllende->id,
                'user_id' => $this->user->id,
                'customer_name' => "Cliente Corto {$i}",
                'type' => 'income',
                'amount' => 350.00,
                'description' => "Operación corta #{$i}",
                'reference' => "MOV-CORT-{$i}",
                'module' => 'Cobro Centralizado',
                'payment_method' => 'efectivo',
                'cash_amount' => 350.00,
            ], Carbon::parse($shortDate)->setTime(10 + $i, 15));
        }

        $shortResp = $this->get(route('cash.printCut', [
            'branch_id' => $this->branchAllende->id,
            'filter_type' => 'dia',
            'date' => $shortDate,
        ]));
        $shortResp->assertStatus(200);
        $shortHtml = $shortResp->getContent();
        $this->assertStringContainsString('size: letter portrait', $shortHtml);
        $this->assertStringContainsString('7mm 8mm', $shortHtml);
        $this->assertStringContainsString('Resumen de Caja', $shortHtml);
        $this->assertStringContainsString('Conteo Físico por Denominación', $shortHtml);
        $this->assertStringContainsString('Bitácora Cronológica con Detalle por Operación del Cliente', $shortHtml);
        $this->assertStringContainsString('Cliente Corto 6', $shortHtml);

        // Día de sábado con 35 movimientos
        $saturdayDate = '2026-11-07';
        for ($i = 1; $i <= 35; $i++) {
            $this->createCashFlowAt([
                'branch_id' => $this->branchAllende->id,
                'user_id' => $this->user->id,
                'customer_name' => "Cliente Sábado {$i}",
                'type' => ($i % 7 === 0) ? 'expense' : 'income',
                'amount' => 280.00,
                'description' => "Operación sábado #{$i}",
                'reference' => "MOV-SAB-{$i}",
                'module' => ($i % 7 === 0) ? 'Gastos' : 'Abono Financiera',
                'payment_method' => 'efectivo',
                'cash_amount' => 280.00,
            ], Carbon::parse($saturdayDate)->setTime(9 + intdiv($i, 4), ($i * 7) % 60));
        }

        $satResp = $this->get(route('cash.printCut', [
            'branch_id' => $this->branchAllende->id,
            'filter_type' => 'dia',
            'date' => $saturdayDate,
        ]));
        $satResp->assertStatus(200);
        $satHtml = $satResp->getContent();
        $this->assertStringContainsString('Cliente Sábado 1', $satHtml);
        $this->assertStringContainsString('Cliente Sábado 35', $satHtml);

        // Verificación empírica de compacidad renderizando .cut-print-sheet en Carta con @media print
        $extractPrintable = function (string $fullHtml): string {
            preg_match_all('/<style\b[^>]*>(.*?)<\/style>/is', $fullHtml, $styleMatches);
            $styles = implode("\n", $styleMatches[1] ?? []);
            $start = strpos($fullHtml, '<div class="container cut-print-sheet">');
            $end = strrpos($fullHtml, '<script>');
            $body = ($start !== false)
                ? substr($fullHtml, $start, ($end !== false && $end > $start) ? $end - $start : strlen($fullHtml) - $start)
                : $fullHtml;
            $bsGridShim = '
                * { box-sizing: border-box; font-family: DejaVu Sans, sans-serif; }
                .no-print { display: none !important; }
                .row { display: table; width: 100%; table-layout: fixed; margin-bottom: 4px; }
                .col-3 { display: table-cell; width: 25%; vertical-align: top; padding: 0 2px; }
                .col-5 { display: table-cell; width: 41.66%; vertical-align: top; padding-left: 3px; }
                .col-6 { display: table-cell; width: 50%; vertical-align: top; padding: 0 2px; }
                .col-7 { display: table-cell; width: 58.33%; vertical-align: top; padding-right: 3px; }
                table { width: 100%; border-collapse: collapse; }
                th, td { border: 1px solid #cbd5e1; }
            ';
            return "<html><head><meta charset='utf-8'><style>{$bsGridShim}\n{$styles}</style></head><body>{$body}</body></html>";
        };

        $shortPdf = Pdf::setOption('defaultMediaType', 'print')->loadHTML($extractPrintable($shortHtml))->setPaper('letter', 'portrait');
        $shortDompdf = $shortPdf->getDomPDF();
        $shortDompdf->render();
        $shortPages = $shortDompdf->getCanvas()->get_page_count();
        $this->assertLessThanOrEqual(2, $shortPages, "El corte corto de 6 movimientos debe caber en <= 2 hojas (actual: {$shortPages}).");

        $satPdf = Pdf::setOption('defaultMediaType', 'print')->loadHTML($extractPrintable($satHtml))->setPaper('letter', 'portrait');
        $satDompdf = $satPdf->getDomPDF();
        $satDompdf->render();
        $satPages = $satDompdf->getCanvas()->get_page_count();
        $this->assertGreaterThanOrEqual(2, $satPages);
        $this->assertLessThanOrEqual(4, $satPages);
    }

    /**
     * Punto 10: Nuevos formatos separados de Pagaré Mercantil y Recibo de Entrega de Préstamo,
     * con llenado automático y firma del aval condicional ("según corresponda").
     */
    public function test_punto10_loan_separated_pagare_and_delivery_receipt_with_and_without_aval(): void
    {
        $this->actingAs($this->user);

        // 1) Préstamo CON aval y medio de entrega transferencia
        $this->customer->update([
            'tit_phone' => '8261615000',
            'tit_address' => 'Juan Salazar, col. Bernardo Flores',
            'aval_name' => 'Fernando Gabriel Telles Izaguirre',
            'aval_phone' => '8262136975',
            'aval_address' => 'Calle Pablo A. de la Garza, Valle de los Duraznos',
        ]);

        $respWithAval = $this->post(route('loans.store'), [
            'operation_type' => 'prestamo',
            'delivery_method' => 'transferencia',
            'customer_id' => $this->customer->id,
            'branch_id' => $this->branchAllende->id,
            'principal_amount' => 3000.00,
            'installment_amount' => 601.00,
            'term_periods' => 8,
            'payment_frequency' => '12_26',
            'start_date' => '2026-11-11',
            'opening_commission' => 0,
            'aval_name' => 'Fernando Gabriel Telles Izaguirre',
            'aval_phone' => '8262136975',
            'aval_address' => 'Calle Pablo A. de la Garza, Valle de los Duraznos',
        ]);
        $loanWithAval = Loan::where('customer_id', $this->customer->id)->latest('id')->firstOrFail();
        $respWithAval->assertRedirect(route('loans.show', $loanWithAval->id));
        $this->assertEquals('prestamo', $loanWithAval->operation_type);
        $this->assertEquals('transferencia', $loanWithAval->delivery_method);
        $this->assertStringStartsWith('PREST-', $loanWithAval->loan_code);

        // Pagaré separado CON aval + PDF válido
        $pagareResp = $this->get(route('loans.pagare.print', $loanWithAval->id));
        $pagareResp->assertStatus(200)
            ->assertSee('PAGARÉ MERCANTIL')
            ->assertSee('BUENO POR: $4,808.00 M.N.')
            ->assertSee('Datos del Deudor')
            ->assertSee('Datos del Aval')
            ->assertSee('Fernando Gabriel Telles Izaguirre')
            ->assertSee('8262136975')
            ->assertSee('ACEPTO Y PAGARÉ')
            ->assertSee('FIRMA DEL AVAL')
            ->assertSee('Huella del pulgar')
            ->assertSee('Lugar de firma:');

        $pagarePdfResp = $this->get(route('loans.pagare.print', ['loan' => $loanWithAval->id, 'pdf' => 1]));
        $pagarePdfResp->assertStatus(200);
        $this->assertStringStartsWith('%PDF-', $pagarePdfResp->getContent());

        // Recibo de Entrega separado CON aval + check transferencia + PDF válido
        $receiptResp = $this->get(route('loans.delivery-receipt.print', $loanWithAval->id));
        $receiptResp->assertStatus(200)
            ->assertSee('RECIBO DE ENTREGA DE PRÉSTAMO')
            ->assertSee('$3,000.00 MXN')
            ->assertSee('tres mil pesos 00/100 m.n.')
            ->assertSee('>X</span>Transferencia', false)
            ->assertSee('FIRMA DEL TITULAR QUE RECIBE')
            ->assertSee('FIRMA DEL AVAL')
            ->assertSee('Fernando Gabriel Telles Izaguirre');

        $receiptPdfResp = $this->get(route('loans.delivery-receipt.print', ['loan' => $loanWithAval->id, 'pdf' => 1]));
        $receiptPdfResp->assertStatus(200);
        $this->assertStringStartsWith('%PDF-', $receiptPdfResp->getContent());

        // 2) Préstamo SIN aval: omite bloques del aval ("según corresponda")
        $customerSinAval = Customer::create([
            'tit_name' => 'Cliente Sin Aval Prueba',
            'tit_email' => 'sin_aval_' . uniqid() . '@example.com',
            'tit_phone' => '8269990001',
            'tit_address' => 'Col. Centro Allende',
            'branch_id' => $this->branchAllende->id,
        ]);

        $this->post(route('loans.store'), [
            'operation_type' => 'prestamo',
            'delivery_method' => 'efectivo',
            'customer_id' => $customerSinAval->id,
            'branch_id' => $this->branchAllende->id,
            'principal_amount' => 2000.00,
            'installment_amount' => 700.00,
            'term_periods' => 4,
            'payment_frequency' => 'quincenal',
            'start_date' => '2026-11-11',
            'opening_commission' => 100.00,
        ])->assertRedirect();

        $loanNoAval = Loan::where('customer_id', $customerSinAval->id)->latest('id')->firstOrFail();

        $pagareNoAval = $this->get(route('loans.pagare.print', $loanNoAval->id));
        $pagareNoAval->assertStatus(200)
            ->assertSee('Datos del Deudor')
            ->assertSee('FIRMA DEL DEUDOR')
            ->assertDontSee('Datos del Aval')
            ->assertDontSee('FIRMA DEL AVAL');

        $receiptNoAval = $this->get(route('loans.delivery-receipt.print', $loanNoAval->id));
        $receiptNoAval->assertStatus(200)
            ->assertSee('RECIBO DE ENTREGA DE PRÉSTAMO')
            ->assertSee('>X</span>Efectivo', false)
            ->assertSee('FIRMA DEL TITULAR QUE RECIBE')
            ->assertDontSee('FIRMA DEL AVAL');
    }

    /**
     * Punto 7: Venta a crédito de artículos (estufa, laptop, tablet, plancha, etc.),
     * ficha técnica con campos opcionales vacíos, enganche en caja, POS y formatos adaptados.
     */
    public function test_punto7_article_credit_flow_specs_down_payment_cash_pos_and_documents(): void
    {
        $this->actingAs($this->user);

        // 1) Vistas de listado y creación cargan correctamente con todos los campos solicitados
        $this->get(route('article-credits.index'))
            ->assertStatus(200)
            ->assertSee('Venta a Crédito de Artículos')
            ->assertSee('Nueva Venta a Crédito');

        $this->get(route('article-credits.create'))
            ->assertStatus(200)
            ->assertSee('Nueva Venta a Crédito de Artículo')
            ->assertSee('Ficha del Artículo a Crédito')
            ->assertSee('name="article_status"', false)
            ->assertSee('name="article_name"', false)
            ->assertSee('name="article_brand"', false)
            ->assertSee('name="article_model"', false)
            ->assertSee('name="article_color"', false)
            ->assertSee('name="article_capacity"', false)
            ->assertSee('name="article_imei"', false)
            ->assertSee('name="article_serial"', false)
            ->assertSee('name="article_warranty"', false)
            ->assertSee('name="article_observations"', false)
            ->assertSee('name="article_price"', false)
            ->assertSee('name="down_payment"', false);

        // 2) Validación: el enganche no puede igualar ni superar el precio del artículo
        $invalidDownResp = $this->post(route('article-credits.store'), [
            'operation_type' => 'credito_articulo',
            'customer_id' => $this->customer->id,
            'branch_id' => $this->branchAllende->id,
            'article_name' => 'Plancha Oster',
            'article_price' => 1000.00,
            'down_payment' => 1000.00,
            'down_payment_method' => 'efectivo',
            'installment_amount' => 250.00,
            'term_periods' => 4,
            'payment_frequency' => 'semanal',
            'start_date' => '2026-11-12',
        ]);
        $invalidDownResp->assertSessionHasErrors('down_payment');

        // 2b) Validación: método de pago del enganche obligatorio únicamente si enganche > 0
        $missingMethodResp = $this->post(route('article-credits.store'), [
            'operation_type' => 'credito_articulo',
            'customer_id' => $this->customer->id,
            'branch_id' => $this->branchAllende->id,
            'article_name' => 'Microondas LG',
            'down_payment' => 500.00,
            'installment_amount' => 300.00,
            'term_periods' => 6,
            'payment_frequency' => 'semanal',
            'start_date' => '2026-11-12',
        ]);
        $missingMethodResp->assertSessionHasErrors('down_payment_method');

        // 2c) Registro sin Precio de contado ni Monto financiado, con enganche > 0 y fecha base en febrero
        $noCashResp = $this->post(route('article-credits.store'), [
            'operation_type' => 'credito_articulo',
            'customer_id' => $this->customer->id,
            'branch_id' => $this->branchAllende->id,
            'article_name' => 'Licuadora Ninja',
            'down_payment' => 500.00,
            'down_payment_method' => 'transferencia',
            'installment_amount' => 400.00,
            'term_periods' => 4,
            'payment_frequency' => 'quincenal',
            'start_date' => '2026-02-10',
            'first_payment_date' => '2026-02-15',
        ]);
        $noCashCredit = Loan::where('article_name', 'Licuadora Ninja')->latest('id')->firstOrFail();
        $noCashResp->assertRedirect(route('loans.show', $noCashCredit->id));
        $this->assertNull($noCashCredit->article_price);
        $this->assertNull($noCashCredit->principal_amount);
        $this->assertNull($noCashCredit->interest_amount);
        $this->assertNull($noCashCredit->interest_rate);
        $this->assertEquals(1600.00, (float) $noCashCredit->total_amount);
        $this->assertEquals(2100.00, (float) $noCashCredit->credit_sale_total);
        $this->assertEquals('2026-02-15', $noCashCredit->first_payment_date?->toDateString());
        $this->assertEquals([
            '2026-02-15',
            '2026-02-28',
            '2026-03-15',
            '2026-03-30',
        ], $noCashCredit->installments()->orderBy('installment_number')->pluck('scheduled_date')->map->toDateString()->all());
        $this->get(route('loans.show', $noCashCredit->id))
            ->assertStatus(200)
            ->assertSee('Sin calcular')
            ->assertSee('$2,100.00');

        // 3) Alta de Estufa con enganche ($1,500) dejando IMEI vacío (no aplica)
        $cashBefore = CashFlow::count();
        $storeResp = $this->post(route('article-credits.store'), [
            'operation_type' => 'credito_articulo',
            'customer_id' => $this->customer->id,
            'branch_id' => $this->branchAllende->id,
            'article_status' => 'nuevo',
            'article_name' => 'Estufa 6 Quemadores',
            'article_brand' => 'Mabe',
            'article_model' => 'EM7620BA0',
            'article_color' => 'Plata',
            'article_capacity' => '30 pulgadas',
            'article_imei' => null,
            'article_serial' => 'SN-MABE-7788',
            'article_warranty' => '1 año fabricante',
            'article_observations' => 'Incluye manguera y kit de instalación',
            'article_price' => 6500.00,
            'down_payment' => 1500.00,
            'down_payment_method' => 'efectivo',
            'installment_amount' => 750.00,
            'term_periods' => 8,
            'payment_frequency' => '12_26',
            'start_date' => '2026-11-12',
            'opening_commission' => 0,
            'aval_name' => 'María Guadalupe Torres',
            'aval_phone' => '8261002030',
        ]);

        $credit = Loan::where('operation_type', 'credito_articulo')->latest('id')->firstOrFail();
        $storeResp->assertRedirect(route('loans.show', $credit->id));
        $this->assertStringStartsWith('CRED-', $credit->loan_code);
        $this->assertEquals('mercancia', $credit->delivery_method);
        $this->assertEquals(6500.00, (float) $credit->article_price);
        $this->assertEquals(1500.00, (float) $credit->down_payment);
        $this->assertEquals(5000.00, (float) $credit->principal_amount);
        $this->assertEquals(6000.00, (float) $credit->total_amount);
        $this->assertEquals(7500.00, (float) $credit->credit_sale_total);
        $this->assertEquals(1000.00, (float) $credit->interest_amount);
        $this->assertEquals(6000.00, (float) $credit->balance_amount);
        $this->assertNull($credit->article_imei);

        // Enganche registrado como pago tipo 'enganche' + único movimiento de caja de $1,500
        $enganchePayment = LoanPayment::where('loan_id', $credit->id)
            ->where('payment_type', 'enganche')
            ->first();
        $this->assertNotNull($enganchePayment);
        $this->assertEquals(1500.00, (float) $enganchePayment->amount);

        $this->assertEquals($cashBefore + 1, CashFlow::count());
        $cashEntry = CashFlow::latest('id')->first();
        $this->assertEquals(1500.00, (float) $cashEntry->amount);
        $this->assertEquals('Crédito de Artículos (Enganche)', $cashEntry->module);

        // Separación en listados: aparece en article-credits y no ensucia el catálogo por defecto de loans
        $this->get(route('article-credits.index'))
            ->assertStatus(200)
            ->assertSee($credit->loan_code)
            ->assertSee('Estufa 6 Quemadores')
            ->assertSee('Mabe');

        $this->get(route('loans.index'))
            ->assertStatus(200)
            ->assertDontSee($credit->loan_code);

        // Detalle muestra ficha técnica y enganche
        $this->get(route('loans.show', $credit->id))
            ->assertStatus(200)
            ->assertSee('Crédito de Artículo')
            ->assertSee('Ficha del Artículo')
            ->assertSee('Estufa 6 Quemadores')
            ->assertSee('SN-MABE-7788')
            ->assertSee('Enganche');

        // Pagaré adaptado a compraventa a crédito + PDF
        $pagareResp = $this->get(route('loans.pagare.print', $credit->id));
        $pagareResp->assertStatus(200)
            ->assertSee('PAGARÉ MERCANTIL')
            ->assertSee('Folio de venta a crédito')
            ->assertSee('BUENO POR: $6,000.00 M.N.')
            ->assertSee('compraventa a crédito')
            ->assertSee('Precio total:')
            ->assertSee('$6,500.00 MXN')
            ->assertSee('Enganche cubierto:')
            ->assertSee('$1,500.00 MXN')
            ->assertSee('Ficha del Artículo en Compraventa a Crédito')
            ->assertSee('Estufa 6 Quemadores')
            ->assertDontSee('<strong>IMEI:</strong>', false);

        // Recibo de Entrega adaptado a Mercancía + PDF
        $receiptResp = $this->get(route('loans.delivery-receipt.print', $credit->id));
        $receiptResp->assertStatus(200)
            ->assertSee('RECIBO DE ENTREGA DE MERCANCÍA')
            ->assertSee('$6,500.00 MXN')
            ->assertSee('Identificación de la Mercancía / Artículo Entregado')
            ->assertSee('Estufa 6 Quemadores')
            ->assertSee('EM7620BA0')
            ->assertSee('SN-MABE-7788');

        $receiptPdf = $this->get(route('loans.delivery-receipt.print', ['loan' => $credit->id, 'pdf' => 1]));
        $receiptPdf->assertStatus(200);
        $this->assertStringStartsWith('%PDF-', $receiptPdf->getContent());

        // 4) Segundo artículo seminuevo sin enganche con IMEI/Serie (Tablet) e integración en POS 4 en 1
        $this->post(route('article-credits.store'), [
            'operation_type' => 'credito_articulo',
            'customer_id' => $this->customer->id,
            'branch_id' => $this->branchAllende->id,
            'article_status' => 'seminuevo',
            'article_name' => 'Tablet Galaxy Tab A9',
            'article_brand' => 'Samsung',
            'article_model' => 'SM-X115',
            'article_capacity' => '64 GB',
            'article_imei' => '358888111222333',
            'article_price' => 3000.00,
            'down_payment' => 0,
            'installment_amount' => 900.00,
            'term_periods' => 4,
            'payment_frequency' => 'semanal',
            'start_date' => '2026-11-12',
        ])->assertRedirect();

        $tabletCredit = Loan::where('article_imei', '358888111222333')->firstOrFail();

        $posLookup = $this->getJson(route('central-pos.lookup-loans', ['customer_id' => $this->customer->id]));
        $posLookup->assertStatus(200);
        $lookupJson = json_encode($posLookup->json(), JSON_UNESCAPED_UNICODE);
        $this->assertStringContainsString($credit->loan_code, $lookupJson);
        $this->assertStringContainsString('Crédito Artículo (Estufa 6 Quemadores', $lookupJson);
        $this->assertStringContainsString($tabletCredit->loan_code, $lookupJson);

        // Cobro de abono de $750 en POS 4 en 1 contra el crédito del artículo
        $checkoutResp = $this->postJson(route('central-pos.checkout'), [
            'branch_id' => $this->branchAllende->id,
            'customer_id' => $this->customer->id,
            'customer_name' => $this->customer->tit_name,
            'payment_method' => 'efectivo',
            'cash_amount' => 750.00,
            'paid_amount' => 750.00,
            'items' => [
                [
                    'section' => 'prestamo_abono',
                    'loan_id' => $credit->id,
                    'title' => 'Abono Crédito Estufa',
                    'quantity' => 1,
                    'amount' => 750.00,
                    'commission' => 0.00,
                ],
            ],
        ]);
        $checkoutResp->assertStatus(200);

        $credit->refresh();
        $this->assertEquals(750.00, (float) $credit->paid_amount);
        $this->assertEquals(5250.00, (float) $credit->balance_amount);
    }

    /**
     * Visualizar en Punto de Venta 4 en 1 si un cliente seleccionado tiene notas pendientes.
     */
    public function test_pos_customer_pending_notes_detection_links_and_walkin_isolation(): void
    {
        $this->actingAs($this->user);

        // 1. La consola POS incluye el contenedor de alerta de notas pendientes y el endpoint
        $this->get(route('central-pos.index'))
            ->assertStatus(200)
            ->assertSee('id="posCustomerPendingNotesBox"', false)
            ->assertSee(route('central-pos.lookup-pending-notes'), false);

        // 2. Generar una venta parcial en POS dejando saldo pendiente ($450.00) al cliente del catálogo
        $partialResp = $this->postJson(route('central-pos.checkout'), [
            'branch_id' => $this->branchMorelos->id,
            'customer_id' => $this->customer->id,
            'customer_name' => $this->customer->tit_name,
            'customer_phone' => $this->customer->tit_phone,
            'payment_method' => 'efectivo',
            'cash_amount' => 750.00,
            'paid_amount' => 750.00,
            'items' => [
                [
                    'section' => 'producto_dinamico',
                    'name' => 'Audifonos Bluetooth Prueba',
                    'title' => 'Audifonos Bluetooth Prueba',
                    'quantity' => 1,
                    'amount' => 1200.00,
                    'commission' => 0.00,
                ],
            ],
        ]);
        $partialResp->assertStatus(200);

        $tx = PosTransaction::findOrFail($partialResp->json('transaction_id'));
        $order = Order::findOrFail($tx->order_id);
        $this->assertEquals(450.00, (float) $order->due);

        // 3. Venta parcial de mostrador sin customer_id (fallback orders.customer_id hacia primer cliente)
        $walkinResp = $this->postJson(route('central-pos.checkout'), [
            'branch_id' => $this->branchAllende->id,
            'customer_id' => null,
            'customer_name' => 'Pedro Walkin Prueba',
            'customer_phone' => '8265559988',
            'payment_method' => 'efectivo',
            'cash_amount' => 100.00,
            'paid_amount' => 100.00,
            'items' => [
                [
                    'section' => 'producto_dinamico',
                    'name' => 'Cargador Turbo Prueba',
                    'title' => 'Cargador Turbo Prueba',
                    'quantity' => 1,
                    'amount' => 300.00,
                    'commission' => 0.00,
                ],
            ],
        ]);
        $walkinResp->assertStatus(200);
        $walkinTx = PosTransaction::findOrFail($walkinResp->json('transaction_id'));

        // 4. Seleccionar al cliente en POS detecta su nota pendiente con detalle y enlaces
        $lookupCust = $this->getJson(route('central-pos.lookup-pending-notes', [
            'customer_id' => $this->customer->id,
        ]));
        $lookupCust->assertStatus(200)
            ->assertJson([
                'searched' => true,
                'has_pending_notes' => true,
                'pending_notes_count' => 1,
                'total_due' => 450.0,
            ]);

        $note = $lookupCust->json('pending_notes.0');
        $this->assertEquals($order->invoice_no, $note['invoice_no']);
        $this->assertEquals('Montemorelos', $note['branch_name']);
        $this->assertStringContainsString('Audifonos Bluetooth Prueba', $note['concepts']);
        $this->assertEquals(1200.0, $note['total']);
        $this->assertEquals(750.0, $note['pay']);
        $this->assertEquals(450.0, $note['due']);
        $this->assertNotEmpty($note['detail_url']);
        $this->assertNotEmpty($note['pending_due_url']);

        // Verificar que las rutas destino (Ver nota y Notas Pendientes filtrada) respondan 200 sin error SQL
        $this->get($note['detail_url'])
            ->assertStatus(200)
            ->assertSee($order->invoice_no);

        $this->get($note['pending_due_url'])
            ->assertStatus(200)
            ->assertSee($order->invoice_no)
            ->assertSee($this->customer->tit_name);

        // 5. La nota walk-in no se mezcla con otros clientes del catálogo, y sí aparece al buscar por nombre/teléfono
        $cleanCustomer = Customer::create([
            'tit_name' => 'Cliente Limpio Sin Deuda',
            'tit_email' => 'limpio_' . uniqid() . '@example.com',
            'tit_phone' => '8260001122',
            'branch_id' => $this->branchAllende->id,
        ]);

        $this->getJson(route('central-pos.lookup-pending-notes', ['customer_id' => $cleanCustomer->id]))
            ->assertStatus(200)
            ->assertJson([
                'searched' => true,
                'has_pending_notes' => false,
                'pending_notes_count' => 0,
                'total_due' => 0,
            ]);

        $fallbackOwner = Customer::find($walkinTx->order->customer_id);
        if ($fallbackOwner && mb_strtolower($fallbackOwner->tit_name, 'UTF-8') !== 'pedro walkin prueba') {
            $fallbackCheck = $this->getJson(route('central-pos.lookup-pending-notes', ['customer_id' => $fallbackOwner->id]));
            $fallbackCheck->assertStatus(200);
            $fallbackInvoices = collect($fallbackCheck->json('pending_notes'))->pluck('invoice_no')->all();
            $this->assertNotContains($walkinTx->order->invoice_no, $fallbackInvoices);
        }

        $lookupByName = $this->getJson(route('central-pos.lookup-pending-notes', ['customer_name' => 'Pedro Walkin Prueba']));
        $lookupByName->assertStatus(200)
            ->assertJson([
                'searched' => true,
                'has_pending_notes' => true,
                'pending_notes_count' => 1,
                'total_due' => 200.0,
            ]);

        // 6. Permisos: cajera POS sin orders.read ve la alerta pero sin enlaces de órdenes; usuario sin POS recibe 403
        $posOnlyRole = Role::create(['name' => 'PosNotasView_' . uniqid(), 'guard_name' => 'web']);
        $posOnlyRole->givePermissionTo(['cobros.menu', 'cobros.read']);
        $posOnlyUser = User::create([
            'name' => 'Cajera Solo POS',
            'username' => 'posonly_' . uniqid(),
            'email' => 'posonly_' . uniqid() . '@example.com',
            'password' => bcrypt('secret123'),
            'branch_id' => $this->branchAllende->id,
        ]);
        $posOnlyUser->assignRole($posOnlyRole);

        $this->actingAs($posOnlyUser)
            ->getJson(route('central-pos.lookup-pending-notes', ['customer_id' => $this->customer->id]))
            ->assertStatus(200)
            ->assertJsonPath('has_pending_notes', true)
            ->assertJsonPath('pending_notes.0.detail_url', null)
            ->assertJsonPath('pending_notes.0.pending_due_url', null);

        $noPosRole = Role::create(['name' => 'NoPosNotas_' . uniqid(), 'guard_name' => 'web']);
        $noPosRole->givePermissionTo(['customer.read']);
        $noPosUser = User::create([
            'name' => 'Sin POS',
            'username' => 'nopos2_' . uniqid(),
            'email' => 'nopos2_' . uniqid() . '@example.com',
            'password' => bcrypt('secret123'),
            'branch_id' => $this->branchAllende->id,
        ]);
        $noPosUser->assignRole($noPosRole);

        $this->actingAs($noPosUser)
            ->getJson(route('central-pos.lookup-pending-notes', ['customer_id' => $this->customer->id]))
            ->assertStatus(403);
    }
}
