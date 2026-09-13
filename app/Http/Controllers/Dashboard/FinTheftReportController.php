<?php

namespace App\Http\Controllers\Dashboard;

use App\Events\FinancierasUpdated;
use App\Http\Controllers\Controller;
use App\Http\Controllers\Traits\ModulePermissionTrait;
use App\Models\Branch;
use App\Models\FinBrand;
use App\Models\FinDevice;
use App\Models\FinTheftReport;
use App\Models\FinTheftReportLog;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use PhpOffice\PhpSpreadsheet\Spreadsheet;
use PhpOffice\PhpSpreadsheet\Writer\Xls;

class FinTheftReportController extends Controller
{
    use ModulePermissionTrait;

    protected ?string $permissionResource = 'financieras.robos';

    protected array $permissionMapping = [
        'index' => 'read',
        'show' => 'read',
        'create' => 'create',
        'store' => 'create',
        'edit' => 'edit',
        'update' => 'edit',
        'destroy' => 'delete',
        'updateStatus' => 'edit',
        'exportExcel' => 'read',
    ];

    public function __construct()
    {
        $this->initializeModulePermission();
    }

    public function index(Request $request)
    {
        $filters = $request->only(['search', 'status', 'branch_id']);

        $theftReports = FinTheftReport::filter($filters)
            ->with(['device.brand', 'sale', 'branch', 'reporter'])
            ->latest('incident_date')
            ->paginate(15)
            ->withQueryString();

        if ($request->ajax()) {
            return view('financieras.robos.table', compact('theftReports'))->render();
        }

        $branches = Branch::all();

        return view('financieras.robos.index', compact('theftReports', 'branches'));
    }

    public function create(Request $request)
    {
        $prefilledImei = $request->query('imei', '');
        $prefilledDevice = null;
        if ($prefilledImei) {
            $prefilledDevice = FinDevice::with(['brand', 'branch', 'latestSale'])->where('imei', $prefilledImei)->first();
        }

        $branches = Branch::all();
        $brands = FinBrand::where('is_active', true)->get();

        return view('financieras.robos.create', compact('branches', 'brands', 'prefilledImei', 'prefilledDevice'));
    }

    public function store(Request $request)
    {
        $request->validate([
            'imei' => 'required|string|max:50',
            'branch_id' => 'required|exists:branches,id',
            'incident_date' => 'required|date',
            'police_report_number' => 'nullable|string|max:100',
            'description' => 'required|string|max:2000',
            'model' => 'nullable|string|max:150',
            'brand_name' => 'nullable|string|max:100',
            'brand_id' => 'nullable|exists:fin_brands,id',
            'color' => 'nullable|string|max:50',
        ], [
            'imei.required' => 'El número de IMEI es obligatorio.',
            'branch_id.required' => 'La sucursal es obligatoria.',
            'incident_date.required' => 'La fecha del incidente es obligatoria.',
            'description.required' => 'La narrativa o descripción de los hechos es obligatoria.',
        ]);

        $imei = trim($request->input('imei'));
        $device = FinDevice::where('imei', $imei)->first();

        if (!$device) {
            $brandId = $request->input('brand_id');
            if (!$brandId && $request->filled('brand_name')) {
                $brand = FinBrand::firstOrCreate(['name' => trim($request->input('brand_name'))]);
                $brandId = $brand->id;
            }

            $model = trim($request->input('model') ?? '');
            if (empty($model)) {
                $model = 'Modelo no especificado';
            }

            $device = FinDevice::create([
                'imei' => $imei,
                'brand_id' => $brandId,
                'model' => $model,
                'color' => $request->input('color') ?: 'Sin color',
                'branch_id' => $request->input('branch_id'),
                'status' => 'disponible',
            ]);
        }

        $sale = $device->activeSale ?? $device->latestSale;

        $report = DB::transaction(function () use ($request, $device, $sale) {
            $reportCode = 'ROB-' . date('Ymd') . '-' . strtoupper(substr(uniqid(), -4));

            $report = FinTheftReport::create([
                'report_code' => $reportCode,
                'device_id' => $device->id,
                'sale_id' => $sale?->id,
                'branch_id' => $request->input('branch_id'),
                'reported_by' => auth()->id(),
                'incident_date' => $request->input('incident_date'),
                'police_report_number' => $request->input('police_report_number'),
                'description' => $request->input('description'),
                'status' => 'reportado',
            ]);

            // Mark device as robado
            $device->update(['status' => 'robado']);

            // Initial log
            FinTheftReportLog::create([
                'theft_report_id' => $report->id,
                'user_id' => auth()->id(),
                'status_change' => 'Reportado',
                'notes' => 'Apertura de reporte de robo. Acta / Folio: ' . ($report->police_report_number ?: 'Sin acta'),
            ]);

            return $report;
        });

        try {
            event(new FinancierasUpdated(
                'robos',
                'created',
                "Reporte de robo {$report->report_code} para IMEI {$device->imei}",
                ['report_id' => $report->id, 'imei' => $device->imei]
            ));
        } catch (\Throwable $e) {
            \Log::warning('Reverb broadcast warning: ' . $e->getMessage());
        }

        return redirect()->route('financieras.robos.show', $report->id)
            ->with('success', "Reporte de robo {$report->report_code} creado exitosamente.");
    }

    public function show(FinTheftReport $theftReport)
    {
        $theftReport->load([
            'device.brand',
            'device.branch',
            'sale.financiera',
            'sale.seller',
            'branch',
            'reporter',
            'logs.user'
        ]);

        return view('financieras.robos.show', compact('theftReport'));
    }

    public function edit(FinTheftReport $theftReport)
    {
        $branches = Branch::all();
        return view('financieras.robos.edit', compact('theftReport', 'branches'));
    }

    public function update(Request $request, FinTheftReport $theftReport)
    {
        $request->validate([
            'branch_id' => 'required|exists:branches,id',
            'incident_date' => 'required|date',
            'police_report_number' => 'nullable|string|max:100',
            'description' => 'required|string|max:2000',
        ], [
            'branch_id.required' => 'La sucursal es obligatoria.',
            'branch_id.exists' => 'La sucursal seleccionada no es válida.',
            'incident_date.required' => 'La fecha del incidente es obligatoria.',
            'description.required' => 'La descripción del incidente es obligatoria.',
        ]);

        $theftReport->update($request->only([
            'branch_id',
            'incident_date',
            'police_report_number',
            'description',
        ]));

        return redirect()->route('financieras.robos.show', $theftReport->id)
            ->with('success', 'Reporte de robo actualizado.');
    }

    /**
     * Update status and movement notes
     */
    public function updateStatus(Request $request, FinTheftReport $theftReport)
    {
        $request->validate([
            'status' => 'required|in:reportado,en_investigacion,recuperado,cerrado',
            'notes' => 'required|string|max:1000',
            'return_device_status' => 'nullable|in:disponible,vendido',
        ], [
            'status.required' => 'El estado es obligatorio.',
            'notes.required' => 'Debe ingresar una nota de seguimiento o justificación del estado.',
        ]);

        $newStatus = $request->input('status');
        $oldStatus = $theftReport->status;

        DB::transaction(function () use ($request, $theftReport, $newStatus, $oldStatus) {
            $theftReport->update(['status' => $newStatus]);

            FinTheftReportLog::create([
                'theft_report_id' => $theftReport->id,
                'user_id' => auth()->id(),
                'status_change' => ucfirst(str_replace('_', ' ', $newStatus)),
                'notes' => $request->input('notes'),
            ]);

            // If recuperado, return device status
            if ($newStatus === 'recuperado') {
                $targetStatus = $request->input('return_device_status', $theftReport->sale_id ? 'vendido' : 'disponible');
                $theftReport->device->update(['status' => $targetStatus]);
            }
        });

        try {
            event(new FinancierasUpdated(
                'robos',
                'updated',
                "Reporte {$theftReport->report_code} actualizado a estado: {$newStatus}",
                ['report_id' => $theftReport->id, 'status' => $newStatus]
            ));
        } catch (\Throwable $e) {
            \Log::warning('Reverb broadcast warning: ' . $e->getMessage());
        }

        return back()->with('success', "Estado actualizado a '{$newStatus}'.");
    }

    public function destroy(FinTheftReport $theftReport)
    {
        if ($theftReport->device && $theftReport->device->status === 'robado') {
            $theftReport->device->update(['status' => $theftReport->sale_id ? 'vendido' : 'disponible']);
        }

        $theftReport->delete();

        return redirect()->route('financieras.index', ['tab' => 'robos'])
            ->with('success', 'Reporte de robo eliminado.');
    }

    /**
     * Export theft reports to Excel
     */
    public function exportExcel(Request $request)
    {
        ini_set('max_execution_time', 0);
        ini_set('memory_limit', '1024M');

        $reports = FinTheftReport::filter($request->all())
            ->with(['device.brand', 'sale', 'branch', 'reporter'])
            ->latest('incident_date')
            ->get();

        $data = [
            [
                'Código Reporte',
                'IMEI',
                'Marca',
                'Modelo',
                'Sucursal',
                'Fecha Incidente',
                'No. Acta / Denuncia',
                'Estado',
                'Cliente Afectado',
                'Reportado Por',
                'Descripción',
            ]
        ];

        foreach ($reports as $r) {
            $data[] = [
                $r->report_code,
                $r->device?->imei ?? 'N/A',
                $r->device?->brand?->name ?? 'N/A',
                $r->device?->model ?? 'N/A',
                $r->branch?->name ?? 'N/A',
                $r->incident_date ? $r->incident_date->format('Y-m-d H:i') : '',
                $r->police_report_number ?? 'Sin acta',
                ucfirst(str_replace('_', ' ', $r->status)),
                $r->sale?->customer_name ?? 'Sin venta vinculada',
                $r->reporter?->name ?? 'N/A',
                $r->description,
            ];
        }

        $spreadsheet = new Spreadsheet();
        $sheet = $spreadsheet->getActiveSheet();
        $sheet->getDefaultColumnDimension()->setWidth(18);
        $sheet->fromArray($data);

        $writer = new Xls($spreadsheet);
        $filename = 'Financieras_Robos_' . date('Ymd_His') . '.xls';

        header('Content-Type: application/vnd.ms-excel');
        header("Content-Disposition: attachment;filename=\"{$filename}\"");
        header('Cache-Control: max-age=0');
        ob_end_clean();
        $writer->save('php://output');
        exit();
    }
}
