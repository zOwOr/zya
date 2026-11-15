<?php

namespace Tests\Unit;

use App\Services\FinSaleSellerChartService;
use Barryvdh\DomPDF\Facade\Pdf;
use Carbon\Carbon;
use Illuminate\Support\Collection;
use PhpOffice\PhpSpreadsheet\IOFactory;
use PhpOffice\PhpSpreadsheet\Spreadsheet;
use PhpOffice\PhpSpreadsheet\Writer\Xlsx;
use Tests\TestCase;
use ZipArchive;

class FinSaleExportChartTest extends TestCase
{
    protected function makeFakeSale(
        string $seller,
        ?string $financieraName,
        string $saleType = 'credito',
        string $status = 'activa',
        ?int $sellerId = null
    ): \stdClass {
        $sale = new \stdClass();
        $sale->seller_id = $sellerId;
        $sale->seller_display_name = $seller;
        $sale->seller_name = $seller;
        $sale->financiera = $financieraName ? (object) ['name' => $financieraName] : null;
        $sale->sale_type = $saleType;
        $sale->status = $status;
        $sale->sale_code = 'VFIN-TEST';
        $sale->sale_date = Carbon::parse('2026-10-08');
        $sale->tag_contrato = 'TAG-1';
        $sale->customer_name = 'Cliente Test';
        $sale->customer_phone = '5550000000';
        $sale->price = 3500.0;
        $sale->down_payment = 500.0;
        $sale->enganche_descuento = null;
        $sale->credit_amount = 3000.0;
        $sale->total_abonos_paid = 0.0;
        $sale->branch = (object) ['name' => 'Matriz'];
        $sale->device = (object) ['brand' => (object) ['name' => 'Xiaomi'], 'model' => 'Redmi', 'imei' => '123456789012345'];

        return $sale;
    }

    protected function buildReferenceDataset(): Collection
    {
        $sales = collect();

        foreach (range(1, 2) as $i) {
            $sales->push($this->makeFakeSale('Alicia', 'PayJoy'));
        }
        $sales->push($this->makeFakeSale('Alicia', 'Credimás'));
        $sales->push($this->makeFakeSale('Alicia', 'Creditienda'));
        $sales->push($this->makeFakeSale('Alicia', 'Credimás', 'credito', 'cancelada'));

        foreach (range(1, 4) as $i) {
            $sales->push($this->makeFakeSale('Jimena', 'PayJoy'));
        }
        foreach (range(1, 2) as $i) {
            $sales->push($this->makeFakeSale('Jimena', 'Credimás'));
        }

        foreach (range(1, 5) as $i) {
            $sales->push($this->makeFakeSale('Lucero', 'PayJoy'));
        }
        foreach (range(1, 2) as $i) {
            $sales->push($this->makeFakeSale('Lucero', 'Credimás'));
        }
        $sales->push($this->makeFakeSale('Lucero', null, 'contado'));

        foreach (range(1, 8) as $i) {
            $sales->push($this->makeFakeSale('Monse', 'PayJoy'));
        }
        foreach (range(1, 3) as $i) {
            $sales->push($this->makeFakeSale('Monse', 'Credimás'));
        }

        return $sales;
    }

    public function test_builds_seller_chart_data_matching_period_contado_and_cancelled_exclusion(): void
    {
        $sales = $this->buildReferenceDataset();
        $dateFilter = [
            'filter_type' => 'rango',
            'start_date' => '2026-10-05',
            'end_date' => '2026-10-10',
        ];

        $chart = FinSaleSellerChartService::buildFromCollection($sales, $dateFilter);

        $this->assertSame('5 al 10 de octubre de 2026', $chart['period_text']);
        $this->assertSame(29, $chart['total_sales']);
        $this->assertSame(30, $chart['total_all_rows']);
        $this->assertSame(1, $chart['cancelled_count']);
        $this->assertSame('Venta cancelada de Alicia en Credimás descontada.', $chart['cancelled_note']);

        $sellerNames = array_map(fn ($s) => $s['upper_name'], $chart['sellers']);
        $sellerTotals = array_map(fn ($s) => $s['total'], $chart['sellers']);
        $this->assertSame(['ALICIA', 'JIMENA', 'LUCERO', 'MONSE'], $sellerNames);
        $this->assertSame([4, 6, 8, 11], $sellerTotals);

        $lucero = $chart['sellers'][2];
        $this->assertSame([
            ['name' => 'PayJoy', 'count' => 5],
            ['name' => 'Credimás', 'count' => 2],
            ['name' => 'Contado', 'count' => 1],
        ], $lucero['financieras']);

        $this->assertSame([
            ['name' => 'PayJoy', 'count' => 19],
            ['name' => 'Credimás', 'count' => 8],
            ['name' => 'Creditienda', 'count' => 1],
            ['name' => 'Contado', 'count' => 1],
        ], $chart['global_financieras']);
    }

    public function test_spanish_period_formatting_covers_all_filter_modes(): void
    {
        $this->assertSame(
            '14 de noviembre de 2026',
            FinSaleSellerChartService::formatSpanishPeriod(['filter_type' => 'dia', 'date' => '2026-11-14'])
        );
        $this->assertSame(
            '28 de octubre al 3 de noviembre de 2026',
            FinSaleSellerChartService::formatSpanishPeriod([
                'filter_type' => 'rango',
                'start_date' => '2026-10-28',
                'end_date' => '2026-11-03',
            ])
        );
        $this->assertSame(
            '28 de diciembre de 2025 al 4 de enero de 2026',
            FinSaleSellerChartService::formatSpanishPeriod([
                'filter_type' => 'rango',
                'start_date' => '2025-12-28',
                'end_date' => '2026-01-04',
            ])
        );
        $this->assertSame(
            'Todos los registros (Histórico completo)',
            FinSaleSellerChartService::formatSpanishPeriod(['filter_type' => 'todos'])
        );
        $this->assertSame(
            'Noviembre 2026',
            FinSaleSellerChartService::formatSpanishPeriod([], 'Noviembre 2026')
        );
    }

    public function test_handles_zero_active_sales_single_seller_and_multiple_cancellations(): void
    {
        // Solo ventas canceladas -> círculo vacío + nota
        $onlyCancelled = collect([
            $this->makeFakeSale('Alicia', 'PayJoy', 'credito', 'cancelada'),
            $this->makeFakeSale('Alicia', 'PayJoy', 'credito', 'cancelada'),
        ]);
        $emptyChart = FinSaleSellerChartService::buildFromCollection($onlyCancelled, ['filter_type' => 'todos']);
        $this->assertSame(0, $emptyChart['total_sales']);
        $this->assertSame('2 ventas canceladas de Alicia en PayJoy descontadas.', $emptyChart['cancelled_note']);
        $emptySvg = FinSaleSellerChartService::renderSvg($emptyChart);
        $this->assertStringContainsString('Sin ventas activas registradas en este período', $emptySvg);

        // Una sola vendedora -> círculo completo y múltiples cancelaciones mixtas
        $singleSeller = collect([
            $this->makeFakeSale('Lucero', 'PayJoy'),
            $this->makeFakeSale('Lucero', null, 'credito'), // Directo sin financiera asociada
            $this->makeFakeSale('Alicia', 'PayJoy', 'credito', 'cancelada'),
            $this->makeFakeSale('Jimena', 'Credimás', 'credito', 'cancelada'),
        ]);
        $singleChart = FinSaleSellerChartService::buildFromCollection($singleSeller, ['filter_type' => 'dia', 'date' => '2026-11-14']);
        $this->assertSame(2, $singleChart['total_sales']);
        $this->assertSame(
            '2 ventas canceladas descontadas: Alicia en PayJoy, Jimena en Credimás.',
            $singleChart['cancelled_note']
        );
        $singleSvg = FinSaleSellerChartService::renderSvg($singleChart);
        $this->assertStringContainsString('<circle ', $singleSvg);
        $this->assertStringContainsString('LUCERO', $singleSvg);
        $this->assertStringContainsString('Directo: 1', $singleSvg);
    }

    public function test_renders_valid_svg_callout_fallback_for_narrow_slices_and_valid_png_binary(): void
    {
        $counts = [
            ['Ángela Núñez', [['PayJoy', 1]]],
            ['Sofía Méndez', [['Credimás', 1]]],
            ['Valeria Cruz', [['Krediya', 1]]],
            ['Camila Reyes', [['PayJoy', 1], ['Contado', 1]]],
            ['Fernanda Gil', [['PayJoy', 2], ['Credimás', 1]]],
            ['Lucero Pérez', [['PayJoy', 3], ['Creditienda', 2]]],
            ['Jimena Ortiz', [['PayJoy', 5], ['Credimás', 3]]],
            ['Monse Herrera', [['PayJoy', 8], ['Credimás', 4]]],
        ];

        $sales = collect();
        foreach ($counts as [$seller, $items]) {
            foreach ($items as [$fin, $qty]) {
                for ($i = 0; $i < $qty; $i++) {
                    $sales->push($this->makeFakeSale(
                        $seller,
                        $fin === 'Contado' ? null : $fin,
                        $fin === 'Contado' ? 'contado' : 'credito'
                    ));
                }
            }
        }

        $chart = FinSaleSellerChartService::buildFromCollection($sales, [
            'filter_type' => 'mes',
            'start_date' => '2026-11-01',
            'end_date' => '2026-11-30',
        ]);

        $svg = FinSaleSellerChartService::renderSvg($chart);
        $this->assertStringContainsString('<svg ', $svg);
        $this->assertStringContainsString('<polyline ', $svg);
        $this->assertStringContainsString('SOFÍA MÉNDEZ', $svg);
        $this->assertStringContainsString('MONSE HERRERA', $svg);

        $png = FinSaleSellerChartService::renderPngBinary($chart);
        $this->assertStringStartsWith("\x89PNG\r\n\x1a\n", $png);
        $info = @getimagesizefromstring($png);
        $this->assertIsArray($info);
        $this->assertSame('image/png', $info['mime']);
        $this->assertGreaterThan(500, $info[0]);
        $this->assertGreaterThan(400, $info[1]);
    }

    public function test_list_pdf_view_renders_with_and_without_explicit_chart_data_and_outputs_pdf(): void
    {
        $sales = $this->buildReferenceDataset();
        $activeSales = $sales->where('status', 'activa');
        $totals = [
            'count' => $sales->count(),
            'active_count' => $activeSales->count(),
            'price_total' => 101500.00,
            'down_payment_total' => 14500.00,
            'credit_total' => 87000.00,
            'abonos_total' => 12000.00,
        ];
        $periodLabel = 'Del 05/10/2026 al 10/10/2026';
        $branchName = 'Todas las Sucursales';
        $financieraFilterLabel = 'Todas las Financieras';

        // Retrocompatibilidad sin $sellerChart ni $dateFilter
        $compatHtml = view(
            'financieras.ventas.list-pdf',
            compact('sales', 'totals', 'periodLabel', 'branchName', 'financieraFilterLabel')
        )->render();
        $this->assertStringContainsString('VENTAS POR VENDEDORA', $compatHtml);
        $this->assertStringContainsString('data:image/svg+xml;base64,', $compatHtml);
        $this->assertStringContainsString('PayJoy: 19', $compatHtml);

        // Con $dateFilter y $sellerChart calculados explícitamente (como en FinSaleController::exportListPdf)
        $dateFilter = [
            'filter_type' => 'rango',
            'start_date' => '2026-10-05',
            'end_date' => '2026-10-10',
        ];
        $sellerChart = FinSaleSellerChartService::buildFromCollection($sales, $dateFilter, $periodLabel);
        $fullHtml = view(
            'financieras.ventas.list-pdf',
            compact('sales', 'totals', 'periodLabel', 'dateFilter', 'sellerChart', 'branchName', 'financieraFilterLabel')
        )->render();

        $this->assertStringContainsString('5 al 10 de octubre de 2026', $fullHtml);
        $this->assertStringContainsString('Venta cancelada de Alicia en Credimás descontada.', $fullHtml);

        $pdfBytes = Pdf::loadHTML($fullHtml)->setPaper('letter', 'landscape')->output();
        $this->assertStringStartsWith('%PDF-', $pdfBytes);
        $this->assertGreaterThan(5000, strlen($pdfBytes));
    }

    public function test_excel_chart_sheet_embeds_png_drawing_and_summary_formulas(): void
    {
        $sales = $this->buildReferenceDataset();
        $chart = FinSaleSellerChartService::buildFromCollection($sales, [
            'filter_type' => 'rango',
            'start_date' => '2026-10-05',
            'end_date' => '2026-10-10',
        ]);

        $spreadsheet = new Spreadsheet();
        $sheet1 = $spreadsheet->getActiveSheet();
        $sheet1->setTitle('Ventas Financieras');
        $sheet1->setCellValue('A1', 'FECHA DE VENTA');

        $pngPath = FinSaleSellerChartService::appendExcelChartSheet($spreadsheet, $chart);
        $this->assertNotNull($pngPath);
        $this->assertFileExists($pngPath);

        $baseXlsx = tempnam(sys_get_temp_dir(), 'fin_chart_xlsx_test_');
        @unlink($baseXlsx);
        $xlsxPath = $baseXlsx . '.xlsx';
        try {
            (new Xlsx($spreadsheet))->save($xlsxPath);
        } finally {
            @unlink($pngPath);
        }

        try {
            $loaded = IOFactory::load($xlsxPath);
            $this->assertSame(['Ventas Financieras', 'Gráfico Vendedora'], $loaded->getSheetNames());
            $this->assertSame(1, $loaded->getActiveSheetIndex());

            $chartSheet = $loaded->getSheetByName('Gráfico Vendedora');
            $this->assertNotNull($chartSheet);

            $drawings = $chartSheet->getDrawingCollection();
            $this->assertCount(1, $drawings);
            $this->assertSame('Ventas por Vendedora', $drawings[0]->getName());
            $this->assertSame('A1', $drawings[0]->getCoordinates());

            $this->assertSame('RESUMEN DE VENTAS POR VENDEDORA', $chartSheet->getCell('K2')->getValue());
            $this->assertSame('5 al 10 de octubre de 2026 • Total: 29 ventas', $chartSheet->getCell('K3')->getValue());
            $this->assertSame('VENDEDORA', $chartSheet->getCell('K5')->getValue());
            $this->assertSame('PayJoy', $chartSheet->getCell('L5')->getValue());
            $this->assertSame('Credimás', $chartSheet->getCell('M5')->getValue());
            $this->assertSame('Creditienda', $chartSheet->getCell('N5')->getValue());
            $this->assertSame('Contado', $chartSheet->getCell('O5')->getValue());
            $this->assertSame('TOTAL', $chartSheet->getCell('P5')->getValue());

            $this->assertSame('ALICIA', $chartSheet->getCell('K6')->getValue());
            $this->assertSame(2, (int) $chartSheet->getCell('L6')->getValue());
            $this->assertStringStartsWith('=SUM(', (string) $chartSheet->getCell('P6')->getValue());

            $this->assertSame('TOTAL GENERAL', $chartSheet->getCell('K10')->getValue());
            $this->assertSame('=SUM(L6:L9)', $chartSheet->getCell('L10')->getValue());
            $this->assertSame('=SUM(P6:P9)', $chartSheet->getCell('P10')->getValue());
            $this->assertStringContainsString('Venta cancelada de Alicia en Credimás descontada.', (string) $chartSheet->getCell('K12')->getValue());

            $zip = new ZipArchive();
            $this->assertTrue($zip->open($xlsxPath));
            $mediaPng = null;
            for ($i = 0; $i < $zip->numFiles; $i++) {
                $entryName = $zip->getNameIndex($i);
                if (is_string($entryName) && str_starts_with($entryName, 'xl/media/') && str_ends_with($entryName, '.png')) {
                    $mediaPng = $zip->getFromIndex($i);
                    break;
                }
            }
            $zip->close();
            $this->assertIsString($mediaPng);
            $this->assertStringStartsWith("\x89PNG\r\n\x1a\n", $mediaPng);
            $imgInfo = @getimagesizefromstring($mediaPng);
            $this->assertIsArray($imgInfo);
            $this->assertGreaterThan(500, $imgInfo[0]);
            $this->assertGreaterThan(400, $imgInfo[1]);
        } finally {
            @unlink($xlsxPath);
        }
    }

    public function test_controller_and_ventas_view_export_wiring_are_in_place(): void
    {
        $controller = file_get_contents(app_path('Http/Controllers/Dashboard/FinSaleController.php'));
        $this->assertStringContainsString('FinSaleSellerChartService::appendExcelChartSheet', $controller);
        $this->assertStringContainsString("FinSaleSellerChartService::buildFromCollection(\$sales, \$dateFilter, \$periodLabel)", $controller);
        $this->assertStringContainsString("compact('sales', 'totals', 'periodLabel', 'dateFilter', 'sellerChart', 'branchName', 'financieraFilterLabel')", $controller);

        $ventasView = file_get_contents(resource_path('views/financieras/ventas/index.blade.php'));
        $this->assertStringContainsString('id="btnExportFinSalesPdf"', $ventasView);
        $this->assertStringContainsString('id="btnExportFinSalesExcel"', $ventasView);
        $this->assertStringContainsString('syncFinSalesExportLinks', $ventasView);
    }
}
