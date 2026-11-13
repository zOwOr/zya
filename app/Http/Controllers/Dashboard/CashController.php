<?php

namespace App\Http\Controllers\Dashboard;

use App\Console\Commands\AutoDailyCutCommand;
use App\Http\Controllers\Controller;
use App\Http\Controllers\Traits\ModulePermissionTrait;
use App\Models\Branch;
use App\Models\CashFlow;
use App\Models\DailyCut;
use App\Models\ExpenseType;
use App\Models\FinSalePayment;
use App\Models\Order;
use App\Models\OrderDetails;
use App\Models\PaymentHistory;
use App\Models\PosTransaction;
use App\Services\CashFlowService;
use Barryvdh\DomPDF\Facade\Pdf;
use Carbon\Carbon;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Str;
use PhpOffice\PhpSpreadsheet\Spreadsheet;
use PhpOffice\PhpSpreadsheet\Writer\Xlsx;

class CashController extends Controller
{
    use ModulePermissionTrait;

    protected ?string $permissionResource = 'cash';

    protected array $permissionMapping = [
        'index' => 'read',
        'dailyCut' => 'read',
        'store' => 'create',
        'applyPreCut' => 'pre_cut',
        'applyCut' => 'edit',
        'updateAutoCutConfig' => 'edit',
        'filterByDate' => 'read',
        'printCut' => 'read',
        'exportCutsPdf' => 'export',
        'exportCutsExcel' => 'export',
        'expensesReport' => 'read',
        'exportExpensesPdf' => 'export',
        'exportExpensesExcel' => 'export',
        'expenseTypesIndex' => 'read',
        'storeExpenseType' => 'edit',
        'updateExpenseType' => 'edit',
        'toggleExpenseType' => 'edit',
        'destroyExpenseType' => 'edit',
    ];

    public function __construct()
    {
        $this->initializeModulePermission();
    }

    protected function canAccessAllBranches(): bool
    {
        return auth()->check() && (auth()->user()->isSuperAdmin() || auth()->user()->can('cash.all_branches'));
    }

    protected function resolveBranchId(?Request $request = null): int
    {
        if ($request && $this->canAccessAllBranches() && $request->filled('branch_id') && $request->input('branch_id') !== 'all') {
            return (int) $request->input('branch_id');
        }
        return (int) (auth()->user()->branch_id ?? Branch::value('id') ?? 1);
    }

    /**
     * Resuelve el alcance de sucursales para consultas/reportes (una sucursal o 'all' para usuarios multi-sucursal).
     */
    protected function resolveBranchScope(Request $request, bool $defaultAllForMultiBranch = false): array
    {
        $canAll = $this->canAccessAllBranches();
        $requested = $request->input('branch_id');

        if ($canAll && ($requested === 'all' || ($defaultAllForMultiBranch && !$request->filled('branch_id')))) {
            $branchIds = Branch::pluck('id')->map(fn($id) => (int) $id)->all();
            return [
                'is_all' => true,
                'branch_id' => 'all',
                'branch_ids' => !empty($branchIds) ? $branchIds : [1],
                'label' => 'Todas las Sucursales',
            ];
        }

        $branchId = $this->resolveBranchId($request);
        $branchName = Branch::find($branchId)?->name ?? 'Principal';

        return [
            'is_all' => false,
            'branch_id' => $branchId,
            'branch_ids' => [$branchId],
            'label' => $branchName,
        ];
    }

    /**
     * Resumen de abonos a financieras en el periodo/corte (excluye cancelaciones y filas en ceros).
     */
    protected function buildFinancieraSummary(array $branchIds, ?string $startDate = null, ?string $endDate = null): array
    {
        $payments = FinSalePayment::with(['financiera', 'sale.financiera', 'posTransaction'])
            ->whereIn('branch_id', $branchIds)
            ->where(function ($q) {
                $q->whereNull('pos_transaction_id')
                    ->orWhereHas('posTransaction', fn($pt) => $pt->where('status', '!=', 'cancelado'));
            })
            ->when($startDate, fn($q) => $q->whereDate('payment_date', '>=', $startDate))
            ->when($endDate, fn($q) => $q->whereDate('payment_date', '<=', $endDate))
            ->get();

        $items = $payments
            ->groupBy(fn($p) => $p->financiera?->name ?? ($p->sale?->financiera?->name ?? 'Financiera'))
            ->map(function ($group, $finName) {
                $amount = round((float) $group->sum('amount'), 2);
                $commission = round((float) $group->sum('commission_amount'), 2);
                $total = round((float) $group->sum('total_amount'), 2);

                return [
                    'name' => $finName,
                    'count' => $group->count(),
                    'amount' => $amount,
                    'commission' => $commission,
                    'total' => $total,
                ];
            })
            ->filter(fn($row) => $row['count'] > 0 && ($row['total'] > 0 || $row['amount'] > 0))
            ->sortBy('name')
            ->values();

        return [
            'items' => $items->all(),
            'grand_count' => (int) $items->sum('count'),
            'grand_amount' => round((float) $items->sum('amount'), 2),
            'grand_commission' => round((float) $items->sum('commission'), 2),
            'grand_total' => round((float) $items->sum('total'), 2),
        ];
    }

    /**
     * Asegura que los días anteriores con movimientos tengan su Corte Final (Corte Z) si la sucursal tiene corte automático activo.
     */
    protected function ensurePastDaysAutoClosed(int $branchId): void
    {
        try {
            $branch = Branch::find($branchId);
            // Solo auto-cerrar días pasados si la sucursal tiene habilitado el corte automático
            if ($branch && $branch->auto_cut_enabled) {
                $yesterday = Carbon::yesterday()->toDateString();
                AutoDailyCutCommand::closeBranchDayIfPending($branchId, $yesterday, 'programado');
            }
        } catch (\Throwable $e) {
            // Continuar sin interrumpir la vista
        }
    }

    public function index(Request $request)
    {
        $perPage = 15;
        $date = $request->input('date');
        $branchScope = $this->resolveBranchScope($request, false);
        $isAllBranches = $branchScope['is_all'];
        $branchIds = $branchScope['branch_ids'];
        $branchId = $branchScope['branch_id'];

        if (!$isAllBranches) {
            $this->ensurePastDaysAutoClosed((int) $branchId);
        }

        $cashFlows = CashFlow::with(['user', 'branch', 'expenseType', 'externalCustomer'])
            ->where('branch_id', $branchId)
            ->when($date, fn($q) => $q->whereDate('created_at', $date))
            ->orderByDesc('created_at')
            ->paginate($perPage)
            ->withQueryString();

        $allForPeriod = CashFlow::with('externalCustomer')
            ->where('branch_id', $branchId)
            ->when($date, fn($q) => $q->whereDate('created_at', $date))
            ->get();

        $totalIncome = (float) $allForPeriod->where('type', 'income')->sum('amount');
        $totalExpense = (float) $allForPeriod->where('type', 'expense')->sum(fn($f) => $f->effective_cash_amount);
        $cashIncome = (float) $allForPeriod->where('type', 'income')->sum(fn($f) => $f->effective_cash_amount);
        $cardIncome = (float) $allForPeriod->where('type', 'income')->sum(fn($f) => $f->effective_card_amount);
        $transferIncome = (float) $allForPeriod->where('type', 'income')->sum(fn($f) => $f->effective_transfer_amount);
        $cashBalance = round($cashIncome - $totalExpense, 2);
        $balance = round($totalIncome - $totalExpense, 2);

        // Desglose por cliente del día/periodo consultado
        $customerMovements = $allForPeriod->groupBy(fn($f) => $f->customer_display_name)
            ->map(function ($items, $customerName) {
                $extId = $items->pluck('external_customer_id')->filter()->first();
                return [
                    'customer_name' => $customerName,
                    'external_customer_id' => $extId ? (int) $extId : null,
                    'is_external_customer' => $extId !== null,
                    'count' => $items->count(),
                    'income' => $items->where('type', 'income')->sum('amount'),
                    'expense' => $items->where('type', 'expense')->sum(fn($f) => $f->effective_cash_amount),
                    'cash' => $items->where('type', 'income')->sum(fn($f) => $f->effective_cash_amount),
                    'card' => $items->where('type', 'income')->sum(fn($f) => $f->effective_card_amount),
                    'transfer' => $items->where('type', 'income')->sum(fn($f) => $f->effective_transfer_amount),
                    'items' => $items,
                ];
            });

        $branches = $this->canAccessAllBranches()
            ? Branch::orderBy('name')->get()
            : Branch::where('id', $branchId)->get();

        $expenseSelector = ExpenseType::forBranchSelector((int) $branchId);
        $mostUsedExpenseTypes = $expenseSelector['mostUsed'];
        $allExpenseTypes = $expenseSelector['allTypes'];

        return view('cash.index', compact(
            'cashFlows',
            'date',
            'totalIncome',
            'totalExpense',
            'cashIncome',
            'cardIncome',
            'transferIncome',
            'cashBalance',
            'balance',
            'perPage',
            'customerMovements',
            'branches',
            'branchId',
            'mostUsedExpenseTypes',
            'allExpenseTypes'
        ));
    }

    public function dailyCut(Request $request)
    {
        $filterType = $request->input('filter_type');
        if (!$filterType) {
            if ($request->filled('start_date') || $request->filled('end_date')) {
                $filterType = 'rango';
            } elseif ($request->filled('date')) {
                $filterType = 'dia';
            } else {
                $filterType = 'dia';
            }
        }
        if (!in_array($filterType, ['dia', 'rango', 'todos'])) {
            $filterType = 'dia';
        }

        $selectedDate = $request->input('date') ? Carbon::parse($request->input('date'))->toDateString() : Carbon::today()->toDateString();
        $startDate = $request->input('start_date') ? Carbon::parse($request->input('start_date'))->toDateString() : Carbon::today()->subDays(7)->toDateString();
        $endDate = $request->input('end_date') ? Carbon::parse($request->input('end_date'))->toDateString() : Carbon::today()->toDateString();

        if ($startDate > $endDate) {
            $tmp = $startDate;
            $startDate = $endDate;
            $endDate = $tmp;
        }

        $branchScope = $this->resolveBranchScope($request, false);
        $isAllBranches = $branchScope['is_all'];
        $branchIds = $branchScope['branch_ids'];
        $branchId = $branchScope['branch_id'];

        if (!$isAllBranches) {
            $this->ensurePastDaysAutoClosed((int) $branchId);
        }

        $search = $request->input('search');

        $methodMap = [
            'efectivo' => 'HandCash',
            'transferencia' => 'Cheque',
            'tarjeta' => 'Due',
        ];

        $searchMethod = null;
        $searchLower = strtolower((string) $search);
        if (isset($methodMap[$searchLower])) {
            $searchMethod = $methodMap[$searchLower];
        }

        $specialProviderOrderIds = OrderDetails::whereHas('productRelation.supplier', function ($q) {
            $q->whereIn('name', ['Payjoy', 'KrediYa']);
        })->pluck('order_id')->unique()->toArray();

        $dateScope = function ($q) use ($filterType, $selectedDate, $startDate, $endDate) {
            if ($filterType === 'dia') {
                $q->whereDate('created_at', $selectedDate);
            } elseif ($filterType === 'rango') {
                $q->whereBetween('created_at', [
                    Carbon::parse($startDate)->startOfDay(),
                    Carbon::parse($endDate)->endOfDay()
                ]);
            }
        };

        $cutDateScope = function ($q) use ($filterType, $selectedDate, $startDate, $endDate) {
            if ($filterType === 'dia') {
                $q->whereDate('date', $selectedDate);
            } elseif ($filterType === 'rango') {
                $q->whereBetween('date', [$startDate, $endDate]);
            }
        };

        $specialProviderPayments = PaymentHistory::with('user')
            ->whereIn('branch_id', $branchIds)
            ->tap($dateScope)
            ->whereIn('order_id', $specialProviderOrderIds)
            ->get();

        $externalPayments = PaymentHistory::with('user')
            ->whereIn('branch_id', $branchIds)
            ->tap($dateScope)
            ->whereIn('method', ['Cheque', 'Due'])
            ->whereNotIn('order_id', $specialProviderOrderIds)
            ->get();

        $applyCashSearch = function ($q) use ($search) {
            $q->where(function ($sub) use ($search) {
                $sub->where('customer_name', 'like', "%{$search}%")
                    ->orWhere('description', 'like', "%{$search}%")
                    ->orWhere('reference', 'like', "%{$search}%")
                    ->orWhere('module', 'like', "%{$search}%")
                    ->orWhere('expense_type_name', 'like', "%{$search}%")
                    ->orWhere('payment_method', 'like', "%{$search}%")
                    ->orWhereHas('externalCustomer', function ($ext) use ($search) {
                        $ext->where('tit_name', 'like', "%{$search}%")
                            ->orWhere('tit_phone', 'like', "%{$search}%");
                    });
            });
        };

        $cashFlows = CashFlow::with(['user', 'posTransaction', 'branch', 'expenseType', 'externalCustomer'])
            ->whereIn('branch_id', $branchIds)
            ->tap($dateScope)
            ->when($search, $applyCashSearch)
            ->orderByDesc('created_at')
            ->paginate(100)
            ->withQueryString();

        $allDailyCashFlows = CashFlow::with(['user', 'posTransaction', 'branch', 'expenseType', 'externalCustomer'])
            ->whereIn('branch_id', $branchIds)
            ->tap($dateScope)
            ->when($search, $applyCashSearch)
            ->orderByDesc('created_at')
            ->get();

        $incomes = $allDailyCashFlows->where('type', 'income');
        $expenses = $allDailyCashFlows->where('type', 'expense');

        $totalIncome = round((float) $incomes->sum('amount'), 2);
        // Los egresos de caja se calculan únicamente sobre salidas en efectivo (nunca transferencias ni tarjeta)
        $totalExpense = round((float) $expenses->sum(fn($f) => $f->effective_cash_amount), 2);

        // Desglose por método de pago (incluyendo pagos mixtos, abonos de financieras, préstamos, tandas y ventas)
        $handCash = round((float) $incomes->sum(fn($f) => $f->effective_cash_amount), 2);
        $due = round((float) $incomes->sum(fn($f) => $f->effective_card_amount), 2);
        $cheque = round((float) $incomes->sum(fn($f) => $f->effective_transfer_amount), 2);

        $totalBanco = round($handCash + $cheque + $due, 2);

        // El saldo inicial depende del modo de consulta
        if ($filterType === 'dia' && !$isAllBranches) {
            $openingBalance = DailyCut::resolveOpeningBalance((int) $branchId, $selectedDate);
            $finalDailyCut = DailyCut::with('user')
                ->where('branch_id', (int) $branchId)
                ->whereDate('date', $selectedDate)
                ->where('cut_type', 'corte_z')
                ->latest('id')
                ->first();

            $preCuts = DailyCut::with('user')
                ->where('branch_id', (int) $branchId)
                ->whereDate('date', $selectedDate)
                ->where('cut_type', 'pre_corte')
                ->orderByDesc('created_at')
                ->get();
        } elseif ($filterType === 'rango' && !$isAllBranches) {
            $openingBalance = DailyCut::resolveOpeningBalance((int) $branchId, $startDate);
            $finalDailyCut = null;
            $preCuts = collect();
        } else {
            $openingBalance = 0.0;
            $finalDailyCut = null;
            $preCuts = collect();
        }

        // Historial de cortes para el filtro solicitado (día, rango o todos)
        $cutsHistory = DailyCut::with(['user', 'branch'])
            ->whereIn('branch_id', $branchIds)
            ->tap($cutDateScope)
            ->orderByDesc('date')
            ->orderByDesc('id')
            ->get();

        // El efectivo esperado en caja se calcula solo como: saldo inicial + ingresos en efectivo - egresos en efectivo
        $balance = round($openingBalance + $handCash - $totalExpense, 2);

        $dailyCut = $finalDailyCut ?: (object) [
            'id' => null,
            'date' => $filterType === 'dia' ? $selectedDate : ($startDate ?? Carbon::today()->toDateString()),
            'cut_type' => 'en_curso',
            'cut_mode' => 'activo',
            'opening_balance' => $openingBalance,
            'total_income' => $totalIncome,
            'cash_income' => $handCash,
            'card_income' => $due,
            'transfer_income' => $cheque,
            'total_expense' => $totalExpense,
            'balance' => $balance,
        ];

        // Desglose por cliente de todo el movimiento por día / periodo
        $customerBreakdown = $allDailyCashFlows->groupBy(fn($f) => $f->customer_display_name)
            ->map(function ($group, $clientName) {
                $inc = $group->where('type', 'income');
                $exp = $group->where('type', 'expense');
                $extId = $group->pluck('external_customer_id')->filter()->first();
                return [
                    'customer_name' => $clientName,
                    'external_customer_id' => $extId ? (int) $extId : null,
                    'is_external_customer' => $extId !== null,
                    'movements_count' => $group->count(),
                    'total_income' => round((float) $inc->sum('amount'), 2),
                    'total_expense' => round((float) $exp->sum(fn($f) => $f->effective_cash_amount), 2),
                    'cash' => round((float) $inc->sum(fn($f) => $f->effective_cash_amount), 2),
                    'card' => round((float) $inc->sum(fn($f) => $f->effective_card_amount), 2),
                    'transfer' => round((float) $inc->sum(fn($f) => $f->effective_transfer_amount), 2),
                    'commission' => round((float) $inc->sum('commission_amount'), 2),
                    'modules' => $group->pluck('module')->filter()->unique()->implode(', '),
                    'movements' => $group,
                ];
            });

        // Desglose por módulo integrado (Financieras, Préstamos, Tandas, Ventas, etc.)
        $moduleBreakdown = $allDailyCashFlows->groupBy(fn($f) => $f->module ?: 'General')
            ->map(function ($group, $modName) {
                return [
                    'module' => $modName,
                    'count' => $group->count(),
                    'income' => round((float) $group->where('type', 'income')->sum('amount'), 2),
                    'expense' => round((float) $group->where('type', 'expense')->sum(fn($f) => $f->effective_cash_amount), 2),
                ];
            });

        $branches = $this->canAccessAllBranches()
            ? Branch::orderBy('name')->get()
            : Branch::where('id', (int) $branchId)->get();
        $currentBranch = !$isAllBranches
            ? ($branches->firstWhere('id', (int) $branchId) ?? Branch::find((int) $branchId))
            : (object) ['name' => 'Todas las Sucursales', 'auto_cut_enabled' => false, 'auto_cut_time' => '21:00'];

        $pendingDueTotal = ($filterType === 'dia' && !$isAllBranches)
            ? $this->resolvePendingDueTotal((int) $branchId, $selectedDate)
            : 0.0;
        $expectedCash = round($openingBalance + $handCash - $totalExpense, 2);
        $chronologicalSource = $filterType === 'dia' ? $allDailyCashFlows : $cashFlows;
        $chronologicalOperations = $this->buildChronologicalOperations($chronologicalSource);
        $isChronologicalPaginated = $filterType !== 'dia' && $cashFlows->hasPages();
        $expenseDetails = $this->buildExpenseDetails($allDailyCashFlows);

        $finStart = $filterType === 'dia' ? $selectedDate : ($filterType === 'rango' ? $startDate : null);
        $finEnd = $filterType === 'dia' ? $selectedDate : ($filterType === 'rango' ? $endDate : null);
        $financieraSummary = $this->buildFinancieraSummary($branchIds, $finStart, $finEnd);

        return view('cash.daily-cut', compact(
            'cashFlows',
            'specialProviderPayments',
            'dailyCut',
            'finalDailyCut',
            'preCuts',
            'cutsHistory',
            'externalPayments',
            'totalIncome',
            'totalExpense',
            'handCash',
            'cheque',
            'due',
            'totalBanco',
            'search',
            'filterType',
            'selectedDate',
            'startDate',
            'endDate',
            'customerBreakdown',
            'moduleBreakdown',
            'branches',
            'branchId',
            'isAllBranches',
            'branchScope',
            'currentBranch',
            'pendingDueTotal',
            'expectedCash',
            'chronologicalOperations',
            'isChronologicalPaginated',
            'expenseDetails',
            'financieraSummary'
        ));
    }

    /**
     * Registrar un movimiento manual en Caja (con soporte de pagos mixtos, catálogo de egresos y cliente nominal).
     */
    public function store(Request $request)
    {
        $isExpense = $request->input('type') === 'expense';

        $validated = $request->validate([
            'type' => 'required|in:income,expense',
            'amount' => 'required|numeric|min:0.01',
            'customer_name' => 'nullable|string|max:190',
            'payment_method' => 'nullable|in:efectivo,tarjeta,transferencia,mixto',
            'cash_amount' => 'nullable|numeric|min:0',
            'card_amount' => 'nullable|numeric|min:0',
            'transfer_amount' => 'nullable|numeric|min:0',
            'expense_type_id' => $isExpense ? 'required|exists:expense_types,id' : 'nullable|exists:expense_types,id',
            'description' => $isExpense ? 'nullable|string|max:255' : 'required|string|max:255',
            'reference' => 'nullable|string|max:100',
            'module' => 'nullable|string|max:100',
            'branch_id' => 'nullable|exists:branches,id',
        ], [
            'expense_type_id.required' => 'Selecciona un tipo de egreso de la lista.',
            'expense_type_id.exists' => 'El tipo de egreso seleccionado no es válido.',
            'description.required' => 'Captura el concepto o motivo del movimiento.',
        ]);

        $branchId = $this->resolveBranchId($request);
        $amount = round((float) $validated['amount'], 2);
        $method = $validated['payment_method'] ?? 'efectivo';

        if ($isExpense && $method !== 'efectivo') {
            return back()
                ->withInput()
                ->withErrors(['payment_method' => 'Los egresos de caja solo pueden registrarse en efectivo. Las transferencias y pagos con tarjeta nunca deben registrarse como egresos de caja.']);
        }

        $expenseTypeId = null;
        $expenseTypeName = null;
        $descriptionText = trim((string) ($validated['description'] ?? ''));

        if ($isExpense) {
            $expenseType = ExpenseType::findOrFail((int) $validated['expense_type_id']);
            if (!$expenseType->is_active) {
                return back()
                    ->withInput()
                    ->withErrors(['expense_type_id' => 'El tipo de egreso seleccionado está desactivado en el catálogo.']);
            }
            if (($expenseType->is_other || $expenseType->requires_reason) && $descriptionText === '') {
                return back()
                    ->withInput()
                    ->withErrors(['description' => "Para el tipo de egreso \"{$expenseType->name}\" es obligatorio capturar el motivo."]);
            }

            $expenseTypeId = $expenseType->id;
            $expenseTypeName = $expenseType->name;
            $descriptionToSave = $descriptionText !== '' ? $descriptionText : null;
            $moduleToSave = trim((string) ($validated['module'] ?? '')) !== ''
                ? trim((string) $validated['module'])
                : 'Egresos / Gastos';
        } else {
            $descriptionToSave = $descriptionText !== '' ? $descriptionText : 'Movimiento manual de caja';
            $moduleToSave = trim((string) ($validated['module'] ?? '')) !== ''
                ? trim((string) $validated['module'])
                : 'Movimiento Manual';
        }

        $cashAmount = $method === 'mixto' ? round((float) $request->input('cash_amount', 0), 2) : ($method === 'efectivo' ? $amount : 0);
        $cardAmount = $method === 'mixto' ? round((float) $request->input('card_amount', 0), 2) : ($method === 'tarjeta' ? $amount : 0);
        $transferAmount = $method === 'mixto' ? round((float) $request->input('transfer_amount', 0), 2) : ($method === 'transferencia' ? $amount : 0);

        CashFlowService::register(
            $validated['type'],
            $amount,
            $descriptionToSave,
            $validated['reference'] ?? null,
            $moduleToSave,
            $branchId,
            [
                'customer_name' => $validated['customer_name'] ?? 'Operación Manual en Caja',
                'payment_method' => $method,
                'cash_amount' => $cashAmount,
                'card_amount' => $cardAmount,
                'transfer_amount' => $transferAmount,
                'expense_type_id' => $expenseTypeId,
                'expense_type_name' => $expenseTypeName,
            ]
        );

        return redirect()
            ->route('cash.index', ['branch_id' => $branchId])
            ->with('success', 'Movimiento registrado en caja correctamente.');
    }

    /**
     * Generar Pre-Corte (Corte X) sin cerrar la caja del día.
     */
    public function applyPreCut(Request $request)
    {
        $today = $request->input('date') ? Carbon::parse($request->input('date'))->toDateString() : Carbon::today()->toDateString();
        $branchId = $this->resolveBranchId($request);

        $flows = CashFlow::with('externalCustomer')
            ->where('branch_id', $branchId)
            ->whereDate('created_at', $today)
            ->get();

        $incomes = $flows->where('type', 'income');
        $expenses = $flows->where('type', 'expense');

        $totalIncome = round((float) $incomes->sum('amount'), 2);
        $totalExpense = round((float) $expenses->sum(fn($f) => $f->effective_cash_amount), 2);
        $cashIncome = round((float) $incomes->sum(fn($f) => $f->effective_cash_amount), 2);
        $cardIncome = round((float) $incomes->sum(fn($f) => $f->effective_card_amount), 2);
        $transferIncome = round((float) $incomes->sum(fn($f) => $f->effective_transfer_amount), 2);

        $openingBalance = DailyCut::resolveOpeningBalance($branchId, $today);
        $expectedCashInDrawer = round($openingBalance + $cashIncome - $totalExpense, 2);
        $balance = $expectedCashInDrawer;
        $declaredCash = $request->filled('declared_cash') ? round((float) $request->input('declared_cash'), 2) : null;
        $difference = $declaredCash !== null ? round($declaredCash - $expectedCashInDrawer, 2) : 0;

        $preCut = DailyCut::create([
            'branch_id' => $branchId,
            'date' => $today,
            'cut_type' => 'pre_corte',
            'cut_mode' => 'manual',
            'user_id' => auth()->id(),
            'opening_balance' => $openingBalance,
            'total_income' => $totalIncome,
            'cash_income' => $cashIncome,
            'card_income' => $cardIncome,
            'transfer_income' => $transferIncome,
            'total_expense' => $totalExpense,
            'withdraw_amount' => 0,
            'balance' => $balance,
            'declared_cash' => $declaredCash,
            'difference_amount' => $difference,
            'notes' => $request->input('notes', 'Pre-Corte parcial (Corte X)'),
            'closed_at' => now(),
        ]);

        return redirect()
            ->route('cash.dailyCut', ['date' => $today, 'branch_id' => $branchId])
            ->with('success', 'Pre-Corte (Corte X) #' . $preCut->id . ' generado correctamente. La caja continúa abierta.');
    }

    /**
     * Aplicar Corte Final (Corte Z) manual del día.
     */
    public function applyCut(Request $request)
    {
        $validated = $request->validate([
            'declared_cash' => 'nullable|numeric|min:0',
            'withdraw_amount' => 'nullable|numeric|min:0',
            'notes' => 'nullable|string|max:500',
            'denominations' => 'nullable|array',
            'denominations.b1000' => 'nullable|integer|min:0',
            'denominations.b500' => 'nullable|integer|min:0',
            'denominations.b200' => 'nullable|integer|min:0',
            'denominations.b100' => 'nullable|integer|min:0',
            'denominations.b50' => 'nullable|integer|min:0',
            'denominations.b20' => 'nullable|integer|min:0',
            'denominations.coins' => 'nullable|numeric|min:0',
        ], [
            'declared_cash.numeric' => 'El efectivo físico contado debe ser un monto numérico válido.',
            'declared_cash.min' => 'El efectivo físico contado no puede ser negativo.',
        ]);

        $today = $request->input('date') ? Carbon::parse($request->input('date'))->toDateString() : Carbon::today()->toDateString();
        $branchId = $this->resolveBranchId($request);

        if (DailyCut::where('branch_id', $branchId)->whereDate('date', $today)->where('cut_type', 'corte_z')->exists()) {
            return back()->with('info', 'El Corte Final (Corte Z) ya fue aplicado para esta fecha en esta sucursal.');
        }

        $flows = CashFlow::with('externalCustomer')
            ->where('branch_id', $branchId)
            ->whereDate('created_at', $today)
            ->get();

        $incomes = $flows->where('type', 'income');
        $expenses = $flows->where('type', 'expense');

        $totalIncome = round((float) $incomes->sum('amount'), 2);
        $totalExpense = round((float) $expenses->sum(fn($f) => $f->effective_cash_amount), 2);
        $cashIncome = round((float) $incomes->sum(fn($f) => $f->effective_cash_amount), 2);
        $cardIncome = round((float) $incomes->sum(fn($f) => $f->effective_card_amount), 2);
        $transferIncome = round((float) $incomes->sum(fn($f) => $f->effective_transfer_amount), 2);

        $openingBalance = DailyCut::resolveOpeningBalance($branchId, $today);

        $withdrawAmount = max(0, round((float) ($validated['withdraw_amount'] ?? 0), 2));
        if ($withdrawAmount > 0) {
            CashFlowService::register(
                'expense',
                $withdrawAmount,
                'Retiro de efectivo al aplicar Corte Final (Corte Z)',
                'CORTE-Z-' . $today,
                'Corte Final',
                $branchId,
                [
                    'customer_name' => 'Retiro Interno de Corte',
                    'payment_method' => 'efectivo',
                    'cash_amount' => $withdrawAmount,
                ]
            );
            $totalExpense = round($totalExpense + $withdrawAmount, 2);
        }

        // Conteo físico por denominación
        $denominations = $request->input('denominations', []);
        $b1000 = max(0, (int) ($denominations['b1000'] ?? ($denominations['1000'] ?? 0)));
        $b500 = max(0, (int) ($denominations['b500'] ?? ($denominations['500'] ?? 0)));
        $b200 = max(0, (int) ($denominations['b200'] ?? ($denominations['200'] ?? 0)));
        $b100 = max(0, (int) ($denominations['b100'] ?? ($denominations['100'] ?? 0)));
        $b50 = max(0, (int) ($denominations['b50'] ?? ($denominations['50'] ?? 0)));
        $b20 = max(0, (int) ($denominations['b20'] ?? ($denominations['20'] ?? 0)));
        $coins = max(0, round((float) ($denominations['coins'] ?? 0), 2));

        $calcFromDenoms = round(
            ($b1000 * 1000) +
            ($b500 * 500) +
            ($b200 * 200) +
            ($b100 * 100) +
            ($b50 * 50) +
            ($b20 * 20) +
            $coins,
            2
        );

        $hasDenomInput = !empty($denominations) && ($calcFromDenoms > 0 || $coins > 0);
        $declaredCash = $hasDenomInput
            ? $calcFromDenoms
            : round((float) ($validated['declared_cash'] ?? 0), 2);

        $denominationBreakdown = [
            'b1000' => $b1000,
            'b500' => $b500,
            'b200' => $b200,
            'b100' => $b100,
            'b50' => $b50,
            'b20' => $b20,
            'coins' => $coins,
            'total' => $declaredCash,
        ];

        // Efectivo esperado en caja (saldo inicial + ingresos en efectivo - egresos en efectivo)
        $expectedCash = round($openingBalance + $cashIncome - $totalExpense, 2);
        // Saldo final contable global (saldo inicial + total ingresos - egresos)
        $finalBalance = round($openingBalance + $totalIncome - $totalExpense, 2);
        // Diferencia contra efectivo esperado
        $difference = round($declaredCash - $expectedCash, 2);

        $customerBreakdown = $flows->groupBy(fn($f) => $f->customer_display_name)
            ->map(function ($group, $clientName) {
                $extId = $group->pluck('external_customer_id')->filter()->first();
                return [
                    'customer_name' => $clientName,
                    'external_customer_id' => $extId ? (int) $extId : null,
                    'is_external_customer' => $extId !== null,
                    'movements_count' => $group->count(),
                    'total_income' => round((float) $group->where('type', 'income')->sum('amount'), 2),
                    'total_expense' => round((float) $group->where('type', 'expense')->sum(fn($f) => $f->effective_cash_amount), 2),
                ];
            })->values()->toArray();

        $cut = DailyCut::create([
            'branch_id' => $branchId,
            'date' => $today,
            'cut_type' => 'corte_z',
            'cut_mode' => 'manual',
            'user_id' => auth()->id(),
            'opening_balance' => $openingBalance,
            'total_income' => $totalIncome,
            'cash_income' => $cashIncome,
            'card_income' => $cardIncome,
            'transfer_income' => $transferIncome,
            'total_expense' => $totalExpense,
            'withdraw_amount' => $withdrawAmount,
            'balance' => $finalBalance,
            'declared_cash' => $declaredCash,
            'difference_amount' => $difference,
            'denomination_breakdown' => $denominationBreakdown,
            'customer_breakdown' => $customerBreakdown,
            'notes' => $request->input('notes', 'Corte Final (Corte Z) manual.'),
            'closed_at' => now(),
        ]);

        CashFlow::where('branch_id', $branchId)
            ->whereDate('created_at', $today)
            ->whereNull('daily_cut_id')
            ->update(['daily_cut_id' => $cut->id]);

        return redirect()
            ->route('cash.dailyCut', ['date' => $today, 'branch_id' => $branchId])
            ->with('success', 'Corte Final (Corte Z) aplicado correctamente.');
    }

    /**
     * Actualizar configuración de corte automático (activado/desactivado y hora programada) por sucursal.
     */
    public function updateAutoCutConfig(Request $request)
    {
        $canAll = $this->canAccessAllBranches();

        $validated = $request->validate([
            'branch_id' => 'nullable|exists:branches,id',
            'auto_cut_enabled' => 'nullable|boolean',
            'auto_cut_time' => 'required|date_format:H:i',
        ]);

        $targetBranchId = $validated['branch_id'] ?? $this->resolveBranchId($request);

        if (!$canAll) {
            $userBranchId = (int) auth()->user()->branch_id;
            if ($userBranchId !== (int) $targetBranchId || !auth()->user()->can('cash.edit')) {
                abort(403, 'No tienes permiso para modificar la configuración de corte de esta sucursal.');
            }
        }

        $branch = Branch::findOrFail($targetBranchId);

        $branch->update([
            'auto_cut_enabled' => $request->boolean('auto_cut_enabled'),
            'auto_cut_time' => $validated['auto_cut_time'],
        ]);

        return back()->with('success', 'Configuración de corte para la sucursal "' . $branch->name . '" actualizada correctamente.');
    }

    public function filterByDate(Request $request)
    {
        return $this->index($request);
    }

    public function printCut(Request $request)
    {
        $cutId = $request->input('cut_id');
        $branchScope = $this->resolveBranchScope($request, false);
        $isAllBranches = $branchScope['is_all'];
        $branchIds = $branchScope['branch_ids'];
        $branchId = $this->resolveBranchId($request);
        $filterType = $request->input('filter_type');

        // Si se solicita un corte específico por cut_id, siempre imprimir ese corte individual
        if ($cutId) {
            $cutQuery = DailyCut::with(['branch', 'user']);
            if (!$this->canAccessAllBranches()) {
                $cutQuery->where('branch_id', $branchId);
            }
            $dailyCut = $cutQuery->findOrFail($cutId);
            $cutBranchId = (int) $dailyCut->branch_id;
            $date = $dailyCut->date ? $dailyCut->date->toDateString() : Carbon::parse($dailyCut->created_at)->toDateString();

            $cashFlows = CashFlow::with(['user', 'posTransaction', 'expenseType', 'externalCustomer'])
                ->where('branch_id', $cutBranchId)
                ->whereDate('created_at', $date)
                ->orderBy('created_at')
                ->get();

            $openingBalance = (float) $dailyCut->opening_balance;
            $cashIncome = (float) $dailyCut->cash_income;
            $totalExpense = (float) $dailyCut->total_expense;
            $expectedCash = round($openingBalance + $cashIncome - $totalExpense, 2);
            $pendingDueTotal = $this->resolvePendingDueTotal($cutBranchId, $date);
            $chronologicalOperations = $this->buildChronologicalOperations($cashFlows);
            $expenseDetails = $this->buildExpenseDetails($cashFlows);
            $financieraSummary = $this->buildFinancieraSummary([$cutBranchId], $date, $date);

            return view('cash.print', compact(
                'dailyCut',
                'cashFlows',
                'expectedCash',
                'pendingDueTotal',
                'chronologicalOperations',
                'expenseDetails',
                'financieraSummary'
            ));
        }

        // Si no hay cut_id, determinar si se solicita imprimir reporte del rango o todos los registros
        if (!$filterType) {
            if ($request->filled('start_date') || $request->filled('end_date')) {
                $filterType = 'rango';
            } elseif ($request->filled('date')) {
                $filterType = 'dia';
            } else {
                $filterType = 'dia';
            }
        }

        $branch = $isAllBranches
            ? (object) ['name' => 'Todas las Sucursales']
            : Branch::find($branchId);

        if ($filterType === 'rango' || $filterType === 'todos' || $isAllBranches) {
            if (!$filterType || ($filterType === 'dia' && $isAllBranches)) {
                $filterType = $request->filled('start_date') || $request->filled('end_date') ? 'rango' : 'dia';
            }
            $selectedDate = $request->input('date') ? Carbon::parse($request->input('date'))->toDateString() : Carbon::today()->toDateString();
            $startDate = $filterType === 'dia'
                ? $selectedDate
                : ($request->input('start_date') ? Carbon::parse($request->input('start_date'))->toDateString() : Carbon::today()->subDays(7)->toDateString());
            $endDate = $filterType === 'dia'
                ? $selectedDate
                : ($request->input('end_date') ? Carbon::parse($request->input('end_date'))->toDateString() : Carbon::today()->toDateString());

            if ($startDate > $endDate) {
                $tmp = $startDate;
                $startDate = $endDate;
                $endDate = $tmp;
            }

            $cutsQuery = DailyCut::with(['branch', 'user'])->whereIn('branch_id', $branchIds);
            $flowsQuery = CashFlow::with(['user', 'posTransaction', 'expenseType', 'externalCustomer'])->whereIn('branch_id', $branchIds);

            if ($filterType === 'rango' || $filterType === 'dia') {
                $cutsQuery->whereBetween('date', [$startDate, $endDate]);
                $flowsQuery->whereBetween('created_at', [
                    Carbon::parse($startDate)->startOfDay(),
                    Carbon::parse($endDate)->endOfDay()
                ]);
            }

            $cutsHistory = $cutsQuery->orderByDesc('date')->orderByDesc('id')->get();
            $cashFlows = $flowsQuery->orderBy('created_at')->get();

            $incomes = $cashFlows->where('type', 'income');
            $expenses = $cashFlows->where('type', 'expense');

            $totalIncome = round((float) $incomes->sum('amount'), 2);
            $cashIncome = round((float) $incomes->sum(fn($f) => $f->effective_cash_amount), 2);
            $cardIncome = round((float) $incomes->sum(fn($f) => $f->effective_card_amount), 2);
            $transferIncome = round((float) $incomes->sum(fn($f) => $f->effective_transfer_amount), 2);
            $totalExpense = round((float) $expenses->sum(fn($f) => $f->effective_cash_amount), 2);

            $cutsCount = $cutsHistory->count();
            $cutsZCount = $cutsHistory->where('cut_type', 'corte_z')->count();
            $cutsXCount = $cutsHistory->where('cut_type', 'pre_corte')->count();
            $totalDeclaredCash = round((float) $cutsHistory->where('cut_type', 'corte_z')->sum('declared_cash'), 2);
            $totalDifference = round((float) $cutsHistory->where('cut_type', 'corte_z')->sum('difference_amount'), 2);

            $chronologicalOperations = $this->buildChronologicalOperations($cashFlows);
            $expenseDetails = $this->buildExpenseDetails($cashFlows);
            $financieraSummary = $this->buildFinancieraSummary(
                $branchIds,
                $filterType === 'todos' ? null : $startDate,
                $filterType === 'todos' ? null : $endDate
            );
            $isMultiCutReport = true;

            return view('cash.print', compact(
                'isMultiCutReport',
                'filterType',
                'startDate',
                'endDate',
                'branch',
                'cutsHistory',
                'cashFlows',
                'totalIncome',
                'cashIncome',
                'cardIncome',
                'transferIncome',
                'totalExpense',
                'cutsCount',
                'cutsZCount',
                'cutsXCount',
                'totalDeclaredCash',
                'totalDifference',
                'chronologicalOperations',
                'expenseDetails',
                'financieraSummary'
            ));
        }

        // Modo 'dia' sin cut_id (comportamiento original para corte de un día específico)
        $date = $request->input('date') ? Carbon::parse($request->input('date'))->toDateString() : Carbon::today()->toDateString();
        $dailyCut = DailyCut::with(['branch', 'user'])
            ->where('branch_id', $branchId)
            ->whereDate('date', $date)
            ->orderByDesc('id')
            ->first();

        $cashFlows = CashFlow::with(['user', 'posTransaction', 'expenseType', 'externalCustomer'])
            ->where('branch_id', $branchId)
            ->whereDate('created_at', $date)
            ->orderBy('created_at')
            ->get();

        $openingBalance = $dailyCut ? (float) $dailyCut->opening_balance : DailyCut::resolveOpeningBalance($branchId, $date);

        if (!$dailyCut) {
            $incomes = $cashFlows->where('type', 'income');
            $expenses = $cashFlows->where('type', 'expense');
            $totalIncome = round((float) $incomes->sum('amount'), 2);
            $cashIncome = round((float) $incomes->sum(fn($f) => $f->effective_cash_amount), 2);
            $cardIncome = round((float) $incomes->sum(fn($f) => $f->effective_card_amount), 2);
            $transferIncome = round((float) $incomes->sum(fn($f) => $f->effective_transfer_amount), 2);
            $totalExpense = round((float) $expenses->sum(fn($f) => $f->effective_cash_amount), 2);
            $expectedCash = round($openingBalance + $cashIncome - $totalExpense, 2);

            $dailyCut = (object) [
                'date' => $date,
                'cut_type' => 'pre_corte',
                'cut_mode' => 'vista_previa',
                'opening_balance' => $openingBalance,
                'total_income' => $totalIncome,
                'cash_income' => $cashIncome,
                'card_income' => $cardIncome,
                'transfer_income' => $transferIncome,
                'total_expense' => $totalExpense,
                'withdraw_amount' => 0.00,
                'balance' => round($openingBalance + $totalIncome - $totalExpense, 2),
                'declared_cash' => null,
                'difference_amount' => 0.00,
                'denomination_breakdown' => null,
                'branch' => Branch::find($branchId),
                'user' => auth()->user(),
            ];
        } else {
            $cashIncome = (float) $dailyCut->cash_income;
            $totalExpense = (float) $dailyCut->total_expense;
            $expectedCash = round($openingBalance + $cashIncome - $totalExpense, 2);
        }

        $pendingDueTotal = $this->resolvePendingDueTotal($branchId, $date);
        $chronologicalOperations = $this->buildChronologicalOperations($cashFlows);
        $expenseDetails = $this->buildExpenseDetails($cashFlows);
        $financieraSummary = $this->buildFinancieraSummary([$branchId], $date, $date);

        return view('cash.print', compact(
            'dailyCut',
            'cashFlows',
            'expectedCash',
            'pendingDueTotal',
            'chronologicalOperations',
            'expenseDetails',
            'financieraSummary'
        ));
    }

    /**
     * Construye datos compartidos para exportación de Cortes Z (PDF y Excel) multi-sucursal o por sucursal.
     */
    protected function buildCutsReportData(Request $request): array
    {
        $branchScope = $this->resolveBranchScope($request, true);
        $branchIds = $branchScope['branch_ids'];

        $filterType = $request->input('filter_type');
        if (!$filterType) {
            if ($request->filled('start_date') || $request->filled('end_date')) {
                $filterType = 'rango';
            } elseif ($request->filled('date')) {
                $filterType = 'dia';
            } else {
                $filterType = 'rango';
            }
        }
        if (!in_array($filterType, ['dia', 'rango', 'todos'], true)) {
            $filterType = 'rango';
        }

        $selectedDate = $request->input('date') ? Carbon::parse($request->input('date'))->toDateString() : Carbon::today()->toDateString();
        $startDate = $request->input('start_date') ? Carbon::parse($request->input('start_date'))->toDateString() : Carbon::today()->startOfMonth()->toDateString();
        $endDate = $request->input('end_date') ? Carbon::parse($request->input('end_date'))->toDateString() : Carbon::today()->toDateString();

        if ($startDate > $endDate) {
            $tmp = $startDate;
            $startDate = $endDate;
            $endDate = $tmp;
        }

        $onlyZ = $request->has('only_z') ? $request->boolean('only_z') : true;

        $cutsQuery = DailyCut::with(['branch', 'user'])
            ->whereIn('branch_id', $branchIds)
            ->when($onlyZ, fn($q) => $q->where('cut_type', 'corte_z'));

        if ($filterType === 'dia') {
            $cutsQuery->whereDate('date', $selectedDate);
            $finStart = $selectedDate;
            $finEnd = $selectedDate;
        } elseif ($filterType === 'rango') {
            $cutsQuery->whereBetween('date', [$startDate, $endDate]);
            $finStart = $startDate;
            $finEnd = $endDate;
        } else {
            $finStart = null;
            $finEnd = null;
        }

        $cuts = $cutsQuery->orderByDesc('date')->orderBy('branch_id')->orderByDesc('id')->get();
        $financieraSummary = $this->buildFinancieraSummary($branchIds, $finStart, $finEnd);

        $totals = [
            'total_income' => round((float) $cuts->sum('total_income'), 2),
            'cash_income' => round((float) $cuts->sum('cash_income'), 2),
            'card_income' => round((float) $cuts->sum('card_income'), 2),
            'transfer_income' => round((float) $cuts->sum('transfer_income'), 2),
            'total_expense' => round((float) $cuts->sum('total_expense'), 2),
            'declared_cash' => round((float) $cuts->whereNotNull('declared_cash')->sum('declared_cash'), 2),
            'difference' => round((float) $cuts->whereNotNull('declared_cash')->sum('difference_amount'), 2),
        ];

        return [
            'cuts' => $cuts,
            'totals' => $totals,
            'financieraSummary' => $financieraSummary,
            'filterType' => $filterType,
            'selectedDate' => $selectedDate,
            'startDate' => $startDate,
            'endDate' => $endDate,
            'onlyZ' => $onlyZ,
            'branchScope' => $branchScope,
            'branchLabel' => $branchScope['label'],
        ];
    }

    /**
     * Exportar Cortes Z de una o todas las sucursales a PDF.
     */
    public function exportCutsPdf(Request $request)
    {
        $data = $this->buildCutsReportData($request);
        $pdf = Pdf::loadView('cash.cuts-pdf', $data)->setPaper('letter', 'landscape');
        $suffix = $data['filterType'] === 'dia'
            ? $data['selectedDate']
            : ($data['filterType'] === 'rango' ? "{$data['startDate']}_a_{$data['endDate']}" : 'historico');

        return $pdf->download("Cortes_Z_{$suffix}.pdf");
    }

    /**
     * Exportar Cortes Z de una o todas las sucursales a Excel (.xlsx).
     */
    public function exportCutsExcel(Request $request)
    {
        $data = $this->buildCutsReportData($request);
        $cuts = $data['cuts'];
        $financieraSummary = $data['financieraSummary'];

        $spreadsheet = new Spreadsheet();
        $sheet = $spreadsheet->getActiveSheet();
        $sheet->setTitle('Cortes Z');

        $headers = [
            'Folio', 'Tipo', 'Sucursal', 'Fecha', 'Hora Cierre', 'Cajero',
            'Fondo Inicial ($)', 'Total Ingresos ($)', 'Ingresos Efectivo ($)',
            'Ingresos Tarjeta ($)', 'Ingresos Transferencia ($)', 'Egresos Efectivo ($)',
            'Efectivo Esperado ($)', 'Efectivo Declarado ($)', 'Diferencia ($)', 'Modo', 'Notas'
        ];
        $sheet->fromArray($headers, null, 'A1');
        $sheet->getStyle('A1:Q1')->getFont()->setBold(true);

        $row = 2;
        foreach ($cuts as $cut) {
            $opening = (float) ($cut->opening_balance ?? 0);
            $cashInc = (float) ($cut->cash_income ?? 0);
            $expense = (float) ($cut->total_expense ?? 0);
            $expected = round($opening + $cashInc - $expense, 2);

            $sheet->setCellValue("A{$row}", '#' . $cut->id);
            $sheet->setCellValue("B{$row}", $cut->cut_type === 'corte_z' ? 'Corte Z' : 'Pre-Corte X');
            $sheet->setCellValue("C{$row}", $cut->branch?->name ?? 'Sucursal');
            $sheet->setCellValue("D{$row}", $cut->date ? Carbon::parse($cut->date)->format('d/m/Y') : '');
            $sheet->setCellValue("E{$row}", optional($cut->closed_at ?? $cut->created_at)->format('H:i'));
            $sheet->setCellValue("F{$row}", $cut->user?->name ?? 'Automático');
            $sheet->setCellValue("G{$row}", $opening);
            $sheet->setCellValue("H{$row}", (float) ($cut->total_income ?? 0));
            $sheet->setCellValue("I{$row}", $cashInc);
            $sheet->setCellValue("J{$row}", (float) ($cut->card_income ?? 0));
            $sheet->setCellValue("K{$row}", (float) ($cut->transfer_income ?? 0));
            $sheet->setCellValue("L{$row}", $expense);
            $sheet->setCellValue("M{$row}", $expected);
            $sheet->setCellValue("N{$row}", $cut->declared_cash !== null ? (float) $cut->declared_cash : '');
            $sheet->setCellValue("O{$row}", $cut->declared_cash !== null ? (float) ($cut->difference_amount ?? 0) : '');
            $sheet->setCellValue("P{$row}", strtoupper((string) ($cut->cut_mode ?? 'manual')));
            $sheet->setCellValue("Q{$row}", (string) ($cut->notes ?? ''));
            $row++;
        }

        if (!empty($financieraSummary['items'])) {
            $finSheet = $spreadsheet->createSheet();
            $finSheet->setTitle('Abonos Financieras');
            $finSheet->fromArray(['Financiera', 'Operaciones', 'Abonos ($)', 'Comisiones ($)', 'Total ($)'], null, 'A1');
            $finSheet->getStyle('A1:E1')->getFont()->setBold(true);
            $fRow = 2;
            foreach ($financieraSummary['items'] as $finItem) {
                $finSheet->fromArray([
                    $finItem['name'],
                    $finItem['count'],
                    $finItem['amount'],
                    $finItem['commission'],
                    $finItem['total'],
                ], null, "A{$fRow}");
                $fRow++;
            }
            $finSheet->fromArray([
                'TOTAL GENERAL',
                $financieraSummary['grand_count'],
                $financieraSummary['grand_amount'],
                $financieraSummary['grand_commission'],
                $financieraSummary['grand_total'],
            ], null, "A{$fRow}");
            $finSheet->getStyle("A{$fRow}:E{$fRow}")->getFont()->setBold(true);
            foreach (range('A', 'E') as $col) {
                $finSheet->getColumnDimension($col)->setAutoSize(true);
            }
        }

        foreach (range('A', 'Q') as $col) {
            $sheet->getColumnDimension($col)->setAutoSize(true);
        }

        $suffix = $data['filterType'] === 'dia'
            ? $data['selectedDate']
            : ($data['filterType'] === 'rango' ? "{$data['startDate']}_a_{$data['endDate']}" : 'historico');

        $tempFile = tempnam(sys_get_temp_dir(), 'cuts_xlsx_');
        (new Xlsx($spreadsheet))->save($tempFile);

        return response()->download($tempFile, "Cortes_Z_{$suffix}.xlsx")->deleteFileAfterSend(true);
    }

    /**
     * Construye datos compartidos para el Reporte de Gastos por día, semana, mes o rango personalizado.
     */
    protected function buildExpensesReportData(Request $request): array
    {
        $branchScope = $this->resolveBranchScope($request, true);
        $branchIds = $branchScope['branch_ids'];

        $period = $request->input('period', 'dia');
        if (!in_array($period, ['dia', 'semana', 'mes', 'rango'], true)) {
            $period = 'dia';
        }

        $refDate = $request->input('date') ? Carbon::parse($request->input('date'))->toDateString() : Carbon::today()->toDateString();
        $refCarbon = Carbon::parse($refDate);

        if ($period === 'semana') {
            $startDate = $refCarbon->copy()->startOfWeek(Carbon::MONDAY)->toDateString();
            $endDate = $refCarbon->copy()->endOfWeek(Carbon::SUNDAY)->toDateString();
            $periodLabel = 'Semana';
        } elseif ($period === 'mes') {
            $startDate = $refCarbon->copy()->startOfMonth()->toDateString();
            $endDate = $refCarbon->copy()->endOfMonth()->toDateString();
            $periodLabel = 'Mes (' . ucfirst($refCarbon->locale('es')->monthName) . ' ' . $refCarbon->year . ')';
        } elseif ($period === 'rango') {
            $startDate = $request->input('start_date') ? Carbon::parse($request->input('start_date'))->toDateString() : Carbon::today()->startOfMonth()->toDateString();
            $endDate = $request->input('end_date') ? Carbon::parse($request->input('end_date'))->toDateString() : Carbon::today()->toDateString();
            if ($startDate > $endDate) {
                $tmp = $startDate;
                $startDate = $endDate;
                $endDate = $tmp;
            }
            $periodLabel = 'Rango personalizado';
        } else {
            $startDate = $refDate;
            $endDate = $refDate;
            $periodLabel = 'Día';
        }

        $search = trim((string) $request->input('search', ''));

        $expenses = CashFlow::with(['branch', 'user', 'expenseType'])
            ->where('type', 'expense')
            ->whereIn('branch_id', $branchIds)
            ->whereBetween('created_at', [
                Carbon::parse($startDate)->startOfDay(),
                Carbon::parse($endDate)->endOfDay(),
            ])
            ->when($search !== '', function ($q) use ($search) {
                $q->where(function ($sub) use ($search) {
                    $sub->where('description', 'like', "%{$search}%")
                        ->orWhere('reference', 'like', "%{$search}%")
                        ->orWhere('module', 'like', "%{$search}%")
                        ->orWhere('expense_type_name', 'like', "%{$search}%")
                        ->orWhere('customer_name', 'like', "%{$search}%");
                });
            })
            ->orderByDesc('created_at')
            ->get();

        $totalExpense = round((float) $expenses->sum('amount'), 2);
        $byBranch = $expenses
            ->groupBy(fn($e) => $e->branch?->name ?? 'General')
            ->map(function ($group, $name) {
                return [
                    'branch_name' => $name,
                    'count' => $group->count(),
                    'total' => round((float) $group->sum('amount'), 2),
                ];
            })
            ->sortKeys()
            ->values();

        $branches = $this->canAccessAllBranches()
            ? Branch::orderBy('name')->get()
            : Branch::whereIn('id', $branchIds)->get();

        return [
            'expenses' => $expenses,
            'totalExpense' => $totalExpense,
            'byBranch' => $byBranch,
            'period' => $period,
            'periodLabel' => $periodLabel,
            'refDate' => $refDate,
            'startDate' => $startDate,
            'endDate' => $endDate,
            'search' => $search,
            'branches' => $branches,
            'branchId' => $branchScope['branch_id'],
            'isAllBranches' => $branchScope['is_all'],
            'branchLabel' => $branchScope['label'],
        ];
    }

    /**
     * Reporte de gastos por día, semana, mes o rango de fechas personalizado.
     */
    public function expensesReport(Request $request)
    {
        return view('cash.expenses', $this->buildExpensesReportData($request));
    }

    /**
     * Exportar Reporte de Gastos a PDF.
     */
    public function exportExpensesPdf(Request $request)
    {
        $data = $this->buildExpensesReportData($request);
        $pdf = Pdf::loadView('cash.expenses-pdf', $data)->setPaper('letter', 'portrait');
        return $pdf->download("Gastos_{$data['startDate']}_a_{$data['endDate']}.pdf");
    }

    /**
     * Exportar Reporte de Gastos a Excel (.xlsx).
     */
    public function exportExpensesExcel(Request $request)
    {
        $data = $this->buildExpensesReportData($request);
        $expenses = $data['expenses'];

        $spreadsheet = new Spreadsheet();
        $sheet = $spreadsheet->getActiveSheet();
        $sheet->setTitle('Gastos');
        $sheet->fromArray(['Fecha/Hora', 'Sucursal', 'Tipo de Egreso / Módulo', 'Referencia', 'Motivo / Detalle', 'Registró', 'Importe ($)'], null, 'A1');
        $sheet->getStyle('A1:G1')->getFont()->setBold(true);

        $row = 2;
        foreach ($expenses as $exp) {
            $sheet->fromArray([
                optional($exp->created_at)->format('d/m/Y H:i'),
                $exp->branch?->name ?? 'General',
                $exp->resolved_expense_type_label ?: ($exp->module ?: 'Caja'),
                $exp->reference ?: '',
                $exp->description ?: 'Gasto registrado en caja',
                $exp->user?->name ?? 'Sistema',
                (float) $exp->amount,
            ], null, "A{$row}");
            $row++;
        }
        $sheet->fromArray(['', '', '', '', 'TOTAL DEL PERÍODO', '', $data['totalExpense']], null, "A{$row}");
        $sheet->getStyle("E{$row}:G{$row}")->getFont()->setBold(true);

        foreach (range('A', 'G') as $col) {
            $sheet->getColumnDimension($col)->setAutoSize(true);
        }

        $tempFile = tempnam(sys_get_temp_dir(), 'expenses_xlsx_');
        (new Xlsx($spreadsheet))->save($tempFile);

        return response()->download($tempFile, "Gastos_{$data['startDate']}_a_{$data['endDate']}.xlsx")->deleteFileAfterSend(true);
    }

    /**
     * Construir bitácora cronológica de operaciones agrupadas con detalle por concepto (bullets).
     */
    protected function buildChronologicalOperations($cashFlows): array
    {
        $operations = [];

        foreach ($cashFlows as $flow) {
            $concepts = [];

            if ($flow->type === 'income' && $flow->posTransaction && !empty($flow->posTransaction->items_payload)) {
                foreach ($flow->posTransaction->items_payload as $item) {
                    $qty = max(1, (int) ($item['quantity'] ?? 1));
                    $sec = $item['section'] ?? '';
                    $unitAmt = (float) ($item['amount'] ?? 0);
                    $comm = (float) ($item['commission'] ?? 0);
                    $lineAmt = ($sec === 'prestamo_apertura') ? $comm : ($unitAmt * $qty);

                    $concepts[] = [
                        'title' => $item['title'] ?? ($item['label'] ?? ($item['description'] ?? 'Concepto')),
                        'amount' => $lineAmt,
                        'commission' => $comm,
                        'quantity' => $qty,
                        'section' => $sec,
                    ];
                }
            } else {
                $expenseTypeLabel = $flow->type === 'expense' ? $flow->resolved_expense_type_label : null;
                $concepts[] = [
                    'title' => $flow->description ?: ($expenseTypeLabel ?: ($flow->module ?: 'Operación en Caja')),
                    'amount' => (float) $flow->amount,
                    'commission' => (float) ($flow->commission_amount ?? 0),
                    'quantity' => 1,
                    'section' => $flow->type === 'expense' ? 'egreso' : 'general',
                    'expense_type' => $expenseTypeLabel,
                    'expense_reason' => $flow->type === 'expense' ? $flow->description : null,
                ];
            }

            $expenseTypeLabel = $flow->type === 'expense' ? $flow->resolved_expense_type_label : null;

            $operations[] = [
                'id' => $flow->id,
                'created_at' => $flow->created_at,
                'time' => $flow->created_at ? $flow->created_at->format('H:i') : '--:--',
                'customer_name' => $flow->customer_display_name,
                'external_customer_id' => $flow->external_customer_id ? (int) $flow->external_customer_id : null,
                'is_external_customer' => !empty($flow->external_customer_id),
                'payment_method' => $flow->payment_method ?? 'efectivo',
                'total_amount' => (float) $flow->amount,
                'type' => $flow->type,
                'module' => $expenseTypeLabel ?: ($flow->module ?: 'General'),
                'expense_type_name' => $expenseTypeLabel,
                'expense_reason' => $flow->type === 'expense' ? $flow->description : null,
                'reference' => $flow->reference,
                'user_name' => $flow->user?->name ?? 'Cajero',
                'concepts' => $concepts,
            ];
        }

        return $operations;
    }

    /**
     * Construye el desglose explícito de egresos (tipo de egreso, motivo e importe) para el Corte X / Z.
     */
    protected function buildExpenseDetails($cashFlows): array
    {
        return collect($cashFlows)
            ->where('type', 'expense')
            ->values()
            ->map(function (CashFlow $flow) {
                $typeLabel = $flow->resolved_expense_type_label ?: ($flow->module ?: 'Egreso en Caja');
                $reason = trim((string) ($flow->description ?? ''));
                if ($reason === '' && $flow->customer_name) {
                    $reason = $flow->customer_name;
                }

                return [
                    'id' => $flow->id,
                    'time' => $flow->created_at ? $flow->created_at->format('H:i') : '--:--',
                    'expense_type' => $typeLabel,
                    'reason' => $reason !== '' ? $reason : 'Sin motivo adicional',
                    'amount' => round((float) $flow->effective_cash_amount > 0 ? (float) $flow->effective_cash_amount : (float) $flow->amount, 2),
                    'user_name' => $flow->user?->name ?? 'Cajero',
                    'reference' => $flow->reference,
                ];
            })
            ->all();
    }

    /**
     * Obtener el total de notas pendientes de pago (saldo pendiente / due) dejadas en la fecha.
     */
    protected function resolvePendingDueTotal(int $branchId, string $date): float
    {
        $orderDue = (float) Order::where('branch_id', $branchId)
            ->whereDate('order_date', $date)
            ->where('due', '>', 0)
            ->sum('due');

        $posDue = (float) PosTransaction::where('branch_id', $branchId)
            ->whereDate('created_at', $date)
            ->whereNotIn('status', ['cancelado', 'cancelada'])
            ->get()
            ->sum(function ($tx) {
                $netPaid = max(0, (float) $tx->paid_amount - (float) $tx->change_amount);
                return max(0, round((float) $tx->total_amount - $netPaid, 2));
            });

        return round(max($orderDue, $posDue), 2);
    }

    /**
     * Vista CRUD del Catálogo de Tipos de Egreso / Salidas de Caja.
     */
    public function expenseTypesIndex(Request $request)
    {
        $search = trim((string) $request->input('search', ''));
        $expenseTypes = ExpenseType::withCount('cashFlows')
            ->when($search !== '', function ($q) use ($search) {
                $q->where(function ($sub) use ($search) {
                    $sub->where('name', 'like', "%{$search}%")
                        ->orWhere('examples', 'like', "%{$search}%")
                        ->orWhere('slug', 'like', "%{$search}%");
                });
            })
            ->ordered()
            ->get();

        return view('cash.expense-types', compact('expenseTypes', 'search'));
    }

    /**
     * Crear un nuevo tipo de egreso en el catálogo.
     */
    public function storeExpenseType(Request $request)
    {
        $validated = $request->validate([
            'name' => 'required|string|max:160',
            'examples' => 'nullable|string|max:255',
            'sort_order' => 'nullable|integer|min:1|max:9998',
            'requires_reason' => 'nullable|boolean',
            'is_active' => 'nullable|boolean',
        ]);

        $baseSlug = Str::slug($validated['name']) ?: 'tipo-egreso';
        $slug = $baseSlug;
        if (ExpenseType::where('slug', $slug)->exists()) {
            $slug = $baseSlug . '-' . strtolower(Str::random(4));
        }

        $nextOrder = ((int) ExpenseType::where('is_other', false)->max('sort_order')) + 1;
        $sortOrder = isset($validated['sort_order']) ? (int) $validated['sort_order'] : min(9998, max(1, $nextOrder));

        ExpenseType::create([
            'name' => trim($validated['name']),
            'examples' => trim((string) ($validated['examples'] ?? '')) ?: null,
            'slug' => $slug,
            'is_other' => false,
            'requires_reason' => $request->boolean('requires_reason'),
            'sort_order' => $sortOrder,
            'is_active' => $request->has('is_active') ? $request->boolean('is_active') : true,
        ]);

        return back()->with('success', 'Tipo de egreso agregado correctamente al catálogo.');
    }

    /**
     * Actualizar un tipo de egreso existente del catálogo.
     */
    public function updateExpenseType(Request $request, ExpenseType $expenseType)
    {
        $validated = $request->validate([
            'name' => 'required|string|max:160',
            'examples' => 'nullable|string|max:255',
            'sort_order' => 'nullable|integer|min:1|max:9999',
            'requires_reason' => 'nullable|boolean',
            'is_active' => 'nullable|boolean',
        ]);

        $isOther = (bool) $expenseType->is_other;

        $expenseType->update([
            'name' => trim($validated['name']),
            'examples' => trim((string) ($validated['examples'] ?? '')) ?: null,
            'requires_reason' => $isOther ? true : $request->boolean('requires_reason'),
            'sort_order' => $isOther ? 9999 : min(9998, max(1, (int) ($validated['sort_order'] ?? $expenseType->sort_order))),
            'is_active' => $isOther ? true : ($request->has('is_active') ? $request->boolean('is_active') : $expenseType->is_active),
        ]);

        return back()->with('success', "Tipo de egreso \"{$expenseType->name}\" actualizado correctamente.");
    }

    /**
     * Activar / desactivar rápidamente un tipo de egreso.
     */
    public function toggleExpenseType(ExpenseType $expenseType)
    {
        if ($expenseType->is_other) {
            return back()->with('error', 'El tipo "Otro (motivo obligatorio)" siempre debe permanecer activo al final del catálogo.');
        }

        $expenseType->update([
            'is_active' => !$expenseType->is_active,
        ]);

        $estado = $expenseType->is_active ? 'activado' : 'desactivado';
        return back()->with('success', "Tipo de egreso \"{$expenseType->name}\" {$estado}.");
    }

    /**
     * Eliminar un tipo de egreso del catálogo preservando el nombre histórico en cortes previos.
     */
    public function destroyExpenseType(ExpenseType $expenseType)
    {
        if ($expenseType->is_other) {
            return back()->with('error', 'El tipo "Otro (motivo obligatorio)" no se puede eliminar porque es respaldo obligatorio al final del menú.');
        }

        $name = $expenseType->name;

        DB::transaction(function () use ($expenseType, $name) {
            CashFlow::where('expense_type_id', $expenseType->id)
                ->where(function ($q) {
                    $q->whereNull('expense_type_name')->orWhere('expense_type_name', '');
                })
                ->update(['expense_type_name' => $name]);

            CashFlow::where('expense_type_id', $expenseType->id)
                ->update(['expense_type_id' => null]);

            $expenseType->delete();
        });

        return back()->with('success', "Tipo de egreso \"{$name}\" eliminado del catálogo.");
    }
}
