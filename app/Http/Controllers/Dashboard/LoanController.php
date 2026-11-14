<?php

namespace App\Http\Controllers\Dashboard;

use App\Http\Controllers\Controller;
use App\Http\Controllers\Traits\ModulePermissionTrait;
use App\Models\Branch;
use App\Models\Customer;
use App\Models\DailyCut;
use App\Models\Loan;
use App\Models\LoanInstallment;
use App\Models\LoanPayment;
use App\Models\LoanType;
use App\Services\CashFlowService;
use Barryvdh\DomPDF\Facade\Pdf;
use Carbon\Carbon;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Str;
use Illuminate\Validation\ValidationException;

class LoanController extends Controller
{
    use ModulePermissionTrait;

    protected ?string $permissionResource = 'prestamos';

    protected array $permissionMapping = [
        'index' => 'read',
        'articleCreditsIndex' => 'read',
        'create' => 'create',
        'articleCreditsCreate' => 'create',
        'store' => 'create',
        'show' => 'read',
        'checkCustomerAlerts' => 'read',
        'registerPayment' => 'abonar',
        'manualClose' => 'close_manual',
        'cancel' => 'delete',
        'printPromissoryNote' => 'read',
        'printDeliveryReceipt' => 'read',
        'uploadPromissoryNote' => 'edit',
        'downloadPromissoryNote' => 'read',
        'printTicket' => 'read',
        'loanTypesIndex' => 'catalogs',
        'storeLoanType' => 'catalogs',
        'updateLoanType' => 'catalogs',
        'toggleLoanType' => 'catalogs',
        'destroyLoanType' => 'catalogs',
    ];

    public function __construct()
    {
        $this->initializeModulePermission();
    }

    protected function canAccessAllBranches(): bool
    {
        return auth()->check() && (auth()->user()->isSuperAdmin() || auth()->user()->can('prestamos.all_branches'));
    }

    protected function resolveBranchId(?int $requestedBranchId = null): ?int
    {
        if ($this->canAccessAllBranches() && $requestedBranchId) {
            return $requestedBranchId;
        }
        return auth()->user()->branch_id ?? $requestedBranchId ?? Branch::value('id');
    }

    protected function authorizeBranch(Loan $loan): void
    {
        if (!$this->canAccessAllBranches() && $loan->branch_id && $loan->branch_id !== auth()->user()->branch_id) {
            abort(403, 'No tienes permiso para acceder a préstamos de otra sucursal.');
        }
    }

    public function index(Request $request)
    {
        return $this->renderIndex($request, null);
    }

    public function articleCreditsIndex(Request $request)
    {
        return $this->renderIndex($request, 'credito_articulo');
    }

    protected function renderIndex(Request $request, ?string $forcedOperationType = null)
    {
        $search = trim((string) $request->input('search', ''));
        $status = $request->input('status');
        $loanTypeId = $request->input('loan_type_id');
        $operationType = $forcedOperationType ?: $request->input('operation_type', 'prestamo');
        if (!in_array($operationType, ['prestamo', 'credito_articulo', 'all'], true)) {
            $operationType = 'prestamo';
        }
        $branchId = $this->canAccessAllBranches()
            ? $request->input('branch_id')
            : auth()->user()->branch_id;

        // Actualizar automáticamente a estado 'mora' aquellos préstamos/créditos activos con cuotas vencidas
        Loan::where('status', 'activo')
            ->when($branchId, fn($q) => $q->where('branch_id', $branchId))
            ->when($operationType !== 'all', fn($q) => $q->where('operation_type', $operationType))
            ->whereHas('installments', function ($q) {
                $q->whereIn('status', ['pendiente', 'parcial'])
                  ->whereDate('scheduled_date', '<', Carbon::today());
            })
            ->update(['status' => 'mora']);

        $loans = Loan::with(['customer', 'loanType', 'branch', 'user', 'installments'])
            ->when($branchId, fn($q) => $q->where('branch_id', $branchId))
            ->when($operationType !== 'all', fn($q) => $q->where('operation_type', $operationType))
            ->when($status, fn($q) => $q->where('status', $status))
            ->when($loanTypeId, fn($q) => $q->where('loan_type_id', $loanTypeId))
            ->when($search, function ($q) use ($search) {
                $q->where(function ($sub) use ($search) {
                    $sub->where('loan_code', 'like', "%{$search}%")
                        ->orWhere('article_name', 'like', "%{$search}%")
                        ->orWhere('article_brand', 'like', "%{$search}%")
                        ->orWhere('article_model', 'like', "%{$search}%")
                        ->orWhere('article_imei', 'like', "%{$search}%")
                        ->orWhere('article_serial', 'like', "%{$search}%")
                        ->orWhereHas('customer', function ($c) use ($search) {
                            $c->where('tit_name', 'like', "%{$search}%")
                              ->orWhere('tit_phone', 'like', "%{$search}%");
                        });
                });
            })
            ->orderByDesc('created_at')
            ->paginate(15)
            ->withQueryString();

        $loanTypes = LoanType::orderBy('name')->get();
        $branches = Branch::orderBy('name')->get();

        $statsQuery = Loan::when($branchId, fn($q) => $q->where('branch_id', $branchId))
            ->when($operationType !== 'all', fn($q) => $q->where('operation_type', $operationType));
        $stats = [
            'active_count' => (clone $statsQuery)->whereIn('status', ['activo', 'mora'])->count(),
            'mora_count' => (clone $statsQuery)->where('status', 'mora')->count(),
            'total_lent' => (clone $statsQuery)->where('status', '!=', 'cancelado')->sum('principal_amount'),
            'total_price' => (clone $statsQuery)->where('status', '!=', 'cancelado')->sum('article_price'),
            'total_down_payment' => (clone $statsQuery)->where('status', '!=', 'cancelado')->sum('down_payment'),
            'total_collected' => (clone $statsQuery)->where('status', '!=', 'cancelado')->sum('paid_amount'),
            'total_balance' => (clone $statsQuery)->where('status', '!=', 'cancelado')->sum('balance_amount'),
            'total_commissions' => (clone $statsQuery)->where('status', '!=', 'cancelado')->sum('opening_commission'),
        ];

        $isArticleCreditView = ($operationType === 'credito_articulo');

        return view('loans.index', compact(
            'loans',
            'loanTypes',
            'branches',
            'stats',
            'search',
            'status',
            'loanTypeId',
            'branchId',
            'operationType',
            'isArticleCreditView'
        ));
    }

    public function create(Request $request)
    {
        $operationType = $request->input('operation_type') === 'credito_articulo' ? 'credito_articulo' : 'prestamo';
        return $this->renderCreate($request, $operationType);
    }

    public function articleCreditsCreate(Request $request)
    {
        return $this->renderCreate($request, 'credito_articulo');
    }

    protected function renderCreate(Request $request, string $operationType = 'prestamo')
    {
        $customers = Customer::orderBy('tit_name')->get();
        $loanTypes = LoanType::where('is_active', true)->orderBy('name')->get();
        $branches = Branch::orderBy('name')->get();
        $selectedCustomerId = $request->input('customer_id');

        return view('loans.create', compact('customers', 'loanTypes', 'branches', 'selectedCustomerId', 'operationType'));
    }

    /**
     * Endpoint AJAX que detecta si el cliente tiene préstamos abiertos y alerta si tiene pagos pendientes / vencidos.
     */
    public function checkCustomerAlerts(Request $request)
    {
        $customerId = $request->input('customer_id');
        if (!$customerId) {
            return response()->json(['has_open_loans' => false, 'open_loans' => [], 'pending_installments' => []]);
        }

        $openLoans = Loan::with(['loanType', 'branch', 'installments' => function ($q) {
                $q->whereIn('status', ['pendiente', 'parcial', 'vencido'])->orderBy('scheduled_date');
            }])
            ->where('customer_id', $customerId)
            ->whereIn('status', ['activo', 'mora'])
            ->get();

        $pendingInstallments = [];
        $overdueCount = 0;

        foreach ($openLoans as $loan) {
            foreach ($loan->installments as $inst) {
                $isOverdue = $inst->scheduled_date && $inst->scheduled_date->lt(Carbon::today());
                if ($isOverdue) {
                    $overdueCount++;
                }
                $pendingInstallments[] = [
                    'loan_id' => $loan->id,
                    'loan_code' => $loan->loan_code,
                    'installment_number' => $inst->installment_number,
                    'scheduled_date' => $inst->scheduled_date ? $inst->scheduled_date->format('d/m/Y') : '-',
                    'scheduled_amount' => number_format((float) $inst->scheduled_amount, 2),
                    'remaining_amount' => number_format($inst->remaining_amount, 2),
                    'is_overdue' => $isOverdue,
                ];
            }
        }

        return response()->json([
            'has_open_loans' => $openLoans->isNotEmpty(),
            'open_loans_count' => $openLoans->count(),
            'has_pending_payments' => count($pendingInstallments) > 0,
            'overdue_count' => $overdueCount,
            'open_loans' => $openLoans->map(fn($l) => [
                'id' => $l->id,
                'loan_code' => $l->loan_code,
                'type_name' => $l->loanType?->name ?? 'General',
                'branch_name' => $l->branch?->name ?? 'Sucursal',
                'total_amount' => number_format((float) $l->total_amount, 2),
                'paid_amount' => number_format((float) $l->paid_amount, 2),
                'balance_amount' => number_format((float) $l->balance_amount, 2),
                'status' => strtoupper($l->status),
                'url' => route('loans.show', $l->id),
            ]),
            'pending_installments' => $pendingInstallments,
        ]);
    }

    /**
     * Resuelve método de pago y montos desglosados (efectivo/tarjeta/transferencia) sin duplicar lógica.
     *
     * @return array{method: string, cash: float, card: float, transfer: float}
     */
    protected function resolvePaymentBreakdown(Request $request, float $amount, string $methodField, string $cashField, string $cardField, string $transferField): array
    {
        $method = (string) $request->input($methodField, 'efectivo');
        if (!in_array($method, ['efectivo', 'tarjeta', 'transferencia', 'mixto'], true)) {
            $method = 'efectivo';
        }

        $cash = $method === 'mixto' ? round((float) $request->input($cashField, 0), 2) : ($method === 'efectivo' ? $amount : 0);
        $card = $method === 'mixto' ? round((float) $request->input($cardField, 0), 2) : ($method === 'tarjeta' ? $amount : 0);
        $transfer = $method === 'mixto' ? round((float) $request->input($transferField, 0), 2) : ($method === 'transferencia' ? $amount : 0);

        if (($cash + $card + $transfer) <= 0 && $amount > 0) {
            $cash = $amount;
            $method = 'efectivo';
        }

        return [
            'method' => $method,
            'cash' => $cash,
            'card' => $card,
            'transfer' => $transfer,
        ];
    }

    /**
     * Valida las condiciones particulares del enganche y método de pago en venta a crédito de artículos.
     *
     * @return array<string, string>
     */
    protected function validateArticleCreditConditions(array $validated, ?float $cashPrice, float $downPayment, ?int $branchId): array
    {
        $errors = [];

        if ($cashPrice !== null && $downPayment >= $cashPrice) {
            $errors['down_payment'] = 'El enganche no puede ser mayor o igual al precio de contado; debe existir un saldo financiado mayor a $0.00.';
        }

        if ($downPayment > 0) {
            $downMethod = $validated['down_payment_method'] ?? null;
            if (empty($downMethod)) {
                $errors['down_payment_method'] = 'Selecciona el método de pago del enganche cuando el enganche es mayor a $0.00.';
            } elseif ($downMethod === 'mixto') {
                $mixSum = round(
                    (float) ($validated['down_payment_cash'] ?? 0)
                    + (float) ($validated['down_payment_card'] ?? 0)
                    + (float) ($validated['down_payment_transfer'] ?? 0),
                    2
                );
                if (abs($mixSum - $downPayment) > 0.01) {
                    $errors['down_payment_method'] = 'El desglose del pago mixto debe sumar exactamente $' . number_format($downPayment, 2) . '.';
                }
            }

            if (!$branchId) {
                $errors['down_payment'] = 'No se pudo determinar una sucursal con caja activa para registrar el enganche.';
            }
        }

        return $errors;
    }

    public function store(Request $request)
    {
        $operationType = $request->input('operation_type') === 'credito_articulo' ? 'credito_articulo' : 'prestamo';

        $validated = $request->validate([
            'operation_type' => 'nullable|in:prestamo,credito_articulo',
            'delivery_method' => 'nullable|in:efectivo,transferencia,mercancia',
            'customer_id' => 'required|exists:customers,id',
            'loan_type_id' => 'nullable|exists:loan_types,id',
            'branch_id' => 'nullable|exists:branches,id',
            'principal_amount' => ($operationType === 'credito_articulo' ? 'nullable|numeric|min:0' : 'required|numeric|min:1'),
            'article_price' => 'nullable|numeric|min:0',
            'down_payment' => 'nullable|numeric|min:0',
            'down_payment_method' => 'nullable|in:efectivo,tarjeta,transferencia,mixto',
            'down_payment_cash' => 'nullable|numeric|min:0',
            'down_payment_card' => 'nullable|numeric|min:0',
            'down_payment_transfer' => 'nullable|numeric|min:0',
            'article_status' => 'nullable|in:nuevo,seminuevo,servicio',
            'article_name' => 'nullable|string|max:200',
            'article_brand' => 'nullable|string|max:120',
            'article_model' => 'nullable|string|max:120',
            'article_color' => 'nullable|string|max:80',
            'article_capacity' => 'nullable|string|max:80',
            'article_imei' => 'nullable|string|max:80',
            'article_serial' => 'nullable|string|max:120',
            'article_warranty' => 'nullable|string|max:150',
            'article_observations' => 'nullable|string|max:2000',
            'installment_amount' => ($operationType === 'credito_articulo' ? 'required' : 'nullable') . '|numeric|min:0.01',
            'interest_rate' => 'nullable|numeric|min:0',
            'opening_commission' => 'nullable|numeric|min:0',
            'payment_frequency' => 'required|in:12_26,quincenal,semanal,mensual',
            'term_periods' => 'required|integer|min:1|max:240',
            'start_date' => 'required|date',
            'first_payment_date' => 'nullable|date',
            'payment_day_of_week' => 'nullable|integer|between:1,7',
            'payment_day_of_month' => 'nullable|integer|between:1,31',
            'commission_payment_method' => 'nullable|in:efectivo,tarjeta,transferencia,mixto',
            'commission_cash' => 'nullable|numeric|min:0',
            'commission_card' => 'nullable|numeric|min:0',
            'commission_transfer' => 'nullable|numeric|min:0',
            'aval_name' => 'nullable|string|max:150',
            'aval_phone' => 'nullable|string|max:50',
            'aval_address' => 'nullable|string|max:255',
            'type_specific_data' => 'nullable|array',
            'video_evidence' => 'nullable|file|mimetypes:video/mp4,video/webm,video/quicktime,video/x-msvideo|max:102400',
            'promissory_note_file' => 'nullable|file|mimes:pdf,jpg,jpeg,png|max:15360',
            'notes' => 'nullable|string|max:2000',
        ]);

        $branchId = $this->resolveBranchId($request->input('branch_id'));
        $periods = (int) $validated['term_periods'];
        $articlePrice = null;
        $downPayment = 0.0;
        $creditSaleTotal = null;

        if ($operationType === 'credito_articulo') {
            $cashPrice = (isset($validated['article_price']) && $validated['article_price'] !== '' && (float) $validated['article_price'] > 0)
                ? round((float) $validated['article_price'], 2)
                : null;
            $downPayment = round((float) ($validated['down_payment'] ?? 0), 2);

            $articleErrors = $this->validateArticleCreditConditions($validated, $cashPrice, $downPayment, $branchId);
            if (!empty($articleErrors)) {
                return back()->withInput()->withErrors($articleErrors);
            }

            $manualPrincipal = (isset($validated['principal_amount']) && $validated['principal_amount'] !== '' && (float) $validated['principal_amount'] > 0)
                ? (float) $validated['principal_amount']
                : null;

            $summary = Loan::calculateArticleCreditSummary(
                (float) $validated['installment_amount'],
                $periods,
                $downPayment,
                $cashPrice,
                $manualPrincipal
            );

            $articlePrice = $summary['cash_price'];
            $principal = $summary['principal_amount'];
            $installmentAmount = $summary['installment_amount'];
            $totalAmount = $summary['installments_total'];
            $creditSaleTotal = $summary['credit_sale_total'];
            $interestAmount = $summary['interest_amount'];
            $rate = $summary['interest_rate'];
            $openingCommission = 0.0;
        } else {
            $principal = round((float) $validated['principal_amount'], 2);

            // Si se captura el abono por cuota directamente, se calculan total, interés y tasa
            if (!empty($validated['installment_amount']) && (float) $validated['installment_amount'] > 0) {
                $installmentAmount = round((float) $validated['installment_amount'], 2);
                $totalAmount = round($installmentAmount * $periods, 2);
                $interestAmount = max(0, round($totalAmount - $principal, 2));
                $rate = $principal > 0 ? round(($interestAmount / $principal) * 100, 2) : 0;
            } else {
                $rate = round((float) ($validated['interest_rate'] ?? 0), 2);
                $interestAmount = round($principal * ($rate / 100), 2);
                $totalAmount = round($principal + $interestAmount, 2);
                $installmentAmount = round($totalAmount / $periods, 2);
            }

            $openingCommission = round((float) ($validated['opening_commission'] ?? 0), 2);
        }

        $deliveryMethod = $validated['delivery_method']
            ?? ($operationType === 'credito_articulo' ? 'mercancia' : null);

        $videoPath = null;
        if ($request->hasFile('video_evidence')) {
            $videoPath = $request->file('video_evidence')->store('private/loans/videos');
        }

        $promissoryPath = null;
        if ($request->hasFile('promissory_note_file')) {
            $promissoryPath = $request->file('promissory_note_file')->store('public/loans/pagares');
        }

        $loan = DB::transaction(function () use (
            $request,
            $validated,
            $operationType,
            $deliveryMethod,
            $branchId,
            $articlePrice,
            $downPayment,
            $principal,
            $rate,
            $interestAmount,
            $totalAmount,
            $creditSaleTotal,
            $periods,
            $installmentAmount,
            $openingCommission,
            $videoPath,
            $promissoryPath
        ) {
            if ($operationType === 'credito_articulo' && $downPayment > 0 && DailyCut::hasTodayCorteZ((int) $branchId, true)) {
                throw ValidationException::withMessages([
                    'down_payment' => 'No es posible registrar el enganche en caja porque el Corte Final (Corte Z) del día de hoy ya fue aplicado en esta sucursal.',
                ]);
            }

            $prefix = $operationType === 'credito_articulo' ? 'CRED-' : 'PREST-';
            $loanCode = $prefix . date('Ymd') . '-' . strtoupper(Str::random(4));
            $startDate = Carbon::parse($validated['start_date']);
            $firstPaymentDate = !empty($validated['first_payment_date']) ? Carbon::parse($validated['first_payment_date']) : null;
            $dayOfWeek = !empty($validated['payment_day_of_week']) ? (int) $validated['payment_day_of_week'] : null;
            $dayOfMonth = !empty($validated['payment_day_of_month']) ? (int) $validated['payment_day_of_month'] : null;

            $installmentDates = Loan::calculateInstallmentDates(
                $startDate,
                $validated['payment_frequency'],
                $periods,
                $firstPaymentDate,
                $dayOfWeek,
                $dayOfMonth
            );

            $dueDate = Carbon::parse(end($installmentDates));

            $typeSpecificData = $validated['type_specific_data'] ?? [];
            $cust = Customer::find($validated['customer_id']);
            $custUpdates = [];

            foreach ([
                'aval_name' => ['key' => 'aval_nombre', 'col' => 'aval_name'],
                'aval_phone' => ['key' => 'aval_telefono', 'col' => 'aval_phone'],
                'aval_address' => ['key' => 'aval_domicilio', 'col' => 'aval_address'],
            ] as $inputField => $map) {
                if ($request->filled($inputField)) {
                    $val = trim((string) $request->input($inputField));
                    $typeSpecificData[$map['key']] = $val;
                    if ($cust && ($cust->{$map['col']} !== $val)) {
                        $custUpdates[$map['col']] = $val;
                    }
                }
            }
            if ($cust && !empty($custUpdates)) {
                $cust->update($custUpdates);
            }

            $downBreak = $downPayment > 0
                ? $this->resolvePaymentBreakdown($request, $downPayment, 'down_payment_method', 'down_payment_cash', 'down_payment_card', 'down_payment_transfer')
                : null;

            $loan = Loan::create([
                'loan_code' => $loanCode,
                'operation_type' => $operationType,
                'delivery_method' => $deliveryMethod,
                'branch_id' => $branchId,
                'customer_id' => $validated['customer_id'],
                'user_id' => auth()->id(),
                'loan_type_id' => $validated['loan_type_id'] ?? null,
                'article_price' => $articlePrice,
                'down_payment' => $downPayment,
                'down_payment_method' => $downBreak['method'] ?? null,
                'article_status' => $validated['article_status'] ?? null,
                'article_name' => $validated['article_name'] ?? null,
                'article_brand' => $validated['article_brand'] ?? null,
                'article_model' => $validated['article_model'] ?? null,
                'article_color' => $validated['article_color'] ?? null,
                'article_capacity' => $validated['article_capacity'] ?? null,
                'article_imei' => $validated['article_imei'] ?? null,
                'article_serial' => $validated['article_serial'] ?? null,
                'article_warranty' => $validated['article_warranty'] ?? null,
                'article_observations' => $validated['article_observations'] ?? null,
                'principal_amount' => $principal,
                'interest_rate' => $rate,
                'interest_amount' => $interestAmount,
                'total_amount' => $totalAmount,
                'credit_sale_total' => $creditSaleTotal,
                'opening_commission' => $openingCommission,
                'paid_amount' => 0,
                'balance_amount' => $totalAmount,
                'payment_frequency' => $validated['payment_frequency'],
                'term_periods' => $periods,
                'installment_amount' => $installmentAmount,
                'start_date' => $startDate->toDateString(),
                'first_payment_date' => $firstPaymentDate?->toDateString(),
                'due_date' => $dueDate->toDateString(),
                'type_specific_data' => $typeSpecificData,
                'video_path' => $videoPath,
                'promissory_note_path' => $promissoryPath,
                'status' => 'activo',
                'notes' => $validated['notes'] ?? null,
            ]);

            // Generar tabla de pagos pendientes programados
            $accumulated = 0;
            for ($i = 1; $i <= $periods; $i++) {
                $schedDate = $installmentDates[$i - 1];
                $quota = ($i === $periods)
                    ? round($totalAmount - $accumulated, 2)
                    : $installmentAmount;
                $accumulated += $quota;

                LoanInstallment::create([
                    'loan_id' => $loan->id,
                    'installment_number' => $i,
                    'scheduled_date' => $schedDate,
                    'scheduled_amount' => $quota,
                    'paid_amount' => 0,
                    'status' => 'pendiente',
                ]);
            }

            // En venta a crédito de artículos: si hay enganche > 0, sí ingresa a Caja hoy
            if ($operationType === 'credito_articulo' && $downPayment > 0 && $downBreak) {
                $engancheCode = 'ENG-' . date('Ymd') . '-' . strtoupper(Str::random(4));
                LoanPayment::create([
                    'loan_id' => $loan->id,
                    'branch_id' => $branchId,
                    'user_id' => auth()->id(),
                    'payment_code' => $engancheCode,
                    'payment_type' => 'enganche',
                    'amount' => $downPayment,
                    'payment_method' => $downBreak['method'],
                    'payment_breakdown' => [
                        'efectivo' => $downBreak['cash'],
                        'tarjeta' => $downBreak['card'],
                        'transferencia' => $downBreak['transfer'],
                    ],
                    'paid_at' => now(),
                    'notes' => "Enganche inicial de venta a crédito {$loan->loan_code} ({$loan->article_summary_label})",
                ]);

                CashFlowService::register(
                    'income',
                    $downPayment,
                    "Enganche Crédito Artículo {$loan->loan_code} ({$loan->article_summary_label})",
                    $loan->loan_code,
                    'Crédito de Artículos (Enganche)',
                    $branchId,
                    [
                        'customer_id' => $cust?->id,
                        'customer_name' => $cust?->tit_name,
                        'payment_method' => $downBreak['method'],
                        'cash_amount' => $downBreak['cash'],
                        'card_amount' => $downBreak['card'],
                        'transfer_amount' => $downBreak['transfer'],
                    ]
                );
            }

            // REGLA CONTABLE CRÍTICA:
            // El desembolso del préstamo NO tiene impacto de egreso en caja.
            // ÚNICAMENTE se refleja en caja la "Comisión por apertura" si es > 0.
            if ($openingCommission > 0) {
                $commBreak = $this->resolvePaymentBreakdown($request, $openingCommission, 'commission_payment_method', 'commission_cash', 'commission_card', 'commission_transfer');
                $paymentCode = 'COM-' . date('Ymd') . '-' . strtoupper(Str::random(4));

                LoanPayment::create([
                    'loan_id' => $loan->id,
                    'branch_id' => $branchId,
                    'user_id' => auth()->id(),
                    'payment_code' => $paymentCode,
                    'payment_type' => 'comision_apertura',
                    'amount' => $openingCommission,
                    'payment_method' => $commBreak['method'],
                    'payment_breakdown' => [
                        'efectivo' => $commBreak['cash'],
                        'tarjeta' => $commBreak['card'],
                        'transferencia' => $commBreak['transfer'],
                    ],
                    'paid_at' => now(),
                    'notes' => ($operationType === 'credito_articulo' ? 'Comisión por apertura de crédito ' : 'Comisión por apertura de préstamo ') . $loan->loan_code,
                ]);

                CashFlowService::register(
                    'income',
                    $openingCommission,
                    ($operationType === 'credito_articulo' ? "Comisión por apertura de crédito {$loan->loan_code}" : "Comisión por apertura de préstamo {$loan->loan_code}"),
                    $loan->loan_code,
                    ($operationType === 'credito_articulo' ? 'Crédito de Artículos (Comisión Apertura)' : 'Préstamos (Comisión Apertura)'),
                    $branchId,
                    [
                        'customer_id' => $cust?->id,
                        'customer_name' => $cust?->tit_name,
                        'payment_method' => $commBreak['method'],
                        'cash_amount' => $commBreak['cash'],
                        'card_amount' => $commBreak['card'],
                        'transfer_amount' => $commBreak['transfer'],
                        'commission_amount' => $openingCommission,
                    ]
                );
            }

            return $loan;
        });

        $successMsg = $loan->isArticleCredit()
            ? "Venta a crédito {$loan->loan_code} registrada correctamente. Ya puedes imprimir el Pagaré y el Recibo de Entrega de Mercancía."
            : "Préstamo {$loan->loan_code} creado correctamente. Puedes imprimir el Pagaré y el Recibo de Entrega para firma del cliente.";

        return redirect()
            ->route('loans.show', $loan->id)
            ->with('success', $successMsg);
    }

    public function show(Loan $loan)
    {
        $this->authorizeBranch($loan);
        $loan->load(['customer', 'loanType', 'branch', 'user', 'closer', 'canceller', 'installments', 'payments.user', 'payments.installment']);

        // Detectar otros préstamos abiertos del mismo cliente
        $otherOpenLoans = Loan::where('customer_id', $loan->customer_id)
            ->where('id', '!=', $loan->id)
            ->whereIn('status', ['activo', 'mora'])
            ->get();

        return view('loans.show', compact('loan', 'otherOpenLoans'));
    }

    /**
     * Registrar abono a préstamo (registra día programado y día real de pago e impacta caja).
     */
    public function registerPayment(Request $request, Loan $loan)
    {
        $this->authorizeBranch($loan);

        $validated = $request->validate([
            'amount' => 'required|numeric|min:0.01',
            'installment_id' => 'nullable|exists:loan_installments,id',
            'paid_date' => 'nullable|date',
            'payment_method' => 'required|in:efectivo,tarjeta,transferencia,mixto',
            'cash_amount' => 'nullable|numeric|min:0',
            'card_amount' => 'nullable|numeric|min:0',
            'transfer_amount' => 'nullable|numeric|min:0',
            'notes' => 'nullable|string|max:500',
        ]);

        if (!$loan->isOpen()) {
            return back()->with('error', 'Este préstamo ya se encuentra cerrado o liquidado.');
        }

        $amount = round((float) $validated['amount'], 2);
        $paidAt = !empty($validated['paid_date']) ? Carbon::parse($validated['paid_date']) : now();
        $method = $validated['payment_method'];

        $cashAmount = $method === 'mixto' ? round((float) $request->input('cash_amount', 0), 2) : ($method === 'efectivo' ? $amount : 0);
        $cardAmount = $method === 'mixto' ? round((float) $request->input('card_amount', 0), 2) : ($method === 'tarjeta' ? $amount : 0);
        $transferAmount = $method === 'mixto' ? round((float) $request->input('transfer_amount', 0), 2) : ($method === 'transferencia' ? $amount : 0);

        if ($method === 'mixto' && abs(($cashAmount + $cardAmount + $transferAmount) - $amount) > 0.05) {
            return back()->with('error', 'La suma del desglose mixto (Efectivo + Tarjeta + Transferencia) debe coincidir con el monto total del abono.');
        }

        $payment = DB::transaction(function () use (
            $loan,
            $validated,
            $amount,
            $paidAt,
            $method,
            $cashAmount,
            $cardAmount,
            $transferAmount
        ) {
            $lockedLoan = Loan::where('id', $loan->id)->lockForUpdate()->firstOrFail();
            $remainingToApply = $amount;
            $primaryInstallment = null;
            $scheduledDate = null;

            if (!empty($validated['installment_id'])) {
                $inst = LoanInstallment::where('loan_id', $lockedLoan->id)
                    ->where('id', $validated['installment_id'])
                    ->lockForUpdate()
                    ->first();

                if ($inst && $inst->status !== 'pagado') {
                    $primaryInstallment = $inst;
                    $scheduledDate = $inst->scheduled_date;
                    $needed = max(0, (float) $inst->scheduled_amount - (float) $inst->paid_amount);
                    $applied = min($remainingToApply, $needed);
                    $inst->paid_amount = round((float) $inst->paid_amount + $applied, 2);
                    $inst->paid_date = $paidAt;
                    $inst->status = ($inst->paid_amount >= $inst->scheduled_amount) ? 'pagado' : 'parcial';
                    $inst->save();
                    $remainingToApply = round($remainingToApply - $applied, 2);
                }
            }

            // Aplicar excedente o monto general en orden de cuotas pendientes
            if ($remainingToApply > 0) {
                $pendingInstallments = LoanInstallment::where('loan_id', $lockedLoan->id)
                    ->whereIn('status', ['pendiente', 'parcial', 'vencido'])
                    ->orderBy('installment_number')
                    ->lockForUpdate()
                    ->get();

                foreach ($pendingInstallments as $inst) {
                    if ($remainingToApply <= 0) {
                        break;
                    }
                    if (!$primaryInstallment) {
                        $primaryInstallment = $inst;
                        $scheduledDate = $inst->scheduled_date;
                    }
                    $needed = max(0, (float) $inst->scheduled_amount - (float) $inst->paid_amount);
                    if ($needed <= 0) {
                        continue;
                    }
                    $applied = min($remainingToApply, $needed);
                    $inst->paid_amount = round((float) $inst->paid_amount + $applied, 2);
                    $inst->paid_date = $paidAt;
                    $inst->status = ($inst->paid_amount >= $inst->scheduled_amount) ? 'pagado' : 'parcial';
                    $inst->save();
                    $remainingToApply = round($remainingToApply - $applied, 2);
                }
            }

            $lockedLoan->paid_amount = round((float) $lockedLoan->paid_amount + $amount, 2);
            $lockedLoan->balance_amount = max(0, round((float) $lockedLoan->total_amount - (float) $lockedLoan->paid_amount, 2));

            $stillPending = LoanInstallment::where('loan_id', $lockedLoan->id)
                ->whereIn('status', ['pendiente', 'parcial', 'vencido'])
                ->exists();

            if (!$stillPending && $lockedLoan->balance_amount <= 0) {
                $lockedLoan->status = 'liquidado';
            } else {
                $hasOverdue = LoanInstallment::where('loan_id', $lockedLoan->id)
                    ->whereIn('status', ['pendiente', 'parcial', 'vencido'])
                    ->whereDate('scheduled_date', '<', Carbon::today())
                    ->exists();
                $lockedLoan->status = $hasOverdue ? 'mora' : 'activo';
            }
            $lockedLoan->save();

            $paymentCode = 'ABPR-' . date('Ymd') . '-' . strtoupper(Str::random(4));
            $branchId = auth()->user()->branch_id ?? $lockedLoan->branch_id;

            $payment = LoanPayment::create([
                'loan_id' => $lockedLoan->id,
                'installment_id' => $primaryInstallment?->id,
                'branch_id' => $branchId,
                'user_id' => auth()->id(),
                'payment_code' => $paymentCode,
                'payment_type' => 'abono',
                'amount' => $amount,
                'payment_method' => $method,
                'payment_breakdown' => [
                    'efectivo' => $cashAmount,
                    'tarjeta' => $cardAmount,
                    'transferencia' => $transferAmount,
                ],
                'scheduled_date' => $scheduledDate,
                'paid_at' => $paidAt,
                'notes' => $validated['notes'] ?? null,
            ]);

            CashFlowService::register(
                'income',
                $amount,
                $lockedLoan->isArticleCredit()
                    ? "Abono a crédito de artículo {$lockedLoan->loan_code}"
                    : "Abono a préstamo {$lockedLoan->loan_code}",
                $paymentCode,
                $lockedLoan->isArticleCredit()
                    ? 'Crédito de Artículos (Abono)'
                    : 'Préstamos (Abono)',
                $branchId,
                [
                    'customer_id' => $lockedLoan->customer_id,
                    'customer_name' => $lockedLoan->customer?->tit_name,
                    'payment_method' => $method,
                    'cash_amount' => $cashAmount,
                    'card_amount' => $cardAmount,
                    'transfer_amount' => $transferAmount,
                ]
            );

            return $payment;
        });

        return redirect()
            ->route('loans.show', $loan->id)
            ->with('success', "Abono {$payment->payment_code} registrado correctamente.")
            ->with('print_ticket_url', route('loans.ticket', $payment->id));
    }

    /**
     * Permitir cerrar préstamo de forma manual sin importar si la cantidad total abonada
     * es inferior o superior al monto total.
     */
    public function manualClose(Request $request, Loan $loan)
    {
        $this->authorizeBranch($loan);

        $validated = $request->validate([
            'closing_reason' => 'required|string|max:1000',
            'additional_payment' => 'nullable|numeric|min:0',
            'payment_method' => 'nullable|in:efectivo,tarjeta,transferencia',
        ]);

        DB::transaction(function () use ($request, $loan, $validated) {
            $lockedLoan = Loan::where('id', $loan->id)->lockForUpdate()->firstOrFail();
            $additional = round((float) ($validated['additional_payment'] ?? 0), 2);
            $branchId = auth()->user()->branch_id ?? $lockedLoan->branch_id;

            if ($additional > 0) {
                $method = $validated['payment_method'] ?? 'efectivo';
                $paymentCode = 'CIER-' . date('Ymd') . '-' . strtoupper(Str::random(4));

                $cashAmount = $method === 'efectivo' ? $additional : 0;
                $cardAmount = $method === 'tarjeta' ? $additional : 0;
                $transferAmount = $method === 'transferencia' ? $additional : 0;

                LoanPayment::create([
                    'loan_id' => $lockedLoan->id,
                    'branch_id' => $branchId,
                    'user_id' => auth()->id(),
                    'payment_code' => $paymentCode,
                    'payment_type' => 'cierre_manual',
                    'amount' => $additional,
                    'payment_method' => $method,
                    'payment_breakdown' => [
                        'efectivo' => $cashAmount,
                        'tarjeta' => $cardAmount,
                        'transferencia' => $transferAmount,
                    ],
                    'paid_at' => now(),
                    'notes' => 'Abono final al cerrar préstamo manualmente: ' . $validated['closing_reason'],
                ]);

                CashFlowService::register(
                    'income',
                    $additional,
                    "Pago en cierre manual de préstamo {$lockedLoan->loan_code}",
                    $paymentCode,
                    'Préstamos (Cierre Manual)',
                    $branchId,
                    [
                        'customer_id' => $lockedLoan->customer_id,
                        'customer_name' => $lockedLoan->customer?->tit_name,
                        'payment_method' => $method,
                        'cash_amount' => $cashAmount,
                        'card_amount' => $cardAmount,
                        'transfer_amount' => $transferAmount,
                    ]
                );

                $lockedLoan->paid_amount = round((float) $lockedLoan->paid_amount + $additional, 2);
            }

            // Marcar cuotas restantes como cerradas manualmente
            LoanInstallment::where('loan_id', $lockedLoan->id)
                ->whereIn('status', ['pendiente', 'parcial', 'vencido'])
                ->update([
                    'status' => 'cerrado_manual',
                    'notes' => 'Cerrado manualmente: ' . Str::limit($validated['closing_reason'], 120),
                ]);

            $lockedLoan->status = 'cerrado_manual';
            $lockedLoan->closed_manually_at = now();
            $lockedLoan->closed_by = auth()->id();
            $lockedLoan->closing_reason = $validated['closing_reason'];
            $lockedLoan->closing_final_paid = $lockedLoan->paid_amount;
            $lockedLoan->balance_amount = 0;
            $lockedLoan->save();
        });

        return redirect()
            ->route('loans.show', $loan->id)
            ->with('success', "El préstamo {$loan->loan_code} ha sido cerrado manualmente con un total abonado de $" . number_format((float) $loan->fresh()->paid_amount, 2));
    }

    /**
     * Cancelar un préstamo por error de captura o desistimiento sin generar movimientos de caja ni alterar cortes.
     */
    public function cancel(Request $request, Loan $loan)
    {
        $this->authorizeBranch($loan);

        $validated = $request->validate([
            'cancellation_reason' => 'required|string|max:1000',
        ]);

        if ($loan->status === 'cancelado') {
            return back()->with('warning', "El préstamo {$loan->loan_code} ya se encuentra cancelado.");
        }

        DB::transaction(function () use ($loan, $validated) {
            $lockedLoan = Loan::lockForUpdate()->findOrFail($loan->id);

            if ($lockedLoan->status === 'cancelado') {
                return;
            }

            LoanInstallment::where('loan_id', $lockedLoan->id)
                ->where('status', '!=', 'cancelado')
                ->update([
                    'status' => 'cancelado',
                    'notes' => 'Préstamo cancelado: ' . Str::limit($validated['cancellation_reason'], 120),
                ]);

            $lockedLoan->status = 'cancelado';
            $lockedLoan->cancelled_at = now();
            $lockedLoan->cancelled_by = auth()->id();
            $lockedLoan->cancellation_reason = $validated['cancellation_reason'];
            $lockedLoan->balance_amount = 0;
            $lockedLoan->save();
        });

        return redirect()
            ->route('loans.show', $loan->id)
            ->with('success', "El préstamo {$loan->loan_code} fue cancelado correctamente sin afectar caja ni cortes anteriores.");
    }

    /**
     * Renderiza documentos legales del préstamo o venta a crédito (vista imprimible o PDF).
     */
    protected function renderLegalDocument(Loan $loan, string $viewName, string $filePrefix)
    {
        $this->authorizeBranch($loan);
        $loan->load(['customer', 'loanType', 'branch', 'installments']);

        if (request()->boolean('pdf')) {
            $pdf = Pdf::loadView($viewName, compact('loan'))->setPaper('letter', 'portrait');
            return $pdf->stream("{$filePrefix}_{$loan->loan_code}.pdf");
        }

        return view($viewName, compact('loan'));
    }

    /**
     * Impresión de Pagaré Mercantil para firma del cliente y aval.
     */
    public function printPromissoryNote(Loan $loan)
    {
        return $this->renderLegalDocument($loan, 'loans.pagare-pdf', 'Pagare');
    }

    /**
     * Impresión de Recibo de Entrega de Préstamo / Recibo de Entrega de Mercancía por separado.
     */
    public function printDeliveryReceipt(Loan $loan)
    {
        $prefix = $loan->isArticleCredit() ? 'Recibo_Entrega_Mercancia' : 'Recibo_Entrega_Prestamo';
        return $this->renderLegalDocument($loan, 'loans.delivery-receipt-pdf', $prefix);
    }

    /**
     * Carga del archivo de Pagaré firmado por el cliente.
     */
    public function uploadPromissoryNote(Request $request, Loan $loan)
    {
        $this->authorizeBranch($loan);

        $request->validate([
            'promissory_note_file' => 'required|file|mimes:pdf,jpg,jpeg,png|max:15360',
        ]);

        if ($loan->promissory_note_path && Storage::exists($loan->promissory_note_path)) {
            Storage::delete($loan->promissory_note_path);
        }

        $path = $request->file('promissory_note_file')->store('public/loans/pagares');
        $loan->update(['promissory_note_path' => $path]);

        return back()->with('success', 'Archivo de Pagaré firmado cargado correctamente.');
    }

    public function downloadPromissoryNote(Loan $loan)
    {
        $this->authorizeBranch($loan);

        if (!$loan->promissory_note_path || !Storage::exists($loan->promissory_note_path)) {
            abort(404, 'El archivo del pagaré no existe.');
        }

        return Storage::download($loan->promissory_note_path, "Pagare_Firmado_{$loan->loan_code}." . pathinfo($loan->promissory_note_path, PATHINFO_EXTENSION));
    }

    /**
     * Video del préstamo: ÚNICAMENTE el rol SuperAdmin puede verlo y editarlo.
     */
    public function streamVideo(Loan $loan)
    {
        if (!auth()->check() || !auth()->user()->isSuperAdmin()) {
            abort(403, 'Acceso restringido: Únicamente el rol SuperAdmin puede ver el video de evidencia del préstamo.');
        }

        if (!$loan->video_path || !Storage::exists($loan->video_path)) {
            abort(404, 'No hay video registrado para este préstamo.');
        }

        return Storage::response($loan->video_path);
    }

    public function updateVideo(Request $request, Loan $loan)
    {
        if (!auth()->check() || !auth()->user()->isSuperAdmin()) {
            abort(403, 'Acceso restringido: Únicamente el rol SuperAdmin puede editar o reemplazar el video del préstamo.');
        }

        $request->validate([
            'video_evidence' => 'required|file|mimetypes:video/mp4,video/webm,video/quicktime,video/x-msvideo|max:102400',
        ]);

        if ($loan->video_path && Storage::exists($loan->video_path)) {
            Storage::delete($loan->video_path);
        }

        $path = $request->file('video_evidence')->store('private/loans/videos');
        $loan->update(['video_path' => $path]);

        return back()->with('success', 'Video de evidencia actualizado correctamente (SuperAdmin).');
    }

    public function deleteVideo(Loan $loan)
    {
        if (!auth()->check() || !auth()->user()->isSuperAdmin()) {
            abort(403, 'Acceso restringido: Únicamente el rol SuperAdmin puede eliminar el video del préstamo.');
        }

        if ($loan->video_path && Storage::exists($loan->video_path)) {
            Storage::delete($loan->video_path);
        }

        $loan->update(['video_path' => null]);

        return back()->with('success', 'Video de evidencia eliminado (SuperAdmin).');
    }

    /**
     * Ticket de pago de préstamo: NO muestra el saldo pendiente por privacidad.
     */
    public function printTicket(LoanPayment $payment)
    {
        $payment->load(['loan.customer', 'loan.loanType', 'loan.branch', 'installment', 'user']);
        $this->authorizeBranch($payment->loan);

        return view('loans.ticket', compact('payment'));
    }

    /**
     * Vista CRUD completa del Catálogo de Tipos de Préstamo.
     */
    public function loanTypesIndex(Request $request)
    {
        $search = trim((string) $request->input('search', ''));
        $loanTypes = LoanType::withCount('loans')
            ->when($search, function ($q) use ($search) {
                $q->where('name', 'like', "%{$search}%")
                  ->orWhere('description', 'like', "%{$search}%")
                  ->orWhere('slug', 'like', "%{$search}%");
            })
            ->orderByDesc('is_active')
            ->orderBy('name')
            ->get();

        return view('loans.types', compact('loanTypes', 'search'));
    }

    /**
     * Normaliza el arreglo de campos dinámicos requeridos por un tipo de préstamo.
     */
    protected function parseLoanTypeFields(Request $request): array
    {
        $fields = [];

        if ($request->has('fields') && is_array($request->input('fields'))) {
            foreach ($request->input('fields') as $item) {
                $label = trim((string) ($item['label'] ?? ''));
                if ($label === '') {
                    continue;
                }
                $type = in_array($item['type'] ?? 'text', ['text', 'number', 'date', 'textarea'])
                    ? $item['type']
                    : 'text';
                $key = !empty($item['key']) ? Str::slug($item['key'], '_') : Str::slug($label, '_');
                if ($key === '') {
                    $key = 'campo_' . count($fields);
                }

                $fields[] = [
                    'key' => $key,
                    'label' => $label,
                    'type' => $type,
                    'required' => !empty($item['required']) && ($item['required'] == '1' || $item['required'] === true || $item['required'] === 'true'),
                ];
            }
        } elseif ($request->has('field_labels') && is_array($request->input('field_labels'))) {
            $types = $request->input('field_types', []);
            $reqs = $request->input('field_required', []);

            foreach ($request->input('field_labels') as $idx => $label) {
                $label = trim((string) $label);
                if ($label === '') {
                    continue;
                }
                $type = in_array($types[$idx] ?? 'text', ['text', 'number', 'date', 'textarea'])
                    ? $types[$idx]
                    : 'text';
                $fields[] = [
                    'key' => Str::slug($label, '_') ?: ('campo_' . $idx),
                    'label' => $label,
                    'type' => $type,
                    'required' => !empty($reqs[$idx]),
                ];
            }
        }

        return $fields;
    }

    /**
     * Crear un nuevo Tipo de Préstamo (CRUD Create).
     */
    public function storeLoanType(Request $request)
    {
        $validated = $request->validate([
            'name' => 'required|string|max:150',
            'description' => 'nullable|string|max:500',
            'default_interest_rate' => 'required|numeric|min:0',
            'default_opening_fee' => 'required|numeric|min:0',
            'requires_collateral' => 'nullable|boolean',
            'default_frequency' => 'required|in:12_26,quincenal,semanal,mensual',
            'default_periods' => 'required|integer|min:1',
            'is_active' => 'nullable|boolean',
        ]);

        $fields = $this->parseLoanTypeFields($request);

        $baseSlug = Str::slug($validated['name']) ?: 'tipo-prestamo';
        $slug = $baseSlug;
        if (LoanType::where('slug', $slug)->exists()) {
            $slug = $baseSlug . '-' . strtolower(Str::random(4));
        }

        LoanType::create([
            'name' => $validated['name'],
            'slug' => $slug,
            'description' => $validated['description'] ?? null,
            'default_interest_rate' => $validated['default_interest_rate'],
            'default_opening_fee' => $validated['default_opening_fee'],
            'requires_collateral' => $request->boolean('requires_collateral'),
            'default_frequency' => $validated['default_frequency'],
            'default_periods' => $validated['default_periods'],
            'required_fields' => $fields,
            'is_active' => $request->has('is_active') ? $request->boolean('is_active') : true,
        ]);

        return back()->with('success', 'Tipo de préstamo creado correctamente en el catálogo.');
    }

    /**
     * Actualizar un Tipo de Préstamo existente (CRUD Update).
     */
    public function updateLoanType(Request $request, LoanType $loanType)
    {
        $validated = $request->validate([
            'name' => 'required|string|max:150',
            'description' => 'nullable|string|max:500',
            'default_interest_rate' => 'required|numeric|min:0',
            'default_opening_fee' => 'required|numeric|min:0',
            'requires_collateral' => 'nullable|boolean',
            'default_frequency' => 'required|in:12_26,quincenal,semanal,mensual',
            'default_periods' => 'required|integer|min:1',
            'is_active' => 'nullable|boolean',
        ]);

        $fields = $this->parseLoanTypeFields($request);

        $loanType->update([
            'name' => $validated['name'],
            'description' => $validated['description'] ?? null,
            'default_interest_rate' => $validated['default_interest_rate'],
            'default_opening_fee' => $validated['default_opening_fee'],
            'requires_collateral' => $request->boolean('requires_collateral'),
            'default_frequency' => $validated['default_frequency'],
            'default_periods' => $validated['default_periods'],
            'required_fields' => $fields,
            'is_active' => $request->has('is_active') ? $request->boolean('is_active') : $loanType->is_active,
        ]);

        return back()->with('success', "Tipo de préstamo '{$loanType->name}' actualizado correctamente.");
    }

    /**
     * Activar / Desactivar un Tipo de Préstamo rápidamente.
     */
    public function toggleLoanType(LoanType $loanType)
    {
        $loanType->update([
            'is_active' => !$loanType->is_active,
        ]);

        $estado = $loanType->is_active ? 'activado' : 'desactivado';
        return back()->with('success', "Tipo de préstamo '{$loanType->name}' {$estado}.");
    }

    /**
     * Eliminar un Tipo de Préstamo del catálogo (CRUD Delete).
     */
    public function destroyLoanType(LoanType $loanType)
    {
        $name = $loanType->name;
        $loanType->delete();

        return back()->with('success', "Tipo de préstamo '{$name}' eliminado del catálogo.");
    }
}

