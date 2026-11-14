<?php

namespace App\Http\Controllers\Dashboard;

use App\Http\Controllers\Controller;
use App\Http\Controllers\Traits\ModulePermissionTrait;
use App\Models\Branch;
use App\Models\Customer;
use App\Models\FinSupplier;
use App\Models\Gift;
use App\Models\GiftMovement;
use App\Models\GiftStock;
use App\Models\GiftTransfer;
use App\Models\Supplier;
use App\Services\GiftInventoryService;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Validation\ValidationException;

class GiftController extends Controller
{
    use ModulePermissionTrait;

    protected ?string $permissionResource = 'gifts';

    protected array $permissionMapping = [
        'index' => 'read',
        'store' => 'create',
        'registerEntry' => 'create',
        'transfer' => 'transfer',
        'registerDelivery' => 'deliver',
        'update' => 'edit',
        'destroy' => 'delete',
    ];

    protected function canAccessAllBranches(): bool
    {
        return auth()->user()?->can('gifts.all_branches') ?? false;
    }

    protected function resolveDeliveryBranchId(Request $request): ?int
    {
        if ($this->canAccessAllBranches()) {
            $requested = $request->input('branch_id');
            if ($requested !== null && $requested !== '' && $requested !== 'all' && $requested !== 'general' && ctype_digit((string) $requested)) {
                return (int) $requested;
            }
        }

        return auth()->user()?->branch_id ? (int) auth()->user()->branch_id : null;
    }

    public function index(Request $request)
    {
        $canAllBranches = $this->canAccessAllBranches();
        $userBranchId = auth()->user()?->branch_id ? (int) auth()->user()->branch_id : null;
        $requestedBranch = (string) $request->input('branch_id', $canAllBranches ? 'all' : (string) ($userBranchId ?: 'all'));

        $onlyGeneral = false;
        $activeBranchId = null;

        if (!$canAllBranches) {
            $activeBranchId = $userBranchId;
            $branchScope = $userBranchId ? (string) $userBranchId : 'all';
        } elseif ($requestedBranch === 'general') {
            $onlyGeneral = true;
            $branchScope = 'general';
        } elseif ($requestedBranch !== '' && $requestedBranch !== 'all' && ctype_digit($requestedBranch) && (int) $requestedBranch > 0) {
            $activeBranchId = (int) $requestedBranch;
            $branchScope = (string) $activeBranchId;
        } else {
            $branchScope = 'all';
        }

        $q = trim((string) $request->input('q', ''));
        $statusFilter = trim((string) $request->input('status', ''));
        $supplierFilter = trim((string) $request->input('supplier_filter', ''));
        $movementTypeFilter = trim((string) $request->input('movement_type', ''));
        $onlyWithStock = $request->boolean('only_with_stock');

        $giftsQuery = Gift::with(['supplier', 'finSupplier', 'stocks.branch'])
            ->when($q !== '', function ($query) use ($q) {
                $query->where(function ($sub) use ($q) {
                    $sub->where('name', 'like', "%{$q}%")
                        ->orWhere('code', 'like', "%{$q}%")
                        ->orWhere('brand', 'like', "%{$q}%")
                        ->orWhere('supplier_name', 'like', "%{$q}%");
                });
            })
            ->when(in_array($statusFilter, ['activo', 'inactivo'], true), fn ($query) => $query->where('status', $statusFilter))
            ->when($supplierFilter !== '', function ($query) use ($supplierFilter) {
                if (str_starts_with($supplierFilter, 'fin:')) {
                    $query->where('fin_supplier_id', (int) substr($supplierFilter, 4));
                } elseif (str_starts_with($supplierFilter, 'sup:')) {
                    $query->where('supplier_id', (int) substr($supplierFilter, 4));
                }
            })
            ->when($onlyGeneral && $onlyWithStock, fn ($query) => $query->where('stock_quantity', '>', 0))
            ->when($activeBranchId !== null && $onlyWithStock, function ($query) use ($activeBranchId) {
                $query->whereHas('stocks', fn ($sq) => $sq->where('branch_id', $activeBranchId)->where('stock_quantity', '>', 0));
            })
            ->orderBy('name');

        $gifts = $giftsQuery->paginate(15)->withQueryString();

        $transfers = GiftTransfer::with(['gift', 'fromBranch', 'toBranch', 'user'])
            ->when($q !== '', function ($query) use ($q) {
                $query->where(function ($sub) use ($q) {
                    $sub->where('transfer_code', 'like', "%{$q}%")
                        ->orWhere('notes', 'like', "%{$q}%")
                        ->orWhereHas('gift', fn ($gq) => $gq->where('name', 'like', "%{$q}%")->orWhere('code', 'like', "%{$q}%"));
                });
            })
            ->when($onlyGeneral, fn ($query) => $query->whereNull('from_branch_id'))
            ->when($activeBranchId !== null, function ($query) use ($activeBranchId) {
                $query->where(fn ($sub) => $sub->where('from_branch_id', $activeBranchId)->orWhere('to_branch_id', $activeBranchId));
            })
            ->orderByDesc('transferred_at')
            ->orderByDesc('id')
            ->paginate(12, ['*'], 'transfer_page')
            ->withQueryString();

        $movements = GiftMovement::with(['gift', 'branch', 'user', 'transfer'])
            ->when($q !== '', function ($query) use ($q) {
                $query->where(function ($sub) use ($q) {
                    $sub->where('customer_name', 'like', "%{$q}%")
                        ->orWhere('reference_code', 'like', "%{$q}%")
                        ->orWhere('notes', 'like', "%{$q}%")
                        ->orWhereHas('gift', fn ($gq) => $gq->where('name', 'like', "%{$q}%")->orWhere('code', 'like', "%{$q}%"));
                });
            })
            ->when($movementTypeFilter !== '', fn ($query) => $query->where('movement_type', $movementTypeFilter))
            ->when($onlyGeneral, fn ($query) => $query->whereNull('branch_id'))
            ->when($activeBranchId !== null, fn ($query) => $query->where('branch_id', $activeBranchId))
            ->orderByDesc('movement_date')
            ->orderByDesc('id')
            ->paginate(20, ['*'], 'mov_page')
            ->withQueryString();

        $stats = [
            'total_catalog' => Gift::where('status', 'activo')->count(),
            'central_stock' => (int) Gift::where('status', 'activo')->sum('stock_quantity'),
            'branch_stock' => $activeBranchId
                ? (int) GiftStock::where('branch_id', $activeBranchId)->sum('stock_quantity')
                : (int) GiftStock::sum('stock_quantity'),
            'transfers_month' => (int) GiftTransfer::whereMonth('transferred_at', now()->month)
                ->whereYear('transferred_at', now()->year)
                ->when($activeBranchId !== null, fn ($query) => $query->where(fn ($sub) => $sub->where('from_branch_id', $activeBranchId)->orWhere('to_branch_id', $activeBranchId)))
                ->sum('quantity'),
            'deliveries_month' => (int) GiftMovement::where('movement_type', 'entrega_cliente')
                ->whereMonth('movement_date', now()->month)
                ->whereYear('movement_date', now()->year)
                ->when($activeBranchId !== null, fn ($query) => $query->where('branch_id', $activeBranchId))
                ->sum('quantity'),
            'entries_month' => (int) GiftMovement::where('movement_type', 'entrada_proveedor')
                ->whereMonth('movement_date', now()->month)
                ->whereYear('movement_date', now()->year)
                ->sum('quantity'),
        ];

        $masterCatalog = Gift::with(['supplier', 'finSupplier', 'stocks.branch'])
            ->where('status', 'activo')
            ->orderBy('name')
            ->get();

        $branches = Branch::orderBy('name')->get();
        $suppliers = Supplier::orderBy('name')->get();
        $finSuppliers = FinSupplier::where('is_active', true)->orderBy('name')->get();
        $customers = Customer::orderBy('tit_name')->limit(500)->get(['id', 'tit_name', 'tit_phone']);
        $selectedBranchObj = $activeBranchId ? $branches->firstWhere('id', $activeBranchId) : null;

        return view('gifts.index', compact(
            'gifts',
            'transfers',
            'movements',
            'stats',
            'masterCatalog',
            'branches',
            'suppliers',
            'finSuppliers',
            'customers',
            'canAllBranches',
            'userBranchId',
            'branchScope',
            'activeBranchId',
            'onlyGeneral',
            'selectedBranchObj',
            'q',
            'statusFilter',
            'supplierFilter',
            'movementTypeFilter',
            'onlyWithStock'
        ));
    }

    /**
     * Alta única en Catálogo Maestro (Inventario General).
     */
    public function store(Request $request)
    {
        $validated = $request->validate([
            'code' => 'nullable|string|max:50',
            'name' => 'required|string|max:160',
            'brand' => 'nullable|string|max:100',
            'description' => 'nullable|string|max:500',
            'supplier_id' => 'nullable|exists:suppliers,id',
            'fin_supplier_id' => 'nullable|exists:fin_suppliers,id',
            'supplier_name' => 'nullable|string|max:120',
            'stock_quantity' => 'nullable|integer|min:0',
            'min_stock' => 'nullable|integer|min:0',
            'unit_cost' => 'nullable|numeric|min:0',
            'notes' => 'nullable|string|max:500',
        ]);

        $gift = GiftInventoryService::createMasterGift($validated, auth()->id());

        return back()->with(
            'success',
            "Regalo '{$gift->name}' ({$gift->code}) registrado en el Catálogo Maestro e Inventario General correctamente."
        );
    }

    /**
     * Registrar recepción de piezas al Inventario General (Almacén Matriz).
     */
    public function registerEntry(Request $request)
    {
        $validated = $request->validate([
            'gift_id' => 'required|exists:gifts,id',
            'quantity' => 'required|integer|min:1',
            'unit_cost' => 'nullable|numeric|min:0',
            'reference_code' => 'nullable|string|max:60',
            'notes' => 'nullable|string|max:500',
        ]);

        $result = GiftInventoryService::addCentralEntry(
            (int) $validated['gift_id'],
            (int) $validated['quantity'],
            [
                'unit_cost' => $validated['unit_cost'] ?? null,
                'reference_code' => $validated['reference_code'] ?? null,
                'notes' => $validated['notes'] ?? null,
            ],
            auth()->id()
        );

        return back()->with(
            'success',
            "Entrada de +{$validated['quantity']} pzas de '{$result['gift']->name}' al Inventario General registrada (Stock General actual: {$result['new_stock']})."
        );
    }

    /**
     * Registrar traspaso de Inventario General (o sucursal origen) hacia sucursal destino.
     */
    public function transfer(Request $request)
    {
        $validated = $request->validate([
            'gift_id' => 'required|exists:gifts,id',
            'from_branch_id' => 'nullable|integer|exists:branches,id',
            'to_branch_id' => 'required|integer|exists:branches,id',
            'quantity' => 'required|integer|min:1',
            'notes' => 'nullable|string|max:500',
        ]);

        $fromBranchId = !empty($validated['from_branch_id']) ? (int) $validated['from_branch_id'] : null;

        if ($fromBranchId !== null && !$this->canAccessAllBranches()) {
            $userBranchId = auth()->user()?->branch_id ? (int) auth()->user()->branch_id : null;
            if ($userBranchId !== $fromBranchId) {
                abort(403, 'No tienes permiso para traspasar desde otra sucursal.');
            }
        }

        $transfer = GiftInventoryService::transfer(
            (int) $validated['gift_id'],
            $fromBranchId,
            (int) $validated['to_branch_id'],
            (int) $validated['quantity'],
            $validated['notes'] ?? null,
            auth()->id()
        );

        return back()->with(
            'success',
            "Traspaso {$transfer->transfer_code} completado: {$transfer->quantity} pzas de '{$transfer->gift->name}' de {$transfer->origin_label} a {$transfer->destination_label}."
        );
    }

    /**
     * Registrar entrega de Gift a cliente descontando exclusivamente del stock de la sucursal.
     */
    public function registerDelivery(Request $request)
    {
        $validated = $request->validate([
            'gift_id' => 'required|exists:gifts,id',
            'branch_id' => 'nullable|integer|exists:branches,id',
            'quantity' => 'required|integer|min:1',
            'customer_id' => 'nullable|integer|exists:customers,id',
            'customer_name' => 'nullable|string|max:160',
            'customer_phone' => 'nullable|string|max:40',
            'notes' => 'nullable|string|max:500',
        ]);

        $branchId = $this->resolveDeliveryBranchId($request);
        if (!$branchId) {
            throw ValidationException::withMessages([
                'branch_id' => 'Debes seleccionar la sucursal desde la cual se entrega el regalo al cliente.',
            ]);
        }

        $customerName = trim((string) ($validated['customer_name'] ?? ''));
        $customerPhone = trim((string) ($validated['customer_phone'] ?? ''));

        if (!empty($validated['customer_id'])) {
            $customer = Customer::find((int) $validated['customer_id']);
            if ($customer) {
                $customerName = $customer->tit_name ?: $customerName;
                $customerPhone = $customerPhone !== '' ? $customerPhone : ($customer->tit_phone ?? '');
            }
        }

        $movement = GiftInventoryService::deliverFromBranch(
            (int) $validated['gift_id'],
            $branchId,
            (int) $validated['quantity'],
            [
                'customer_name' => $customerName !== '' ? $customerName : 'Cliente General',
                'customer_phone' => $customerPhone !== '' ? $customerPhone : null,
                'reference_module' => 'gifts',
                'notes' => $validated['notes'] ?? null,
            ],
            auth()->id()
        );

        $branchName = $movement->branch?->name ?? 'la sucursal';

        return back()->with(
            'success',
            "Entrega de {$movement->quantity} pza(s) de '{$movement->gift->name}' registrada en {$branchName} (Stock restante en sucursal: {$movement->new_stock})."
        );
    }

    public function update(Request $request, Gift $gift)
    {
        $validated = $request->validate([
            'code' => 'nullable|string|max:50',
            'name' => 'required|string|max:160',
            'brand' => 'nullable|string|max:100',
            'description' => 'nullable|string|max:500',
            'supplier_id' => 'nullable|exists:suppliers,id',
            'fin_supplier_id' => 'nullable|exists:fin_suppliers,id',
            'supplier_name' => 'nullable|string|max:120',
            'min_stock' => 'nullable|integer|min:0',
            'unit_cost' => 'nullable|numeric|min:0',
            'status' => 'nullable|in:activo,inactivo',
        ]);

        DB::transaction(function () use ($gift, $validated) {
            $locked = Gift::where('id', $gift->id)->lockForUpdate()->firstOrFail();

            $duplicate = GiftInventoryService::findDuplicateByName($validated['name'], $locked->id);
            if ($duplicate) {
                throw ValidationException::withMessages([
                    'name' => "Ya existe otro regalo llamado '{$duplicate->name}' ({$duplicate->code}) en el Catálogo Maestro.",
                ]);
            }

            $code = trim((string) ($validated['code'] ?? ''));
            if ($code !== '' && $code !== $locked->code) {
                if (Gift::where('code', $code)->where('id', '!=', $locked->id)->exists()) {
                    throw ValidationException::withMessages([
                        'code' => "El código '{$code}' ya pertenece a otro regalo del Catálogo Maestro.",
                    ]);
                }
                $locked->code = $code;
            }

            $locked->name = GiftInventoryService::cleanName($validated['name']);
            $locked->brand = filled($validated['brand'] ?? null) ? trim((string) $validated['brand']) : null;
            $locked->description = filled($validated['description'] ?? null) ? trim((string) $validated['description']) : null;
            $locked->supplier_id = !empty($validated['supplier_id']) ? (int) $validated['supplier_id'] : null;
            $locked->fin_supplier_id = !empty($validated['fin_supplier_id']) ? (int) $validated['fin_supplier_id'] : null;
            $locked->supplier_name = filled($validated['supplier_name'] ?? null) ? trim((string) $validated['supplier_name']) : null;
            $locked->min_stock = isset($validated['min_stock']) ? max(0, (int) $validated['min_stock']) : $locked->min_stock;
            $locked->unit_cost = isset($validated['unit_cost']) ? max(0, (float) $validated['unit_cost']) : $locked->unit_cost;
            $locked->status = $validated['status'] ?? $locked->status;
            $locked->save();
        });

        return back()->with('success', 'Información del regalo en el Catálogo Maestro actualizada correctamente.');
    }

    public function destroy(Gift $gift)
    {
        if ($gift->total_global_stock > 0 || $gift->movements()->exists() || $gift->transfers()->exists()) {
            return back()->with(
                'error',
                "No se puede eliminar '{$gift->name}' porque tiene stock activo ({$gift->total_global_stock} pzas globales) o historial de movimientos/traspasos. Cámbialo a estado 'Inactivo' desde Editar."
            );
        }

        $gift->stocks()->delete();
        $gift->delete();

        return back()->with('success', 'Gift eliminado del Catálogo Maestro.');
    }
}
