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
use Carbon\Carbon;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Str;
use PhpOffice\PhpSpreadsheet\Cell\DataType;
use PhpOffice\PhpSpreadsheet\IOFactory;
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
        'exportExcel' => 'export',
        'exportPdf' => 'read',
    ];

    public function __construct()
    {
        $this->initializeModulePermission();
    }

    public function index(Request $request)
    {
        $defaultStartDate = now()->startOfMonth()->format('Y-m-d');
        $defaultEndDate = now()->endOfMonth()->format('Y-m-d');

        if ($request->has('start_date') || $request->has('end_date')) {
            $startDate = $request->input('start_date');
            $endDate = $request->input('end_date');
        } else {
            $startDate = $defaultStartDate;
            $endDate = $defaultEndDate;
            $request->merge(['start_date' => $startDate, 'end_date' => $endDate]);
        }

        $filters = array_merge(
            $request->only([
                'search',
                'financiera_id',
                'branch_id',
                'seller_id',
                'status',
            ]),
            [
                'start_date' => $startDate,
                'end_date'   => $endDate,
            ]
        );

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
        $availableDevices = FinDevice::where('status', 'disponible')
            ->when(auth()->check() && !auth()->user()->can('financieras.inventario.all_branches'), function ($q) {
                $q->where('branch_id', auth()->user()->branch_id);
            })
            ->with('brand', 'branch')
            ->get();

        return view('financieras.ventas.create', compact('branches', 'brands', 'financieras', 'sellers', 'availableDevices'));
    }

    protected function canAssignSeller(): bool
    {
        $user = auth()->user();
        if (!$user) {
            return false;
        }

        return $user->can('financieras.ventas.assign_seller') || $user->isSuperAdmin();
    }

    protected function checkSaleBranchAccess(FinSale $sale): void
    {
        if (auth()->check() && !auth()->user()->can('financieras.inventario.all_branches')) {
            if ($sale->branch_id !== auth()->user()->branch_id) {
                abort(403, 'No tienes permiso para acceder a ventas de otra sucursal.');
            }
        }
    }

    public function store(Request $request)
    {
        if (auth()->check() && !auth()->user()->can('financieras.inventario.all_branches')) {
            $request->merge(['branch_id' => auth()->user()->branch_id]);
        }

        $canAssignSeller = $this->canAssignSeller();

        $request->validate([
            'device_id' => 'nullable|exists:fin_devices,id',
            'imei' => 'required_without:device_id|nullable|string|max:50',
            'model' => 'required_without:device_id|nullable|string|max:150',
            'brand_id' => 'nullable|exists:fin_brands,id',
            'brand_name' => 'nullable|string|max:100',
            'color' => 'nullable|string|max:50',
            'financiera_id' => 'required|exists:fin_financieras,id',
            'branch_id' => 'required|exists:branches,id',
            'seller_id' => $canAssignSeller ? 'required|exists:users,id' : 'nullable',
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

        $sale = DB::transaction(function () use ($request, $device, $canAssignSeller) {
            $saleCode = 'FIN-' . date('Ymd') . '-' . strtoupper(substr(uniqid(), -4));

            $price = (float) $request->input('price', 0);
            $downPayment = (float) $request->input('down_payment', 0);
            $creditAmount = $request->filled('credit_amount')
                ? (float) $request->input('credit_amount')
                : max(0, $price - $downPayment);

            $sellerId = ($canAssignSeller && $request->filled('seller_id')) ? $request->input('seller_id') : auth()->id();

            $sale = FinSale::create([
                'sale_code' => $saleCode,
                'device_id' => $device->id,
                'financiera_id' => $request->input('financiera_id'),
                'branch_id' => $request->input('branch_id', $device->branch_id),
                'seller_id' => $sellerId,
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
        $this->checkSaleBranchAccess($sale);

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
        $this->checkSaleBranchAccess($sale);

        $branches = Branch::all();
        $financieras = FinFinanciera::where('is_active', true)->get();
        $sellers = User::orderBy('name')->get();

        return view('financieras.ventas.edit', compact('sale', 'branches', 'financieras', 'sellers'));
    }

    public function update(Request $request, FinSale $sale)
    {
        $this->checkSaleBranchAccess($sale);

        if (auth()->check() && !auth()->user()->can('financieras.inventario.all_branches')) {
            $request->merge(['branch_id' => auth()->user()->branch_id]);
        }

        $canAssignSeller = $this->canAssignSeller();

        $request->validate([
            'financiera_id' => 'required|exists:fin_financieras,id',
            'branch_id' => 'required|exists:branches,id',
            'seller_id' => $canAssignSeller ? 'nullable|exists:users,id' : 'nullable',
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

        $updateData = $request->only([
            'financiera_id',
            'branch_id',
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
        ]);

        // Únicamente usuarios con permiso pueden editar el vendedor
        if ($canAssignSeller && $request->filled('seller_id')) {
            $updateData['seller_id'] = $request->input('seller_id');
        }

        $sale->update($updateData);

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
        $this->checkSaleBranchAccess($sale);

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
        $this->checkSaleBranchAccess($sale);

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
        $this->checkSaleBranchAccess($sale);

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

        $spreadsheet = new Spreadsheet();
        $sheet = $spreadsheet->getActiveSheet();
        $sheet->setTitle('Ventas Financieras');

        // Encabezados en el orden solicitado
        $headers = [
            'FECHA DE VENTA',
            'VENDEDOR',
            'CLIENTE',
            'CONTACTO',
            'CHIP INGRESADO',
            'FINANCIERA',
            'ID/CONTRATO',
            'MARCA',
            'MODELO',
            'CAPACIDAD',
            'IMEI',
            'PRECIO DE VENTA',
            'ENGANCHE ORIGINAL',
            'DESCUENTO DE ENGANCHE',
            'PAGO INICIAL',
            'ABONO',
            'PLAZO',
            'DIRECCIÓN',
            'FACEBOOK',
            'EMAIL',
            'REFERENCIAS',
        ];

        foreach ($headers as $colIdx => $header) {
            $sheet->setCellValueByColumnAndRow($colIdx + 1, 1, $header);
        }

        // Estilos para la fila de encabezados
        $lastCol = \PhpOffice\PhpSpreadsheet\Cell\Coordinate::stringFromColumnIndex(count($headers));
        $sheet->getStyle("A1:{$lastCol}1")->applyFromArray([
            'font' => [
                'bold'  => true,
                'color' => ['rgb' => 'FFFFFF'],
                'size'  => 11,
            ],
            'fill' => [
                'fillType'   => \PhpOffice\PhpSpreadsheet\Style\Fill::FILL_SOLID,
                'startColor' => ['rgb' => '1D6FA4'],
            ],
            'alignment' => [
                'horizontal' => \PhpOffice\PhpSpreadsheet\Style\Alignment::HORIZONTAL_CENTER,
                'vertical'   => \PhpOffice\PhpSpreadsheet\Style\Alignment::VERTICAL_CENTER,
            ],
        ]);
        $sheet->getRowDimension(1)->setRowHeight(22);

        // Columna IMEI (col 11) como texto para evitar notación científica
        $imeiCol = \PhpOffice\PhpSpreadsheet\Cell\Coordinate::stringFromColumnIndex(11);
        $sheet->getStyle("{$imeiCol}2:{$imeiCol}60000")
            ->getNumberFormat()->setFormatCode('@');

        $rowNum = 2;
        foreach ($sales as $s) {
            // Concatenar las hasta 3 referencias en una sola celda
            $refs = [];
            if ($s->ref1_name || $s->ref1_phone) {
                $refs[] = trim(($s->ref1_name ?? '') . ' ' . ($s->ref1_phone ?? ''));
            }
            if ($s->ref2_name || $s->ref2_phone) {
                $refs[] = trim(($s->ref2_name ?? '') . ' ' . ($s->ref2_phone ?? ''));
            }
            if ($s->ref3_name || $s->ref3_phone) {
                $refs[] = trim(($s->ref3_name ?? '') . ' ' . ($s->ref3_phone ?? ''));
            }

            $sheet->setCellValueByColumnAndRow(1,  $rowNum, $s->sale_date ? $s->sale_date->format('d/m/Y') : '');
            $sheet->setCellValueByColumnAndRow(2,  $rowNum, $s->seller_display_name !== 'N/A' ? $s->seller_display_name : '');
            $sheet->setCellValueByColumnAndRow(3,  $rowNum, $s->customer_name ?? '');
            $sheet->setCellValueByColumnAndRow(4,  $rowNum, $s->customer_phone ?? '');
            $sheet->setCellValueByColumnAndRow(5,  $rowNum, $s->customer_chip ?? '');
            $sheet->setCellValueByColumnAndRow(6,  $rowNum, $s->financiera?->name ?? 'Directo');
            $sheet->setCellValueByColumnAndRow(7,  $rowNum, $s->tag_contrato ?? '');
            $sheet->setCellValueByColumnAndRow(8,  $rowNum, $s->device?->brand?->name ?? '');
            $sheet->setCellValueByColumnAndRow(9,  $rowNum, $s->device?->model ?? '');
            $sheet->setCellValueByColumnAndRow(10, $rowNum, $s->device?->storage ?? '');
            $sheet->setCellValueExplicitByColumnAndRow(11, $rowNum, (string)($s->device?->imei ?? ''), \PhpOffice\PhpSpreadsheet\Cell\DataType::TYPE_STRING);
            $sheet->setCellValueByColumnAndRow(12, $rowNum, $s->price);
            $sheet->setCellValueByColumnAndRow(13, $rowNum, $s->down_payment);
            $sheet->setCellValueByColumnAndRow(14, $rowNum, $s->enganche_descuento ?? '');
            $sheet->setCellValueByColumnAndRow(15, $rowNum, $s->credit_amount);
            $sheet->setCellValueByColumnAndRow(16, $rowNum, $s->abono_semanal ?? '');
            $sheet->setCellValueByColumnAndRow(17, $rowNum, $s->term_months ? $s->term_months . ' meses' : ($s->term_weeks ? $s->term_weeks . ' semanas' : ''));
            $sheet->setCellValueByColumnAndRow(18, $rowNum, $s->customer_address ?? '');
            $sheet->setCellValueByColumnAndRow(19, $rowNum, $s->customer_facebook ?? '');
            $sheet->setCellValueByColumnAndRow(20, $rowNum, $s->customer_email ?? '');
            $sheet->setCellValueByColumnAndRow(21, $rowNum, implode(' | ', $refs));
            $rowNum++;
        }

        // Ancho automático de columnas
        foreach (range(1, count($headers)) as $col) {
            $colLetter = \PhpOffice\PhpSpreadsheet\Cell\Coordinate::stringFromColumnIndex($col);
            $sheet->getColumnDimension($colLetter)->setAutoSize(true);
        }

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

    /**
     * Download Excel template for historical sales bulk import
     */
    public function downloadTemplate()
    {
        $spreadsheet = new Spreadsheet();
        $sheet = $spreadsheet->getActiveSheet();
        $sheet->setTitle('Plantilla Ventas Históricas');

        $headers = [
            'FECHA DE VENTA',
            'VENDEDOR',
            'CLIENTE',
            'CONTACTO',
            'CHIP INGRESADO',
            'FINANCIERA',
            'ID/CONTRATO',
            'MARCA',
            'MODELO',
            'CAP',
            'IMEI',
            'PRECIO DE VENTA',
            'ENGANCHE ORIGINAL',
            'DESCTO AL ENGANCHE',
            'PAGO INICIAL',
            'ABONO',
            'PLAZO',
            'DIRECCION',
            'FACEBOOK',
            'EMAIL',
            'REFERENCIAS',
        ];

        foreach ($headers as $colIdx => $header) {
            $sheet->setCellValueByColumnAndRow($colIdx + 1, 1, $header);
        }

        $lastCol = \PhpOffice\PhpSpreadsheet\Cell\Coordinate::stringFromColumnIndex(count($headers));
        $sheet->getStyle("A1:{$lastCol}1")->applyFromArray([
            'font' => [
                'bold'  => true,
                'color' => ['rgb' => 'FFFFFF'],
                'size'  => 11,
            ],
            'fill' => [
                'fillType'   => \PhpOffice\PhpSpreadsheet\Style\Fill::FILL_SOLID,
                'startColor' => ['rgb' => '1D6FA4'],
            ],
            'alignment' => [
                'horizontal' => \PhpOffice\PhpSpreadsheet\Style\Alignment::HORIZONTAL_CENTER,
                'vertical'   => \PhpOffice\PhpSpreadsheet\Style\Alignment::VERTICAL_CENTER,
            ],
        ]);
        $sheet->getRowDimension(1)->setRowHeight(24);

        $imeiCol = \PhpOffice\PhpSpreadsheet\Cell\Coordinate::stringFromColumnIndex(11);
        $phoneCol = \PhpOffice\PhpSpreadsheet\Cell\Coordinate::stringFromColumnIndex(4);
        $chipCol = \PhpOffice\PhpSpreadsheet\Cell\Coordinate::stringFromColumnIndex(5);
        $sheet->getStyle("{$imeiCol}2:{$imeiCol}1000")->getNumberFormat()->setFormatCode('@');
        $sheet->getStyle("{$phoneCol}2:{$phoneCol}1000")->getNumberFormat()->setFormatCode('@');
        $sheet->getStyle("{$chipCol}2:{$chipCol}1000")->getNumberFormat()->setFormatCode('@');

        // Fila de ejemplo
        $sample = [
            date('d/m/Y'),
            'Nombre Vendedor',
            'Carlos Mendoza',
            '5512345678',
            '5598765432',
            'Krediya',
            'KRD-12345',
            'Samsung',
            'Galaxy A15',
            '128GB',
            '358941234567890',
            '4500.00',
            '500.00',
            '0.00',
            '500.00',
            '250.00',
            '12 meses',
            'Av. Principal #123, Col. Centro',
            'fb.com/carlosmendoza',
            'carlos@email.com',
            'Hermano: Luis Mendoza 5587654321 | Mamá: Rosa 5533221100',
        ];

        foreach ($sample as $colIdx => $val) {
            if ($colIdx === 10) {
                $sheet->setCellValueExplicitByColumnAndRow($colIdx + 1, 2, (string)$val, DataType::TYPE_STRING);
            } else {
                $sheet->setCellValueByColumnAndRow($colIdx + 1, 2, $val);
            }
        }

        foreach (range(1, count($headers)) as $col) {
            $colLetter = \PhpOffice\PhpSpreadsheet\Cell\Coordinate::stringFromColumnIndex($col);
            $sheet->getColumnDimension($colLetter)->setAutoSize(true);
        }

        $writer = new Xls($spreadsheet);
        $filename = 'Plantilla_Ventas_Historicas.xls';

        header('Content-Type: application/vnd.ms-excel');
        header("Content-Disposition: attachment;filename=\"{$filename}\"");
        header('Cache-Control: max-age=0');
        ob_end_clean();
        $writer->save('php://output');
        exit();
    }

    /**
     * Bulk import historical sales from Excel
     */
    public function importExcel(Request $request)
    {
        ini_set('max_execution_time', 0);
        ini_set('memory_limit', '1024M');

        $canAllBranches = auth()->check() && auth()->user()->can('financieras.inventario.all_branches');
        $userBranchId = auth()->check() ? auth()->user()->branch_id : null;

        $request->validate([
            'import_file' => 'required|file|mimes:xlsx,xls,csv|max:20480',
            'branch_id' => 'required|exists:branches,id',
        ], [
            'import_file.required' => 'Debe seleccionar un archivo Excel o CSV.',
            'import_file.mimes' => 'El formato del archivo debe ser .xlsx, .xls o .csv.',
            'import_file.max' => 'El tamaño máximo permitido es de 20 MB.',
            'branch_id.required' => 'Debe seleccionar la sucursal de destino.',
            'branch_id.exists' => 'La sucursal seleccionada no es válida.',
        ]);

        $branchId = (int)$request->input('branch_id');
        if (!$canAllBranches && $userBranchId && $branchId !== (int)$userBranchId) {
            return back()->with('error', 'No tienes permiso para importar ventas en otra sucursal distinta a tu sucursal asignada.');
        }

        try {
            $file = $request->file('import_file');
            $spreadsheet = IOFactory::load($file->getRealPath());
            $worksheet = $spreadsheet->getActiveSheet();
            $rows = $worksheet->toArray(null, true, false, false);

            if (count($rows) <= 1) {
                return back()->with('error', 'El archivo no contiene registros o está vacío.');
            }

            // Normalizador de texto para encabezados
            $normalizeHeader = function ($str) {
                if ($str === null || $str === '') return '';
                $str = mb_strtoupper(trim((string)$str), 'UTF-8');
                $str = strtr(utf8_decode($str), utf8_decode('ÀÁÂÃÄÅàáâãäåÒÓÔÕÖØòóôõöøÈÉÊËèéêëÇçÌÍÎÏìíîïÙÚÛÜùúûüÿÑñ'), 'AAAAAAaaaaaaOOOOOOooooooEEEEeeeeCcIIIIiiiiUUUUuuuuyNn');
                return preg_replace('/[^A-Z0-9]/', '', $str);
            };

            // Helper para limpiar strings de catálogos (case-insensitive, sin acentos)
            $normalizeCatalogKey = function ($str) {
                if ($str === null || $str === '') return '';
                $str = mb_strtolower(trim((string)$str), 'UTF-8');
                $str = strtr(utf8_decode($str), utf8_decode('àáâãäåòóôõöøèéêëçìíîïùúûüñ'), 'aaaaaaooooooeeeeciiiiuuuun');
                $str = preg_replace('/[^a-z0-9]/', ' ', $str);
                return trim(preg_replace('/\s+/', ' ', $str));
            };

            // Mapear encabezados
            $headerMap = [];
            foreach ($rows[0] as $colIdx => $colVal) {
                $cleaned = $normalizeHeader($colVal);
                if (empty($cleaned)) continue;

                if (in_array($cleaned, ['FECHADEVENTA', 'FECHA', 'FECHAVENTA', 'DATE'])) {
                    $headerMap['date'] = $colIdx;
                } elseif (in_array($cleaned, ['VENDEDOR', 'SELLER'])) {
                    $headerMap['seller'] = $colIdx;
                } elseif (in_array($cleaned, ['CLIENTE', 'CUSTOMER', 'NOMBRECLIENTE', 'NOMBRE'])) {
                    $headerMap['customer_name'] = $colIdx;
                } elseif (in_array($cleaned, ['CONTACTO', 'TELEFONO', 'CELULAR', 'PHONE'])) {
                    $headerMap['customer_phone'] = $colIdx;
                } elseif (in_array($cleaned, ['CHIPINGRESADO', 'CHIP', 'NUMEROCHIP'])) {
                    $headerMap['customer_chip'] = $colIdx;
                } elseif (in_array($cleaned, ['FINANCIERA', 'FINANCIERAID'])) {
                    $headerMap['financiera'] = $colIdx;
                } elseif (in_array($cleaned, ['IDCONTRATO', 'CONTRATO', 'DEVICEIDCONTRATO', 'ID', 'TAGCONTRATO', 'DEVICEID'])) {
                    $headerMap['tag_contrato'] = $colIdx;
                } elseif (in_array($cleaned, ['MARCA', 'BRAND'])) {
                    $headerMap['brand'] = $colIdx;
                } elseif (in_array($cleaned, ['MODELO', 'MODEL'])) {
                    $headerMap['model'] = $colIdx;
                } elseif (in_array($cleaned, ['CAP', 'CAPACIDAD', 'ALMACENAMIENTO', 'STORAGE', 'MEMORIA'])) {
                    $headerMap['storage'] = $colIdx;
                } elseif (in_array($cleaned, ['IMEI'])) {
                    $headerMap['imei'] = $colIdx;
                } elseif (in_array($cleaned, ['PRECIODEVENTA', 'PRECIO', 'PRICE'])) {
                    $headerMap['price'] = $colIdx;
                } elseif (in_array($cleaned, ['ENGANCHEORIGINAL', 'ENGANCHE'])) {
                    $headerMap['down_payment'] = $colIdx;
                } elseif (in_array($cleaned, ['DESCTOALENGANCHE', 'DESCUENTOENGANCHE', 'DESCUENTO', 'DESCTOENGANCHE'])) {
                    $headerMap['enganche_descuento'] = $colIdx;
                } elseif (in_array($cleaned, ['PAGOINICIAL', 'PAGO', 'CREDITAMOUNT', 'PAGOCREDITO'])) {
                    $headerMap['credit_amount'] = $colIdx;
                } elseif (in_array($cleaned, ['ABONO', 'ABONOSEMANAL'])) {
                    $headerMap['abono_semanal'] = $colIdx;
                } elseif (in_array($cleaned, ['PLAZO', 'TERM', 'MESES'])) {
                    $headerMap['term'] = $colIdx;
                } elseif (in_array($cleaned, ['DIRECCION', 'DIRECCIONCLIENTE', 'ADDRESS'])) {
                    $headerMap['customer_address'] = $colIdx;
                } elseif (in_array($cleaned, ['FACEBOOK', 'FB'])) {
                    $headerMap['customer_facebook'] = $colIdx;
                } elseif (in_array($cleaned, ['EMAIL', 'CORREO', 'CORREOELECTRONICO'])) {
                    $headerMap['customer_email'] = $colIdx;
                } elseif (in_array($cleaned, ['REFERENCIAS', 'REFERENCIA', 'REFS'])) {
                    $headerMap['references'] = $colIdx;
                }
            }

            // Validar columnas indispensables
            $missingCols = [];
            if (!isset($headerMap['date'])) $missingCols[] = 'FECHA DE VENTA';
            if (!isset($headerMap['financiera'])) $missingCols[] = 'FINANCIERA';
            if (!isset($headerMap['brand'])) $missingCols[] = 'MARCA';
            if (!isset($headerMap['model'])) $missingCols[] = 'MODELO';

            if (!empty($missingCols)) {
                return back()->with('error', 'El archivo no contiene las columnas requeridas: ' . implode(', ', $missingCols) . '. Verifique los encabezados del archivo Excel.');
            }

            // Pre-cargar catálogos
            $financierasMap = [];
            foreach (FinFinanciera::all() as $fin) {
                $financierasMap[$normalizeCatalogKey($fin->name)] = $fin->id;
                if (!empty($fin->code)) {
                    $financierasMap[$normalizeCatalogKey($fin->code)] = $fin->id;
                }
            }

            $brandsMap = [];
            foreach (FinBrand::all() as $brand) {
                $brandsMap[$normalizeCatalogKey($brand->name)] = $brand->id;
            }

            $usersMap = [];
            foreach (User::all() as $user) {
                $usersMap[$normalizeCatalogKey($user->name)] = $user->id;
                if (!empty($user->username)) {
                    $usersMap[$normalizeCatalogKey($user->username)] = $user->id;
                }
            }

            // Helper de validación de fechas (ESTRICTO: sin fallback a fechas distintas)
            $validateDate = function ($rawDate) {
                if ($rawDate === null || trim((string)$rawDate) === '') {
                    return ['error' => 'La fecha de venta está vacía.'];
                }

                if (is_numeric($rawDate)) {
                    try {
                        $dt = \PhpOffice\PhpSpreadsheet\Shared\Date::excelToDateTimeObject($rawDate);
                        $year = (int)$dt->format('Y');
                        if ($year < 2000 || $year > 2099) {
                            return ['error' => "Fecha inválida '{$rawDate}' (año {$year}). El año debe ser congruente (2000-2099)."];
                        }
                        return ['date' => Carbon::instance($dt)];
                    } catch (\Throwable $t) {
                        return ['error' => "Fecha numérica no interpretable '{$rawDate}'."];
                    }
                }

                $str = trim((string)$rawDate);
                if (preg_match('/^(ND|#|N\/A|SIN|NULL)/i', $str) || preg_match('/\bND\b/i', $str)) {
                    return ['error' => "La fecha '{$str}' contiene valores no válidos (ND/#). Debe corregirse a una fecha real."];
                }

                $formats = ['d/m/Y', 'd-m-Y', 'Y-m-d', 'd/m/y', 'd-m-y', 'Y/m/d', 'd/m/Y H:i', 'd/m/Y H:i:s', 'Y-m-d H:i:s'];
                foreach ($formats as $fmt) {
                    try {
                        $d = Carbon::createFromFormat($fmt, $str);
                        if ($d && checkdate((int)$d->month, (int)$d->day, (int)$d->year)) {
                            $year = (int)$d->year;
                            if ($year < 2000 || $year > 2099) {
                                return ['error' => "La fecha '{$str}' tiene un año inválido ({$year})."];
                            }
                            return ['date' => $d];
                        }
                    } catch (\Throwable $t) {
                        // Continuar con los siguientes formatos
                    }
                }

                try {
                    $parsed = Carbon::parse($str);
                    if ($parsed && checkdate((int)$parsed->month, (int)$parsed->day, (int)$parsed->year)) {
                        $year = (int)$parsed->year;
                        if ($year < 2000 || $year > 2099) {
                            return ['error' => "La fecha '{$str}' tiene un año inválido ({$year})."];
                        }
                        return ['date' => $parsed];
                    }
                } catch (\Throwable $t) {
                    // Falló parse
                }

                return ['error' => "Formato de fecha inválido '{$str}'. Utilice el formato DD/MM/AAAA."];
            };

            // Helper de números
            $cleanNumber = function ($val, $default = 0) {
                if ($val === null || $val === '') return $default;
                if (is_numeric($val)) return (float)$val;
                $str = trim((string)$val);
                $str = str_replace(['$', ',', ' '], '', $str);
                return is_numeric($str) ? (float)$str : $default;
            };

            // Helper de plazo
            $parseTerm = function ($val) {
                if ($val === null || trim((string)$val) === '') {
                    return ['months' => null, 'weeks' => null];
                }
                $str = mb_strtolower(trim((string)$val), 'UTF-8');
                preg_match('/(\d+)/', $str, $matches);
                $num = isset($matches[1]) ? (int)$matches[1] : null;
                if (!$num) {
                    return ['months' => null, 'weeks' => null];
                }
                if (str_contains($str, 'sem') || str_contains($str, 'week')) {
                    return ['months' => null, 'weeks' => $num];
                }
                return ['months' => $num, 'weeks' => null];
            };

            // Helper de referencias
            $parseReferences = function ($rawRef) {
                $data = [
                    'ref1_name' => null, 'ref1_phone' => null,
                    'ref2_name' => null, 'ref2_phone' => null,
                    'ref3_name' => null, 'ref3_phone' => null,
                ];
                if (empty($rawRef)) return $data;

                $parts = preg_split('/[|\n\r]+/', (string)$rawRef);
                $parts = array_values(array_filter(array_map('trim', $parts)));

                for ($idx = 0; $idx < min(3, count($parts)); $idx++) {
                    $part = $parts[$idx];
                    $slot = $idx + 1;
                    if (preg_match('/(\+?[\d\s\-\(\)]{7,15})/', $part, $pm)) {
                        $phone = trim($pm[1]);
                        $name = trim(str_replace($phone, '', $part));
                        $data["ref{$slot}_name"] = mb_substr($name, 0, 150) ?: mb_substr($part, 0, 150);
                        $data["ref{$slot}_phone"] = mb_substr($phone, 0, 30);
                    } else {
                        $data["ref{$slot}_name"] = mb_substr($part, 0, 150);
                    }
                }

                if (count($parts) === 0 && !empty(trim((string)$rawRef))) {
                    $data['ref1_name'] = mb_substr(trim((string)$rawRef), 0, 150);
                }
                return $data;
            };

            // Helper para limpiar IMEI
            $cleanImei = function ($val) {
                if ($val === null || $val === '') return '';
                if (is_float($val) || is_int($val)) {
                    return number_format($val, 0, '', '');
                }
                $str = trim((string)$val);
                if (preg_match('/^[0-9]+(\.[0-9]+)?[eE][\+\-]?[0-9]+$/i', $str) || (stripos($str, 'e+') !== false && is_numeric($str))) {
                    return number_format((float)$str, 0, '', '');
                }
                return preg_replace('/[^0-9A-Za-z]/', '', $str);
            };

            $validRows = [];
            $rowErrors = [];

            for ($i = 1; $i < count($rows); $i++) {
                $row = $rows[$i];
                $rowNum = $i + 1;

                // Verificar si la fila está totalmente vacía
                $hasContent = false;
                foreach ($row as $cell) {
                    if (!empty(trim((string)$cell))) {
                        $hasContent = true;
                        break;
                    }
                }
                if (!$hasContent) {
                    continue;
                }

                // 1. FECHA DE VENTA (ESTRICTA)
                $rawDate = $row[$headerMap['date']] ?? null;
                $dateResult = $validateDate($rawDate);
                if (isset($dateResult['error'])) {
                    $rowErrors[] = "Fila {$rowNum}: {$dateResult['error']}";
                    $saleDate = null;
                } else {
                    $saleDate = $dateResult['date'];
                }

                // 2. FINANCIERA (ESTRICTA: no crear nueva)
                $rawFinanciera = trim((string)($row[$headerMap['financiera']] ?? ''));
                $financieraId = null;
                if ($rawFinanciera === '') {
                    $rowErrors[] = "Fila {$rowNum}: La columna FINANCIERA está vacía.";
                } else {
                    $finKey = $normalizeCatalogKey($rawFinanciera);
                    if (!isset($financierasMap[$finKey])) {
                        $rowErrors[] = "Fila {$rowNum}: La financiera '{$rawFinanciera}' no fue encontrada en el catálogo. Verifique la ortografía o regístrela previamente en Catálogos.";
                    } else {
                        $financieraId = $financierasMap[$finKey];
                    }
                }

                // 3. MARCA (ESTRICTA: no crear nueva)
                $rawBrand = trim((string)($row[$headerMap['brand']] ?? ''));
                $brandId = null;
                if ($rawBrand === '') {
                    $rowErrors[] = "Fila {$rowNum}: La columna MARCA está vacía.";
                } else {
                    $brandKey = $normalizeCatalogKey($rawBrand);
                    if (!isset($brandsMap[$brandKey])) {
                        $rowErrors[] = "Fila {$rowNum}: La marca '{$rawBrand}' no fue encontrada en el catálogo. Verifique la ortografía o regístrela previamente en Catálogos.";
                    } else {
                        $brandId = $brandsMap[$brandKey];
                    }
                }

                // 4. MODELO
                $model = trim((string)($row[$headerMap['model']] ?? ''));
                if ($model === '') {
                    $model = 'Modelo no especificado';
                }

                // 5. VENDEDOR (búsqueda en usuarios; si no existe, guardar texto libre)
                $rawSeller = isset($headerMap['seller']) ? trim((string)($row[$headerMap['seller']] ?? '')) : '';
                $sellerId = null;
                $sellerName = null;
                if ($rawSeller !== '') {
                    $sellerKey = $normalizeCatalogKey($rawSeller);
                    if (isset($usersMap[$sellerKey])) {
                        $sellerId = $usersMap[$sellerKey];
                        $sellerName = null;
                    } else {
                        $sellerId = null;
                        $sellerName = mb_substr($rawSeller, 0, 150);
                    }
                }

                // 6. IMEI & DISPOSITIVO
                $rawImei = isset($headerMap['imei']) ? $row[$headerMap['imei']] : null;
                $imei = $cleanImei($rawImei);
                $storage = isset($headerMap['storage']) ? trim((string)($row[$headerMap['storage']] ?? '')) : null;

                // 7. CLIENTE Y DATOS DE CONTACTO
                $customerName = isset($headerMap['customer_name']) ? trim((string)($row[$headerMap['customer_name']] ?? '')) : null;
                $customerPhone = isset($headerMap['customer_phone']) ? trim((string)($row[$headerMap['customer_phone']] ?? '')) : null;
                $customerChip = isset($headerMap['customer_chip']) ? trim((string)($row[$headerMap['customer_chip']] ?? '')) : null;
                $tagContrato = isset($headerMap['tag_contrato']) ? trim((string)($row[$headerMap['tag_contrato']] ?? '')) : null;
                $customerAddress = isset($headerMap['customer_address']) ? trim((string)($row[$headerMap['customer_address']] ?? '')) : null;
                $customerFacebook = isset($headerMap['customer_facebook']) ? trim((string)($row[$headerMap['customer_facebook']] ?? '')) : null;
                $customerEmail = isset($headerMap['customer_email']) ? trim((string)($row[$headerMap['customer_email']] ?? '')) : null;

                // 8. FINANCIERO
                $price = isset($headerMap['price']) ? $cleanNumber($row[$headerMap['price']], 0) : 0;
                $downPayment = isset($headerMap['down_payment']) ? $cleanNumber($row[$headerMap['down_payment']], 0) : 0;
                $engancheDescuento = isset($headerMap['enganche_descuento']) ? $cleanNumber($row[$headerMap['enganche_descuento']], 0) : 0;
                $creditAmount = isset($headerMap['credit_amount']) && $row[$headerMap['credit_amount']] !== '' && $row[$headerMap['credit_amount']] !== null
                    ? $cleanNumber($row[$headerMap['credit_amount']], 0)
                    : max(0, $price - $downPayment);
                $abonoSemanal = isset($headerMap['abono_semanal']) ? $cleanNumber($row[$headerMap['abono_semanal']], 0) : null;

                // 9. PLAZO
                $termRaw = isset($headerMap['term']) ? $row[$headerMap['term']] : null;
                $termParsed = $parseTerm($termRaw);
                $termMonths = $termParsed['months'];
                $termWeeks = $termParsed['weeks'];

                // 10. REFERENCIAS
                $rawReferences = isset($headerMap['references']) ? $row[$headerMap['references']] : null;
                $references = $parseReferences($rawReferences);

                if (count($rowErrors) === 0) {
                    $validRows[] = [
                        'row_num' => $rowNum,
                        'sale_date' => $saleDate,
                        'financiera_id' => $financieraId,
                        'brand_id' => $brandId,
                        'model' => $model,
                        'storage' => !empty($storage) ? $storage : null,
                        'imei' => $imei,
                        'seller_id' => $sellerId,
                        'seller_name' => $sellerName,
                        'customer_name' => !empty($customerName) ? $customerName : null,
                        'customer_phone' => !empty($customerPhone) ? $customerPhone : null,
                        'customer_chip' => !empty($customerChip) ? $customerChip : null,
                        'customer_email' => !empty($customerEmail) ? $customerEmail : null,
                        'customer_address' => !empty($customerAddress) ? $customerAddress : null,
                        'customer_facebook' => !empty($customerFacebook) ? $customerFacebook : null,
                        'tag_contrato' => !empty($tagContrato) ? $tagContrato : null,
                        'price' => $price,
                        'down_payment' => $downPayment,
                        'enganche_descuento' => $engancheDescuento,
                        'credit_amount' => $creditAmount,
                        'abono_semanal' => $abonoSemanal,
                        'term_months' => $termMonths,
                        'term_weeks' => $termWeeks,
                        'references' => $references,
                    ];
                }
            }

            // REGLA ATÓMICA: Si existe al menos una incidencia, no se importa NADA
            if (count($rowErrors) > 0) {
                $sampleErrors = array_slice($rowErrors, 0, 8);
                $msg = "No se importó ninguna venta. Se encontraron " . count($rowErrors) . " incidencias que deben corregirse: " . implode(" | ", $sampleErrors);
                if (count($rowErrors) > 8) {
                    $msg .= " ... y " . (count($rowErrors) - 8) . " incidencias más.";
                }
                return back()->with('error', $msg)->with('import_errors', $rowErrors);
            }

            if (empty($validRows)) {
                return back()->with('error', 'No se encontraron registros válidos para importar en el archivo.');
            }

            // Inserción en transacción atómica
            $importedCount = 0;
            DB::transaction(function () use ($validRows, $branchId, &$importedCount) {
                foreach ($validRows as $item) {
                    $saleDate = $item['sale_date'];
                    $cleanImei = $item['imei'];

                    // Manejo del dispositivo e IMEI
                    if (empty($cleanImei) || in_array(strtoupper($cleanImei), ['ND', 'FMT', 'SINIMEI', 'NA', 'NULL']) || strlen($cleanImei) < 5) {
                        $cleanImei = 'HIST-' . $saleDate->format('Ymd') . '-' . $item['row_num'] . '-' . strtoupper(Str::random(6));
                    }

                    $device = FinDevice::withTrashed()->where('imei', $cleanImei)->first();
                    if ($device) {
                        if ($device->trashed()) {
                            $device->restore();
                        }
                        $device->update([
                            'branch_id' => $branchId,
                            'brand_id'  => $item['brand_id'],
                            'model'     => $item['model'],
                            'storage'   => $item['storage'] ?: $device->storage,
                            'status'    => 'vendido',
                        ]);
                    } else {
                        $device = FinDevice::create([
                            'imei'       => $cleanImei,
                            'brand_id'   => $item['brand_id'],
                            'model'      => $item['model'],
                            'storage'    => $item['storage'],
                            'color'      => 'Sin color',
                            'branch_id'  => $branchId,
                            'status'     => 'vendido',
                            'created_at' => $saleDate,
                            'updated_at' => now(),
                        ]);
                    }

                    // Código de venta único
                    do {
                        $saleCode = 'FIN-HIST-' . $saleDate->format('Ymd') . '-' . strtoupper(Str::random(5));
                    } while (FinSale::where('sale_code', $saleCode)->exists());

                    $saleData = [
                        'sale_code'          => $saleCode,
                        'device_id'          => $device->id,
                        'financiera_id'      => $item['financiera_id'],
                        'branch_id'          => $branchId,
                        'seller_id'          => $item['seller_id'],
                        'seller_name'        => $item['seller_name'],
                        'customer_name'      => $item['customer_name'],
                        'customer_phone'     => $item['customer_phone'],
                        'customer_email'     => $item['customer_email'],
                        'customer_address'   => $item['customer_address'],
                        'customer_chip'      => $item['customer_chip'],
                        'customer_facebook'  => $item['customer_facebook'],
                        'ref1_name'          => $item['references']['ref1_name'],
                        'ref1_phone'         => $item['references']['ref1_phone'],
                        'ref2_name'          => $item['references']['ref2_name'],
                        'ref2_phone'         => $item['references']['ref2_phone'],
                        'ref3_name'          => $item['references']['ref3_name'],
                        'ref3_phone'         => $item['references']['ref3_phone'],
                        'price'              => $item['price'],
                        'down_payment'       => $item['down_payment'],
                        'enganche_descuento' => $item['enganche_descuento'],
                        'credit_amount'      => $item['credit_amount'],
                        'abono_semanal'      => $item['abono_semanal'],
                        'term_months'        => $item['term_months'],
                        'term_weeks'         => $item['term_weeks'],
                        'tag_contrato'       => $item['tag_contrato'],
                        'sale_date'          => $saleDate,
                        'status'             => 'activa',
                    ];

                    $sale = new FinSale($saleData);
                    $sale->created_at = $saleDate;
                    $sale->updated_at = now();
                    $sale->save();

                    $importedCount++;
                }
            });

            try {
                event(new FinancierasUpdated(
                    'ventas',
                    'created',
                    "Importación histórica completada: {$importedCount} ventas registradas.",
                    ['imported' => $importedCount]
                ));
            } catch (\Throwable $e) {
                \Log::warning('Reverb broadcast warning: ' . $e->getMessage());
            }

            return redirect()->route('financieras.index', ['tab' => 'ventas'])
                ->with('success', "Se importaron exitosamente {$importedCount} ventas históricas y sus equipos vinculados.");
        } catch (\Exception $e) {
            return back()->with('error', 'Error al procesar el archivo: ' . $e->getMessage());
        }
    }
}
