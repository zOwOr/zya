<?php

namespace App\Services;

use App\Models\Branch;
use App\Models\Category;
use App\Models\Product;
use App\Models\Supplier;
use Carbon\Carbon;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\DB;
use PhpOffice\PhpSpreadsheet\Cell\Cell;
use PhpOffice\PhpSpreadsheet\Cell\Coordinate;
use PhpOffice\PhpSpreadsheet\Cell\DataType;
use PhpOffice\PhpSpreadsheet\IOFactory;
use PhpOffice\PhpSpreadsheet\Shared\Date as ExcelDate;
use PhpOffice\PhpSpreadsheet\Spreadsheet;
use PhpOffice\PhpSpreadsheet\Style\Alignment;
use PhpOffice\PhpSpreadsheet\Style\Border;
use PhpOffice\PhpSpreadsheet\Style\Fill;
use PhpOffice\PhpSpreadsheet\Style\NumberFormat;

class ProductImportExportService
{
    /**
     * Contrato único de columnas para la plantilla Excel, la guía visual en Blade y el importador.
     *
     * @return array<int, array<string, mixed>>
     */
    public function templateColumns(): array
    {
        return [
            [
                'key' => 'product_name',
                'header' => 'NOMBRE DEL PRODUCTO *',
                'required' => true,
                'type' => 'Texto',
                'description' => 'Nombre comercial del producto o equipo.',
                'example' => 'Cargador Rápido USB-C 20W',
                'format' => NumberFormat::FORMAT_TEXT,
                'width' => 30,
                'aliases' => ['nombre del producto', 'nombre', 'producto', 'product name', 'product_name', 'descripcion'],
            ],
            [
                'key' => 'category',
                'header' => 'CATEGORIA *',
                'required' => true,
                'type' => 'Nombre o ID',
                'description' => 'Nombre exacto de categoría registrada o su ID numérico.',
                'example' => 'Accesorios',
                'format' => NumberFormat::FORMAT_GENERAL,
                'width' => 22,
                'aliases' => ['categoria', 'category', 'category id', 'category_id', 'categoria id'],
            ],
            [
                'key' => 'supplier',
                'header' => 'PROVEEDOR *',
                'required' => true,
                'type' => 'Nombre o ID',
                'description' => 'Nombre (o nombre de tienda) del proveedor registrado, o su ID numérico.',
                'example' => 'Proveedor Principal',
                'format' => NumberFormat::FORMAT_GENERAL,
                'width' => 24,
                'aliases' => ['proveedor', 'supplier', 'supplier id', 'supplier_id', 'proveedor id'],
            ],
            [
                'key' => 'branch',
                'header' => 'SUCURSAL',
                'required' => false,
                'type' => 'Nombre o ID',
                'description' => 'Nombre exacto o ID de sucursal. Vacío usa tu sucursal asignada o la seleccionada.',
                'example' => 'Matriz',
                'format' => NumberFormat::FORMAT_GENERAL,
                'width' => 20,
                'aliases' => ['sucursal', 'branch', 'branch id', 'branch_id', 'ubicacion'],
            ],
            [
                'key' => 'product_code',
                'header' => 'CODIGO / SKU',
                'required' => false,
                'type' => 'Texto',
                'description' => 'SKU o código de barras único en la sucursal. Vacío autogenera PC0001...',
                'example' => 'PC0101',
                'format' => NumberFormat::FORMAT_TEXT,
                'width' => 18,
                'aliases' => ['codigo / sku', 'codigo', 'sku', 'product code', 'product_code', 'codigo de barras'],
            ],
            [
                'key' => 'buying_price',
                'header' => 'PRECIO COMPRA *',
                'required' => true,
                'type' => 'Decimal ≥ 0',
                'description' => 'Costo unitario de compra (acepta números o moneda como $150.00).',
                'example' => 150.00,
                'format' => NumberFormat::FORMAT_NUMBER_00,
                'width' => 18,
                'aliases' => ['precio compra', 'costo', 'precio de compra', 'buying price', 'buying_price'],
            ],
            [
                'key' => 'selling_price',
                'header' => 'PRECIO VENTA *',
                'required' => true,
                'type' => 'Decimal ≥ 0',
                'description' => 'Precio unitario de venta al público.',
                'example' => 299.00,
                'format' => NumberFormat::FORMAT_NUMBER_00,
                'width' => 18,
                'aliases' => ['precio venta', 'precio', 'precio de venta', 'selling price', 'selling_price'],
            ],
            [
                'key' => 'stock_quantity',
                'header' => 'STOCK *',
                'required' => true,
                'type' => 'Entero ≥ 0',
                'description' => 'Existencia inicial disponible en la sucursal.',
                'example' => 10,
                'format' => NumberFormat::FORMAT_NUMBER,
                'width' => 14,
                'aliases' => ['stock', 'existencia', 'cantidad', 'stock quantity', 'stock_quantity'],
            ],
            [
                'key' => 'imei',
                'header' => 'IMEI / SERIE',
                'required' => false,
                'type' => 'Texto',
                'description' => 'IMEI o número de serie único (opcional, ideal para telefonía).',
                'example' => '',
                'format' => NumberFormat::FORMAT_TEXT,
                'width' => 22,
                'aliases' => ['imei / serie', 'imei', 'serie', 'numero de serie', 'serial'],
            ],
            [
                'key' => 'brand',
                'header' => 'MARCA',
                'required' => false,
                'type' => 'Texto',
                'description' => 'Marca comercial del producto (opcional).',
                'example' => 'Apple',
                'format' => NumberFormat::FORMAT_TEXT,
                'width' => 16,
                'aliases' => ['marca', 'brand'],
            ],
            [
                'key' => 'model',
                'header' => 'MODELO',
                'required' => false,
                'type' => 'Texto',
                'description' => 'Modelo específico del producto (opcional).',
                'example' => '20W A2305',
                'format' => NumberFormat::FORMAT_TEXT,
                'width' => 18,
                'aliases' => ['modelo', 'model'],
            ],
            [
                'key' => 'category_status',
                'header' => 'CONDICION',
                'required' => false,
                'type' => 'Texto',
                'description' => 'Estado o condición (ej. Nuevo, Seminuevo, Exhibición).',
                'example' => 'Nuevo',
                'format' => NumberFormat::FORMAT_TEXT,
                'width' => 16,
                'aliases' => ['condicion', 'estado', 'category status', 'category_status'],
            ],
            [
                'key' => 'warranty_time',
                'header' => 'GARANTIA',
                'required' => false,
                'type' => 'Texto',
                'description' => 'Tiempo de garantía (ej. 3 meses, 12 meses).',
                'example' => '3 meses',
                'format' => NumberFormat::FORMAT_TEXT,
                'width' => 16,
                'aliases' => ['garantia', 'tiempo de garantia', 'warranty time', 'warranty_time', 'warranty'],
            ],
            [
                'key' => 'product_garage',
                'header' => 'ALMACEN / PASILLO',
                'required' => false,
                'type' => 'Texto',
                'description' => 'Ubicación física en bodega o vitrina.',
                'example' => 'Vitrina A',
                'format' => NumberFormat::FORMAT_TEXT,
                'width' => 20,
                'aliases' => ['almacen / pasillo', 'almacen', 'bodega', 'pasillo', 'product garage', 'product_garage'],
            ],
            [
                'key' => 'product_store',
                'header' => 'SECCION / TIENDA',
                'required' => false,
                'type' => 'Texto',
                'description' => 'Mostrador o área de exhibición en tienda.',
                'example' => 'Mostrador 1',
                'format' => NumberFormat::FORMAT_TEXT,
                'width' => 20,
                'aliases' => ['seccion / tienda', 'tienda', 'seccion', 'product store', 'product_store'],
            ],
            [
                'key' => 'buying_date',
                'header' => 'FECHA COMPRA',
                'required' => false,
                'type' => 'Fecha',
                'description' => 'Formato YYYY-MM-DD o DD/MM/YYYY.',
                'example' => date('Y-m-d'),
                'format' => NumberFormat::FORMAT_DATE_YYYYMMDD,
                'width' => 16,
                'aliases' => ['fecha compra', 'fecha de compra', 'buying date', 'buying_date'],
            ],
            [
                'key' => 'expire_date',
                'header' => 'FECHA EXPIRACION',
                'required' => false,
                'type' => 'Fecha',
                'description' => 'Fecha de caducidad en formato YYYY-MM-DD o DD/MM/YYYY.',
                'example' => '',
                'format' => NumberFormat::FORMAT_DATE_YYYYMMDD,
                'width' => 18,
                'aliases' => ['fecha expiracion', 'fecha de expiracion', 'fecha caducidad', 'expire date', 'expire_date'],
            ],
            [
                'key' => 'observations',
                'header' => 'OBSERVACIONES',
                'required' => false,
                'type' => 'Texto',
                'description' => 'Notas internas u observaciones del producto.',
                'example' => 'Caja sellada',
                'format' => NumberFormat::FORMAT_TEXT,
                'width' => 28,
                'aliases' => ['observaciones', 'notas', 'observations'],
            ],
        ];
    }

    /**
     * Genera el libro Excel (.xlsx) descargable con plantilla, ejemplo y hoja de catálogos válidos.
     */
    public function generateTemplateSpreadsheet($user = null): Spreadsheet
    {
        $canAllBranches = Product::userCanAccessAllBranches($user);
        $categories = Category::orderBy('name')->get(['id', 'name']);
        $suppliers = Supplier::orderBy('name')->get(['id', 'name', 'shopname']);

        if ($canAllBranches) {
            $branches = Branch::orderBy('name')->get(['id', 'name']);
        } elseif ($user && $user->branch_id) {
            $branches = Branch::where('id', $user->branch_id)->get(['id', 'name']);
        } else {
            $branches = collect();
        }

        $columns = $this->templateColumns();
        $spreadsheet = new Spreadsheet();
        $spreadsheet->getProperties()
            ->setCreator('ZYA POS')
            ->setTitle('Plantilla de Importación de Productos')
            ->setDescription('Plantilla oficial multisucursal para carga masiva de productos');

        // ====== HOJA 1: PLANTILLA PRODUCTOS ======
        $sheet = $spreadsheet->getActiveSheet();
        $sheet->setTitle('Plantilla Productos');
        $sheet->freezePane('A2');

        $sampleCategory = $categories->first()?->name ?? 'General';
        $sampleSupplier = $suppliers->first()?->name ?? 'Proveedor Principal';
        $sampleBranch = $branches->first()?->name ?? '';

        foreach ($columns as $idx => $col) {
            $colLetter = Coordinate::stringFromColumnIndex($idx + 1);
            $headerCoord = $colLetter . '1';
            $sheet->setCellValueExplicit($headerCoord, $col['header'], DataType::TYPE_STRING);
            $sheet->getColumnDimension($colLetter)->setWidth($col['width']);

            $headerBg = $col['required'] ? '1E3A8A' : '334155';
            $sheet->getStyle($headerCoord)->applyFromArray([
                'font' => [
                    'bold' => true,
                    'color' => ['rgb' => 'FFFFFF'],
                    'size' => 10,
                ],
                'fill' => [
                    'fillType' => Fill::FILL_SOLID,
                    'startColor' => ['rgb' => $headerBg],
                ],
                'alignment' => [
                    'horizontal' => Alignment::HORIZONTAL_CENTER,
                    'vertical' => Alignment::VERTICAL_CENTER,
                ],
                'borders' => [
                    'allBorders' => [
                        'borderStyle' => Border::BORDER_THIN,
                        'color' => ['rgb' => 'CBD5E1'],
                    ],
                ],
            ]);

            // Aplicar formato preventivo a filas de captura 2..200 para evitar notación científica
            if ($col['format'] !== NumberFormat::FORMAT_GENERAL) {
                $sheet->getStyle("{$colLetter}2:{$colLetter}200")
                    ->getNumberFormat()
                    ->setFormatCode($col['format']);
            }

            // Escribir fila de ejemplo lista para editar
            $sampleValue = match ($col['key']) {
                'category' => $sampleCategory,
                'supplier' => $sampleSupplier,
                'branch' => $sampleBranch,
                default => $col['example'],
            };

            $sampleCoord = $colLetter . '2';
            if (is_int($sampleValue) || is_float($sampleValue)) {
                $sheet->setCellValue($sampleCoord, $sampleValue);
            } else {
                $sheet->setCellValueExplicit($sampleCoord, (string) $sampleValue, DataType::TYPE_STRING);
            }
        }

        $lastColLetter = Coordinate::stringFromColumnIndex(count($columns));
        $sheet->getRowDimension(1)->setRowHeight(28);
        $sheet->getRowDimension(2)->setRowHeight(22);
        $sheet->getStyle("A2:{$lastColLetter}2")->applyFromArray([
            'fill' => [
                'fillType' => Fill::FILL_SOLID,
                'startColor' => ['rgb' => 'EFF6FF'],
            ],
            'borders' => [
                'allBorders' => [
                    'borderStyle' => Border::BORDER_THIN,
                    'color' => ['rgb' => 'CBD5E1'],
                ],
            ],
        ]);

        // ====== HOJA 2: GUÍA Y CATÁLOGOS ======
        $guideSheet = $spreadsheet->createSheet();
        $guideSheet->setTitle('Guía y Catálogos');

        $guideSheet->setCellValue('A1', 'GUÍA DE IMPORTACIÓN DE PRODUCTOS (MULTISUCURSAL)');
        $guideSheet->mergeCells('A1:E1');
        $guideSheet->getStyle('A1')->getFont()->setBold(true)->setSize(13)->getColor()->setRGB('0F172A');

        $notes = [
            '1. Los encabezados marcados con (*) son obligatorios.',
            '2. La fila 2 de "Plantilla Productos" es un ejemplo editable; modifícala o elimínala antes de subir tus productos.',
            '3. Categoría, Proveedor y Sucursal pueden escribirse por NOMBRE exacto (sin importar mayúsculas/acentos) o por ID.',
            '4. Si dejas CODIGO / SKU vacío, el sistema generará un código PC secuencial automáticamente.',
            '5. Usuarios sin permiso global solo pueden importar productos para su propia sucursal asignada.',
        ];
        $row = 2;
        foreach ($notes as $note) {
            $guideSheet->setCellValue("A{$row}", $note);
            $guideSheet->mergeCells("A{$row}:E{$row}");
            $row++;
        }

        $row += 1;
        $guideSheet->setCellValue("A{$row}", 'ESTRUCTURA DE COLUMNAS');
        $guideSheet->getStyle("A{$row}")->getFont()->setBold(true);
        $row++;

        $specHeaders = ['COLUMNA', 'REQUERIDA', 'TIPO', 'EJEMPLO', 'DESCRIPCIÓN'];
        foreach ($specHeaders as $i => $text) {
            $coord = Coordinate::stringFromColumnIndex($i + 1) . $row;
            $guideSheet->setCellValue($coord, $text);
            $guideSheet->getStyle($coord)->applyFromArray([
                'font' => ['bold' => true, 'color' => ['rgb' => 'FFFFFF']],
                'fill' => ['fillType' => Fill::FILL_SOLID, 'startColor' => ['rgb' => '1E293B']],
            ]);
        }
        $row++;

        foreach ($columns as $col) {
            $guideSheet->setCellValue("A{$row}", $col['header']);
            $guideSheet->setCellValue("B{$row}", $col['required'] ? 'Sí (*)' : 'Opcional');
            $guideSheet->setCellValue("C{$row}", $col['type']);
            $guideSheet->setCellValueExplicit("D{$row}", (string) $col['example'], DataType::TYPE_STRING);
            $guideSheet->setCellValue("E{$row}", $col['description']);
            $row++;
        }

        // Sección de catálogos reales para copiar/pegar sin errores
        $row += 1;
        $catalogHeaderRow = $row;
        $guideSheet->setCellValue("A{$catalogHeaderRow}", 'SUCURSALES VÁLIDAS (ID | Nombre)');
        $guideSheet->setCellValue("B{$catalogHeaderRow}", 'CATEGORÍAS VÁLIDAS (ID | Nombre)');
        $guideSheet->setCellValue("C{$catalogHeaderRow}", 'PROVEEDORES VÁLIDOS (ID | Nombre)');
        $guideSheet->getStyle("A{$catalogHeaderRow}:C{$catalogHeaderRow}")->applyFromArray([
            'font' => ['bold' => true, 'color' => ['rgb' => 'FFFFFF']],
            'fill' => ['fillType' => Fill::FILL_SOLID, 'startColor' => ['rgb' => '0F766E']],
        ]);

        $maxCatalogRows = max($branches->count(), $categories->count(), $suppliers->count());
        for ($i = 0; $i < $maxCatalogRows; $i++) {
            $r = $catalogHeaderRow + 1 + $i;
            if ($branch = $branches->values()->get($i)) {
                $guideSheet->setCellValue("A{$r}", "{$branch->id} - {$branch->name}");
            }
            if ($cat = $categories->values()->get($i)) {
                $guideSheet->setCellValue("B{$r}", "{$cat->id} - {$cat->name}");
            }
            if ($sup = $suppliers->values()->get($i)) {
                $shop = $sup->shopname ? " ({$sup->shopname})" : '';
                $guideSheet->setCellValue("C{$r}", "{$sup->id} - {$sup->name}{$shop}");
            }
        }

        foreach (['A' => 30, 'B' => 30, 'C' => 34, 'D' => 22, 'E' => 56] as $col => $width) {
            $guideSheet->getColumnDimension($col)->setWidth($width);
        }

        $spreadsheet->setActiveSheetIndex(0);

        return $spreadsheet;
    }

    /**
     * Genera libro Excel (.xlsx) para exportar productos con formato compatible de re-importación.
     */
    public function generateExportSpreadsheet(Collection $products): Spreadsheet
    {
        $columns = $this->templateColumns();
        $spreadsheet = new Spreadsheet();
        $sheet = $spreadsheet->getActiveSheet();
        $sheet->setTitle('Productos');
        $sheet->freezePane('A2');

        foreach ($columns as $idx => $col) {
            $colLetter = Coordinate::stringFromColumnIndex($idx + 1);
            $headerCoord = $colLetter . '1';
            // Quitamos el asterisco en exportación manteniendo compatibilidad total con aliases del importador
            $cleanHeader = trim(str_replace('*', '', $col['header']));
            $sheet->setCellValueExplicit($headerCoord, $cleanHeader, DataType::TYPE_STRING);
            $sheet->getColumnDimension($colLetter)->setWidth($col['width']);
            $sheet->getStyle($headerCoord)->applyFromArray([
                'font' => ['bold' => true, 'color' => ['rgb' => 'FFFFFF']],
                'fill' => ['fillType' => Fill::FILL_SOLID, 'startColor' => ['rgb' => '0F172A']],
                'alignment' => ['horizontal' => Alignment::HORIZONTAL_CENTER],
            ]);
        }

        $rowIndex = 2;
        foreach ($products as $product) {
            $values = [
                'product_name' => $product->product_name,
                'category' => $product->category?->name ?? $product->category_id,
                'supplier' => $product->supplier?->name ?? $product->supplier_id,
                'branch' => $product->branch?->name ?? $product->branch_id ?? '',
                'product_code' => $product->product_code ?? '',
                'buying_price' => (float) ($product->buying_price ?? 0),
                'selling_price' => (float) ($product->selling_price ?? 0),
                'stock_quantity' => (int) ($product->stock_quantity ?? 0),
                'imei' => $product->imei ?? '',
                'brand' => $product->brand ?? '',
                'model' => $product->model ?? '',
                'category_status' => $product->category_status ?? '',
                'warranty_time' => $product->warranty_time ?? '',
                'product_garage' => $product->product_garage ?? '',
                'product_store' => $product->product_store ?? '',
                'buying_date' => $product->buying_date ?? '',
                'expire_date' => $product->expire_date ?? '',
                'observations' => $product->observations ?? '',
            ];

            foreach ($columns as $idx => $col) {
                $colLetter = Coordinate::stringFromColumnIndex($idx + 1);
                $coord = $colLetter . $rowIndex;
                $val = $values[$col['key']] ?? '';

                if (in_array($col['key'], ['buying_price', 'selling_price'], true)) {
                    $sheet->setCellValue($coord, (float) $val);
                    $sheet->getStyle($coord)->getNumberFormat()->setFormatCode(NumberFormat::FORMAT_NUMBER_00);
                } elseif ($col['key'] === 'stock_quantity') {
                    $sheet->setCellValue($coord, (int) $val);
                    $sheet->getStyle($coord)->getNumberFormat()->setFormatCode(NumberFormat::FORMAT_NUMBER);
                } else {
                    $sheet->setCellValueExplicit($coord, (string) $val, DataType::TYPE_STRING);
                }
            }

            $rowIndex++;
        }

        return $spreadsheet;
    }

    /**
     * Valida e importa productos de forma atómica (todo o nada) con soporte multisucursal.
     *
     * @return array{ok: bool, inserted: int, updated: int, skipped_empty: int, errors: array<int, string>}
     */
    public function importFromPath(
        string $filePath,
        $user = null,
        ?int $defaultBranchId = null,
        string $duplicateMode = 'reject'
    ): array {
        $duplicateMode = in_array($duplicateMode, ['reject', 'update'], true) ? $duplicateMode : 'reject';
        $canAllBranches = Product::userCanAccessAllBranches($user);
        $userBranchId = $user && $user->branch_id ? (int) $user->branch_id : null;

        if (!$canAllBranches && !$userBranchId) {
            return [
                'ok' => false,
                'inserted' => 0,
                'updated' => 0,
                'skipped_empty' => 0,
                'errors' => ['Tu usuario no tiene una sucursal asignada para importar productos. Solicita asignación a un administrador.'],
            ];
        }

        if (!$canAllBranches) {
            $defaultBranchId = $userBranchId;
        }

        $spreadsheet = IOFactory::load($filePath);
        $sheet = $spreadsheet->getSheet(0);
        $highestRow = $sheet->getHighestDataRow();
        $highestColIndex = Coordinate::columnIndexFromString($sheet->getHighestDataColumn());

        if ($highestRow < 2) {
            return [
                'ok' => false,
                'inserted' => 0,
                'updated' => 0,
                'skipped_empty' => 0,
                'errors' => ['El archivo no contiene filas de datos debajo del encabezado (fila 1).'],
            ];
        }

        $headerMap = $this->resolveHeaderMap($sheet, $highestColIndex);
        $missingHeaders = [];
        foreach (['product_name', 'category', 'supplier', 'buying_price', 'selling_price', 'stock_quantity'] as $requiredKey) {
            if (!isset($headerMap[$requiredKey])) {
                $missingHeaders[] = $this->labelForKey($requiredKey);
            }
        }
        if (!empty($missingHeaders)) {
            return [
                'ok' => false,
                'inserted' => 0,
                'updated' => 0,
                'skipped_empty' => 0,
                'errors' => [
                    'El archivo no coincide con la estructura esperada. Faltan las columnas obligatorias: '
                    . implode(', ', $missingHeaders)
                    . '. Descarga la plantilla oficial para evitar errores.',
                ],
            ];
        }

        // Pre-cargar catálogos en memoria para lookup rápido por ID o nombre normalizado
        $categories = Category::all(['id', 'name']);
        $catById = $categories->keyBy('id');
        $catByName = $categories->keyBy(fn($c) => self::normalizeText($c->name));

        $suppliers = Supplier::all(['id', 'name', 'shopname']);
        $supById = $suppliers->keyBy('id');
        $supByName = [];
        foreach ($suppliers as $sup) {
            if ($sup->name) {
                $supByName[self::normalizeText($sup->name)] = $sup;
            }
            if ($sup->shopname && !isset($supByName[self::normalizeText($sup->shopname)])) {
                $supByName[self::normalizeText($sup->shopname)] = $sup;
            }
        }

        $branches = Branch::all(['id', 'name']);
        $branchById = $branches->keyBy('id');
        $branchByName = $branches->keyBy(fn($b) => self::normalizeText($b->name));

        if ($defaultBranchId && !$branchById->has($defaultBranchId)) {
            return [
                'ok' => false,
                'inserted' => 0,
                'updated' => 0,
                'skipped_empty' => 0,
                'errors' => ["La sucursal predeterminada seleccionada (ID {$defaultBranchId}) no existe."],
            ];
        }

        // Pre-cargar índices de productos existentes para validar duplicados de forma consistente
        $existingProducts = Product::query()->get(['id', 'branch_id', 'product_code', 'imei']);
        $existingByBranchCode = [];
        $existingByImei = [];
        $reservedCodes = [];
        $maxPcNumber = 0;

        foreach ($existingProducts as $existing) {
            $code = trim((string) ($existing->product_code ?? ''));
            if ($code !== '') {
                $normCode = mb_strtoupper($code, 'UTF-8');
                $reservedCodes[$normCode] = true;
                $branchKey = (int) ($existing->branch_id ?? 0);
                $existingByBranchCode["{$branchKey}|{$normCode}"] = $existing;

                if (preg_match('/^PC0*(\d+)$/i', $code, $m)) {
                    $maxPcNumber = max($maxPcNumber, (int) $m[1]);
                }
            }

            $existingImei = trim((string) ($existing->imei ?? ''));
            if ($existingImei !== '') {
                $existingByImei[mb_strtoupper($existingImei, 'UTF-8')] = $existing;
            }
        }

        $operations = [];
        $seenBranchCodesInFile = [];
        $seenImeisInFile = [];
        $rowErrors = [];
        $skippedEmpty = 0;

        for ($rowNum = 2; $rowNum <= $highestRow; $rowNum++) {
            $rawRow = $this->readRowValues($sheet, $rowNum, $headerMap);

            if ($this->isRowCompletelyEmpty($rawRow)) {
                $skippedEmpty++;
                continue;
            }

            $currentErrors = [];

            // 1. Nombre del producto *
            $productName = $rawRow['product_name'] ?? '';
            if ($productName === '') {
                $currentErrors[] = 'NOMBRE DEL PRODUCTO es obligatorio.';
            }

            // 2. Categoría *
            $categoryId = $this->resolveCatalogId($rawRow['category'] ?? '', $catById, $catByName);
            if (!$categoryId) {
                $val = $rawRow['category'] ?? '';
                $currentErrors[] = $val === ''
                    ? 'CATEGORIA es obligatoria.'
                    : "La categoría '{$val}' no existe en el catálogo.";
            }

            // 3. Proveedor *
            $supplierId = $this->resolveCatalogId($rawRow['supplier'] ?? '', $supById, $supByName);
            if (!$supplierId) {
                $val = $rawRow['supplier'] ?? '';
                $currentErrors[] = $val === ''
                    ? 'PROVEEDOR es obligatorio.'
                    : "El proveedor '{$val}' no existe en el catálogo.";
            }

            // 4. Sucursal (con aislamiento estricto)
            $rawBranch = $rawRow['branch'] ?? '';
            $branchId = null;
            if ($rawBranch !== '') {
                $resolvedBranchId = $this->resolveCatalogId($rawBranch, $branchById, $branchByName);
                if (!$resolvedBranchId) {
                    $currentErrors[] = "La sucursal '{$rawBranch}' no existe en el catálogo.";
                } elseif (!$canAllBranches && $resolvedBranchId !== $userBranchId) {
                    $allowedBranchName = $branchById->get($userBranchId)?->name ?? "#{$userBranchId}";
                    $currentErrors[] = "No tienes permiso para importar productos a la sucursal '{$rawBranch}' (solo puedes importar a '{$allowedBranchName}').";
                } else {
                    $branchId = $resolvedBranchId;
                }
            } else {
                if ($defaultBranchId) {
                    $branchId = $defaultBranchId;
                } elseif (!$canAllBranches && $userBranchId) {
                    $branchId = $userBranchId;
                } else {
                    $currentErrors[] = 'SUCURSAL está vacía y no seleccionaste una sucursal destino predeterminada.';
                }
            }

            // 5. Precios y stock *
            $buyingPrice = $this->parseNonNegativeDecimal($rawRow['buying_price'] ?? '');
            if ($buyingPrice === null) {
                $currentErrors[] = 'PRECIO COMPRA debe ser un número válido mayor o igual a 0.';
            }

            $sellingPrice = $this->parseNonNegativeDecimal($rawRow['selling_price'] ?? '');
            if ($sellingPrice === null) {
                $currentErrors[] = 'PRECIO VENTA debe ser un número válido mayor o igual a 0.';
            }

            $stockQuantity = $this->parseNonNegativeInteger($rawRow['stock_quantity'] ?? '');
            if ($stockQuantity === null) {
                $currentErrors[] = 'STOCK debe ser un número entero mayor o igual a 0.';
            }

            // 6. Fechas opcionales
            [$buyingDateOk, $buyingDate] = $this->parseFlexibleDate($sheet, $rowNum, $headerMap['buying_date'] ?? null, $rawRow['buying_date'] ?? '');
            if (!$buyingDateOk) {
                $currentErrors[] = "FECHA COMPRA '{$rawRow['buying_date']}' no tiene un formato válido (usa YYYY-MM-DD o DD/MM/YYYY).";
            }

            [$expireDateOk, $expireDate] = $this->parseFlexibleDate($sheet, $rowNum, $headerMap['expire_date'] ?? null, $rawRow['expire_date'] ?? '');
            if (!$expireDateOk) {
                $currentErrors[] = "FECHA EXPIRACION '{$rawRow['expire_date']}' no tiene un formato válido (usa YYYY-MM-DD o DD/MM/YYYY).";
            }

            // 7. Código / SKU y detección de duplicados
            $productCode = trim((string) ($rawRow['product_code'] ?? ''));
            $targetExistingId = null;

            if ($productCode !== '' && !Product::isBarcodeSafe($productCode)) {
                $currentErrors[] = "El código '{$productCode}' contiene caracteres no compatibles con código de barras (evita acentos, eñes o símbolos especiales).";
            }

            if ($productCode !== '' && $branchId !== null) {
                $normCode = mb_strtoupper($productCode, 'UTF-8');
                $branchCodeKey = "{$branchId}|{$normCode}";

                if (isset($seenBranchCodesInFile[$branchCodeKey])) {
                    $firstRow = $seenBranchCodesInFile[$branchCodeKey];
                    $currentErrors[] = "El código '{$productCode}' está duplicado dentro del archivo en la fila {$firstRow} para la misma sucursal.";
                } else {
                    $seenBranchCodesInFile[$branchCodeKey] = $rowNum;
                }

                if (isset($existingByBranchCode[$branchCodeKey])) {
                    if ($duplicateMode === 'update') {
                        $targetExistingId = $existingByBranchCode[$branchCodeKey]->id;
                    } else {
                        $branchLabel = $branchById->get($branchId)?->name ?? "#{$branchId}";
                        $currentErrors[] = "El código '{$productCode}' ya existe en la sucursal '{$branchLabel}'. Activa 'Actualizar existentes' si deseas modificarlo.";
                    }
                }
                $reservedCodes[$normCode] = true;
            }

            // 8. IMEI / Serie opcional
            $imei = trim((string) ($rawRow['imei'] ?? ''));
            if ($imei !== '') {
                $normImei = mb_strtoupper($imei, 'UTF-8');
                if (isset($seenImeisInFile[$normImei])) {
                    $currentErrors[] = "El IMEI/Serie '{$imei}' está duplicado dentro del archivo (fila {$seenImeisInFile[$normImei]}).";
                } else {
                    $seenImeisInFile[$normImei] = $rowNum;
                }

                if (isset($existingByImei[$normImei])) {
                    $ownerId = $existingByImei[$normImei]->id;
                    if ($targetExistingId === null || $ownerId !== $targetExistingId) {
                        if ($duplicateMode === 'update' && $targetExistingId === null && $productCode === '') {
                            $targetExistingId = $ownerId;
                        } else {
                            $currentErrors[] = "El IMEI/Serie '{$imei}' ya pertenece a otro producto registrado (ID #{$ownerId}).";
                        }
                    }
                }
            }

            if (!empty($currentErrors)) {
                $rowErrors[] = "Fila {$rowNum}: " . implode(' ', $currentErrors);
                continue;
            }

            $payload = [
                'branch_id' => $branchId,
                'product_name' => $productName,
                'category_id' => $categoryId,
                'supplier_id' => $supplierId,
                'product_code' => $productCode,
                'buying_price' => $buyingPrice,
                'selling_price' => $sellingPrice,
                'stock_quantity' => $stockQuantity,
                'imei' => $imei !== '' ? $imei : null,
                'brand' => ($rawRow['brand'] ?? '') !== '' ? $rawRow['brand'] : null,
                'model' => ($rawRow['model'] ?? '') !== '' ? $rawRow['model'] : null,
                'category_status' => ($rawRow['category_status'] ?? '') !== '' ? $rawRow['category_status'] : null,
                'warranty_time' => ($rawRow['warranty_time'] ?? '') !== '' ? $rawRow['warranty_time'] : null,
                'product_garage' => ($rawRow['product_garage'] ?? '') !== '' ? $rawRow['product_garage'] : null,
                'product_store' => ($rawRow['product_store'] ?? '') !== '' ? $rawRow['product_store'] : null,
                'buying_date' => $buyingDate,
                'expire_date' => $expireDate,
                'observations' => ($rawRow['observations'] ?? '') !== '' ? $rawRow['observations'] : null,
            ];

            if (!empty($rawRow['product_image'])) {
                $payload['product_image'] = $rawRow['product_image'];
            }

            $operations[] = [
                'action' => $targetExistingId ? 'update' : 'insert',
                'target_id' => $targetExistingId,
                'payload' => $payload,
            ];
        }

        if (!empty($rowErrors)) {
            return [
                'ok' => false,
                'inserted' => 0,
                'updated' => 0,
                'skipped_empty' => $skippedEmpty,
                'errors' => $rowErrors,
            ];
        }

        if (empty($operations)) {
            return [
                'ok' => false,
                'inserted' => 0,
                'updated' => 0,
                'skipped_empty' => $skippedEmpty,
                'errors' => ['No se encontraron filas con datos válidos para importar.'],
            ];
        }

        // Generar códigos secuenciales PC0001+ para filas nuevas sin SKU
        foreach ($operations as &$op) {
            if ($op['action'] === 'insert' && $op['payload']['product_code'] === '') {
                $op['payload']['product_code'] = Product::generateUniqueCode($reservedCodes, $maxPcNumber);
            }
        }
        unset($op);

        $inserted = 0;
        $updated = 0;

        DB::transaction(function () use ($operations, &$inserted, &$updated) {
            foreach ($operations as $op) {
                $payload = $op['payload'];
                if ($op['action'] === 'update' && $op['target_id']) {
                    if ($payload['product_code'] === '') {
                        unset($payload['product_code']);
                    }
                    Product::where('id', $op['target_id'])->update($payload);
                    $updated++;
                } else {
                    Product::create($payload);
                    $inserted++;
                }
            }
        });

        return [
            'ok' => true,
            'inserted' => $inserted,
            'updated' => $updated,
            'skipped_empty' => $skippedEmpty,
            'errors' => [],
        ];
    }

    /**
     * Normaliza texto en UTF-8 (sin usar funciones obsoletas de PHP 8.x).
     */
    public static function normalizeText(?string $value): string
    {
        $str = mb_strtolower(trim((string) $value), 'UTF-8');
        $str = strtr($str, [
            'á' => 'a', 'à' => 'a', 'ä' => 'a', 'â' => 'a', 'ã' => 'a', 'å' => 'a',
            'é' => 'e', 'è' => 'e', 'ë' => 'e', 'ê' => 'e',
            'í' => 'i', 'ì' => 'i', 'ï' => 'i', 'î' => 'i',
            'ó' => 'o', 'ò' => 'o', 'ö' => 'o', 'ô' => 'o', 'õ' => 'o', 'ø' => 'o',
            'ú' => 'u', 'ù' => 'u', 'ü' => 'u', 'û' => 'u',
            'ñ' => 'n', 'ç' => 'c', '*' => '',
        ]);

        return trim(preg_replace('/\s+/', ' ', $str) ?? '');
    }

    /**
     * Genera siguiente código SKU disponible para productos nuevos.
     */
    public function generateUniqueCodeForBranch(): string
    {
        return Product::generateUniqueCode();
    }

    /**
     * Mapea índices 1-based de columna según encabezados de la fila 1.
     *
     * @return array<string, int>
     */
    private function resolveHeaderMap($sheet, int $highestColIndex): array
    {
        $aliasToKey = [
            'product image' => 'product_image',
            'product_image' => 'product_image',
            'imagen' => 'product_image',
        ];

        foreach ($this->templateColumns() as $col) {
            $aliasToKey[self::normalizeText($col['header'])] = $col['key'];
            foreach ($col['aliases'] as $alias) {
                $aliasToKey[self::normalizeText($alias)] = $col['key'];
            }
        }

        $map = [];
        for ($colIdx = 1; $colIdx <= $highestColIndex; $colIdx++) {
            $coord = Coordinate::stringFromColumnIndex($colIdx) . '1';
            $rawHeader = $this->readCellAsString($sheet->getCell($coord));
            $norm = self::normalizeText($rawHeader);
            if ($norm !== '' && isset($aliasToKey[$norm]) && !isset($map[$aliasToKey[$norm]])) {
                $map[$aliasToKey[$norm]] = $colIdx;
            }
        }

        return $map;
    }

    private function labelForKey(string $key): string
    {
        foreach ($this->templateColumns() as $col) {
            if ($col['key'] === $key) {
                return $col['header'];
            }
        }

        return $key;
    }

    private function readRowValues($sheet, int $rowNum, array $headerMap): array
    {
        $row = [];
        foreach ($headerMap as $key => $colIdx) {
            $coord = Coordinate::stringFromColumnIndex($colIdx) . $rowNum;
            $row[$key] = $this->readCellAsString($sheet->getCell($coord));
        }

        return $row;
    }

    private function readCellAsString(Cell $cell): string
    {
        $val = $cell->getCalculatedValue();
        if ($val === null) {
            return '';
        }

        if ($cell->getDataType() === DataType::TYPE_NUMERIC && (is_int($val) || is_float($val))) {
            $floatVal = (float) $val;
            if (floor($floatVal) === $floatVal) {
                return number_format($floatVal, 0, '', '');
            }

            return rtrim(rtrim(sprintf('%.6F', $floatVal), '0'), '.');
        }

        return trim((string) $val);
    }

    private function isRowCompletelyEmpty(array $rawRow): bool
    {
        foreach ($rawRow as $val) {
            if (trim((string) $val) !== '') {
                return false;
            }
        }

        return true;
    }

    private function resolveCatalogId(string $rawValue, Collection $byId, Collection|array $byName): ?int
    {
        $trimmed = trim($rawValue);
        if ($trimmed === '') {
            return null;
        }

        // Aceptar formato "ID - Nombre" por si el usuario copia directo de la hoja de catálogos
        if (preg_match('/^(\d+)\s*-\s*.+$/', $trimmed, $m)) {
            $id = (int) $m[1];
            if ($byId->has($id)) {
                return $id;
            }
        }

        if (ctype_digit($trimmed)) {
            $id = (int) $trimmed;
            if ($byId->has($id)) {
                return $id;
            }
        }

        $norm = self::normalizeText($trimmed);
        $record = $byName[$norm] ?? null;

        return $record ? (int) $record->id : null;
    }

    private function parseNonNegativeDecimal(string $raw): ?float
    {
        $clean = trim($raw);
        if ($clean === '') {
            return null;
        }

        $clean = str_replace(['$', 'MXN', 'mxn', ' '], '', $clean);
        // Si trae coma y punto, o solo coma decimal (ej. 150,50 vs 1,250.00)
        if (str_contains($clean, ',') && str_contains($clean, '.')) {
            $clean = str_replace(',', '', $clean);
        } elseif (str_contains($clean, ',') && preg_match('/^\d+,\d{1,2}$/', $clean)) {
            $clean = str_replace(',', '.', $clean);
        } else {
            $clean = str_replace(',', '', $clean);
        }

        if (!is_numeric($clean)) {
            return null;
        }

        $num = round((float) $clean, 2);

        return $num >= 0 ? $num : null;
    }

    private function parseNonNegativeInteger(string $raw): ?int
    {
        $clean = trim(str_replace(',', '', $raw));
        if ($clean === '' || !is_numeric($clean)) {
            return null;
        }

        $floatVal = (float) $clean;
        if ($floatVal < 0 || floor($floatVal) !== $floatVal) {
            return null;
        }

        return (int) $floatVal;
    }

    /**
     * @return array{0: bool, 1: ?string}
     */
    private function parseFlexibleDate($sheet, int $rowNum, ?int $colIdx, string $raw): array
    {
        if ($raw === '') {
            return [true, null];
        }

        if ($colIdx !== null) {
            $coord = Coordinate::stringFromColumnIndex($colIdx) . $rowNum;
            $cell = $sheet->getCell($coord);
            $rawCellVal = $cell->getCalculatedValue();
            if ($cell->getDataType() === DataType::TYPE_NUMERIC && is_numeric($rawCellVal) && (float) $rawCellVal >= 20000 && (float) $rawCellVal <= 100000) {
                try {
                    return [true, Carbon::instance(ExcelDate::excelToDateTimeObject((float) $rawCellVal))->format('Y-m-d')];
                } catch (\Throwable $e) {
                    // continúa con parseo textual
                }
            }
        }

        $trimmed = trim($raw);
        if (is_numeric($trimmed)) {
            return [false, null];
        }

        foreach (['Y-m-d', 'd/m/Y', 'd-m-Y', 'Y/m/d'] as $format) {
            try {
                $dt = Carbon::createFromFormat($format, $trimmed);
                if ($dt && $dt->format($format) === $trimmed) {
                    return [true, $dt->format('Y-m-d')];
                }
            } catch (\Throwable $e) {
                // prueba siguiente formato
            }
        }

        try {
            $dt = Carbon::parse($trimmed);
            return [true, $dt->format('Y-m-d')];
        } catch (\Throwable $e) {
            return [false, null];
        }
    }
}
