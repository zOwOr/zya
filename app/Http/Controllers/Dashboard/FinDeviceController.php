<?php

namespace App\Http\Controllers\Dashboard;

use App\Events\FinancierasUpdated;
use App\Http\Controllers\Controller;
use App\Http\Controllers\Traits\ModulePermissionTrait;
use App\Models\Branch;
use App\Models\FinBrand;
use App\Models\FinDevice;
use App\Models\FinDeviceTransfer;
use App\Models\FinSupplier;
use Carbon\Carbon;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use PhpOffice\PhpSpreadsheet\Cell\DataType;
use PhpOffice\PhpSpreadsheet\IOFactory;
use PhpOffice\PhpSpreadsheet\Spreadsheet;
use PhpOffice\PhpSpreadsheet\Writer\Xls;

class FinDeviceController extends Controller
{
    use ModulePermissionTrait;

    protected ?string $permissionResource = 'financieras.inventario';

    protected array $permissionMapping = [
        'index' => 'read',
        'show' => 'read',
        'history' => 'read',
        'create' => 'create',
        'store' => 'create',
        'edit' => 'edit',
        'update' => 'edit',
        'destroy' => 'delete',
        'bulkDelete' => 'delete',
        'transfer' => 'transfer',
        'importExcel' => 'create',
        'exportExcel' => 'read',
    ];

    public function __construct()
    {
        $this->initializeModulePermission();
    }

    public function index(Request $request)
    {
        $filters = $request->only(['search', 'branch_id', 'status', 'brand_id', 'supplier_id', 'model']);

        $devices = FinDevice::filter($filters)
            ->with(['brand', 'branch', 'supplier', 'latestSale'])
            ->latest()
            ->paginate(20)
            ->withQueryString();

        if ($request->ajax()) {
            return view('financieras.inventario.table', compact('devices'))->render();
        }

        $branches = Branch::all();
        $brands = FinBrand::where('is_active', true)->get();
        $suppliers = FinSupplier::where('is_active', true)->orderBy('name')->get();
        $models = FinDevice::distinct()->whereNotNull('model')->where('model', '!=', '')->orderBy('model')->pluck('model');

        return view('financieras.inventario.index', compact('devices', 'branches', 'brands', 'suppliers', 'models'));
    }

    public function create()
    {
        $branches = Branch::all();
        $brands = FinBrand::where('is_active', true)->get();
        $suppliers = FinSupplier::where('is_active', true)->orderBy('name')->get();

        return view('financieras.inventario.create', compact('branches', 'brands', 'suppliers'));
    }

    public function store(Request $request)
    {
        $request->validate([
            'imei' => 'required|string|max:50|unique:fin_devices,imei',
            'brand_id' => 'nullable|exists:fin_brands,id',
            'brand_name' => 'nullable|string|max:100',
            'model' => 'required|string|max:150',
            'color' => 'nullable|string|max:50',
            'storage' => 'nullable|string|max:50',
            'branch_id' => 'required|exists:branches,id',
            'supplier_id' => 'nullable|exists:fin_suppliers,id',
            'notes' => 'nullable|string|max:1000',
        ], [
            'imei.required' => 'El número de IMEI es obligatorio.',
            'imei.unique' => 'Este número de IMEI ya se encuentra registrado en el inventario.',
            'model.required' => 'El modelo del dispositivo es obligatorio.',
            'branch_id.required' => 'Debe seleccionar la sucursal de entrada.',
            'branch_id.exists' => 'La sucursal seleccionada no es válida.',
            'supplier_id.exists' => 'El proveedor seleccionado no es válido.',
        ]);

        $brandId = $request->input('brand_id');
        if (!$brandId && $request->filled('brand_name')) {
            $brand = FinBrand::firstOrCreate(['name' => trim($request->input('brand_name'))]);
            $brandId = $brand->id;
        }

        $device = FinDevice::create([
            'imei' => trim($request->input('imei')),
            'brand_id' => $brandId,
            'model' => trim($request->input('model')),
            'color' => $request->input('color'),
            'storage' => $request->input('storage'),
            'branch_id' => $request->input('branch_id'),
            'supplier_id' => $request->input('supplier_id'),
            'status' => 'disponible',
            'notes' => $request->input('notes'),
        ]);

        try {
            event(new FinancierasUpdated(
                'inventario',
                'created',
                "Dispositivo agregado con IMEI {$device->imei}",
                ['device_id' => $device->id, 'imei' => $device->imei]
            ));
        } catch (\Throwable $e) {
            \Log::warning('Reverb broadcast warning: ' . $e->getMessage());
        }

        return redirect()->route('financieras.index', ['tab' => 'inventario'])
            ->with('success', "Dispositivo con IMEI {$device->imei} agregado al inventario.");
    }

    public function show(FinDevice $device)
    {
        $device->load([
            'brand',
            'branch',
            'supplier',
            'transfers.fromBranch',
            'transfers.toBranch',
            'transfers.user',
            'sales.financiera',
            'sales.seller',
            'warranties.currentStage',
            'theftReports'
        ]);

        return view('financieras.inventario.show', compact('device'));
    }

    public function edit(FinDevice $device)
    {
        $branches = Branch::all();
        $brands = FinBrand::where('is_active', true)->get();
        $suppliers = FinSupplier::where('is_active', true)->orderBy('name')->get();

        return view('financieras.inventario.edit', compact('device', 'branches', 'brands', 'suppliers'));
    }

    public function update(Request $request, FinDevice $device)
    {
        $request->validate([
            'imei' => 'required|string|max:50|unique:fin_devices,imei,' . $device->id,
            'brand_id' => 'nullable|exists:fin_brands,id',
            'brand_name' => 'nullable|string|max:100',
            'model' => 'required|string|max:150',
            'color' => 'nullable|string|max:50',
            'storage' => 'nullable|string|max:50',
            'branch_id' => 'required|exists:branches,id',
            'supplier_id' => 'nullable|exists:fin_suppliers,id',
            'status' => 'required|in:disponible,vendido,en_garantia,robado',
            'notes' => 'nullable|string|max:1000',
        ], [
            'imei.required' => 'El número de IMEI es obligatorio.',
            'imei.unique' => 'Este número de IMEI ya se encuentra registrado.',
            'model.required' => 'El modelo del dispositivo es obligatorio.',
            'branch_id.required' => 'Debe seleccionar la sucursal.',
            'branch_id.exists' => 'La sucursal seleccionada no es válida.',
            'supplier_id.exists' => 'El proveedor seleccionado no es válido.',
            'status.required' => 'El estado del dispositivo es obligatorio.',
            'status.in' => 'El estado seleccionado no es válido.',
        ]);

        $brandId = $request->input('brand_id');
        if (!$brandId && $request->filled('brand_name')) {
            $brand = FinBrand::firstOrCreate(['name' => trim($request->input('brand_name'))]);
            $brandId = $brand->id;
        }

        $device->update([
            'imei' => trim($request->input('imei')),
            'brand_id' => $brandId,
            'model' => trim($request->input('model')),
            'color' => $request->input('color'),
            'storage' => $request->input('storage'),
            'branch_id' => $request->input('branch_id'),
            'supplier_id' => $request->input('supplier_id'),
            'status' => $request->input('status'),
            'notes' => $request->input('notes'),
        ]);

        try {
            event(new FinancierasUpdated(
                'inventario',
                'updated',
                "Dispositivo {$device->imei} actualizado",
                ['device_id' => $device->id]
            ));
        } catch (\Throwable $e) {
            \Log::warning('Reverb broadcast warning: ' . $e->getMessage());
        }

        return redirect()->route('financieras.inventario.show', $device->id)
            ->with('success', 'Dispositivo actualizado correctamente.');
    }

    /**
     * Transfer device to another branch
     */
    public function transfer(Request $request, FinDevice $device)
    {
        $request->validate([
            'to_branch_id' => 'required|exists:branches,id|different:current_branch_id',
            'notes' => 'nullable|string|max:500',
        ], [
            'to_branch_id.required' => 'Debe seleccionar la sucursal de destino.',
            'to_branch_id.exists' => 'La sucursal de destino no es válida.',
            'to_branch_id.different' => 'La sucursal de destino no puede ser la misma que la sucursal actual.',
        ]);

        if ($device->status === 'vendido') {
            return back()->with('error', 'No se puede transferir un equipo con estado "vendido".');
        }

        $fromBranchId = $device->branch_id;
        $toBranchId = $request->input('to_branch_id');

        DB::transaction(function () use ($device, $fromBranchId, $toBranchId, $request) {
            FinDeviceTransfer::create([
                'device_id' => $device->id,
                'from_branch_id' => $fromBranchId,
                'to_branch_id' => $toBranchId,
                'user_id' => auth()->id(),
                'notes' => $request->input('notes'),
            ]);

            $device->update(['branch_id' => $toBranchId]);
        });

        $toBranchName = Branch::find($toBranchId)?->name;

        try {
            event(new FinancierasUpdated(
                'inventario',
                'transferred',
                "Dispositivo {$device->imei} transferido a {$toBranchName}",
                ['device_id' => $device->id, 'to_branch' => $toBranchName]
            ));
        } catch (\Throwable $e) {
            \Log::warning('Reverb broadcast warning: ' . $e->getMessage());
        }

        return back()->with('success', "Dispositivo transferido exitosamente a la sucursal {$toBranchName}.");
    }

    /**
     * Full history for device (transfers, sales, warranties, theft reports)
     */
    public function history(FinDevice $device)
    {
        $device->load([
            'brand',
            'branch',
            'supplier',
            'transfers.fromBranch',
            'transfers.toBranch',
            'transfers.user',
            'sales.financiera',
            'sales.seller',
            'sales.notes.user',
            'warranties.currentStage',
            'warranties.logs.user',
            'theftReports.logs.user'
        ]);

        return view('financieras.inventario.history', compact('device'));
    }

    public function destroy(FinDevice $device)
    {
        if ($device->sales()->where('status', 'activa')->exists()) {
            return back()->with('error', 'No se puede eliminar un dispositivo con una venta activa vinculada.');
        }

        $imei = $device->imei;
        $id = $device->id;

        // Si nunca ha tenido ventas, eliminación definitiva para liberar el IMEI en la BD
        if ($device->sales()->exists()) {
            $device->delete();
        } else {
            $device->transfers()->delete();
            $device->forceDelete();
        }

        try {
            event(new FinancierasUpdated(
                'inventario',
                'deleted',
                "Dispositivo {$imei} eliminado de inventario",
                ['device_id' => $id]
            ));
        } catch (\Throwable $e) {
            \Log::warning('Reverb broadcast warning: ' . $e->getMessage());
        }

        return redirect()->route('financieras.index', ['tab' => 'inventario'])
            ->with('success', 'Dispositivo eliminado del inventario.');
    }

    /**
     * Bulk delete selected devices
     */
    public function bulkDelete(Request $request)
    {
        $request->validate([
            'device_ids' => 'required|array|min:1',
            'device_ids.*' => 'exists:fin_devices,id',
        ], [
            'device_ids.required' => 'Debe seleccionar al menos un dispositivo para eliminar.',
            'device_ids.min' => 'Debe seleccionar al menos un dispositivo para eliminar.',
        ]);

        $deviceIds = $request->input('device_ids');
        $devices = FinDevice::whereIn('id', $deviceIds)->with('sales')->get();

        $deletedCount = 0;
        $blockedCount = 0;
        $deletedIds = [];

        foreach ($devices as $device) {
            // Protección: No permitir eliminar dispositivos que tengan una venta activa
            if ($device->sales()->where('status', 'activa')->exists()) {
                $blockedCount++;
                continue;
            }

            $deletedIds[] = $device->id;
            // Si nunca ha tenido ventas, eliminación definitiva para liberar el IMEI
            if ($device->sales()->exists()) {
                $device->delete();
            } else {
                $device->transfers()->delete();
                $device->forceDelete();
            }
            $deletedCount++;
        }

        if ($deletedCount > 0) {
            try {
                event(new FinancierasUpdated(
                    'inventario',
                    'deleted',
                    "Se eliminaron masivamente {$deletedCount} dispositivos de inventario",
                    ['deleted_ids' => $deletedIds]
                ));
            } catch (\Throwable $e) {
                \Log::warning('Reverb broadcast warning: ' . $e->getMessage());
            }
        }

        if ($blockedCount > 0) {
            $msg = "Se eliminaron {$deletedCount} dispositivos.";
            $msg .= " ({$blockedCount} equipos fueron omitidos porque tienen ventas activas vinculadas).";
            return redirect()->route('financieras.index', ['tab' => 'inventario'])
                ->with($deletedCount > 0 ? 'success' : 'error', $msg);
        }

        return redirect()->route('financieras.index', ['tab' => 'inventario'])
            ->with('success', "Se eliminaron exitosamente {$deletedCount} dispositivos seleccionados del inventario.");
    }

    /**
     * Bulk import devices from Excel
     */
    public function importExcel(Request $request)
    {
        $request->validate([
            'import_file' => 'required|file|mimes:xlsx,xls,csv|max:10240',
            'default_branch_id' => 'nullable|exists:branches,id',
            'default_supplier_id' => 'nullable|exists:fin_suppliers,id',
        ], [
            'import_file.required' => 'Debe seleccionar un archivo Excel o CSV.',
            'import_file.mimes' => 'El formato del archivo debe ser .xlsx, .xls o .csv.',
            'import_file.max' => 'El tamaño máximo permitido es de 10 MB.',
            'default_branch_id.exists' => 'La sucursal seleccionada no es válida.',
            'default_supplier_id.exists' => 'El proveedor seleccionado no es válido.',
        ]);

        try {
            $file = $request->file('import_file');
            $spreadsheet = IOFactory::load($file->getRealPath());
            $worksheet = $spreadsheet->getActiveSheet();
            // formatData = false para obtener los valores raw de las celdas y evitar que Excel los formatee como notación científica
            $rows = $worksheet->toArray(null, true, false, false);

            if (count($rows) <= 1) {
                return back()->with('error', 'El archivo no contiene registros o está vacío.');
            }

            // Normalizador de texto para encabezados (remueve espacios, acentos y símbolos)
            $normalizeHeader = function ($str) {
                if ($str === null || $str === '') return '';
                $str = mb_strtoupper(trim((string)$str), 'UTF-8');
                $str = strtr(utf8_decode($str), utf8_decode('ÀÁÂÃÄÅàáâãäåÒÓÔÕÖØòóôõöøÈÉÊËèéêëÇçÌÍÎÏìíîïÙÚÛÜùúûüÿÑñ'), 'AAAAAAaaaaaaOOOOOOooooooEEEEeeeeCcIIIIiiiiUUUUuuuuyNn');
                return preg_replace('/[^A-Z0-9]/', '', $str);
            };

            // Helper robusto para formatear y limpiar el IMEI sin notación científica
            $cleanImei = function ($val) {
                if ($val === null || $val === '') return '';
                if (is_float($val) || is_int($val)) {
                    return number_format($val, 0, '', '');
                }
                $str = trim((string)$val);
                if (preg_match('/^[0-9]+(\.[0-9]+)?[eE][\+\-]?[0-9]+$/i', $str) || (stripos($str, 'e+') !== false && is_numeric($str))) {
                    return number_format((float)$str, 0, '', '');
                }
                return preg_replace('/[^0-9]/', '', $str);
            };

            // Mapear encabezados por nombre
            $headerMap = [];
            foreach ($rows[0] as $colIdx => $colVal) {
                $cleaned = $normalizeHeader($colVal);
                if (empty($cleaned)) continue;

                if (in_array($cleaned, ['IMEI'])) {
                    $headerMap['imei'] = $colIdx;
                } elseif (in_array($cleaned, ['MARCA', 'BRAND'])) {
                    $headerMap['brand'] = $colIdx;
                } elseif (in_array($cleaned, ['MODELO', 'MODEL'])) {
                    $headerMap['model'] = $colIdx;
                } elseif (in_array($cleaned, ['FECHADELLEGADA', 'FECHAINGRESO', 'FECHA', 'LLEGADA', 'FECHAREGISTRO', 'DATE'])) {
                    $headerMap['date'] = $colIdx;
                } elseif (in_array($cleaned, ['PROVEEDOR', 'PROVEEDORES', 'SUPPLIER'])) {
                    $headerMap['supplier'] = $colIdx;
                } elseif (in_array($cleaned, ['UBICACION', 'SUCURSAL', 'BRANCH'])) {
                    $headerMap['branch'] = $colIdx;
                } elseif (in_array($cleaned, ['COLOR', 'COLOUR'])) {
                    $headerMap['color'] = $colIdx;
                } elseif (in_array($cleaned, ['CAPACIDAD', 'ALMACENAMIENTO', 'STORAGE', 'MEMORIA'])) {
                    $headerMap['storage'] = $colIdx;
                }
            }

            // Validar que existan las columnas mínimas requeridas
            $missingCols = [];
            if (!isset($headerMap['imei'])) $missingCols[] = 'IMEI';
            if (!isset($headerMap['brand'])) $missingCols[] = 'MARCA';
            if (!isset($headerMap['model'])) $missingCols[] = 'MODELO';

            if (!empty($missingCols)) {
                return back()->with('error', 'El archivo no contiene las columnas requeridas: ' . implode(', ', $missingCols) . '. Verifique los encabezados del archivo Excel.');
            }

            // Normalizador para búsqueda exacta en catálogos (case-insensitive, sin acentos)
            $normalizeName = function ($str) {
                $str = mb_strtolower(trim((string)$str), 'UTF-8');
                $str = strtr(utf8_decode($str), utf8_decode('àáâãäåòóôõöøèéêëçìíîïùúûüñ'), 'aaaaaaooooooeeeeciiiiuuuun');
                return preg_replace('/\s+/', ' ', $str);
            };

            // Pre-cargar catálogos
            $brands = FinBrand::where('is_active', true)->get()->keyBy(fn($b) => $normalizeName($b->name));
            $branches = Branch::all()->keyBy(fn($b) => $normalizeName($b->name));
            $suppliers = FinSupplier::where('is_active', true)->get()->keyBy(fn($s) => $normalizeName($s->name));

            $defaultBranchId = $request->input('default_branch_id');
            $defaultSupplierId = $request->input('default_supplier_id');

            $devicesToInsert = [];
            $seenImeisInFile = [];
            $rowErrors = [];

            for ($i = 1; $i < count($rows); $i++) {
                $row = $rows[$i];
                $rowNum = $i + 1;

                // IMEI: obtener valor directo de la celda y limpiar notación científica
                $rawImei = $row[$headerMap['imei']] ?? null;
                if (isset($headerMap['imei'])) {
                    try {
                        $cellVal = $worksheet->getCellByColumnAndRow($headerMap['imei'] + 1, $rowNum)->getValue();
                        if ($cellVal !== null && $cellVal !== '') {
                            $rawImei = $cellVal;
                        }
                    } catch (\Throwable $t) {
                        // fallback a $row
                    }
                }
                $imei = $cleanImei($rawImei);

                // Si toda la fila está vacía, ignorarla
                if (empty($imei)) {
                    // Si la fila tiene algún otro dato pero no tiene IMEI, es un error
                    $hasOtherData = false;
                    foreach ($row as $cell) {
                        if (!empty(trim((string)$cell))) {
                            $hasOtherData = true;
                            break;
                        }
                    }
                    if ($hasOtherData) {
                        $rowErrors[] = "Fila {$rowNum}: La columna IMEI está vacía.";
                    }
                    continue;
                }

                // Validar duplicado en el mismo archivo
                if (isset($seenImeisInFile[$imei])) {
                    $rowErrors[] = "Fila {$rowNum}: El IMEI {$imei} está duplicado dentro del mismo archivo (aparece también en la fila {$seenImeisInFile[$imei]}).";
                } else {
                    $seenImeisInFile[$imei] = $rowNum;
                }

                // Validar si ya existe en la base de datos (activo)
                $existingDevice = FinDevice::withTrashed()->where('imei', $imei)->first();
                if ($existingDevice && !$existingDevice->trashed()) {
                    $rowErrors[] = "Fila {$rowNum}: El IMEI {$imei} ya existe en el inventario.";
                }

                // 1. MARCA: Validación estricta con catálogo fin_brands
                $rawBrand = isset($headerMap['brand']) ? trim((string)($row[$headerMap['brand']] ?? '')) : '';
                $brandId = null;
                if (empty($rawBrand)) {
                    $rowErrors[] = "Fila {$rowNum}: La columna MARCA está vacía.";
                } else {
                    $brandKey = $normalizeName($rawBrand);
                    if (!isset($brands[$brandKey])) {
                        $rowErrors[] = "Fila {$rowNum}: La marca '{$rawBrand}' no existe en el catálogo de marcas. Regístrela previamente en Catálogos.";
                    } else {
                        $brandId = $brands[$brandKey]->id;
                    }
                }

                // 2. MODELO
                $model = isset($headerMap['model']) ? trim((string)($row[$headerMap['model']] ?? '')) : '';
                if (empty($model)) {
                    $model = 'Modelo no especificado';
                }

                // 3. UBICACIÓN: Validación estricta con catálogo branches
                $branchId = null;
                if (isset($headerMap['branch']) && !empty(trim((string)($row[$headerMap['branch']] ?? '')))) {
                    $rawBranch = trim((string)$row[$headerMap['branch']]);
                    $branchKey = $normalizeName($rawBranch);
                    if (!isset($branches[$branchKey])) {
                        $rowErrors[] = "Fila {$rowNum}: La sucursal '{$rawBranch}' no existe en el catálogo de sucursales.";
                    } else {
                        $branchId = $branches[$branchKey]->id;
                    }
                } else {
                    $branchId = $defaultBranchId;
                }

                if (!$branchId) {
                    $rowErrors[] = "Fila {$rowNum}: No se especificó ubicación ni se seleccionó una sucursal por defecto.";
                }

                // 4. PROVEEDOR: Validación estricta con catálogo fin_suppliers
                $supplierId = null;
                if (isset($headerMap['supplier']) && !empty(trim((string)($row[$headerMap['supplier']] ?? '')))) {
                    $rawSupplier = trim((string)$row[$headerMap['supplier']]);
                    $supplierKey = $normalizeName($rawSupplier);
                    if (!isset($suppliers[$supplierKey])) {
                        $rowErrors[] = "Fila {$rowNum}: El proveedor '{$rawSupplier}' no existe en el catálogo de proveedores. Regístrelo previamente en Catálogos.";
                    } else {
                        $supplierId = $suppliers[$supplierKey]->id;
                    }
                } else {
                    $supplierId = $defaultSupplierId;
                }

                // 5. COLOR y CAPACIDAD (storage)
                $color = isset($headerMap['color']) ? trim((string)($row[$headerMap['color']] ?? '')) : null;
                $storage = isset($headerMap['storage']) ? trim((string)($row[$headerMap['storage']] ?? '')) : null;

                // 6. FECHA DE LLEGADA -> created_at
                $createdAt = now();
                if (isset($headerMap['date']) && !empty($row[$headerMap['date']])) {
                    $rawDate = $row[$headerMap['date']];
                    if (is_numeric($rawDate)) {
                        try {
                            $createdAt = Carbon::instance(\PhpOffice\PhpSpreadsheet\Shared\Date::excelToDateTimeObject($rawDate));
                        } catch (\Throwable $t) {
                            $createdAt = now();
                        }
                    } else {
                        try {
                            $strDate = trim((string)$rawDate);
                            if (preg_match('/^\d{1,2}\/\d{1,2}\/\d{2,4}/', $strDate)) {
                                $datePart = explode(' ', $strDate)[0];
                                $createdAt = Carbon::createFromFormat('d/m/Y', $datePart);
                            } else {
                                $createdAt = Carbon::parse($strDate);
                            }
                        } catch (\Throwable $t) {
                            $createdAt = now();
                        }
                    }
                }

                // Encolar datos validados si no hay errores acumulados en esta fila
                $devicesToInsert[] = [
                    'imei' => $imei,
                    'brand_id' => $brandId,
                    'model' => $model,
                    'color' => !empty($color) ? $color : null,
                    'storage' => !empty($storage) ? $storage : null,
                    'branch_id' => $branchId,
                    'supplier_id' => $supplierId,
                    'status' => 'disponible',
                    'notes' => null,
                    'created_at' => $createdAt,
                ];
            }

            // REGLA ATÓMICA: Si existe AL MENOS UNA incidencia, NO SE IMPORTA NADA
            if (count($rowErrors) > 0) {
                $errSample = array_slice($rowErrors, 0, 8);
                $errorMsg = "No se importó ningún dispositivo. Se encontraron " . count($rowErrors) . " incidencias que deben corregirse previamente: " . implode(" | ", $errSample);
                if (count($rowErrors) > 8) {
                    $errorMsg .= " ... y " . (count($rowErrors) - 8) . " incidencias más.";
                }

                return back()->with('error', $errorMsg)->with('import_errors', $rowErrors);
            }

            if (empty($devicesToInsert)) {
                return back()->with('error', 'No se encontraron registros válidos para importar en el archivo.');
            }

            // Todas las filas son válidas: insertar todas en una transacción
            $imported = 0;
            DB::transaction(function () use ($devicesToInsert, &$imported) {
                foreach ($devicesToInsert as $data) {
                    $createdAt = $data['created_at'];
                    unset($data['created_at']);

                    // Si existía un registro eliminado para este IMEI, se purga para liberar la clave única
                    $trashed = FinDevice::onlyTrashed()->where('imei', $data['imei'])->first();
                    if ($trashed) {
                        $trashed->transfers()->delete();
                        $trashed->forceDelete();
                    }

                    $device = new FinDevice($data);
                    $device->created_at = $createdAt;
                    $device->updated_at = now();
                    $device->save();
                    $imported++;
                }
            });

            try {
                event(new FinancierasUpdated(
                    'inventario',
                    'created',
                    "Importación masiva completada: {$imported} dispositivos",
                    ['imported' => $imported]
                ));
            } catch (\Throwable $e) {
                \Log::warning('Reverb broadcast warning: ' . $e->getMessage());
            }

            return back()->with('success', "Se importaron exitosamente todos los {$imported} dispositivos sin ninguna incidencia.");
        } catch (\Exception $e) {
            return back()->with('error', 'Error al procesar el archivo: ' . $e->getMessage());
        }
    }

    /**
     * Export inventory to Excel
     */
    public function exportExcel(Request $request)
    {
        ini_set('max_execution_time', 0);
        ini_set('memory_limit', '1024M');

        $devices = FinDevice::filter($request->all())
            ->with(['brand', 'branch', 'supplier', 'latestSale'])
            ->latest()
            ->get();

        $spreadsheet = new Spreadsheet();
        $sheet = $spreadsheet->getActiveSheet();
        $sheet->setTitle('Inventario Financieras');

        // Columnas en el orden solicitado:
        // FECHA DE LLEGADA | PROVEEDOR | UBICACIÓN | MARCA | MODELO | IMEI | COLOR | CAPACIDAD | ESTADO | VENTA VINCULADA | NOTAS
        $headers = [
            'FECHA DE LLEGADA',
            'PROVEEDOR',
            'UBICACIÓN',
            'MARCA',
            'MODELO',
            'IMEI',
            'COLOR',
            'CAPACIDAD',
            'ESTADO',
            'VENTA VINCULADA',
            'NOTAS',
        ];

        foreach ($headers as $colIdx => $header) {
            $sheet->setCellValueByColumnAndRow($colIdx + 1, 1, $header);
        }

        $rowNum = 2;
        foreach ($devices as $d) {
            $sheet->setCellValueByColumnAndRow(1, $rowNum, $d->created_at ? $d->created_at->format('d/m/Y H:i') : '');
            $sheet->setCellValueByColumnAndRow(2, $rowNum, $d->supplier?->name ?? 'N/A');
            $sheet->setCellValueByColumnAndRow(3, $rowNum, $d->branch?->name ?? 'N/A');
            $sheet->setCellValueByColumnAndRow(4, $rowNum, $d->brand?->name ?? 'N/A');
            $sheet->setCellValueByColumnAndRow(5, $rowNum, $d->model);
            // IMEI como DataType::TYPE_STRING para evitar notación científica en Excel
            $sheet->setCellValueExplicitByColumnAndRow(6, $rowNum, (string)$d->imei, DataType::TYPE_STRING);
            $sheet->setCellValueByColumnAndRow(7, $rowNum, $d->color ?? '');
            $sheet->setCellValueByColumnAndRow(8, $rowNum, $d->storage ?? '');
            $sheet->setCellValueByColumnAndRow(9, $rowNum, ucfirst(str_replace('_', ' ', $d->status)));
            $sheet->setCellValueByColumnAndRow(10, $rowNum, $d->latestSale ? $d->latestSale->sale_code : 'Sin Venta');
            $sheet->setCellValueByColumnAndRow(11, $rowNum, $d->notes ?? '');
            $rowNum++;
        }

        $sheet->getDefaultColumnDimension()->setWidth(18);

        $writer = new Xls($spreadsheet);
        $filename = 'Financieras_Inventario_' . date('Ymd_His') . '.xls';

        header('Content-Type: application/vnd.ms-excel');
        header("Content-Disposition: attachment;filename=\"{$filename}\"");
        header('Cache-Control: max-age=0');
        ob_end_clean();
        $writer->save('php://output');
        exit();
    }
}
