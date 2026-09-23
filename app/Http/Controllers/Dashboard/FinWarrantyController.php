<?php

namespace App\Http\Controllers\Dashboard;

use App\Events\FinancierasUpdated;
use App\Http\Controllers\Controller;
use App\Http\Controllers\Traits\ModulePermissionTrait;
use App\Models\Branch;
use App\Models\FinBrand;
use App\Models\FinDevice;
use App\Models\FinWarranty;
use App\Models\FinWarrantyLog;
use App\Models\FinWarrantyStage;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use PhpOffice\PhpSpreadsheet\Spreadsheet;
use PhpOffice\PhpSpreadsheet\Writer\Xls;

class FinWarrantyController extends Controller
{
    use ModulePermissionTrait;

    protected ?string $permissionResource = 'financieras.garantias';

    protected array $permissionMapping = [
        'index' => 'read',
        'show' => 'read',
        'create' => 'create',
        'store' => 'create',
        'edit' => 'edit',
        'update' => 'edit',
        'destroy' => 'delete',
        'changeStage' => 'edit',
        'exportExcel' => 'export',
    ];

    public function __construct()
    {
        $this->initializeModulePermission();
    }

    public function index(Request $request)
    {
        $filters = $request->only(['search', 'stage_id', 'branch_id', 'status']);

        $warranties = FinWarranty::filter($filters)
            ->with(['device.brand', 'sale', 'branch', 'currentStage', 'creator'])
            ->latest('opened_at')
            ->paginate(15)
            ->withQueryString();

        if ($request->ajax()) {
            return view('financieras.garantias.table', compact('warranties'))->render();
        }

        $branches = Branch::all();
        $stages = FinWarrantyStage::orderBy('order')->get();

        return view('financieras.garantias.index', compact('warranties', 'branches', 'stages'));
    }

    public function create(Request $request)
    {
        $prefilledImei = $request->query('imei', '');
        $prefilledDevice = null;
        if ($prefilledImei) {
            $prefilledDevice = FinDevice::with(['brand', 'branch', 'latestSale'])->where('imei', $prefilledImei)->first();
        }

        $branches = Branch::all();
        $stages = FinWarrantyStage::orderBy('order')->get();
        $brands = FinBrand::where('is_active', true)->get();

        return view('financieras.garantias.create', compact('branches', 'stages', 'brands', 'prefilledImei', 'prefilledDevice'));
    }

    public function store(Request $request)
    {
        $request->validate([
            'imei' => 'required|string|max:50',
            'branch_id' => 'required|exists:branches,id',
            'current_stage_id' => 'required|exists:fin_warranty_stages,id',
            'issue_description' => 'required|string|max:2000',
            'model' => 'nullable|string|max:150',
            'brand_name' => 'nullable|string|max:100',
            'brand_id' => 'nullable|exists:fin_brands,id',
            'color' => 'nullable|string|max:50',
        ], [
            'imei.required' => 'El número de IMEI es obligatorio.',
            'branch_id.required' => 'La sucursal es obligatoria.',
            'current_stage_id.required' => 'Debe seleccionar una etapa inicial para la garantía.',
            'issue_description.required' => 'La descripción de la falla es obligatoria.',
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

        // Link active sale if exists
        $sale = $device->activeSale ?? $device->latestSale;

        $warranty = DB::transaction(function () use ($request, $device, $sale) {
            $warrantyCode = 'WAR-' . date('Ymd') . '-' . strtoupper(substr(uniqid(), -4));

            $warranty = FinWarranty::create([
                'warranty_code' => $warrantyCode,
                'device_id' => $device->id,
                'sale_id' => $sale?->id,
                'branch_id' => $request->input('branch_id'),
                'current_stage_id' => $request->input('current_stage_id'),
                'created_by' => auth()->id(),
                'issue_description' => $request->input('issue_description'),
                'status' => 'abierta',
                'opened_at' => now(),
            ]);

            // Initial log
            FinWarrantyLog::create([
                'warranty_id' => $warranty->id,
                'from_stage_id' => null,
                'to_stage_id' => $warranty->current_stage_id,
                'user_id' => auth()->id(),
                'notes' => 'Apertura de garantía: ' . $warranty->issue_description,
            ]);

            // Mark device as en_garantia
            $device->update(['status' => 'en_garantia']);

            return $warranty;
        });

        try {
            event(new FinancierasUpdated(
                'garantias',
                'created',
                "Garantía {$warranty->warranty_code} creada para IMEI {$device->imei}",
                ['warranty_id' => $warranty->id, 'imei' => $device->imei]
            ));
        } catch (\Throwable $e) {
            \Log::warning('Reverb broadcast warning: ' . $e->getMessage());
        }

        return redirect()->route('financieras.garantias.show', $warranty->id)
            ->with('success', "Garantía {$warranty->warranty_code} registrada con éxito.");
    }

    public function show(FinWarranty $warranty)
    {
        $warranty->load([
            'device.brand',
            'device.branch',
            'sale.financiera',
            'sale.seller',
            'branch',
            'currentStage',
            'creator',
            'logs.fromStage',
            'logs.toStage',
            'logs.user'
        ]);

        $stages = FinWarrantyStage::orderBy('order')->get();

        return view('financieras.garantias.show', compact('warranty', 'stages'));
    }

    public function edit(FinWarranty $warranty)
    {
        $branches = Branch::all();
        $stages = FinWarrantyStage::orderBy('order')->get();

        return view('financieras.garantias.edit', compact('warranty', 'branches', 'stages'));
    }

    public function update(Request $request, FinWarranty $warranty)
    {
        $request->validate([
            'branch_id' => 'required|exists:branches,id',
            'issue_description' => 'required|string|max:2000',
            'resolution_notes' => 'nullable|string|max:2000',
        ], [
            'branch_id.required' => 'La sucursal es obligatoria.',
            'branch_id.exists' => 'La sucursal seleccionada no es válida.',
            'issue_description.required' => 'La descripción de la falla es obligatoria.',
        ]);

        $warranty->update($request->only(['branch_id', 'issue_description', 'resolution_notes']));

        return redirect()->route('financieras.garantias.show', $warranty->id)
            ->with('success', 'Garantía actualizada.');
    }

    /**
     * Advance / Change warranty stage
     */
    public function changeStage(Request $request, FinWarranty $warranty)
    {
        $request->validate([
            'to_stage_id' => 'required|exists:fin_warranty_stages,id',
            'notes' => 'nullable|string|max:1000',
            'status' => 'nullable|in:abierta,en_proceso,resuelta,rechazada',
            'return_device_status' => 'nullable|in:disponible,vendido',
        ], [
            'to_stage_id.required' => 'Debe seleccionar la nueva etapa.',
            'to_stage_id.exists' => 'La etapa seleccionada no es válida.',
        ]);

        $fromStageId = $warranty->current_stage_id;
        $toStageId = $request->input('to_stage_id');
        $targetStage = FinWarrantyStage::findOrFail($toStageId);

        DB::transaction(function () use ($request, $warranty, $fromStageId, $toStageId, $targetStage) {
            $newStatus = $request->input('status');
            if (!$newStatus) {
                $newStatus = $targetStage->is_final ? 'resuelta' : 'en_proceso';
            }

            $closedAt = in_array($newStatus, ['resuelta', 'rechazada']) ? now() : null;

            $warranty->update([
                'current_stage_id' => $toStageId,
                'status' => $newStatus,
                'closed_at' => $closedAt,
                'resolution_notes' => $request->filled('notes') ? $request->input('notes') : $warranty->resolution_notes,
            ]);

            FinWarrantyLog::create([
                'warranty_id' => $warranty->id,
                'from_stage_id' => $fromStageId,
                'to_stage_id' => $toStageId,
                'user_id' => auth()->id(),
                'notes' => $request->input('notes', 'Cambio de etapa a: ' . $targetStage->name),
            ]);

            // If closed, return device status
            if ($closedAt) {
                $targetDeviceStatus = $request->input('return_device_status', $warranty->sale_id ? 'vendido' : 'disponible');
                $warranty->device->update(['status' => $targetDeviceStatus]);
            }
        });

        try {
            event(new FinancierasUpdated(
                'garantias',
                'updated',
                "Garantía {$warranty->warranty_code} avanzada a etapa: {$targetStage->name}",
                ['warranty_id' => $warranty->id, 'stage' => $targetStage->name]
            ));
        } catch (\Throwable $e) {
            \Log::warning('Reverb broadcast warning: ' . $e->getMessage());
        }

        return back()->with('success', "Etapa actualizada a '{$targetStage->name}'.");
    }

    public function destroy(FinWarranty $warranty)
    {
        if ($warranty->device && $warranty->device->status === 'en_garantia') {
            $warranty->device->update(['status' => $warranty->sale_id ? 'vendido' : 'disponible']);
        }

        $warranty->delete();

        return redirect()->route('financieras.index', ['tab' => 'garantias'])
            ->with('success', 'Registro de garantía eliminado.');
    }

    /**
     * Export warranties to Excel
     */
    public function exportExcel(Request $request)
    {
        ini_set('max_execution_time', 0);
        ini_set('memory_limit', '1024M');

        $warranties = FinWarranty::filter($request->all())
            ->with(['device.brand', 'sale', 'branch', 'currentStage', 'creator'])
            ->latest('opened_at')
            ->get();

        $data = [
            [
                'Código',
                'IMEI',
                'Marca',
                'Modelo',
                'Sucursal',
                'Etapa Actual',
                'Estado',
                'Cliente',
                'Problema Reportado',
                'Fecha Apertura',
                'Fecha Cierre',
                'Registrado Por',
            ]
        ];

        foreach ($warranties as $w) {
            $data[] = [
                $w->warranty_code,
                $w->device?->imei ?? 'N/A',
                $w->device?->brand?->name ?? 'N/A',
                $w->device?->model ?? 'N/A',
                $w->branch?->name ?? 'N/A',
                $w->currentStage?->name ?? 'N/A',
                ucfirst(str_replace('_', ' ', $w->status)),
                $w->sale?->customer_name ?? 'N/A',
                $w->issue_description,
                $w->opened_at ? $w->opened_at->format('Y-m-d H:i') : '',
                $w->closed_at ? $w->closed_at->format('Y-m-d H:i') : '',
                $w->creator?->name ?? 'N/A',
            ];
        }

        $spreadsheet = new Spreadsheet();
        $sheet = $spreadsheet->getActiveSheet();
        $sheet->getDefaultColumnDimension()->setWidth(18);
        $sheet->fromArray($data);

        $writer = new Xls($spreadsheet);
        $filename = 'Financieras_Garantias_' . date('Ymd_His') . '.xls';

        header('Content-Type: application/vnd.ms-excel');
        header("Content-Disposition: attachment;filename=\"{$filename}\"");
        header('Cache-Control: max-age=0');
        ob_end_clean();
        $writer->save('php://output');
        exit();
    }
}
