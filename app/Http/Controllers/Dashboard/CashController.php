<?php

namespace App\Http\Controllers\Dashboard;

use App\Console\Commands\AutoDailyCutCommand;
use App\Http\Controllers\Controller;
use App\Http\Controllers\Traits\ModulePermissionTrait;
use App\Models\Branch;
use App\Models\CashFlow;
use App\Models\DailyCut;
use App\Models\OrderDetails;
use App\Models\PaymentHistory;
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

        $totalIncome = $allForPeriod->where('type', 'income')->sum('amount');
        $totalExpense = $allForPeriod->where('type', 'expense')->sum(fn($f) => $f->effective_cash_amount);
        $balance = $totalIncome - $totalExpense;

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
            'balance',
            'perPage',
            'customerMovements',
            'branches',
            'branchId'
        ));
    }

    public function dailyCut(Request $request)
    {
        $selectedDate = $request->input('date') ? Carbon::parse($request->input('date'))->toDateString() : Carbon::today()->toDateString();
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

        $specialProviderPayments = PaymentHistory::with('user')
            ->where('branch_id', $branchId)
            ->whereDate('created_at', $selectedDate)
            ->whereIn('order_id', $specialProviderOrderIds)
            ->get();

        $externalPayments = PaymentHistory::with('user')
            ->where('branch_id', $branchId)
            ->whereDate('created_at', $selectedDate)
            ->whereIn('method', ['Cheque', 'Due'])
            ->whereNotIn('order_id', $specialProviderOrderIds)
            ->get();

        $cashFlows = CashFlow::with('user')
            ->where('branch_id', $branchId)
            ->whereDate('created_at', $selectedDate)
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

        $allDailyCashFlows = CashFlow::with('user')
            ->where('branch_id', $branchId)
            ->whereDate('created_at', $selectedDate)
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

        // El saldo inicial del día es el efectivo físico declarado del Corte Z anterior
        $openingBalance = DailyCut::resolveOpeningBalance($branchId, $selectedDate);
        // El efectivo esperado en caja se calcula solo como: saldo inicial + ingresos en efectivo - egresos en efectivo
        $balance = round($openingBalance + $handCash - $totalExpense, 2);

        // Corte Z final del día (si ya se aplicó)
        $finalDailyCut = DailyCut::with('user')
            ->where('branch_id', $branchId)
            ->whereDate('date', $selectedDate)
            ->where('cut_type', 'corte_z')
            ->latest('id')
            ->first();

        // Pre-Cortes (Cortes X) realizados en el día
        $preCuts = DailyCut::with('user')
            ->where('branch_id', $branchId)
            ->whereDate('date', $selectedDate)
            ->where('cut_type', 'pre_corte')
            ->orderByDesc('created_at')
            ->get();

        $dailyCut = $finalDailyCut ?: (object) [
            'id' => null,
            'date' => $selectedDate,
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

        // Desglose por cliente de todo el movimiento por día
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

        return view('cash.daily-cut', compact(
            'cashFlows',
            'specialProviderPayments',
            'dailyCut',
            'finalDailyCut',
            'preCuts',
            'externalPayments',
            'handCash',
            'cheque',
            'due',
            'totalBanco',
            'search',
            'selectedDate',
            'customerBreakdown',
            'moduleBreakdown',
            'branches',
            'branchId',
            'currentBranch'
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
            'declared_cash' => 'required|numeric|min:0',
            'withdraw_amount' => 'nullable|numeric|min:0',
            'notes' => 'nullable|string|max:500',
        ], [
            'declared_cash.required' => 'Debes capturar el efectivo físico contado en cajón para aplicar el Corte Final (Corte Z).',
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

        $expectedCash = round($openingBalance + $cashIncome - $totalExpense, 2);
        $finalBalance = $expectedCash;
        $declaredCash = round((float) $validated['declared_cash'], 2);
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
        $date = $request->input('date', Carbon::today()->toDateString());
        $branchId = $this->resolveBranchId($request);

        if ($cutId) {
            $dailyCut = DailyCut::with(['branch', 'user'])->where('branch_id', $branchId)->findOrFail($cutId);
            $date = $dailyCut->date->toDateString();
        } else {
            $dailyCut = DailyCut::with(['branch', 'user'])
                ->where('branch_id', $branchId)
                ->whereDate('date', $date)
                ->orderByDesc('id')
                ->first();
        }

        $cashFlows = CashFlow::with('user')
            ->where('branch_id', $branchId)
            ->whereDate('created_at', $date)
            ->orderBy('created_at')
            ->get();

        if (!$dailyCut) {
            $incomes = $cashFlows->where('type', 'income');
            $expenses = $cashFlows->where('type', 'expense');
            $totalIncome = round((float) $incomes->sum('amount'), 2);
            $cashIncome = round((float) $incomes->sum(fn($f) => $f->effective_cash_amount), 2);
            $cardIncome = round((float) $incomes->sum(fn($f) => $f->effective_card_amount), 2);
            $transferIncome = round((float) $incomes->sum(fn($f) => $f->effective_transfer_amount), 2);
            $totalExpense = round((float) $expenses->sum(fn($f) => $f->effective_cash_amount), 2);
            $openingBalance = DailyCut::resolveOpeningBalance($branchId, $date);
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
                'balance' => round($openingBalance + $cashIncome - $totalExpense, 2),
            ];
        }

        $customerBreakdown = $cashFlows->groupBy(fn($f) => $f->customer_display_name);

        return view('cash.print', compact('dailyCut', 'cashFlows', 'customerBreakdown'));
    }
}
