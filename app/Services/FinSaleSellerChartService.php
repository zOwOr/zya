<?php

namespace App\Services;

use Barryvdh\DomPDF\Facade\Pdf;
use Carbon\Carbon;
use Illuminate\Support\Collection;
use PhpOffice\PhpSpreadsheet\Cell\Coordinate;
use PhpOffice\PhpSpreadsheet\Spreadsheet;
use PhpOffice\PhpSpreadsheet\Style\Alignment;
use PhpOffice\PhpSpreadsheet\Style\Border;
use PhpOffice\PhpSpreadsheet\Style\Fill;
use PhpOffice\PhpSpreadsheet\Worksheet\Drawing;
use PhpOffice\PhpSpreadsheet\Worksheet\Worksheet;

class FinSaleSellerChartService
{
    public const PASTEL_PALETTE = [
        '#dab8ec', // Soft lilac
        '#edc67c', // Soft amber
        '#b2dec9', // Soft mint
        '#a5c5f4', // Soft periwinkle blue
        '#f3b6c4', // Soft rose
        '#a8dce6', // Soft cyan
        '#f1c0a3', // Soft peach
        '#c4c7f5', // Soft lavender
        '#d9e09b', // Soft sage lime
        '#e7c2b6', // Soft warm blush
    ];

    protected const SPANISH_MONTHS = [
        1 => 'enero',
        2 => 'febrero',
        3 => 'marzo',
        4 => 'abril',
        5 => 'mayo',
        6 => 'junio',
        7 => 'julio',
        8 => 'agosto',
        9 => 'septiembre',
        10 => 'octubre',
        11 => 'noviembre',
        12 => 'diciembre',
    ];

    /**
     * Construye los datos agregados por vendedora y financiera a partir de las ventas filtradas.
     */
    public static function buildFromCollection(
        Collection $sales,
        array $dateFilter = [],
        ?string $fallbackPeriodLabel = null
    ): array {
        $activeSales = $sales->filter(fn ($sale) => strtolower((string) ($sale->status ?? 'activa')) !== 'cancelada')->values();
        $cancelledSales = $sales->filter(fn ($sale) => strtolower((string) ($sale->status ?? '')) === 'cancelada')->values();

        $sellerBuckets = [];
        $globalFinancieras = [];

        foreach ($activeSales as $sale) {
            [$sellerKey, $sellerName] = self::resolveSellerIdentity($sale);
            $finLabel = self::resolveFinancieraLabel($sale);

            if (!isset($sellerBuckets[$sellerKey])) {
                $sellerBuckets[$sellerKey] = [
                    'key' => $sellerKey,
                    'name' => $sellerName,
                    'upper_name' => mb_strtoupper($sellerName, 'UTF-8'),
                    'total' => 0,
                    'financieras' => [],
                ];
            }

            $sellerBuckets[$sellerKey]['total']++;
            $sellerBuckets[$sellerKey]['financieras'][$finLabel] = ($sellerBuckets[$sellerKey]['financieras'][$finLabel] ?? 0) + 1;
            $globalFinancieras[$finLabel] = ($globalFinancieras[$finLabel] ?? 0) + 1;
        }

        // Ordenar vendedoras de menor a mayor (mantiene el acomodo visual equilibrado en sentido horario)
        $sellersList = array_values($sellerBuckets);
        usort($sellersList, function (array $a, array $b) {
            if ($a['total'] !== $b['total']) {
                return $a['total'] <=> $b['total'];
            }

            return strcasecmp($a['name'], $b['name']);
        });

        $totalActive = $activeSales->count();
        $paletteCount = count(self::PASTEL_PALETTE);

        foreach ($sellersList as $idx => &$bucket) {
            $bucket['color'] = self::PASTEL_PALETTE[$idx % $paletteCount];
            $bucket['percentage'] = $totalActive > 0 ? round(($bucket['total'] / $totalActive) * 100, 1) : 0.0;
            $bucket['financieras'] = self::sortFinancieraCounts($bucket['financieras']);
        }
        unset($bucket);

        return [
            'period_text' => self::formatSpanishPeriod($dateFilter, $fallbackPeriodLabel),
            'total_sales' => $totalActive,
            'total_all_rows' => $sales->count(),
            'sellers' => $sellersList,
            'global_financieras' => self::sortFinancieraCounts($globalFinancieras),
            'cancelled_count' => $cancelledSales->count(),
            'cancelled_note' => self::buildCancelledNote($cancelledSales),
        ];
    }

    /**
     * Formatea el período seleccionado en español natural (ej. "5 al 10 de octubre de 2026").
     */
    public static function formatSpanishPeriod(array $dateFilter, ?string $fallbackPeriodLabel = null): string
    {
        $type = $dateFilter['filter_type'] ?? null;

        if ($type === 'todos') {
            return 'Todos los registros (Histórico completo)';
        }

        try {
            if ($type === 'dia' && !empty($dateFilter['date'])) {
                $d = Carbon::parse($dateFilter['date']);

                return $d->day . ' de ' . self::SPANISH_MONTHS[(int) $d->month] . ' de ' . $d->year;
            }

            if (!empty($dateFilter['start_date']) && !empty($dateFilter['end_date'])) {
                $start = Carbon::parse($dateFilter['start_date']);
                $end = Carbon::parse($dateFilter['end_date']);

                if ($start->isSameDay($end)) {
                    return $start->day . ' de ' . self::SPANISH_MONTHS[(int) $start->month] . ' de ' . $start->year;
                }

                if ($start->year === $end->year && $start->month === $end->month) {
                    return $start->day . ' al ' . $end->day . ' de ' . self::SPANISH_MONTHS[(int) $end->month] . ' de ' . $end->year;
                }

                if ($start->year === $end->year) {
                    return $start->day . ' de ' . self::SPANISH_MONTHS[(int) $start->month]
                        . ' al ' . $end->day . ' de ' . self::SPANISH_MONTHS[(int) $end->month]
                        . ' de ' . $end->year;
                }

                return $start->day . ' de ' . self::SPANISH_MONTHS[(int) $start->month] . ' de ' . $start->year
                    . ' al ' . $end->day . ' de ' . self::SPANISH_MONTHS[(int) $end->month] . ' de ' . $end->year;
            }
        } catch (\Throwable $e) {
            // Usar respaldo legible si una fecha viniera malformada
        }

        return trim((string) $fallbackPeriodLabel) !== '' ? (string) $fallbackPeriodLabel : 'Período seleccionado';
    }

    /**
     * Genera el SVG vectorial del gráfico de pastel agrupado por vendedora.
     */
    public static function renderSvg(array $chartData): string
    {
        $width = 800;
        $height = 440;
        $cx = 400.0;
        $cy = 220.0;
        $radius = 184.0;

        $total = (int) ($chartData['total_sales'] ?? 0);
        $sellers = $chartData['sellers'] ?? [];

        if ($total <= 0 || empty($sellers)) {
            return <<<SVG
<svg xmlns="http://www.w3.org/2000/svg" width="{$width}" height="{$height}" viewBox="0 0 {$width} {$height}">
  <rect width="{$width}" height="{$height}" fill="#fcfbf9"/>
  <circle cx="{$cx}" cy="{$cy}" r="{$radius}" fill="#f1f5f9" stroke="#cbd5e1" stroke-width="2" stroke-dasharray="6,4"/>
  <text x="{$cx}" y="{$cy}" text-anchor="middle" font-family="DejaVu Sans" font-size="14" fill="#64748b">Sin ventas activas registradas en este período</text>
</svg>
SVG;
        }

        $slices = self::computeSlicesGeometry($sellers, $total, $cx, $cy, $radius);
        $pathsXml = '';
        $insideLabelsXml = '';
        $externalCallouts = [];

        foreach ($slices as $slice) {
            if ($slice['is_full_circle']) {
                $pathsXml .= sprintf(
                    '<circle cx="%.2f" cy="%.2f" r="%.2f" fill="%s" stroke="#fcfbf9" stroke-width="3"/>' . "\n",
                    $cx,
                    $cy,
                    $radius,
                    $slice['color']
                );
            } else {
                $x1 = $cx + $radius * cos($slice['start_rad']);
                $y1 = $cy + $radius * sin($slice['start_rad']);
                $x2 = $cx + $radius * cos($slice['end_rad']);
                $y2 = $cy + $radius * sin($slice['end_rad']);
                $largeArc = ($slice['sweep'] > M_PI) ? 1 : 0;

                $pathsXml .= sprintf(
                    '<path d="M %.2f %.2f L %.2f %.2f A %.2f %.2f 0 %d 1 %.2f %.2f Z" fill="%s" stroke="#fcfbf9" stroke-width="3" stroke-linejoin="round"/>' . "\n",
                    $cx,
                    $cy,
                    $x1,
                    $y1,
                    $radius,
                    $radius,
                    $largeArc,
                    $x2,
                    $y2,
                    $slice['color']
                );
            }

            $lines = self::buildSellerLabelLines($slice['seller']);
            $placement = self::findInsideWedgePlacement(
                $slice['start_rad'],
                $slice['end_rad'],
                $cx,
                $cy,
                $radius,
                $lines
            );

            if ($placement !== null) {
                $insideLabelsXml .= self::renderLabelLinesXml($placement['x'], $placement['y'], $placement['font_size'], $lines, 'middle');
            } else {
                $externalCallouts[] = [
                    'slice' => $slice,
                    'lines' => $lines,
                ];
            }
        }

        $calloutsXml = self::renderExternalCalloutsXml($externalCallouts, $cx, $cy, $radius, $width, $height);

        return "<svg xmlns=\"http://www.w3.org/2000/svg\" width=\"{$width}\" height=\"{$height}\" viewBox=\"0 0 {$width} {$height}\">\n"
            . "  <rect width=\"{$width}\" height=\"{$height}\" fill=\"#fcfbf9\"/>\n"
            . $pathsXml
            . $insideLabelsXml
            . $calloutsXml
            . '</svg>';
    }

    /**
     * Genera la tarjeta HTML autocontenida (usada tanto en el PDF como para rasterizar a PNG).
     */
    public static function renderCardHtml(array $chartData, array $options = []): string
    {
        $svg = self::renderSvg($chartData);
        $svgDataUri = 'data:image/svg+xml;base64,' . base64_encode($svg);

        $totalSales = (int) ($chartData['total_sales'] ?? 0);
        $totalWord = $totalSales === 1 ? 'venta' : 'ventas';
        $periodEsc = htmlspecialchars((string) ($chartData['period_text'] ?? 'Período seleccionado'), ENT_QUOTES, 'UTF-8');

        $globalFinParts = [];
        foreach ($chartData['global_financieras'] ?? [] as $item) {
            $globalFinParts[] = htmlspecialchars($item['name'] . ': ' . $item['count'], ENT_QUOTES, 'UTF-8');
        }
        $globalSummaryHtml = !empty($globalFinParts)
            ? implode(' <span style="color:#64748b;padding:0 5px;">&bull;</span> ', $globalFinParts)
            : '';

        $cancelledHtml = '';
        if (!empty($chartData['cancelled_note'])) {
            $noteEsc = htmlspecialchars((string) $chartData['cancelled_note'], ENT_QUOTES, 'UTF-8');
            $cancelledHtml = '<div style="margin-top:6px;font-size:8.5pt;color:#475569;text-align:center;">' . $noteEsc . '</div>';
        }

        $standalone = !empty($options['standalone']);
        $imgWidth = $standalone ? '660px' : '540px';
        $cardCss = $standalone
            ? 'background:#fcfbf9;padding:16px 20px;text-align:center;'
            : 'background:#fcfbf9;border:1px solid #e2e8f0;border-radius:8px;padding:12px 16px;text-align:center;page-break-inside:avoid;';

        $summaryBlock = $globalSummaryHtml !== ''
            ? '<div style="margin-top:8px;font-size:10pt;color:#1e293b;text-align:center;line-height:1.4;">' . $globalSummaryHtml . '</div>'
            : '';

        return <<<HTML
<div style="{$cardCss}font-family:'DejaVu Sans',Arial,sans-serif;">
    <div style="font-size:16pt;font-weight:bold;color:#1e293b;letter-spacing:0.3px;">VENTAS POR VENDEDORA</div>
    <div style="font-size:9.5pt;color:#475569;margin-top:3px;margin-bottom:8px;">{$periodEsc} &nbsp;&bull;&nbsp; Total: {$totalSales} {$totalWord}</div>
    <div style="text-align:center;">
        <img src="{$svgDataUri}" alt="Gráfico Ventas por Vendedora" style="width:{$imgWidth};max-width:100%;height:auto;display:inline-block;"/>
    </div>
    {$summaryBlock}
    {$cancelledHtml}
</div>
HTML;
    }

    /**
     * Genera binario PNG del gráfico de pastel para incrustarlo en Excel.
     * Prioriza rasterización exacta DomPDF + pdftoppm/gs, luego GD+TTF, y por último PNG puro en PHP (zlib).
     */
    public static function renderPngBinary(array $chartData): string
    {
        $png = self::tryRenderPngViaDompdf($chartData);
        if ($png !== null && str_starts_with($png, "\x89PNG\r\n\x1a\n")) {
            return $png;
        }

        if (extension_loaded('gd')) {
            $gdPng = self::tryRenderPngViaGd($chartData);
            if ($gdPng !== null && str_starts_with($gdPng, "\x89PNG\r\n\x1a\n")) {
                return $gdPng;
            }
        }

        return self::renderPngPurePhpFallback($chartData);
    }

    /**
     * Agrega la hoja "Gráfico Vendedora" al libro de Excel con el PNG del pastel y la matriz Vendedora × Financiera.
     * Retorna la ruta temporal del PNG para que el llamador lo elimine después de guardar el .xlsx.
     */
    public static function appendExcelChartSheet(Spreadsheet $spreadsheet, array $chartData): ?string
    {
        $chartSheet = $spreadsheet->createSheet();
        $chartSheet->setTitle('Gráfico Vendedora');
        $chartSheet->setShowGridLines(false);

        foreach (range(1, 9) as $colIdx) {
            $chartSheet->getColumnDimension(Coordinate::stringFromColumnIndex($colIdx))->setWidth(10.5);
        }
        $chartSheet->getColumnDimension('J')->setWidth(3.5);

        $pngBinary = self::renderPngBinary($chartData);
        $tempPngBase = tempnam(sys_get_temp_dir(), 'fin_sales_chart_');
        $tempPngPath = $tempPngBase . '.png';
        @unlink($tempPngBase);
        file_put_contents($tempPngPath, $pngBinary);

        $drawing = new Drawing();
        $drawing->setName('Ventas por Vendedora');
        $drawing->setDescription('Gráfico de ventas agrupadas por vendedora y financiera');
        $drawing->setPath($tempPngPath);
        $drawing->setCoordinates('A1');
        $drawing->setWidth(640);
        $drawing->setWorksheet($chartSheet);

        self::writeExcelSummaryTable($chartSheet, $chartData, 11); // Columna K

        $spreadsheet->setActiveSheetIndex($spreadsheet->getIndex($chartSheet));

        return $tempPngPath;
    }

    protected static function writeExcelSummaryTable(Worksheet $sheet, array $chartData, int $startCol): void
    {
        $financieras = array_map(fn ($item) => $item['name'], $chartData['global_financieras'] ?? []);
        $sellers = $chartData['sellers'] ?? [];
        $totalCols = max(3, 1 + count($financieras) + 2); // Vendedora + N financieras + Total + %
        $endCol = $startCol + $totalCols - 1;
        $startLetter = Coordinate::stringFromColumnIndex($startCol);
        $endLetter = Coordinate::stringFromColumnIndex($endCol);

        // Título y subtítulo de la tabla resumen
        $sheet->setCellValueByColumnAndRow($startCol, 2, 'RESUMEN DE VENTAS POR VENDEDORA');
        $sheet->mergeCells("{$startLetter}2:{$endLetter}2");
        $sheet->getStyle("{$startLetter}2:{$endLetter}2")->applyFromArray([
            'font' => ['bold' => true, 'size' => 12, 'color' => ['rgb' => 'FFFFFF']],
            'fill' => ['fillType' => Fill::FILL_SOLID, 'startColor' => ['rgb' => '1E293B']],
            'alignment' => ['horizontal' => Alignment::HORIZONTAL_CENTER, 'vertical' => Alignment::VERTICAL_CENTER],
        ]);
        $sheet->getRowDimension(2)->setRowHeight(24);

        $totalSales = (int) ($chartData['total_sales'] ?? 0);
        $totalWord = $totalSales === 1 ? 'venta' : 'ventas';
        $subtitle = ($chartData['period_text'] ?? 'Período seleccionado') . " • Total: {$totalSales} {$totalWord}";
        $sheet->setCellValueByColumnAndRow($startCol, 3, $subtitle);
        $sheet->mergeCells("{$startLetter}3:{$endLetter}3");
        $sheet->getStyle("{$startLetter}3:{$endLetter}3")->applyFromArray([
            'font' => ['italic' => true, 'size' => 9.5, 'color' => ['rgb' => '475569']],
            'fill' => ['fillType' => Fill::FILL_SOLID, 'startColor' => ['rgb' => 'F8FAFC']],
            'alignment' => ['horizontal' => Alignment::HORIZONTAL_CENTER, 'vertical' => Alignment::VERTICAL_CENTER],
        ]);
        $sheet->getRowDimension(3)->setRowHeight(18);

        $headerRow = 5;
        $headers = array_merge(['VENDEDORA'], $financieras, ['TOTAL', '% PART.']);
        foreach ($headers as $offset => $label) {
            $sheet->setCellValueByColumnAndRow($startCol + $offset, $headerRow, $label);
        }
        $sheet->getStyle("{$startLetter}{$headerRow}:{$endLetter}{$headerRow}")->applyFromArray([
            'font' => ['bold' => true, 'size' => 9.5, 'color' => ['rgb' => 'FFFFFF']],
            'fill' => ['fillType' => Fill::FILL_SOLID, 'startColor' => ['rgb' => '065F46']],
            'alignment' => ['horizontal' => Alignment::HORIZONTAL_CENTER, 'vertical' => Alignment::VERTICAL_CENTER],
            'borders' => ['allBorders' => ['borderStyle' => Border::BORDER_THIN, 'color' => ['rgb' => '047857']]],
        ]);
        $sheet->getRowDimension($headerRow)->setRowHeight(22);

        $finFirstLetter = Coordinate::stringFromColumnIndex($startCol + 1);
        $finLastLetter = Coordinate::stringFromColumnIndex($startCol + max(1, count($financieras)));
        $totalColIdx = $startCol + 1 + count($financieras);
        $totalColLetter = Coordinate::stringFromColumnIndex($totalColIdx);
        $pctColIdx = $totalColIdx + 1;
        $pctColLetter = Coordinate::stringFromColumnIndex($pctColIdx);

        $dataStartRow = $headerRow + 1;
        $row = $dataStartRow;
        $totalSummaryRow = $dataStartRow + max(1, count($sellers));

        if (empty($sellers)) {
            $sheet->setCellValueByColumnAndRow($startCol, $row, 'Sin ventas activas en este período');
            $sheet->mergeCells("{$startLetter}{$row}:{$endLetter}{$row}");
            $sheet->getStyle("{$startLetter}{$row}:{$endLetter}{$row}")->applyFromArray([
                'font' => ['italic' => true, 'color' => ['rgb' => '64748B']],
                'alignment' => ['horizontal' => Alignment::HORIZONTAL_CENTER],
                'borders' => ['allBorders' => ['borderStyle' => Border::BORDER_THIN, 'color' => ['rgb' => 'CBD5E1']]],
            ]);
            $row++;
        } else {
            foreach ($sellers as $seller) {
                $sheet->setCellValueByColumnAndRow($startCol, $row, $seller['upper_name']);
                $finMap = [];
                foreach ($seller['financieras'] ?? [] as $item) {
                    $finMap[$item['name']] = (int) $item['count'];
                }

                foreach ($financieras as $idx => $finName) {
                    $sheet->setCellValueByColumnAndRow($startCol + 1 + $idx, $row, $finMap[$finName] ?? 0);
                }

                if (!empty($financieras)) {
                    $sheet->setCellValueByColumnAndRow(
                        $totalColIdx,
                        $row,
                        "=SUM({$finFirstLetter}{$row}:{$finLastLetter}{$row})"
                    );
                } else {
                    $sheet->setCellValueByColumnAndRow($totalColIdx, $row, (int) $seller['total']);
                }

                $sheet->setCellValueByColumnAndRow(
                    $pctColIdx,
                    $row,
                    "=IF({$totalColLetter}\${$totalSummaryRow}>0,{$totalColLetter}{$row}/{$totalColLetter}\${$totalSummaryRow},0)"
                );

                $fillRgb = ltrim((string) ($seller['color'] ?? 'F8FAFC'), '#');
                $sheet->getStyle("{$startLetter}{$row}")->applyFromArray([
                    'font' => ['bold' => true, 'color' => ['rgb' => '1E293B']],
                    'fill' => ['fillType' => Fill::FILL_SOLID, 'startColor' => ['rgb' => strtoupper($fillRgb)]],
                ]);
                $sheet->getStyle("{$finFirstLetter}{$row}:{$totalColLetter}{$row}")->getAlignment()->setHorizontal(Alignment::HORIZONTAL_CENTER);
                $sheet->getStyle("{$totalColLetter}{$row}")->getFont()->setBold(true);
                $sheet->getStyle("{$pctColLetter}{$row}")->getNumberFormat()->setFormatCode('0.0%');
                $sheet->getStyle("{$pctColLetter}{$row}")->getAlignment()->setHorizontal(Alignment::HORIZONTAL_RIGHT);
                $sheet->getStyle("{$startLetter}{$row}:{$endLetter}{$row}")->applyFromArray([
                    'borders' => ['allBorders' => ['borderStyle' => Border::BORDER_THIN, 'color' => ['rgb' => 'CBD5E1']]],
                ]);
                $sheet->getRowDimension($row)->setRowHeight(20);
                $row++;
            }
        }

        // Fila de totales generales
        $dataEndRow = max($dataStartRow, $row - 1);
        $sheet->setCellValueByColumnAndRow($startCol, $row, 'TOTAL GENERAL');
        foreach ($financieras as $idx => $finName) {
            $colL = Coordinate::stringFromColumnIndex($startCol + 1 + $idx);
            $val = empty($sellers) ? 0 : "=SUM({$colL}{$dataStartRow}:{$colL}{$dataEndRow})";
            $sheet->setCellValueByColumnAndRow($startCol + 1 + $idx, $row, $val);
        }
        $totalFormula = empty($sellers) ? 0 : "=SUM({$totalColLetter}{$dataStartRow}:{$totalColLetter}{$dataEndRow})";
        $sheet->setCellValueByColumnAndRow($totalColIdx, $row, $totalFormula);
        $sheet->setCellValueByColumnAndRow($pctColIdx, $row, empty($sellers) ? 0 : 1);
        $sheet->getStyle("{$pctColLetter}{$row}")->getNumberFormat()->setFormatCode('0.0%');
        $sheet->getStyle("{$startLetter}{$row}:{$endLetter}{$row}")->applyFromArray([
            'font' => ['bold' => true, 'size' => 10, 'color' => ['rgb' => '1E293B']],
            'fill' => ['fillType' => Fill::FILL_SOLID, 'startColor' => ['rgb' => 'E2E8F0']],
            'borders' => ['allBorders' => ['borderStyle' => Border::BORDER_THIN, 'color' => ['rgb' => '94A3B8']]],
        ]);
        $sheet->getStyle("{$finFirstLetter}{$row}:{$totalColLetter}{$row}")->getAlignment()->setHorizontal(Alignment::HORIZONTAL_CENTER);
        $sheet->getRowDimension($row)->setRowHeight(22);

        if (!empty($chartData['cancelled_note'])) {
            $noteRow = $row + 2;
            $sheet->setCellValueByColumnAndRow($startCol, $noteRow, 'Nota: ' . $chartData['cancelled_note']);
            $sheet->mergeCells("{$startLetter}{$noteRow}:{$endLetter}{$noteRow}");
            $sheet->getStyle("{$startLetter}{$noteRow}:{$endLetter}{$noteRow}")->applyFromArray([
                'font' => ['italic' => true, 'size' => 9, 'color' => ['rgb' => '475569']],
            ]);
        }

        for ($c = $startCol; $c <= $endCol; $c++) {
            $colL = Coordinate::stringFromColumnIndex($c);
            $sheet->getColumnDimension($colL)->setAutoSize(false);
            $width = ($c === $startCol) ? 24 : max(13, mb_strlen((string) ($headers[$c - $startCol] ?? ''), 'UTF-8') + 4);
            $sheet->getColumnDimension($colL)->setWidth($width);
        }
    }

    protected static function resolveSellerIdentity($sale): array
    {
        $rawName = trim((string) ($sale->seller_display_name ?? ($sale->seller?->name ?? ($sale->seller_name ?? ''))));
        if ($rawName === '' || strtoupper($rawName) === 'N/A') {
            $rawName = 'Sin vendedora';
        }

        $sellerId = $sale->seller_id ?? null;
        $key = $sellerId ? 'id:' . (int) $sellerId : 'name:' . mb_strtolower($rawName, 'UTF-8');

        return [$key, $rawName];
    }

    protected static function resolveFinancieraLabel($sale): string
    {
        $finName = trim((string) (
            $sale->financiera?->name
            ?? ($sale->financiera_name ?? ($sale->financiera_display_name ?? ''))
        ));
        if ($finName !== '' && strcasecmp($finName, 'Contado') !== 0) {
            return $finName;
        }

        $isContado = method_exists($sale, 'isContado')
            ? $sale->isContado()
            : (($sale->sale_type ?? '') === 'contado');

        return $isContado ? 'Contado' : ($finName !== '' ? $finName : 'Directo');
    }

    protected static function sortFinancieraCounts(array $counts): array
    {
        $items = [];
        foreach ($counts as $name => $count) {
            $items[] = [
                'name' => $name,
                'count' => (int) $count,
            ];
        }

        usort($items, function (array $a, array $b) {
            if ($a['count'] !== $b['count']) {
                return $b['count'] <=> $a['count'];
            }
            $aSpecial = in_array(mb_strtolower($a['name'], 'UTF-8'), ['contado', 'directo'], true) ? 1 : 0;
            $bSpecial = in_array(mb_strtolower($b['name'], 'UTF-8'), ['contado', 'directo'], true) ? 1 : 0;
            if ($aSpecial !== $bSpecial) {
                return $aSpecial <=> $bSpecial;
            }

            return strcasecmp($a['name'], $b['name']);
        });

        return $items;
    }

    protected static function buildCancelledNote(Collection $cancelledSales): ?string
    {
        if ($cancelledSales->isEmpty()) {
            return null;
        }

        $grouped = [];
        foreach ($cancelledSales as $sale) {
            [, $sellerName] = self::resolveSellerIdentity($sale);
            $finName = self::resolveFinancieraLabel($sale);
            $key = mb_strtolower($sellerName . '|' . $finName, 'UTF-8');

            if (!isset($grouped[$key])) {
                $grouped[$key] = [
                    'seller' => $sellerName,
                    'financiera' => $finName,
                    'count' => 0,
                ];
            }
            $grouped[$key]['count']++;
        }

        $totalCancelled = $cancelledSales->count();
        $groups = array_values($grouped);

        if ($totalCancelled === 1) {
            $item = $groups[0];

            return "Venta cancelada de {$item['seller']} en {$item['financiera']} descontada.";
        }

        if (count($groups) === 1) {
            $item = $groups[0];

            return "{$totalCancelled} ventas canceladas de {$item['seller']} en {$item['financiera']} descontadas.";
        }

        $parts = array_map(function (array $item) {
            $suffix = $item['count'] > 1 ? " ({$item['count']})" : '';

            return "{$item['seller']} en {$item['financiera']}{$suffix}";
        }, $groups);

        return "{$totalCancelled} ventas canceladas descontadas: " . implode(', ', $parts) . '.';
    }

    protected static function computeSlicesGeometry(
        array $sellers,
        int $total,
        float $cx,
        float $cy,
        float $radius
    ): array {
        $slices = [];
        $currentAngle = -M_PI / 2;
        $isSingle = count($sellers) === 1;

        foreach ($sellers as $idx => $seller) {
            $sweep = $isSingle
                ? (2 * M_PI)
                : (2 * M_PI * ($seller['total'] / max(1, $total)));
            $endAngle = ($idx === count($sellers) - 1) ? (-M_PI / 2 + 2 * M_PI) : ($currentAngle + $sweep);

            $slices[] = [
                'seller' => $seller,
                'color' => $seller['color'],
                'start_rad' => $currentAngle,
                'end_rad' => $endAngle,
                'sweep' => $endAngle - $currentAngle,
                'mid_rad' => ($currentAngle + $endAngle) / 2,
                'is_full_circle' => $isSingle,
                'cx' => $cx,
                'cy' => $cy,
                'r' => $radius,
            ];

            $currentAngle = $endAngle;
        }

        return $slices;
    }

    protected static function buildSellerLabelLines(array $seller): array
    {
        $lines = [];
        foreach (self::wrapUpperSellerName($seller['upper_name']) as $nameLine) {
            $lines[] = ['text' => $nameLine, 'kind' => 'title'];
        }

        $countWord = ((int) $seller['total'] === 1) ? 'venta' : 'ventas';
        $lines[] = ['text' => "{$seller['total']} {$countWord}", 'kind' => 'total'];
        $lines[] = ['text' => '', 'kind' => 'spacer'];

        foreach ($seller['financieras'] as $fin) {
            $lines[] = ['text' => "{$fin['name']}: {$fin['count']}", 'kind' => 'detail'];
        }

        return $lines;
    }

    protected static function wrapUpperSellerName(string $upperName): array
    {
        $trimmed = trim($upperName);
        if (mb_strlen($trimmed, 'UTF-8') <= 16) {
            return [$trimmed];
        }

        $words = preg_split('/\s+/', $trimmed) ?: [$trimmed];
        if (count($words) <= 1) {
            return [mb_substr($trimmed, 0, 18, 'UTF-8')];
        }

        $line1 = '';
        $line2Parts = [];
        foreach ($words as $word) {
            if ($line1 === '' || (empty($line2Parts) && mb_strlen($line1 . ' ' . $word, 'UTF-8') <= 15)) {
                $line1 = $line1 === '' ? $word : $line1 . ' ' . $word;
            } else {
                $line2Parts[] = $word;
            }
        }

        if (empty($line2Parts)) {
            return [$line1];
        }

        $line2 = implode(' ', $line2Parts);
        if (mb_strlen($line2, 'UTF-8') > 18) {
            $line2 = mb_substr($line2, 0, 16, 'UTF-8') . '…';
        }

        return [$line1, $line2];
    }

    protected static function estimateBlockMetrics(array $lines, float $fontSize): array
    {
        $maxWidth = 0.0;
        $totalHeight = 0.0;
        $lineStep = $fontSize * 1.20;
        $spacerHeight = $fontSize * 0.44;

        foreach ($lines as $line) {
            if ($line['kind'] === 'spacer') {
                $totalHeight += $spacerHeight;
                continue;
            }

            $charWidthFactor = $line['kind'] === 'title' ? 0.63 : 0.55;
            $w = mb_strlen($line['text'], 'UTF-8') * ($fontSize * $charWidthFactor);
            if ($w > $maxWidth) {
                $maxWidth = $w;
            }
            $totalHeight += $lineStep;
        }

        return [
            'width' => $maxWidth + 6.0,
            'height' => $totalHeight + 3.0,
            'line_step' => $lineStep,
            'spacer_height' => $spacerHeight,
        ];
    }

    protected static function findInsideWedgePlacement(
        float $startRad,
        float $endRad,
        float $cx,
        float $cy,
        float $radius,
        array $lines
    ): ?array {
        $sweep = $endRad - $startRad;
        $midRad = ($startRad + $endRad) / 2.0;

        if ($sweep >= (2 * M_PI - 0.001)) {
            return ['x' => $cx, 'y' => $cy, 'font_size' => 13.0, 'clearance' => $radius];
        }

        if ($sweep < 0.44) { // Menor a ~25°, demasiado angosta para bloque multilínea interno
            return null;
        }

        $minClearanceReq = $sweep >= 0.65 ? 3.0 : 4.5;

        foreach ([12.0, 10.8, 9.6, 8.6, 7.8] as $fontSize) {
            $metrics = self::estimateBlockMetrics($lines, $fontSize);
            $halfW = $metrics['width'] / 2.0;
            $halfH = $metrics['height'] / 2.0;

            $best = null;
            $bestScore = -1.0;

            for ($rFrac = 0.36; $rFrac <= 0.72; $rFrac += 0.04) {
                $r = $radius * $rFrac;
                for ($angOffset = -0.14; $angOffset <= 0.14; $angOffset += 0.04) {
                    $ang = $midRad + $angOffset;
                    if ($ang <= $startRad + 0.06 || $ang >= $endRad - 0.06) {
                        continue;
                    }

                    $candX = $cx + $r * cos($ang);
                    $candY = $cy + $r * sin($ang);
                    $clearance = self::rectSectorClearance($candX, $candY, $halfW, $halfH, $cx, $cy, $radius, $startRad, $endRad);

                    if ($clearance >= $minClearanceReq) {
                        $biasScore = $clearance - (abs($rFrac - 0.58) * 20.0) - (abs($angOffset) * 18.0);
                        if ($best === null || $biasScore > $bestScore) {
                            $bestScore = $biasScore;
                            $best = [
                                'x' => $candX,
                                'y' => $candY,
                                'font_size' => $fontSize,
                                'clearance' => $clearance,
                            ];
                        }
                    }
                }
            }

            if ($best !== null) {
                return $best;
            }
        }

        return null;
    }

    protected static function rectSectorClearance(
        float $boxCx,
        float $boxCy,
        float $halfW,
        float $halfH,
        float $pieCx,
        float $pieCy,
        float $radius,
        float $startRad,
        float $endRad
    ): float {
        $sweep = $endRad - $startRad;
        $midRad = ($startRad + $endRad) / 2.0;
        $minClearance = INF;

        $sampleFracsX = [-1.0, 0.0, 1.0];
        $sampleFracsY = [-1.0, 0.0, 1.0];

        foreach ($sampleFracsX as $fx) {
            foreach ($sampleFracsY as $fy) {
                $px = $boxCx + $fx * $halfW;
                $py = $boxCy + $fy * $halfH;
                $dx = $px - $pieCx;
                $dy = $py - $pieCy;
                $dist = hypot($dx, $dy);

                $circleClearance = $radius - $dist;
                if ($circleClearance <= 0) {
                    return -1.0;
                }

                $pointAngle = atan2($dy, $dx);
                $relAngle = atan2(sin($pointAngle - $midRad), cos($pointAngle - $midRad));
                if (abs($relAngle) > ($sweep / 2.0)) {
                    return -1.0;
                }

                $dRay1 = self::pointToRaySegmentDistance($px, $py, $pieCx, $pieCy, $startRad, $radius);
                $dRay2 = self::pointToRaySegmentDistance($px, $py, $pieCx, $pieCy, $endRad, $radius);
                $localClearance = min($circleClearance, $dRay1, $dRay2);

                if ($localClearance < $minClearance) {
                    $minClearance = $localClearance;
                }
            }
        }

        return $minClearance;
    }

    protected static function pointToRaySegmentDistance(
        float $px,
        float $py,
        float $ox,
        float $oy,
        float $angleRad,
        float $length
    ): float {
        $vx = cos($angleRad) * $length;
        $vy = sin($angleRad) * $length;
        $wx = $px - $ox;
        $wy = $py - $oy;
        $lenSq = $vx * $vx + $vy * $vy;

        $t = $lenSq > 0 ? max(0.0, min(1.0, ($wx * $vx + $wy * $vy) / $lenSq)) : 0.0;
        $projX = $ox + $t * $vx;
        $projY = $oy + $t * $vy;

        return hypot($px - $projX, $py - $projY);
    }

    protected static function renderLabelLinesXml(
        float $centerX,
        float $centerY,
        float $fontSize,
        array $lines,
        string $textAnchor = 'middle'
    ): string {
        $metrics = self::estimateBlockMetrics($lines, $fontSize);
        $cursorY = $centerY - ($metrics['height'] / 2.0) + ($fontSize * 0.92);
        $xml = '';

        foreach ($lines as $line) {
            if ($line['kind'] === 'spacer') {
                $cursorY += $metrics['spacer_height'];
                continue;
            }

            $textEsc = htmlspecialchars($line['text'], ENT_XML1 | ENT_QUOTES, 'UTF-8');
            $weight = $line['kind'] === 'title' ? ' font-weight="bold"' : '';
            $lineFont = $line['kind'] === 'detail' ? max(8.5, $fontSize - 0.5) : $fontSize;

            $xml .= sprintf(
                '<text x="%.2f" y="%.2f" text-anchor="%s" font-family="DejaVu Sans" font-size="%.1f"%s fill="#1e293b">%s</text>' . "\n",
                $centerX,
                $cursorY,
                $textAnchor,
                $lineFont,
                $weight,
                $textEsc
            );
            $cursorY += $metrics['line_step'];
        }

        return $xml;
    }

    protected static function renderExternalCalloutsXml(
        array $callouts,
        float $cx,
        float $cy,
        float $radius,
        int $svgWidth,
        int $svgHeight
    ): string {
        if (empty($callouts)) {
            return '';
        }

        $leftItems = [];
        $rightItems = [];

        foreach ($callouts as $item) {
            $mid = $item['slice']['mid_rad'];
            $isRight = cos($mid) >= 0;
            $anchorY = $cy + ($radius + 16) * sin($mid);
            $entry = [
                'item' => $item,
                'mid' => $mid,
                'target_y' => $anchorY,
            ];
            if ($isRight) {
                $rightItems[] = $entry;
            } else {
                $leftItems[] = $entry;
            }
        }

        $xml = '';
        $xml .= self::layoutCalloutSide($rightItems, true, $cx, $cy, $radius, $svgWidth, $svgHeight);
        $xml .= self::layoutCalloutSide($leftItems, false, $cx, $cy, $radius, $svgWidth, $svgHeight);

        return $xml;
    }

    protected static function layoutCalloutSide(
        array $entries,
        bool $isRight,
        float $cx,
        float $cy,
        float $radius,
        int $svgWidth,
        int $svgHeight
    ): string {
        if (empty($entries)) {
            return '';
        }

        usort($entries, fn ($a, $b) => $a['target_y'] <=> $b['target_y']);

        $fontSize = count($entries) >= 4 ? 8.8 : 9.4;
        $minTop = 10.0;
        $maxBottom = $svgHeight - 10.0;
        $placed = [];
        $cursorY = $minTop;

        foreach ($entries as $entry) {
            $compactLines = array_values(array_filter(
                $entry['item']['lines'],
                fn (array $line) => $line['kind'] !== 'spacer'
            ));
            $metrics = self::estimateBlockMetrics($compactLines, $fontSize);
            $blockH = max(28.0, $metrics['height']);
            $centerY = max($entry['target_y'], $cursorY + $blockH / 2.0);
            $centerY = min($centerY, $maxBottom - $blockH / 2.0);
            $cursorY = $centerY + $blockH / 2.0 + 6.0;

            $placed[] = [
                'entry' => $entry,
                'lines' => $compactLines,
                'center_y' => $centerY,
                'metrics' => $metrics,
            ];
        }

        $xml = '';
        $labelAnchorX = $isRight ? ($cx + $radius + 36.0) : ($cx - $radius - 36.0);
        $labelAnchorX = max(14.0, min($svgWidth - 14.0, $labelAnchorX));
        $anchor = $isRight ? 'start' : 'end';

        foreach ($placed as $node) {
            $mid = $node['entry']['mid'];
            $edgeX = $cx + ($radius - 9.0) * cos($mid);
            $edgeY = $cy + ($radius - 9.0) * sin($mid);
            $outerX = $cx + ($radius + 10.0) * cos($mid);
            $outerY = $cy + ($radius + 10.0) * sin($mid);
            $elbowX = $isRight ? ($labelAnchorX - 10.0) : ($labelAnchorX + 10.0);
            $labelY = $node['center_y'];

            $xml .= sprintf(
                '<polyline points="%.1f,%.1f %.1f,%.1f %.1f,%.1f %.1f,%.1f" fill="none" stroke="#64748b" stroke-width="1.15" stroke-linejoin="round"/>' . "\n",
                $edgeX,
                $edgeY,
                $outerX,
                $outerY,
                $elbowX,
                $labelY,
                $labelAnchorX,
                $labelY
            );
            $xml .= sprintf('<circle cx="%.1f" cy="%.1f" r="2.2" fill="#475569"/>' . "\n", $edgeX, $edgeY);
            $xml .= self::renderLabelLinesXml($labelAnchorX + ($isRight ? 4.0 : -4.0), $labelY, $fontSize, $node['lines'], $anchor);
        }

        return $xml;
    }

    protected static function tryRenderPngViaDompdf(array $chartData): ?string
    {
        if (!function_exists('proc_open')) {
            return null;
        }

        $pdftoppm = self::findExecutable(['pdftoppm']);
        $gs = self::findExecutable(['gs']);
        if ($pdftoppm === null && $gs === null) {
            return null;
        }

        $globalLines = max(1, (int) ceil(count($chartData['global_financieras'] ?? []) / 4));
        $noteLines = !empty($chartData['cancelled_note'])
            ? max(1, (int) ceil(mb_strlen((string) $chartData['cancelled_note'], 'UTF-8') / 85))
            : 0;
        $paperHeightPt = 428 + ($globalLines * 18) + ($noteLines * 16);

        $cardHtml = self::renderCardHtml($chartData, ['standalone' => true]);
        $html = "<!DOCTYPE html><html lang=\"es\"><head><meta charset=\"UTF-8\"><style>@page{margin:0;}html,body{margin:0;padding:0;background:#fcfbf9;}</style></head><body>{$cardHtml}</body></html>";

        $tmpDir = sys_get_temp_dir();
        $pdfPath = tempnam($tmpDir, 'fin_chart_pdf_');
        $pngBase = tempnam($tmpDir, 'fin_chart_png_');
        $pngPath = $pngBase . '.png';

        try {
            $pdfBytes = Pdf::loadHTML($html)->setPaper([0, 0, 560, $paperHeightPt])->output();
            file_put_contents($pdfPath, $pdfBytes);

            if ($pdftoppm !== null) {
                $cmd = escapeshellarg($pdftoppm)
                    . ' -png -r 150 -singlefile '
                    . escapeshellarg($pdfPath)
                    . ' '
                    . escapeshellarg($pngBase);
                self::runQuietProcess($cmd);
            } elseif ($gs !== null) {
                $cmd = escapeshellarg($gs)
                    . ' -q -dNOPAUSE -dBATCH -sDEVICE=png16m -r150 -dFirstPage=1 -dLastPage=1 -sOutputFile='
                    . escapeshellarg($pngPath)
                    . ' '
                    . escapeshellarg($pdfPath);
                self::runQuietProcess($cmd);
            }

            if (is_file($pngPath) && filesize($pngPath) > 0) {
                return (string) file_get_contents($pngPath);
            }

            return null;
        } catch (\Throwable $e) {
            return null;
        } finally {
            @unlink($pdfPath);
            @unlink($pngBase);
            @unlink($pngPath);
        }
    }

    protected static function findExecutable(array $names): ?string
    {
        foreach ($names as $name) {
            foreach (['/usr/bin/' . $name, '/usr/local/bin/' . $name, '/bin/' . $name] as $path) {
                if (@is_file($path) && @is_executable($path)) {
                    return $path;
                }
            }
        }

        return null;
    }

    protected static function runQuietProcess(string $command): int
    {
        $descriptors = [
            0 => ['file', '/dev/null', 'r'],
            1 => ['file', '/dev/null', 'w'],
            2 => ['file', '/dev/null', 'w'],
        ];
        $proc = @proc_open($command, $descriptors, $pipes);
        if (!is_resource($proc)) {
            return 1;
        }

        return proc_close($proc);
    }

    protected static function tryRenderPngViaGd(array $chartData): ?string
    {
        if (!extension_loaded('gd')) {
            return null;
        }

        $scale = 2;
        $w = 800;
        $h = 560;
        $sw = $w * $scale;
        $sh = $h * $scale;

        $hi = imagecreatetruecolor($sw, $sh);
        if (!$hi) {
            return null;
        }

        imagealphablending($hi, true);
        imagesavealpha($hi, true);

        $bg = imagecolorallocate($hi, 252, 251, 249);
        imagefilledrectangle($hi, 0, 0, $sw, $sh, $bg);

        $navy = imagecolorallocate($hi, 30, 41, 59);
        $muted = imagecolorallocate($hi, 71, 85, 105);
        $whiteBorder = imagecolorallocate($hi, 252, 251, 249);

        $boldTtf = base_path('vendor/dompdf/dompdf/lib/fonts/DejaVuSans-Bold.ttf');
        $regTtf = base_path('vendor/dompdf/dompdf/lib/fonts/DejaVuSans.ttf');
        $hasTtf = function_exists('imagettftext') && is_file($boldTtf) && is_file($regTtf);

        $total = (int) ($chartData['total_sales'] ?? 0);
        $totalWord = $total === 1 ? 'venta' : 'ventas';
        $subtitle = ($chartData['period_text'] ?? '') . " • Total: {$total} {$totalWord}";

        if ($hasTtf) {
            self::gdDrawCenteredTtf($hi, 17 * $scale, $boldTtf, $navy, 'VENTAS POR VENDEDORA', $sw / 2, 34 * $scale);
            self::gdDrawCenteredTtf($hi, 10 * $scale, $regTtf, $muted, $subtitle, $sw / 2, 56 * $scale);
        }

        $cx = $sw / 2.0;
        $cy = 266.0 * $scale;
        $diam = 360.0 * $scale;
        $radius = $diam / 2.0;

        $sellers = $chartData['sellers'] ?? [];
        if ($total > 0 && !empty($sellers)) {
            $slices = self::computeSlicesGeometry($sellers, $total, $cx, $cy, $radius);
            foreach ($slices as $slice) {
                [$r, $g, $b] = self::hexToRgb($slice['color']);
                $col = imagecolorallocate($hi, $r, $g, $b);

                if ($slice['is_full_circle']) {
                    imagefilledellipse($hi, (int) $cx, (int) $cy, (int) $diam, (int) $diam, $col);
                } else {
                    $startDeg = (int) round(rad2deg($slice['start_rad']));
                    $endDeg = (int) round(rad2deg($slice['end_rad']));
                    if ($endDeg <= $startDeg) {
                        $endDeg = $startDeg + 1;
                    }
                    imagefilledarc($hi, (int) $cx, (int) $cy, (int) $diam, (int) $diam, $startDeg, $endDeg, $col, IMG_ARC_PIE);
                }
            }

            // Divisores blancos entre rebanadas
            imagesetthickness($hi, 3 * $scale);
            foreach ($slices as $slice) {
                if (!$slice['is_full_circle']) {
                    imageline(
                        $hi,
                        (int) $cx,
                        (int) $cy,
                        (int) round($cx + $radius * cos($slice['start_rad'])),
                        (int) round($cy + $radius * sin($slice['start_rad'])),
                        $whiteBorder
                    );
                }
            }
            imagesetthickness($hi, 1);

            if ($hasTtf) {
                foreach ($slices as $slice) {
                    $lines = self::buildSellerLabelLines($slice['seller']);
                    $placement = self::findInsideWedgePlacement(
                        $slice['start_rad'],
                        $slice['end_rad'],
                        $cx,
                        $cy,
                        $radius,
                        $lines
                    );
                    if ($placement !== null) {
                        $lineStep = $placement['font_size'] * $scale * 1.24;
                        $metrics = self::estimateBlockMetrics($lines, $placement['font_size'] * $scale);
                        $yCursor = $placement['y'] - ($metrics['height'] / 2.0) + ($placement['font_size'] * $scale * 0.9);
                        foreach ($lines as $line) {
                            if ($line['kind'] === 'spacer') {
                                $yCursor += $placement['font_size'] * $scale * 0.58;
                                continue;
                            }
                            $ttf = $line['kind'] === 'title' ? $boldTtf : $regTtf;
                            $fSize = ($line['kind'] === 'detail' ? max(8.5, $placement['font_size'] - 0.5) : $placement['font_size']) * $scale;
                            self::gdDrawCenteredTtf($hi, $fSize, $ttf, $navy, $line['text'], $placement['x'], $yCursor);
                            $yCursor += $lineStep;
                        }
                    }
                }
            }
        }

        $finSummary = implode('  •  ', array_map(
            fn ($item) => $item['name'] . ': ' . $item['count'],
            $chartData['global_financieras'] ?? []
        ));

        if ($hasTtf && $finSummary !== '') {
            self::gdDrawCenteredTtf($hi, 10.5 * $scale, $regTtf, $navy, $finSummary, $sw / 2, 495 * $scale);
        }
        if ($hasTtf && !empty($chartData['cancelled_note'])) {
            self::gdDrawCenteredTtf($hi, 8.8 * $scale, $regTtf, $muted, (string) $chartData['cancelled_note'], $sw / 2, 524 * $scale);
        }

        $out = imagecreatetruecolor($w, $h);
        imagecopyresampled($out, $hi, 0, 0, 0, 0, $w, $h, $sw, $sh);
        imagedestroy($hi);

        ob_start();
        imagepng($out);
        $binary = (string) ob_get_clean();
        imagedestroy($out);

        return $binary !== '' ? $binary : null;
    }

    protected static function gdDrawCenteredTtf($img, float $size, string $fontFile, int $color, string $text, float $cx, float $y): void
    {
        $box = imagettfbbox($size, 0, $fontFile, $text);
        if (!$box) {
            return;
        }
        $textW = abs($box[2] - $box[0]);
        $x = $cx - ($textW / 2.0);
        imagettftext($img, $size, 0, (int) round($x), (int) round($y), $color, $fontFile, $text);
    }

    /**
     * Respaldo en PHP puro (solo zlib) cuando no existe ni GD ni pdftoppm/gs.
     */
    protected static function renderPngPurePhpFallback(array $chartData): string
    {
        $w = 640;
        $h = 460;
        $bgR = 252;
        $bgG = 251;
        $bgB = 249;

        $rowStride = 1 + ($w * 3);
        $raw = str_repeat("\x00" . str_repeat(chr($bgR) . chr($bgG) . chr($bgB), $w), $h);

        $total = (int) ($chartData['total_sales'] ?? 0);
        $sellers = $chartData['sellers'] ?? [];
        $cx = $w / 2.0;
        $cy = 232.0;
        $radius = 158.0;
        $slices = ($total > 0 && !empty($sellers)) ? self::computeSlicesGeometry($sellers, $total, $cx, $cy, $radius) : [];

        if (!empty($slices)) {
            $sliceRgbs = array_map(fn ($s) => self::hexToRgb($s['color']), $slices);
            $yMin = max(0, (int) floor($cy - $radius - 2));
            $yMax = min($h - 1, (int) ceil($cy + $radius + 2));
            $xMin = max(0, (int) floor($cx - $radius - 2));
            $xMax = min($w - 1, (int) ceil($cx + $radius + 2));

            for ($y = $yMin; $y <= $yMax; $y++) {
                $rowOff = $y * $rowStride + 1;
                $dy = $y - $cy;
                for ($x = $xMin; $x <= $xMax; $x++) {
                    $dx = $x - $cx;
                    $dist = hypot($dx, $dy);
                    if ($dist > $radius) {
                        continue;
                    }

                    $ang = atan2($dy, $dx);
                    if ($ang < -M_PI / 2) {
                        $ang += 2 * M_PI;
                    }

                    $rgb = [$bgR, $bgG, $bgB];
                    foreach ($slices as $i => $s) {
                        if ($ang >= $s['start_rad'] && $ang <= $s['end_rad']) {
                            $rgb = $sliceRgbs[$i];
                            if (!$s['is_full_circle']) {
                                $d1 = self::pointToRaySegmentDistance($x, $y, $cx, $cy, $s['start_rad'], $radius);
                                $d2 = self::pointToRaySegmentDistance($x, $y, $cx, $cy, $s['end_rad'], $radius);
                                if ($d1 < 1.4 || $d2 < 1.4) {
                                    $rgb = [$bgR, $bgG, $bgB];
                                }
                            }
                            break;
                        }
                    }

                    $pos = $rowOff + ($x * 3);
                    $raw[$pos] = chr($rgb[0]);
                    $raw[$pos + 1] = chr($rgb[1]);
                    $raw[$pos + 2] = chr($rgb[2]);
                }
            }
        }

        $pngSignature = "\x89PNG\r\n\x1a\n";
        $chunk = function (string $type, string $data): string {
            return pack('N', strlen($data)) . $type . $data . pack('N', crc32($type . $data));
        };

        $ihdr = pack('NNCCCCC', $w, $h, 8, 2, 0, 0, 0);
        $idat = gzcompress($raw, 6);

        return $pngSignature . $chunk('IHDR', $ihdr) . $chunk('IDAT', $idat) . $chunk('IEND', '');
    }

    protected static function hexToRgb(string $hex): array
    {
        $clean = ltrim($hex, '#');

        return [
            hexdec(substr($clean, 0, 2)),
            hexdec(substr($clean, 2, 2)),
            hexdec(substr($clean, 4, 2)),
        ];
    }
}
