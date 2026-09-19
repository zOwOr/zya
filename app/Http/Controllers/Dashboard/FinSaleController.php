<?php

namespace App\Http\Controllers\Dashboard;

use App\Events\FinancierasUpdated;
use App\Http\Controllers\Controller;
use App\Http\Controllers\Traits\ModulePermissionTrait;
use App\Models\Branch;
use App\Models\FinBrand;
use App\Models\FinDevice;
use App\Models\FinFinanciera;
use App\Models\FinSale;
use App\Models\FinSaleNote;
use App\Models\User;
use Barryvdh\DomPDF\Facade\Pdf;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use PhpOffice\PhpSpreadsheet\Spreadsheet;
use PhpOffice\PhpSpreadsheet\Writer\Xls;

class FinSaleController extends Controller
{
    use ModulePermissionTrait;

    protected ?string $permissionResource = 'financieras.ventas';

    protected array $permissionMapping = [
        'index' => 'read',
        'show' => 'read',
        'create' => 'create',
        'store' => 'create',
        'edit' => 'edit',
        'update' => 'edit',
        'destroy' => 'delete',
        'cancel' => 'delete',
        'addNote' => 'create',
        'exportExcel' => 'read',
        'exportPdf' => 'read',
    ];

    public function __construct()
    {
        $this->initializeModulePermission();
    }

    public function index(Request $request)
    {
        $filters = $request->only([
            'search',
            'financiera_id',
            'branch_id',
            'seller_id',
            'status',
            'start_date',
            'end_date'
        ]);

        $sales = FinSale::filter($filters)
            ->with(['device.brand', 'financiera', 'branch', 'seller'])
            ->latest('sale_date')
            ->paginate(15)
            ->withQueryString();

        if ($request->ajax()) {
            return view('financieras.ventas.table', compact('sales'))->render();
        }

        $branches = Branch::all();
        $financieras = FinFinanciera::where('is_active', true)->get();
        $sellers = User::orderBy('name')->get();

        return view('financieras.ventas.index', compact('sales', 'branches', 'financieras', 'sellers'));
    }

    public function create()
    {
        $branches = Branch::all();
        $brands = FinBrand::where('is_active', true)->orderBy('name')->get();
        $financieras = FinFinanciera::where('is_active', true)->orderBy('name')->get();
        $sellers = User::orderBy('name')->get();
        $availableDevices = FinDevice::where('status', 'disponible')->with('brand', 'branch')->get();

        return view('financieras.ventas.create', compact('branches', 'brands', 'financieras', 'sellers', 'availableDevices'));
    }

    public function store(Request $request)
    {
        $request->validate([
            'device_id' => 'nullable|exists:fin_devices,id',
            'imei' => 'required_without:device_id|nullable|string|max:50',
            'model' => 'required_without:device_id|nullable|string|max:150',
            'brand_id' => 'nullable|exists:fin_brands,id',
            'brand_name' => 'nullable|string|max:100',
            'color' => 'nullable|string|max:50',
            'financiera_id' => 'nullable|exists:fin_financieras,id',
            'branch_id' => 'required|exists:branches,id',
            'seller_id' => 'required|exists:users,id',
            'price' => 'required|numeric|min:0',
            'down_payment' => 'nullable|numeric|min:0|lte:price',
            'enganche_descuento' => 'nullable|numeric|min:0',
            'credit_amount' => 'nullable|numeric|min:0',
            'abono_semanal' => 'nullable|numeric|min:0',
            'term_months' => 'nullable|integer|min:1|max:120',
            'term_weeks' => 'nullable|integer|min:1|max:520',
            'tag_contrato' => 'nullable|string|max:100',
            'sale_date' => 'nullable|date',
            'customer_name' => 'nullable|string|max:150',
            'customer_phone' => 'nullable|string|max:30',
            'customer_email' => 'nullable|email|max:150',
            'customer_ine' => 'nullable|string|max:50',
            'customer_address' => 'nullable|string|max:500',
            'customer_chip' => 'nullable|string|max:100',
            'customer_facebook' => 'nullable|string|max:150',
            'ref1_name' => 'nullable|string|max:150',
            'ref1_phone' => 'nullable|string|max:30',
            'ref2_name' => 'nullable|string|max:150',
            'ref2_phone' => 'nullable|string|max:30',
            'ref3_name' => 'nullable|string|max:150',
            'ref3_phone' => 'nullable|string|max:30',
            'initial_note' => 'nullable|string|max:2000',
        ], [
            'imei.required_without' => 'El IMEI es obligatorio.',
            'model.required_without' => 'El modelo del equipo es obligatorio.',
            'branch_id.required' => 'La sucursal es obligatoria.',
            'branch_id.exists' => 'La sucursal seleccionada no es válida.',
            'seller_id.required' => 'Debe seleccionar un vendedor.',
            'seller_id.exists' => 'El vendedor seleccionado no es válido.',
            'price.required' => 'El precio total es obligatorio.',
            'price.numeric' => 'El precio debe ser un número válido.',
            'price.min' => 'El precio no puede ser menor a 0.',
            'down_payment.numeric' => 'El enganche debe ser un número válido.',
            'down_payment.min' => 'El enganche no puede ser menor a 0.',
            'down_payment.lte' => 'El enganche no puede ser mayor que el precio total.',
            'credit_amount.numeric' => 'El monto a crédito debe ser numérico.',
            'term_months.integer' => 'El plazo en meses debe ser un número entero.',
            'term_months.min' => 'El plazo debe ser de al menos 1 mes.',
            'customer_email.email' => 'El correo del cliente no tiene un formato válido.',
        ]);

        $deviceId = $request->input('device_id');

        // If no device_id provided but IMEI entered, find or create device in inventory
        if (!$deviceId && $request->filled('imei')) {
            $imei = trim($request->input('imei'));
            $device = FinDevice::where('imei', $imei)->first();

            if (!$device) {
                // Register brand if brand_name provided
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
            $deviceId = $device->id;
        }

        if (!$deviceId) {
            return back()->withInput()->with('error', 'Debe seleccionar un dispositivo o ingresar un IMEI.');
        }

        $device = FinDevice::findOrFail($deviceId);
        if ($device->status === 'vendido') {
            return back()->withInput()->with('error', 'El dispositivo con IMEI ' . $device->imei . ' ya ha sido vendido.');
        }

        $sale = DB::transaction(function () use ($request, $device) {
            $saleCode = 'FIN-' . date('Ymd') . '-' . strtoupper(substr(uniqid(), -4));

            $price = (float) $request->input('price', 0);
            $downPayment = (float) $request->input('down_payment', 0);
            $creditAmount = $request->filled('credit_amount')
                ? (float) $request->input('credit_amount')
                : max(0, $price - $downPayment);

            $sale = FinSale::create([
                'sale_code' => $saleCode,
                'device_id' => $device->id,
                'financiera_id' => $request->input('financiera_id'),
                'branch_id' => $request->input('branch_id', $device->branch_id),
                'seller_id' => $request->input('seller_id', auth()->id()),
                // Datos del cliente
                'customer_name' => $request->input('customer_name'),
                'customer_phone' => $request->input('customer_phone'),
                'customer_email' => $request->input('customer_email'),
                'customer_ine' => $request->input('customer_ine'),
                'customer_address' => $request->input('customer_address'),
                'customer_chip' => $request->input('customer_chip'),
                'customer_facebook' => $request->input('customer_facebook'),
                // Referencias
                'ref1_name' => $request->input('ref1_name'),
                'ref1_phone' => $request->input('ref1_phone'),
                'ref2_name' => $request->input('ref2_name'),
                'ref2_phone' => $request->input('ref2_phone'),
                'ref3_name' => $request->input('ref3_name'),
                'ref3_phone' => $request->input('ref3_phone'),
                // Financiero
                'price' => $price,
                'down_payment' => $downPayment,
                'enganche_descuento' => $request->input('enganche_descuento'),
                'credit_amount' => $creditAmount,
                'abono_semanal' => $request->input('abono_semanal'),
                'term_months' => $request->input('term_months'),
                'term_weeks' => $request->input('term_weeks'),
                'tag_contrato' => $request->input('tag_contrato'),
                'sale_date' => $request->input('sale_date', now()),
                'status' => 'activa',
            ]);

            // Update device status to 'vendido'
            $device->update(['status' => 'vendido']);

            // Optional initial note
            if ($request->filled('initial_note')) {
                FinSaleNote::create([
                    'sale_id' => $sale->id,
                    'user_id' => auth()->id(),
                    'note' => $request->input('initial_note'),
                ]);
            }

            return $sale;
        });

        // Broadcast Reverb WebSocket event
        try {
            event(new FinancierasUpdated(
                'ventas',
                'created',
                "Venta {$sale->sale_code} registrada con IMEI {$device->imei}",
                ['sale_id' => $sale->id, 'sale_code' => $sale->sale_code, 'imei' => $device->imei]
            ));
        } catch (\Throwable $e) {
            \Log::warning('Reverb broadcast warning: ' . $e->getMessage());
        }

        return redirect()->route('financieras.ventas.show', $sale->id)
            ->with('success', 'Venta registrada con éxito. Código: ' . $sale->sale_code);
    }

    public function show(FinSale $sale)
    {
        $sale->load([
            'device.brand',
            'device.branch',
            'financiera',
            'branch',
            'seller',
            'canceller',
            'notes.user',
            'warranties.currentStage',
            'theftReports'
        ]);

        return view('financieras.ventas.show', compact('sale'));
    }

    public function edit(FinSale $sale)
    {
        $branches = Branch::all();
        $financieras = FinFinanciera::where('is_active', true)->get();
        $sellers = User::orderBy('name')->get();

        return view('financieras.ventas.edit', compact('sale', 'branches', 'financieras', 'sellers'));
    }

    public function update(Request $request, FinSale $sale)
    {
        $request->validate([
            'financiera_id' => 'nullable|exists:fin_financieras,id',
            'branch_id' => 'required|exists:branches,id',
            'seller_id' => 'nullable|exists:users,id',
            'tag_contrato' => 'nullable|string|max:100',
            'price' => 'nullable|numeric|min:0',
            'down_payment' => 'nullable|numeric|min:0',
            'enganche_descuento' => 'nullable|numeric|min:0',
            'credit_amount' => 'nullable|numeric|min:0',
            'abono_semanal' => 'nullable|numeric|min:0',
            'term_months' => 'nullable|integer|min:1',
            'term_weeks' => 'nullable|integer|min:1|max:520',
            'sale_date' => 'nullable|date',
            'customer_name' => 'nullable|string|max:150',
            'customer_phone' => 'nullable|string|max:30',
            'customer_email' => 'nullable|email|max:150',
            'customer_ine' => 'nullable|string|max:50',
            'customer_address' => 'nullable|string|max:500',
            'customer_chip' => 'nullable|string|max:100',
            'customer_facebook' => 'nullable|string|max:150',
            'ref1_name' => 'nullable|string|max:150',
            'ref1_phone' => 'nullable|string|max:30',
            'ref2_name' => 'nullable|string|max:150',
            'ref2_phone' => 'nullable|string|max:30',
            'ref3_name' => 'nullable|string|max:150',
            'ref3_phone' => 'nullable|string|max:30',
        ]);

        $sale->update($request->only([
            'financiera_id',
            'branch_id',
            'seller_id',
            'tag_contrato',
            'customer_name',
            'customer_phone',
            'customer_email',
            'customer_ine',
            'customer_address',
            'customer_chip',
            'customer_facebook',
            'ref1_name', 'ref1_phone',
            'ref2_name', 'ref2_phone',
            'ref3_name', 'ref3_phone',
            'price',
            'down_payment',
            'enganche_descuento',
            'credit_amount',
            'abono_semanal',
            'term_months',
            'term_weeks',
            'sale_date',
        ]));

        try {
            event(new FinancierasUpdated(
                'ventas',
                'updated',
                "Venta {$sale->sale_code} actualizada",
                ['sale_id' => $sale->id]
            ));
        } catch (\Throwable $e) {
            \Log::warning('Reverb broadcast warning: ' . $e->getMessage());
        }

        return redirect()->route('financieras.ventas.show', $sale->id)
            ->with('success', 'Venta actualizada correctamente.');
    }

    /**
     * Cancel sale: reverts device to 'disponible' and records cancellation info
     */
    public function cancel(Request $request, FinSale $sale)
    {
        $request->validate([
            'cancellation_reason' => 'required|string|max:1000',
        ]);

        if ($sale->status === 'cancelada') {
            return back()->with('error', 'Esta venta ya se encuentra cancelada.');
        }

        DB::transaction(function () use ($request, $sale) {
            $sale->update([
                'status' => 'cancelada',
                'cancelled_at' => now(),
                'cancelled_by' => auth()->id(),
                'cancellation_reason' => $request->input('cancellation_reason'),
            ]);

            // Revert device back to 'disponible'
            $sale->device->update(['status' => 'disponible']);

            FinSaleNote::create([
                'sale_id' => $sale->id,
                'user_id' => auth()->id(),
                'note' => 'Venta cancelada. Motivo: ' . $request->input('cancellation_reason'),
            ]);
        });

        try {
            event(new FinancierasUpdated(
                'ventas',
                'cancelled',
                "Venta {$sale->sale_code} cancelada. Dispositivo {$sale->device->imei} devuelto a inventario.",
                ['sale_id' => $sale->id, 'device_id' => $sale->device_id]
            ));
        } catch (\Throwable $e) {
            \Log::warning('Reverb broadcast warning: ' . $e->getMessage());
        }

        return redirect()->route('financieras.ventas.show', $sale->id)
            ->with('success', 'La venta ha sido cancelada y el dispositivo regresó a inventario disponible.');
    }

    /**
     * Add free note to sale
     */
    public function addNote(Request $request, FinSale $sale)
    {
        $request->validate([
            'note' => 'required|string|max:2000',
        ]);

        FinSaleNote::create([
            'sale_id' => $sale->id,
            'user_id' => auth()->id(),
            'note' => $request->input('note'),
        ]);

        return back()->with('success', 'Nota agregada exitosamente.');
    }

    public function destroy(FinSale $sale)
    {
        if ($sale->status === 'activa') {
            $sale->device->update(['status' => 'disponible']);
        }

        $sale->delete();

        return redirect()->route('financieras.index', ['tab' => 'ventas'])
            ->with('success', 'Registro de venta eliminado.');
    }

    /**
     * Export sales data to Excel (PhpSpreadsheet)
     */
    public function exportExcel(Request $request)
    {
        ini_set('max_execution_time', 0);
        ini_set('memory_limit', '1024M');

        $sales = FinSale::filter($request->all())
            ->with(['device.brand', 'financiera', 'branch', 'seller'])
            ->latest('sale_date')
            ->get();

        $data = [
            [
                'Código Venta',
                'Fecha',
                'IMEI',
                'Marca',
                'Modelo',
                'Sucursal',
                'Financiera',
                'Vendedor',
                'Cliente',
                'Teléfono',
                'Email',
                'INE',
                'Precio ($)',
                'Enganche ($)',
                'Monto Crédito ($)',
                'Plazo (Meses)',
                'Estado',
            ]
        ];

        foreach ($sales as $s) {
            $data[] = [
                $s->sale_code,
                $s->sale_date ? $s->sale_date->format('Y-m-d H:i') : '',
                $s->device?->imei ?? 'N/A',
                $s->device?->brand?->name ?? 'N/A',
                $s->device?->model ?? 'N/A',
                $s->branch?->name ?? 'N/A',
                $s->financiera?->name ?? 'Directo',
                $s->seller?->name ?? 'N/A',
                $s->customer_name ?? 'N/A',
                $s->customer_phone ?? 'N/A',
                $s->customer_email ?? 'N/A',
                $s->customer_ine ?? 'N/A',
                $s->price,
                $s->down_payment,
                $s->credit_amount,
                $s->term_months,
                ucfirst($s->status),
            ];
        }

        $spreadsheet = new Spreadsheet();
        $sheet = $spreadsheet->getActiveSheet();
        $sheet->getDefaultColumnDimension()->setWidth(18);
        $sheet->fromArray($data);

        $writer = new Xls($spreadsheet);
        $filename = 'Financieras_Ventas_' . date('Ymd_His') . '.xls';

        header('Content-Type: application/vnd.ms-excel');
        header("Content-Disposition: attachment;filename=\"{$filename}\"");
        header('Cache-Control: max-age=0');
        ob_end_clean();
        $writer->save('php://output');
        exit();
    }

    /**
     * Export sale receipt / summary to PDF via barryvdh/laravel-dompdf
     */
    public function exportPdf(FinSale $sale)
    {
        $sale->load([
            'device.brand',
            'device.branch',
            'financiera',
            'branch',
            'seller',
            'notes.user'
        ]);

        // Pasar todas las sucursales para mostrar sus direcciones reales en el PDF
        $branches = Branch::orderBy('name')->get();

        $pdf = Pdf::loadView('financieras.ventas.pdf', compact('sale', 'branches'));
        return $pdf->download("Venta_{$sale->sale_code}.pdf");
    }
}
