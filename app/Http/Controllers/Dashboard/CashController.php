<?php

namespace App\Http\Controllers\Dashboard;

use App\Console\Commands\AutoDailyCutCommand;
use App\Http\Controllers\Controller;
use App\Http\Controllers\Traits\ModulePermissionTrait;
use App\Models\Branch;
use App\Models\CashFlow;
use App\Models\DailyCut;
use App\Models\Order;
use App\Models\OrderDetails;
use App\Models\PaymentHistory;
use App\Models\PosTransaction;
use App\Services\CashFlowService;
use Carbon\Carbon;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;

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
        if ($request && $this->canAccessAllBranches() && $request->filled('branch_id')) {
            return (int) $request->input('branch_id');
        }
        return (int) (auth()->user()->branch_id ?? Branch::value('id') ?? 1);
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
        $branchId = $this->resolveBranchId($request);
        $this->ensurePastDaysAutoClosed($branchId);

        $cashFlows = CashFlow::with(['user', 'branch'])
            ->where('branch_id', $branchId)
            ->when($date, fn($q) => $q->whereDate('created_at', $date))
            ->orderByDesc('created_at')
            ->paginate($perPage)
            ->withQueryString();

        $allForPeriod = CashFlow::where('branch_id', $branchId)
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
                return [
                    'customer_name' => $customerName,
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
            'branchId'
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

        $branchId = $this->resolveBranchId($request);
        $this->ensurePastDaysAutoClosed($branchId);

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
            ->where('branch_id', $branchId)
            ->tap($dateScope)
            ->whereIn('order_id', $specialProviderOrderIds)
            ->get();

        $externalPayments = PaymentHistory::with('user')
            ->where('branch_id', $branchId)
            ->tap($dateScope)
            ->whereIn('method', ['Cheque', 'Due'])
            ->whereNotIn('order_id', $specialProviderOrderIds)
            ->get();

        $cashFlows = CashFlow::with(['user', 'posTransaction'])
            ->where('branch_id', $branchId)
            ->tap($dateScope)
            ->when($search, function ($q) use ($search) {
                $q->where(function ($sub) use ($search) {
                    $sub->where('customer_name', 'like', "%{$search}%")
                        ->orWhere('description', 'like', "%{$search}%")
                        ->orWhere('reference', 'like', "%{$search}%")
                        ->orWhere('module', 'like', "%{$search}%")
                        ->orWhere('payment_method', 'like', "%{$search}%");
                });
            })
            ->orderByDesc('created_at')
            ->paginate(15)
            ->withQueryString();

        $allDailyCashFlows = CashFlow::with(['user', 'posTransaction'])
            ->where('branch_id', $branchId)
            ->tap($dateScope)
            ->when($search, function ($q) use ($search) {
                $q->where(function ($sub) use ($search) {
                    $sub->where('customer_name', 'like', "%{$search}%")
                        ->orWhere('description', 'like', "%{$search}%")
                        ->orWhere('reference', 'like', "%{$search}%")
                        ->orWhere('module', 'like', "%{$search}%")
                        ->orWhere('payment_method', 'like', "%{$search}%");
                });
            })
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
        if ($filterType === 'dia') {
            $openingBalance = DailyCut::resolveOpeningBalance($branchId, $selectedDate);
            $finalDailyCut = DailyCut::with('user')
                ->where('branch_id', $branchId)
                ->whereDate('date', $selectedDate)
                ->where('cut_type', 'corte_z')
                ->latest('id')
                ->first();

            $preCuts = DailyCut::with('user')
                ->where('branch_id', $branchId)
                ->whereDate('date', $selectedDate)
                ->where('cut_type', 'pre_corte')
                ->orderByDesc('created_at')
                ->get();
        } else {
            $openingBalance = $filterType === 'rango'
                ? DailyCut::resolveOpeningBalance($branchId, $startDate)
                : 0.0;
            $finalDailyCut = null;
            $preCuts = collect();
        }

        // Historial de cortes para el filtro solicitado (día, rango o todos)
        $cutsHistory = DailyCut::with(['user', 'branch'])
            ->where('branch_id', $branchId)
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
                return [
                    'customer_name' => $clientName,
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
            : Branch::where('id', $branchId)->get();
        $currentBranch = $branches->firstWhere('id', $branchId) ?? Branch::find($branchId);

        $pendingDueTotal = $filterType === 'dia' ? $this->resolvePendingDueTotal($branchId, $selectedDate) : 0.0;
        $expectedCash = round($openingBalance + $handCash - $totalExpense, 2);
        $chronologicalOperations = $this->buildChronologicalOperations($cashFlows);

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
            'currentBranch',
            'pendingDueTotal',
            'expectedCash',
            'chronologicalOperations'
        ));
    }

    /**
     * Registrar un movimiento manual en Caja (con soporte de pagos mixtos y cliente nominal).
     */
    public function store(Request $request)
    {
        $validated = $request->validate([
            'type' => 'required|in:income,expense',
            'amount' => 'required|numeric|min:0.01',
            'customer_name' => 'nullable|string|max:190',
            'payment_method' => 'nullable|in:efectivo,tarjeta,transferencia,mixto',
            'cash_amount' => 'nullable|numeric|min:0',
            'card_amount' => 'nullable|numeric|min:0',
            'transfer_amount' => 'nullable|numeric|min:0',
            'description' => 'nullable|string|max:255',
            'reference' => 'nullable|string|max:100',
            'module' => 'nullable|string|max:100',
            'branch_id' => 'nullable|exists:branches,id',
        ]);

        $branchId = $this->resolveBranchId($request);
        $amount = round((float) $validated['amount'], 2);
        $method = $validated['payment_method'] ?? 'efectivo';

        if ($validated['type'] === 'expense' && $method !== 'efectivo') {
            return back()
                ->withInput()
                ->withErrors(['payment_method' => 'Los egresos de caja solo pueden registrarse en efectivo. Las transferencias y pagos con tarjeta nunca deben registrarse como egresos de caja.']);
        }

        $cashAmount = $method === 'mixto' ? round((float) $request->input('cash_amount', 0), 2) : ($method === 'efectivo' ? $amount : 0);
        $cardAmount = $method === 'mixto' ? round((float) $request->input('card_amount', 0), 2) : ($method === 'tarjeta' ? $amount : 0);
        $transferAmount = $method === 'mixto' ? round((float) $request->input('transfer_amount', 0), 2) : ($method === 'transferencia' ? $amount : 0);

        CashFlowService::register(
            $validated['type'],
            $amount,
            $validated['description'] ?? 'Movimiento manual de caja',
            $validated['reference'] ?? null,
            $validated['module'] ?? 'Movimiento Manual',
            $branchId,
            [
                'customer_name' => $validated['customer_name'] ?? 'Operación Manual en Caja',
                'payment_method' => $method,
                'cash_amount' => $cashAmount,
                'card_amount' => $cardAmount,
                'transfer_amount' => $transferAmount,
            ]
        );

        return redirect()->route('cash.index')->with('success', 'Movimiento registrado en caja correctamente.');
    }

    /**
     * Generar Pre-Corte (Corte X) sin cerrar la caja del día.
     */
    public function applyPreCut(Request $request)
    {
        $today = $request->input('date') ? Carbon::parse($request->input('date'))->toDateString() : Carbon::today()->toDateString();
        $branchId = $this->resolveBranchId($request);

        $flows = CashFlow::where('branch_id', $branchId)
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

        $flows = CashFlow::where('branch_id', $branchId)
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
                return [
                    'customer_name' => $clientName,
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
        $branchId = $this->resolveBranchId($request);
        $filterType = $request->input('filter_type');

        // Si se solicita un corte específico por cut_id, siempre imprimir ese corte individual
        if ($cutId) {
            $dailyCut = DailyCut::with(['branch', 'user'])->where('branch_id', $branchId)->findOrFail($cutId);
            $date = $dailyCut->date ? $dailyCut->date->toDateString() : Carbon::parse($dailyCut->created_at)->toDateString();

            $cashFlows = CashFlow::with(['user', 'posTransaction'])
                ->where('branch_id', $branchId)
                ->whereDate('created_at', $date)
                ->orderBy('created_at')
                ->get();

            $openingBalance = (float) $dailyCut->opening_balance;
            $cashIncome = (float) $dailyCut->cash_income;
            $totalExpense = (float) $dailyCut->total_expense;
            $expectedCash = round($openingBalance + $cashIncome - $totalExpense, 2);
            $pendingDueTotal = $this->resolvePendingDueTotal($branchId, $date);
            $chronologicalOperations = $this->buildChronologicalOperations($cashFlows);

            return view('cash.print', compact('dailyCut', 'cashFlows', 'expectedCash', 'pendingDueTotal', 'chronologicalOperations'));
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

        $branch = Branch::find($branchId);

        if ($filterType === 'rango' || $filterType === 'todos') {
            $startDate = $request->input('start_date') ? Carbon::parse($request->input('start_date'))->toDateString() : Carbon::today()->subDays(7)->toDateString();
            $endDate = $request->input('end_date') ? Carbon::parse($request->input('end_date'))->toDateString() : Carbon::today()->toDateString();
            if ($startDate > $endDate) {
                $tmp = $startDate;
                $startDate = $endDate;
                $endDate = $tmp;
            }

            $cutsQuery = DailyCut::with(['branch', 'user'])->where('branch_id', $branchId);
            $flowsQuery = CashFlow::with(['user', 'posTransaction'])->where('branch_id', $branchId);

            if ($filterType === 'rango') {
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
                'chronologicalOperations'
            ));
        }

        // Modo 'dia' sin cut_id (comportamiento original para corte de un día específico)
        $date = $request->input('date') ? Carbon::parse($request->input('date'))->toDateString() : Carbon::today()->toDateString();
        $dailyCut = DailyCut::with(['branch', 'user'])
            ->where('branch_id', $branchId)
            ->whereDate('date', $date)
            ->orderByDesc('id')
            ->first();

        $cashFlows = CashFlow::with(['user', 'posTransaction'])
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

        return view('cash.print', compact('dailyCut', 'cashFlows', 'expectedCash', 'pendingDueTotal', 'chronologicalOperations'));
    }

    /**
     * Construir bitácora cronológica de operaciones agrupadas con detalle por concepto (bullets).
     */
    protected function buildChronologicalOperations($cashFlows): array
    {
        $operations = [];

        foreach ($cashFlows as $flow) {
            $concepts = [];

            if ($flow->posTransaction && !empty($flow->posTransaction->items_payload)) {
                foreach ($flow->posTransaction->items_payload as $item) {
                    $qty = max(1, (int) ($item['quantity'] ?? 1));
                    $sec = $item['section'] ?? '';
                    $unitAmt = (float) ($item['amount'] ?? 0);
                    $comm = (float) ($item['commission'] ?? 0);
                    $lineAmt = ($sec === 'prestamo_apertura') ? $comm : ($unitAmt * $qty);

                    $concepts[] = [
                        'title' => $item['title'] ?? 'Concepto',
                        'amount' => $lineAmt,
                        'commission' => $comm,
                        'quantity' => $qty,
                        'section' => $sec,
                    ];
                }
            } else {
                $concepts[] = [
                    'title' => $flow->description ?: ($flow->module ?: 'Operación en Caja'),
                    'amount' => (float) $flow->amount,
                    'commission' => (float) ($flow->commission_amount ?? 0),
                    'quantity' => 1,
                    'section' => 'general',
                ];
            }

            $operations[] = [
                'id' => $flow->id,
                'created_at' => $flow->created_at,
                'time' => $flow->created_at ? $flow->created_at->format('H:i') : '--:--',
                'customer_name' => $flow->customer_display_name,
                'payment_method' => $flow->payment_method ?? 'efectivo',
                'total_amount' => (float) $flow->amount,
                'type' => $flow->type,
                'module' => $flow->module ?: 'General',
                'reference' => $flow->reference,
                'user_name' => $flow->user?->name ?? 'Cajero',
                'concepts' => $concepts,
            ];
        }

        return $operations;
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
            ->where('status', '!=', 'cancelada')
            ->get()
            ->sum(function ($tx) {
                $netPaid = max(0, (float) $tx->paid_amount - (float) $tx->change_amount);
                return max(0, round((float) $tx->total_amount - $netPaid, 2));
            });

        return round(max($orderDue, $posDue), 2);
    }
}
