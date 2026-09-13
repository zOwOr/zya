<?php

namespace App\Http\Controllers\Dashboard;

use App\Events\FinancierasUpdated;
use App\Http\Controllers\Controller;
use App\Http\Controllers\Traits\ModulePermissionTrait;
use App\Models\Branch;
use App\Models\FinBrand;
use App\Models\FinDevice;
use App\Models\FinDeviceTransfer;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
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
        $filters = $request->only(['search', 'branch_id', 'status', 'brand_id']);

        $devices = FinDevice::filter($filters)
            ->with(['brand', 'branch', 'latestSale'])
            ->latest()
            ->paginate(20)
            ->withQueryString();

        if ($request->ajax()) {
            return view('financieras.inventario.table', compact('devices'))->render();
        }

        $branches = Branch::all();
        $brands = FinBrand::where('is_active', true)->get();

        return view('financieras.inventario.index', compact('devices', 'branches', 'brands'));
    }

    public function create()
    {
        $branches = Branch::all();
        $brands = FinBrand::where('is_active', true)->get();

        return view('financieras.inventario.create', compact('branches', 'brands'));
    }

    public function store(Request $request)
    {
        $request->validate([
            'imei' => 'required|string|max:50|unique:fin_devices,imei',
            'brand_id' => 'nullable|exists:fin_brands,id',
            'brand_name' => 'nullable|string|max:100',
            'model' => 'required|string|max:150',
            'color' => 'nullable|string|max:50',
            'branch_id' => 'required|exists:branches,id',
            'notes' => 'nullable|string|max:1000',
        ], [
            'imei.required' => 'El número de IMEI es obligatorio.',
            'imei.unique' => 'Este número de IMEI ya se encuentra registrado en el inventario.',
            'model.required' => 'El modelo del dispositivo es obligatorio.',
            'branch_id.required' => 'Debe seleccionar la sucursal de entrada.',
            'branch_id.exists' => 'La sucursal seleccionada no es válida.',
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
            'branch_id' => $request->input('branch_id'),
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

        return view('financieras.inventario.edit', compact('device', 'branches', 'brands'));
    }

    public function update(Request $request, FinDevice $device)
    {
        $request->validate([
            'imei' => 'required|string|max:50|unique:fin_devices,imei,' . $device->id,
            'brand_id' => 'nullable|exists:fin_brands,id',
            'brand_name' => 'nullable|string|max:100',
            'model' => 'required|string|max:150',
            'color' => 'nullable|string|max:50',
            'branch_id' => 'required|exists:branches,id',
            'status' => 'required|in:disponible,vendido,en_garantia,robado',
            'notes' => 'nullable|string|max:1000',
        ], [
            'imei.required' => 'El número de IMEI es obligatorio.',
            'imei.unique' => 'Este número de IMEI ya se encuentra registrado.',
            'model.required' => 'El modelo del dispositivo es obligatorio.',
            'branch_id.required' => 'Debe seleccionar la sucursal.',
            'branch_id.exists' => 'La sucursal seleccionada no es válida.',
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
            'branch_id' => $request->input('branch_id'),
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

        $device->delete();

        try {
            event(new FinancierasUpdated(
                'inventario',
                'deleted',
                "Dispositivo {$device->imei} eliminado de inventario",
                ['device_id' => $device->id]
            ));
        } catch (\Throwable $e) {
            \Log::warning('Reverb broadcast warning: ' . $e->getMessage());
        }

        return redirect()->route('financieras.index', ['tab' => 'inventario'])
            ->with('success', 'Dispositivo eliminado del inventario.');
    }

    /**
     * Bulk import devices from Excel
     */
    public function importExcel(Request $request)
    {
        $request->validate([
            'import_file' => 'required|file|mimes:xlsx,xls,csv|max:10240',
            'default_branch_id' => 'required|exists:branches,id',
        ], [
            'import_file.required' => 'Debe seleccionar un archivo Excel o CSV.',
            'import_file.mimes' => 'El formato del archivo debe ser .xlsx, .xls o .csv.',
            'import_file.max' => 'El tamaño máximo permitido es de 10 MB.',
            'default_branch_id.required' => 'Debe indicar la sucursal asignada.',
            'default_branch_id.exists' => 'La sucursal seleccionada no es válida.',
        ]);

        try {
            $file = $request->file('import_file');
            $spreadsheet = IOFactory::load($file->getRealPath());
            $worksheet = $spreadsheet->getActiveSheet();
            $rows = $worksheet->toArray();

            if (count($rows) <= 1) {
                return back()->with('error', 'El archivo no contiene registros o está vacío.');
            }

            // Headers row 0: IMEI, Marca, Modelo, Color, Notas
            $imported = 0;
            $duplicates = 0;
            $defaultBranchId = $request->input('default_branch_id');

            for ($i = 1; $i < count($rows); $i++) {
                $row = $rows[$i];
                $imei = isset($row[0]) ? trim((string)$row[0]) : '';
                if (empty($imei)) {
                    continue;
                }

                if (FinDevice::where('imei', $imei)->exists()) {
                    $duplicates++;
                    continue;
                }

                $brandName = isset($row[1]) ? trim((string)$row[1]) : '';
                $model = isset($row[2]) ? trim((string)$row[2]) : 'Modelo no especificado';
                $color = isset($row[3]) ? trim((string)$row[3]) : null;
                $notes = isset($row[4]) ? trim((string)$row[4]) : null;

                $brandId = null;
                if (!empty($brandName)) {
                    $brand = FinBrand::firstOrCreate(['name' => $brandName]);
                    $brandId = $brand->id;
                }

                FinDevice::create([
                    'imei' => $imei,
                    'brand_id' => $brandId,
                    'model' => $model,
                    'color' => $color,
                    'branch_id' => $defaultBranchId,
                    'status' => 'disponible',
                    'notes' => $notes,
                ]);

                $imported++;
            }

            $msg = "Se importaron {$imported} dispositivos correctamente.";
            if ($duplicates > 0) {
                $msg .= " ({$duplicates} omitidos por IMEI duplicado).";
            }

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

            return back()->with('success', $msg);
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
            ->with(['brand', 'branch', 'latestSale'])
            ->latest()
            ->get();

        $data = [
            [
                'IMEI',
                'Marca',
                'Modelo',
                'Color',
                'Sucursal',
                'Estado',
                'Venta Vinculada',
                'Notas',
                'Fecha Registro',
            ]
        ];

        foreach ($devices as $d) {
            $data[] = [
                $d->imei,
                $d->brand?->name ?? 'N/A',
                $d->model,
                $d->color ?? '',
                $d->branch?->name ?? 'N/A',
                ucfirst(str_replace('_', ' ', $d->status)),
                $d->latestSale ? $d->latestSale->sale_code : 'Sin Venta',
                $d->notes ?? '',
                $d->created_at->format('Y-m-d H:i'),
            ];
        }

        $spreadsheet = new Spreadsheet();
        $sheet = $spreadsheet->getActiveSheet();
        $sheet->getDefaultColumnDimension()->setWidth(18);
        $sheet->fromArray($data);

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
