<?php

namespace App\Http\Controllers\Dashboard;

use App\Http\Controllers\Controller;
use App\Http\Controllers\Traits\ModulePermissionTrait;
use App\Models\Branch;
use App\Models\Customer;
use App\Models\FinSupplier;
use App\Models\Gift;
use App\Models\GiftMovement;
use App\Models\Supplier;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;

class GiftController extends Controller
{
    use ModulePermissionTrait;

    protected ?string $permissionResource = 'gifts';

    protected array $permissionMapping = [
        'index' => 'read',
        'store' => 'create',
        'update' => 'edit',
        'destroy' => 'delete',
        'registerEntry' => 'create',
        'registerDelivery' => 'deliver',
    ];

    public function __construct()
    {
        $this->initializeModulePermission();
    }

    protected function canAccessAllBranches(): bool
    {
        return auth()->check() && (auth()->user()->isSuperAdmin() || auth()->user()->can('gifts.all_branches'));
    }

    protected function resolveBranchId(?Request $request = null): int
    {
        if ($request && $this->canAccessAllBranches() && $request->filled('branch_id')) {
            return (int) $request->input('branch_id');
        }
        return (int) (auth()->user()->branch_id ?? Branch::value('id') ?? 1);
    }

    public function index(Request $request)
    {
        $branchId = $this->resolveBranchId($request);
        $search = $request->input('search');
        $supplierFilter = $request->input('supplier_filter');

        $giftsQuery = Gift::with(['supplier', 'finSupplier', 'branch'])
            ->when(!$this->canAccessAllBranches() || $request->filled('branch_id'), function ($q) use ($branchId) {
                $q->where('branch_id', $branchId);
            })
            ->when($search, function ($q) use ($search) {
                $q->where(function ($sub) use ($search) {
                    $sub->where('name', 'like', "%{$search}%")
                        ->orWhere('code', 'like', "%{$search}%")
                        ->orWhere('brand', 'like', "%{$search}%")
                        ->orWhere('supplier_name', 'like', "%{$search}%");
                });
            })
            ->when($supplierFilter, function ($q) use ($supplierFilter) {
                $q->where('supplier_name', 'like', "%{$supplierFilter}%");
            })
            ->orderBy('name');

        $gifts = $giftsQuery->paginate(15)->withQueryString();

        $movements = GiftMovement::with(['gift', 'branch', 'user'])
            ->when(!$this->canAccessAllBranches() || $request->filled('branch_id'), function ($q) use ($branchId) {
                $q->where('branch_id', $branchId);
            })
            ->orderByDesc('movement_date')
            ->orderByDesc('id')
            ->paginate(20, ['*'], 'mov_page')
            ->withQueryString();

        $suppliers = Supplier::orderBy('name')->get(['id', 'name']);
        $finSuppliers = FinSupplier::where('is_active', true)->orderBy('name')->get(['id', 'name']);
        $branches = Branch::orderBy('name')->get();

        $stats = [
            'total_items' => Gift::when(!$this->canAccessAllBranches() || $request->filled('branch_id'), fn($q) => $q->where('branch_id', $branchId))->count(),
            'total_stock' => Gift::when(!$this->canAccessAllBranches() || $request->filled('branch_id'), fn($q) => $q->where('branch_id', $branchId))->sum('stock_quantity'),
            'deliveries_month' => GiftMovement::when(!$this->canAccessAllBranches() || $request->filled('branch_id'), fn($q) => $q->where('branch_id', $branchId))
                ->where('movement_type', 'entrega_cliente')
                ->whereMonth('movement_date', now()->month)
                ->sum('quantity'),
            'entries_month' => GiftMovement::when(!$this->canAccessAllBranches() || $request->filled('branch_id'), fn($q) => $q->where('branch_id', $branchId))
                ->where('movement_type', 'entrada_proveedor')
                ->whereMonth('movement_date', now()->month)
                ->sum('quantity'),
        ];

        $allGiftsForSelect = Gift::where('branch_id', $branchId)->orderBy('name')->get();
        $customers = Customer::orderBy('tit_name')->get(['id', 'tit_name', 'tit_phone']);

        return view('gifts.index', compact(
            'gifts',
            'movements',
            'suppliers',
            'finSuppliers',
            'branches',
            'branchId',
            'stats',
            'allGiftsForSelect',
            'customers'
        ));
    }

    public function store(Request $request)
    {
        $validated = $request->validate([
            'name' => 'required|string|max:190',
            'code' => 'nullable|string|max:80',
            'brand' => 'nullable|string|max:120',
            'description' => 'nullable|string|max:500',
            'supplier_id' => 'nullable|exists:suppliers,id',
            'fin_supplier_id' => 'nullable|exists:fin_suppliers,id',
            'supplier_name' => 'nullable|string|max:190',
            'branch_id' => 'nullable|exists:branches,id',
            'stock_quantity' => 'required|integer|min:0',
            'min_stock' => 'nullable|integer|min:0',
            'unit_cost' => 'nullable|numeric|min:0',
        ]);

        $branchId = $this->resolveBranchId($request);
        $code = !empty($validated['code']) ? strtoupper(trim($validated['code'])) : 'GIFT-' . strtoupper(substr(uniqid(), -6));

        $supplierName = $validated['supplier_name'] ?? null;
        if (empty($supplierName) && !empty($validated['fin_supplier_id'])) {
            $supplierName = FinSupplier::find($validated['fin_supplier_id'])?->name;
        }
        if (empty($supplierName) && !empty($validated['supplier_id'])) {
            $supplierName = Supplier::find($validated['supplier_id'])?->name;
        }

        DB::transaction(function () use ($validated, $branchId, $code, $supplierName) {
            $initialStock = (int) $validated['stock_quantity'];

            $gift = Gift::create([
                'code' => $code,
                'name' => $validated['name'],
                'brand' => $validated['brand'] ?? null,
                'description' => $validated['description'] ?? null,
                'supplier_id' => $validated['supplier_id'] ?? null,
                'fin_supplier_id' => $validated['fin_supplier_id'] ?? null,
                'supplier_name' => $supplierName,
                'branch_id' => $branchId,
                'stock_quantity' => $initialStock,
                'min_stock' => (int) ($validated['min_stock'] ?? 1),
                'unit_cost' => (float) ($validated['unit_cost'] ?? 0),
                'status' => 'activo',
            ]);

            if ($initialStock > 0) {
                GiftMovement::create([
                    'gift_id' => $gift->id,
                    'branch_id' => $branchId,
                    'user_id' => auth()->id(),
                    'movement_type' => 'entrada_proveedor',
                    'quantity' => $initialStock,
                    'previous_stock' => 0,
                    'new_stock' => $initialStock,
                    'supplier_name' => $supplierName ?? 'Inventario Inicial',
                    'notes' => 'Alta inicial en almacén de gifts por proveedor',
                    'movement_date' => now(),
                ]);
            }
        });

        return redirect()->route('gifts.index', ['branch_id' => $branchId])
            ->with('success', 'Gift / Promocional registrado en el inventario correctamente.');
    }

    /**
     * Registrar entrada de lote de Gifts enviado por un proveedor.
     */
    public function registerEntry(Request $request)
    {
        $validated = $request->validate([
            'gift_id' => 'required|exists:gifts,id',
            'quantity' => 'required|integer|min:1',
            'supplier_name' => 'nullable|string|max:190',
            'reference_code' => 'nullable|string|max:120',
            'notes' => 'nullable|string|max:500',
        ]);

        DB::transaction(function () use ($validated) {
            $gift = Gift::lockForUpdate()->findOrFail($validated['gift_id']);
            $prevStock = (int) $gift->stock_quantity;
            $qty = (int) $validated['quantity'];
            $newStock = $prevStock + $qty;

            $gift->update([
                'stock_quantity' => $newStock,
                'status' => 'activo',
                'supplier_name' => !empty($validated['supplier_name']) ? $validated['supplier_name'] : $gift->supplier_name,
            ]);

            GiftMovement::create([
                'gift_id' => $gift->id,
                'branch_id' => $gift->branch_id,
                'user_id' => auth()->id(),
                'movement_type' => 'entrada_proveedor',
                'quantity' => $qty,
                'previous_stock' => $prevStock,
                'new_stock' => $newStock,
                'supplier_name' => $validated['supplier_name'] ?: $gift->supplier_display_name,
                'reference_code' => $validated['reference_code'] ?? null,
                'notes' => $validated['notes'] ?? 'Recepción de lote promocional de proveedor',
                'movement_date' => now(),
            ]);
        });

        return back()->with('success', 'Entrada de stock de Gifts por proveedor registrada correctamente.');
    }

    /**
     * Registrar entrega de Gift a un cliente.
     */
    public function registerDelivery(Request $request)
    {
        $validated = $request->validate([
            'gift_id' => 'required|exists:gifts,id',
            'quantity' => 'required|integer|min:1',
            'customer_name' => 'required|string|max:190',
            'customer_phone' => 'nullable|string|max:40',
            'reference_module' => 'nullable|string|max:80',
            'reference_code' => 'nullable|string|max:120',
            'notes' => 'nullable|string|max:500',
        ]);

        return DB::transaction(function () use ($validated) {
            $gift = Gift::lockForUpdate()->findOrFail($validated['gift_id']);
            $prevStock = (int) $gift->stock_quantity;
            $qty = (int) $validated['quantity'];

            if ($prevStock < $qty) {
                return back()->withErrors(['quantity' => "Stock insuficiente para '{$gift->name}'. Disponible: {$prevStock}."]);
            }

            $newStock = $prevStock - $qty;
            $gift->update([
                'stock_quantity' => $newStock,
                'status' => 'activo',
            ]);

            GiftMovement::create([
                'gift_id' => $gift->id,
                'branch_id' => $gift->branch_id,
                'user_id' => auth()->id(),
                'movement_type' => 'entrega_cliente',
                'quantity' => $qty,
                'previous_stock' => $prevStock,
                'new_stock' => $newStock,
                'supplier_name' => $gift->supplier_display_name,
                'customer_name' => $validated['customer_name'],
                'customer_phone' => $validated['customer_phone'] ?? null,
                'reference_module' => $validated['reference_module'] ?? 'Entrega en Sucursal',
                'reference_code' => $validated['reference_code'] ?? null,
                'notes' => $validated['notes'] ?? 'Entrega de obsequio promocional a cliente',
                'movement_date' => now(),
            ]);

            return back()->with('success', "Se registró la entrega de {$qty}x '{$gift->name}' al cliente {$validated['customer_name']}.");
        });
    }

    public function update(Request $request, Gift $gift)
    {
        $validated = $request->validate([
            'name' => 'required|string|max:190',
            'brand' => 'nullable|string|max:120',
            'supplier_name' => 'nullable|string|max:190',
            'min_stock' => 'nullable|integer|min:0',
            'description' => 'nullable|string|max:500',
        ]);

        $gift->update($validated);

        return back()->with('success', 'Información del Gift actualizada correctamente.');
    }

    public function destroy(Gift $gift)
    {
        $gift->delete();
        return back()->with('success', 'Gift eliminado del catálogo.');
    }
}

