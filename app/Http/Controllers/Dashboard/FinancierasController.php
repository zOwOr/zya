<?php

namespace App\Http\Controllers\Dashboard;

use App\Http\Controllers\Controller;
use App\Http\Controllers\Traits\ModulePermissionTrait;
use App\Models\Branch;
use App\Models\FinBrand;
use App\Models\FinDevice;
use App\Models\FinFinanciera;
use App\Models\FinSale;
use App\Models\FinSupplier;
use App\Models\FinWarranty;
use App\Models\FinWarrantyStage;
use App\Models\FinTheftReport;
use App\Models\User;
use Illuminate\Http\Request;

class FinancierasController extends Controller
{
    use ModulePermissionTrait;

    protected ?string $permissionResource = 'financieras';

    protected array $permissionMapping = [
        'index' => 'menu',
        'autocompleteBrands' => 'menu',
        'autocompleteImeis' => 'menu',
        'lookupImei' => 'menu',
    ];

    public function __construct()
    {
        $this->initializeModulePermission();
    }

    public function index(Request $request)
    {
        $activeTab = $request->query('tab', 'ventas');
        $validTabs = ['ventas', 'inventario', 'garantias', 'robos'];
        if (!in_array($activeTab, $validTabs)) {
            $activeTab = 'ventas';
        }

        // Shared catalogs for filters & forms
        $branches = Branch::all();
        $brands = FinBrand::where('is_active', true)->orderBy('name')->get();
        $financieras = FinFinanciera::where('is_active', true)->orderBy('name')->get();
        $warrantyStages = FinWarrantyStage::orderBy('order')->get();
        $sellers = User::orderBy('name')->get();
        $suppliers = FinSupplier::where('is_active', true)->orderBy('name')->get();
        $models = FinDevice::distinct()->whereNotNull('model')->where('model', '!=', '')->orderBy('model')->pluck('model');

        // Counts for tabs summary
        $counts = [
            'ventas' => FinSale::where('status', 'activa')->count(),
            'inventario_disponible' => FinDevice::where('status', 'disponible')->count(),
            'garantias_activas' => FinWarranty::whereIn('status', ['abierta', 'en_proceso'])->count(),
            'robos_activos' => FinTheftReport::whereIn('status', ['reportado', 'en_investigacion'])->count(),
        ];

        // Cargar datos del tab activo con sus respectivos filtros aplicados
        $devices = null;
        $sales = null;
        $warranties = null;
        $thefts = null;

        if ($activeTab === 'inventario') {
            $deviceFilters = $request->only(['search', 'branch_id', 'status', 'brand_id', 'supplier_id', 'model']);
            $deviceQuery = FinDevice::filter($deviceFilters)
                ->with(['brand', 'branch', 'supplier', 'latestSale']);
            // Ocultar vendidos si no hay filtro de estado explícito
            if (empty($deviceFilters['status'])) {
                $deviceQuery->where('status', '!=', 'vendido');
            }
            $devices = $deviceQuery->latest()->paginate(20)->appends(array_merge($deviceFilters, ['tab' => 'inventario']));
        } elseif ($activeTab === 'ventas') {
            $saleFilters = $request->only(['search', 'branch_id', 'financiera_id', 'status', 'seller_id']);
            $sales = FinSale::filter($saleFilters)
                ->with(['device.brand', 'financiera', 'branch', 'seller'])
                ->latest('sale_date')
                ->paginate(15)->appends(array_merge($saleFilters, ['tab' => 'ventas']));
        } elseif ($activeTab === 'garantias') {
            $warrantyFilters = $request->only(['search', 'stage_id', 'branch_id', 'status']);
            $warranties = FinWarranty::filter($warrantyFilters)
                ->with(['device.brand', 'sale', 'branch', 'currentStage'])
                ->latest('opened_at')
                ->paginate(15)->appends(array_merge($warrantyFilters, ['tab' => 'garantias']));
        } elseif ($activeTab === 'robos') {
            $theftFilters = $request->only(['search', 'branch_id', 'status']);
            $thefts = FinTheftReport::filter($theftFilters)
                ->with(['device.brand', 'sale', 'branch'])
                ->latest('reported_at')
                ->paginate(15)->appends(array_merge($theftFilters, ['tab' => 'robos']));
        }

        return view('financieras.index', compact(
            'activeTab',
            'branches',
            'brands',
            'financieras',
            'warrantyStages',
            'sellers',
            'suppliers',
            'models',
            'counts',
            'devices',
            'sales',
            'warranties',
            'thefts'
        ));
    }

    /**
     * Autocomplete brands for inputs across all sections
     */
    public function autocompleteBrands(Request $request)
    {
        $q = $request->query('q', '');
        $brands = FinBrand::where('is_active', true)
            ->when($q, fn($query) => $query->where('name', 'like', "%{$q}%"))
            ->orderBy('name')
            ->limit(20)
            ->get(['id', 'name']);

        return response()->json($brands);
    }

    /**
     * Autocomplete IMEIs across all sections
     */
    public function autocompleteImeis(Request $request)
    {
        $q = $request->query('q', '');
        $status = $request->query('status');

        $devices = FinDevice::with(['brand', 'branch'])
            ->when($q, fn($query) => $query->where('imei', 'like', "%{$q}%"))
            ->when($status, fn($query) => $query->where('status', $status))
            ->limit(20)
            ->get();

        return response()->json($devices);
    }

    /**
     * Cross-section lookup by IMEI: returns device, active sale, warranty history, theft history
     */
    public function lookupImei(Request $request)
    {
        $imei = trim($request->query('imei', ''));
        if (!$imei) {
            return response()->json(['found' => false, 'message' => 'IMEI no proporcionado'], 400);
        }

        $device = FinDevice::with([
            'brand',
            'branch',
            'latestSale.financiera',
            'latestSale.seller',
            'warranties.currentStage',
            'theftReports'
        ])->where('imei', $imei)->first();

        if (!$device) {
            return response()->json([
                'found' => false,
                'imei' => $imei,
                'message' => 'Dispositivo no encontrado en inventario.'
            ]);
        }

        return response()->json([
            'found' => true,
            'device' => $device,
            'active_sale' => $device->activeSale,
            'latest_sale' => $device->latestSale,
            'has_warranty' => $device->warranties->whereIn('status', ['abierta', 'en_proceso'])->isNotEmpty(),
            'has_theft_report' => $device->theftReports->whereIn('status', ['reportado', 'en_investigacion'])->isNotEmpty(),
        ]);
    }
}
