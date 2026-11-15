<?php

namespace App\Http\Controllers\Dashboard;

use App\Http\Controllers\Controller;
use App\Http\Controllers\Traits\ModulePermissionTrait;
use App\Models\Branch;
use App\Models\Category;
use App\Models\Product;
use App\Models\Supplier;
use App\Services\ProductImportExportService;
use Exception;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Redirect;
use Illuminate\Support\Facades\Storage;
use Illuminate\Validation\ValidationException;
use PhpOffice\PhpSpreadsheet\Writer\Xlsx;
use Picqer\Barcode\BarcodeGeneratorHTML;
use Picqer\Barcode\BarcodeGeneratorSVG;

class ProductController extends Controller
{
    use ModulePermissionTrait;

    protected ?string $permissionResource = 'product';

    protected array $permissionMapping = [
        'index' => 'read',
        'show' => 'read',
        'barcodeLabels' => 'read',
        'create' => 'create',
        'store' => 'create',
        'edit' => 'edit',
        'update' => 'edit',
        'destroy' => 'delete',
        'importView' => 'import',
        'importStore' => 'import',
        'downloadTemplate' => 'import',
        'exportData' => 'export',
        'exportExcel' => 'export',
    ];

    public function __construct(protected ProductImportExportService $importExportService)
    {
        $this->initializeModulePermission();
    }

    /**
     * Listado multisucursal de productos con filtros y ordenamiento.
     */
    public function index()
    {
        $row = (int) request('row', 10);

        if ($row < 1 || $row > 100000) {
            abort(400, 'El parámetro de registros por página debe estar entre 1 y 100000.');
        }

        $user = auth()->user();
        $canAllBranches = Product::userCanAccessAllBranches($user);
        $selectedBranchId = $canAllBranches ? request('branch_id') : $user?->branch_id;

        $products = Product::with(['branch', 'category', 'supplier'])
            ->forUserBranch($user, request('branch_id'))
            ->filter(request(['search', 'category_id']))
            ->sortable()
            ->orderByDesc('id')
            ->paginate($row)
            ->appends(request()->query());

        return view('products.index', [
            'products' => $products,
            'branches' => $this->accessibleBranches($user),
            'categories' => Category::orderBy('name')->get(),
            'canAllBranches' => $canAllBranches,
            'selectedBranchId' => $selectedBranchId,
        ]);
    }

    /**
     * Formulario de creación de producto.
     */
    public function create()
    {
        $user = auth()->user();
        $canAllBranches = Product::userCanAccessAllBranches($user);

        return view('products.create', [
            'branches' => $this->accessibleBranches($user),
            'categories' => Category::orderBy('name')->get(),
            'suppliers' => Supplier::orderBy('name')->get(),
            'canAllBranches' => $canAllBranches,
            'userBranch' => $user?->branch_id ? Branch::find($user->branch_id) : null,
        ]);
    }

    /**
     * Guarda un nuevo producto respetando la lógica multisucursal.
     */
    public function store(Request $request)
    {
        $user = auth()->user();
        $canAllBranches = Product::userCanAccessAllBranches($user);

        $rules = [
            'product_image' => 'nullable|file|mimes:jpeg,png,jpg,gif,pdf,webp|max:1024',
            'product_name' => 'required|string|max:255',
            'product_code' => ['nullable', 'string', 'max:100', 'regex:/^[\x20-\x7E]+$/'],
            'imei' => 'nullable|string|max:100',
            'branch_id' => $canAllBranches ? 'required|integer|exists:branches,id' : 'nullable|integer',
            'category_id' => 'required|integer|exists:categories,id',
            'supplier_id' => 'required|integer|exists:suppliers,id',
            'product_garage' => 'nullable|string|max:255',
            'product_store' => 'nullable|string|max:255',
            'buying_date' => 'nullable|date_format:Y-m-d|max:10',
            'expire_date' => 'nullable|date_format:Y-m-d|max:10',
            'buying_price' => 'required|numeric|min:0',
            'selling_price' => 'required|numeric|min:0',
            'stock_quantity' => 'required|integer|min:0',
            'brand' => 'nullable|string|max:120',
            'model' => 'nullable|string|max:120',
            'category_status' => 'nullable|string|max:120',
            'warranty_time' => 'nullable|string|max:120',
            'observations' => 'nullable|string|max:2000',
        ];

        $validatedData = $request->validate($rules);
        $branchId = $this->resolveBranchIdForWrite($request);
        $validatedData['branch_id'] = $branchId;

        $productCode = trim((string) ($validatedData['product_code'] ?? ''));
        if ($productCode === '') {
            $productCode = $this->importExportService->generateUniqueCodeForBranch();
        } else {
            $this->ensureUniqueCodeInBranch($productCode, $branchId);
        }
        $validatedData['product_code'] = $productCode;

        $imei = trim((string) ($validatedData['imei'] ?? ''));
        if ($imei !== '') {
            $this->ensureUniqueImei($imei);
            $validatedData['imei'] = $imei;
        } else {
            $validatedData['imei'] = null;
        }

        if ($file = $request->file('product_image')) {
            $fileName = hexdec(uniqid()) . '.' . $file->getClientOriginalExtension();
            $file->storeAs('public/products/', $fileName);
            $validatedData['product_image'] = $fileName;
        }

        Product::create($validatedData);

        return Redirect::route('products.index')->with('success', 'El producto ha sido creado correctamente.');
    }

    /**
     * Muestra el detalle del producto si pertenece al alcance de sucursal del usuario.
     */
    public function show(Product $product)
    {
        $this->authorizeProductBranch($product);

        $codeForBarcode = $this->ensurePersistedProductCode($product);
        $generator = new BarcodeGeneratorHTML();
        $barcodeSafeCode = Product::toBarcodeSafeText($codeForBarcode) ?: ('ID-' . $product->id);

        return view('products.show', [
            'product' => $product->load(['branch', 'category', 'supplier']),
            'barcode' => $generator->getBarcode($barcodeSafeCode, $generator::TYPE_CODE_128),
            'barcodeSvg' => $this->renderInlineBarcodeSvg($barcodeSafeCode, 2.0, 52),
            'barcodeCode' => $codeForBarcode,
        ]);
    }

    /**
     * Vista de configuración e impresión de etiquetas con código de barras (por stock o cantidad manual).
     */
    public function barcodeLabels(Request $request, Product $product)
    {
        $this->authorizeProductBranch($product);

        $product->load(['branch', 'category', 'supplier']);
        $barcodeCode = $this->ensurePersistedProductCode($product);
        $barcodeSafeCode = Product::toBarcodeSafeText($barcodeCode) ?: ('ID-' . $product->id);
        $stockQty = max(0, (int) $product->stock_quantity);

        $requestedMode = (string) $request->input('mode', '');
        $defaultMode = in_array($requestedMode, ['stock', 'manual'], true)
            ? $requestedMode
            : ($stockQty > 0 ? 'stock' : 'manual');

        $requestedQuantity = $request->filled('quantity')
            ? max(1, min(500, (int) $request->input('quantity')))
            : ($defaultMode === 'stock' && $stockQty > 0 ? min(500, $stockQty) : 1);

        $preset = (string) $request->input('preset', 'sheet');
        if (!in_array($preset, ['sheet', 'thermal_50x25', 'thermal_58x30', 'thermal_40x25'], true)) {
            $preset = 'sheet';
        }

        return view('products.barcode-labels', [
            'product' => $product,
            'barcodeCode' => $barcodeCode,
            'barcodeSvg' => $this->renderInlineBarcodeSvg($barcodeSafeCode, 1.8, 44),
            'stockQty' => $stockQty,
            'initialMode' => $defaultMode,
            'initialQuantity' => $requestedQuantity,
            'initialPreset' => $preset,
        ]);
    }

    /**
     * Formulario de edición de producto.
     */
    public function edit(Product $product)
    {
        $this->authorizeProductBranch($product);

        $user = auth()->user();
        $canAllBranches = Product::userCanAccessAllBranches($user);

        return view('products.edit', [
            'branches' => $this->accessibleBranches($user),
            'categories' => Category::orderBy('name')->get(),
            'suppliers' => Supplier::orderBy('name')->get(),
            'product' => $product,
            'canAllBranches' => $canAllBranches,
            'userBranch' => $user?->branch_id ? Branch::find($user->branch_id) : null,
        ]);
    }

    /**
     * Actualiza un producto validando sucursal y campos completos.
     */
    public function update(Request $request, Product $product)
    {
        $this->authorizeProductBranch($product);

        $user = auth()->user();
        $canAllBranches = Product::userCanAccessAllBranches($user);

        $rules = [
            'product_image' => 'nullable|file|mimes:jpeg,png,jpg,gif,pdf,webp|max:1024',
            'product_name' => 'required|string|max:255',
            'product_code' => ['nullable', 'string', 'max:100', 'regex:/^[\x20-\x7E]+$/'],
            'imei' => 'nullable|string|max:100',
            'branch_id' => $canAllBranches ? 'required|integer|exists:branches,id' : 'nullable|integer',
            'category_id' => 'required|integer|exists:categories,id',
            'supplier_id' => 'required|integer|exists:suppliers,id',
            'product_garage' => 'nullable|string|max:255',
            'product_store' => 'nullable|string|max:255',
            'buying_date' => 'nullable|date_format:Y-m-d|max:10',
            'expire_date' => 'nullable|date_format:Y-m-d|max:10',
            'buying_price' => 'required|numeric|min:0',
            'selling_price' => 'required|numeric|min:0',
            'stock_quantity' => 'required|integer|min:0',
            'brand' => 'nullable|string|max:120',
            'model' => 'nullable|string|max:120',
            'category_status' => 'nullable|string|max:120',
            'warranty_time' => 'nullable|string|max:120',
            'observations' => 'nullable|string|max:2000',
        ];

        $validatedData = $request->validate($rules);
        $branchId = $canAllBranches
            ? $this->resolveBranchIdForWrite($request)
            : (int) ($user?->branch_id ?: $product->branch_id);
        $validatedData['branch_id'] = $branchId;

        $productCode = trim((string) ($validatedData['product_code'] ?? ''));
        if ($productCode === '') {
            $productCode = $product->product_code ?: $this->importExportService->generateUniqueCodeForBranch();
        } else {
            $this->ensureUniqueCodeInBranch($productCode, $branchId, $product->id);
        }
        $validatedData['product_code'] = $productCode;

        $imei = trim((string) ($validatedData['imei'] ?? ''));
        if ($imei !== '') {
            $this->ensureUniqueImei($imei, $product->id);
            $validatedData['imei'] = $imei;
        } else {
            $validatedData['imei'] = null;
        }

        if ($file = $request->file('product_image')) {
            $fileName = hexdec(uniqid()) . '.' . $file->getClientOriginalExtension();
            $path = 'public/products/';

            if ($product->product_image) {
                Storage::delete($path . $product->product_image);
            }

            $file->storeAs($path, $fileName);
            $validatedData['product_image'] = $fileName;
        }

        $product->update($validatedData);

        return Redirect::route('products.index')->with('success', 'El producto se ha actualizado correctamente.');
    }

    /**
     * Elimina un producto verificando alcance de sucursal.
     */
    public function destroy(Product $product)
    {
        $this->authorizeProductBranch($product);

        if ($product->product_image) {
            Storage::delete('public/products/' . $product->product_image);
        }

        $product->delete();

        return Redirect::route('products.index')->with('success', 'Producto eliminado correctamente.');
    }

    /**
     * Pantalla de importación con guía visual de columnas y descarga de plantilla.
     */
    public function importView()
    {
        $user = auth()->user();
        $canAllBranches = Product::userCanAccessAllBranches($user);

        return view('products.import', [
            'columns' => $this->importExportService->templateColumns(),
            'branches' => $this->accessibleBranches($user),
            'canAllBranches' => $canAllBranches,
            'userBranch' => $user?->branch_id ? Branch::find($user->branch_id) : null,
            'categoriesCount' => Category::count(),
            'suppliersCount' => Supplier::count(),
        ]);
    }

    /**
     * Descarga la plantilla oficial Excel (.xlsx) con guía y catálogos incluidos.
     */
    public function downloadTemplate()
    {
        $spreadsheet = $this->importExportService->generateTemplateSpreadsheet(auth()->user());

        return response()->streamDownload(function () use ($spreadsheet) {
            $writer = new Xlsx($spreadsheet);
            $writer->save('php://output');
        }, 'Plantilla_Importacion_Productos.xlsx', [
            'Content-Type' => 'application/vnd.openxmlformats-officedocument.spreadsheetml.sheet',
            'Cache-Control' => 'max-age=0',
        ]);
    }

    /**
     * Procesa la importación masiva Excel de forma atómica con validaciones multisucursal.
     */
    public function importStore(Request $request)
    {
        $request->validate([
            'upload_file' => 'required|file|mimes:xls,xlsx|max:10240',
            'default_branch_id' => 'nullable|integer|exists:branches,id',
            'duplicate_mode' => 'nullable|in:reject,update',
        ], [
            'upload_file.required' => 'Selecciona un archivo Excel (.xlsx o .xls) para importar.',
            'upload_file.mimes' => 'El archivo debe tener extensión .xlsx o .xls.',
        ]);

        try {
            $defaultBranchId = $request->filled('default_branch_id')
                ? (int) $request->input('default_branch_id')
                : null;
            $duplicateMode = (string) $request->input('duplicate_mode', 'reject');

            $result = $this->importExportService->importFromPath(
                $request->file('upload_file')->getRealPath(),
                auth()->user(),
                $defaultBranchId,
                $duplicateMode
            );

            if (!$result['ok']) {
                return Redirect::back()
                    ->withInput()
                    ->with('error', 'La importación fue cancelada porque se detectaron errores en el archivo (no se guardaron registros parciales).')
                    ->with('import_errors', $result['errors']);
            }

            $parts = [];
            if ($result['inserted'] > 0) {
                $parts[] = "{$result['inserted']} producto(s) creado(s)";
            }
            if ($result['updated'] > 0) {
                $parts[] = "{$result['updated']} producto(s) actualizado(s)";
            }

            $message = 'Importación completada exitosamente: ' . implode(', ', $parts) . '.';

            return Redirect::route('products.index')->with('success', $message);
        } catch (Exception $e) {
            return Redirect::back()
                ->withInput()
                ->with('error', 'No se pudo procesar el archivo Excel: ' . $e->getMessage());
        }
    }

    /**
     * Exporta el catálogo visible (respetando sucursal y filtros activos) a Excel (.xlsx).
     */
    public function exportData(Request $request)
    {
        $products = Product::with(['branch', 'category', 'supplier'])
            ->forUserBranch(auth()->user(), $request->input('branch_id'))
            ->filter($request->only(['search', 'category_id']))
            ->orderByDesc('id')
            ->get();

        $spreadsheet = $this->importExportService->generateExportSpreadsheet($products);
        $fileName = 'Productos_' . now()->format('Ymd_His') . '.xlsx';

        return response()->streamDownload(function () use ($spreadsheet) {
            $writer = new Xlsx($spreadsheet);
            $writer->save('php://output');
        }, $fileName, [
            'Content-Type' => 'application/vnd.openxmlformats-officedocument.spreadsheetml.sheet',
            'Cache-Control' => 'max-age=0',
        ]);
    }

    /**
     * Verifica que el usuario pueda ver o modificar el producto según su sucursal.
     */
    protected function authorizeProductBranch(Product $product): void
    {
        $user = auth()->user();
        if (Product::userCanAccessAllBranches($user)) {
            return;
        }

        $userBranchId = (int) ($user?->branch_id ?? 0);
        if ($userBranchId <= 0 || (int) $product->branch_id !== $userBranchId) {
            abort(403, 'No tienes permiso para acceder a productos de otra sucursal.');
        }
    }

    /**
     * Obtiene la lista de sucursales accesibles para el usuario actual.
     */
    protected function accessibleBranches($user)
    {
        if (Product::userCanAccessAllBranches($user)) {
            return Branch::orderBy('name')->get();
        }

        if ($user && $user->branch_id) {
            return Branch::where('id', $user->branch_id)->get();
        }

        return collect();
    }

    /**
     * Resuelve y valida la sucursal destino al guardar o editar productos.
     */
    protected function resolveBranchIdForWrite(Request $request): int
    {
        $user = auth()->user();
        if (Product::userCanAccessAllBranches($user)) {
            $branchId = (int) $request->input('branch_id');
            if ($branchId <= 0) {
                throw ValidationException::withMessages([
                    'branch_id' => 'Selecciona una sucursal válida para el producto.',
                ]);
            }

            return $branchId;
        }

        $userBranchId = (int) ($user?->branch_id ?? 0);
        if ($userBranchId <= 0) {
            throw ValidationException::withMessages([
                'branch_id' => 'Tu usuario no tiene una sucursal asignada para registrar productos.',
            ]);
        }

        return $userBranchId;
    }

    /**
     * Evita códigos duplicados dentro de la misma sucursal.
     */
    protected function ensureUniqueCodeInBranch(string $productCode, int $branchId, ?int $ignoreId = null): void
    {
        $query = Product::where('branch_id', $branchId)
            ->whereRaw('LOWER(product_code) = ?', [mb_strtolower($productCode, 'UTF-8')]);

        if ($ignoreId) {
            $query->where('id', '!=', $ignoreId);
        }

        if ($query->exists()) {
            throw ValidationException::withMessages([
                'product_code' => "El código '{$productCode}' ya está registrado en esta sucursal.",
            ]);
        }
    }

    /**
     * Evita registrar el mismo IMEI/Serie en dos productos diferentes.
     */
    protected function ensureUniqueImei(string $imei, ?int $ignoreId = null): void
    {
        $query = Product::whereRaw('LOWER(imei) = ?', [mb_strtolower($imei, 'UTF-8')]);

        if ($ignoreId) {
            $query->where('id', '!=', $ignoreId);
        }

        if ($query->exists()) {
            throw ValidationException::withMessages([
                'imei' => "El IMEI/Serie '{$imei}' ya pertenece a otro producto.",
            ]);
        }
    }

    /**
     * Garantiza que todo producto tenga un product_code persistido y escaneable en POS.
     */
    protected function ensurePersistedProductCode(Product $product): string
    {
        $code = trim((string) ($product->product_code ?? ''));
        if ($code === '') {
            $code = $this->importExportService->generateUniqueCodeForBranch();
            $product->forceFill(['product_code' => $code])->saveQuietly();
        }

        return $code;
    }

    /**
     * Renderiza un SVG vectorial limpio (sin encabezado XML) para impresión nítida en etiquetas.
     */
    protected function renderInlineBarcodeSvg(string $code, float $widthFactor = 1.8, float $height = 46): string
    {
        $generator = new BarcodeGeneratorSVG();
        $safe = Product::toBarcodeSafeText($code);
        if ($safe === '') {
            $safe = '0000';
        }

        $svg = $generator->getBarcode($safe, $generator::TYPE_CODE_128, $widthFactor, $height);
        $svgPos = strpos($svg, '<svg');

        return $svgPos !== false ? substr($svg, $svgPos) : $svg;
    }
}
