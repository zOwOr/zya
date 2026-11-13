<?php

namespace App\Http\Controllers\Dashboard;

use App\Http\Controllers\Controller;
use App\Http\Controllers\Traits\ModulePermissionTrait;
use App\Models\Branch;
use App\Models\DailyCut;
use App\Models\FinFinanciera;
use App\Models\FinSale;
use App\Models\FinSaleNote;
use App\Models\FinSalePayment;
use App\Models\Order;
use App\Models\PosTransaction;
use App\Models\Product;
use App\Services\CashFlowService;
use App\Services\DateFilterService;
use Barryvdh\DomPDF\Facade\Pdf;
use Carbon\Carbon;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use PhpOffice\PhpSpreadsheet\Cell\DataType;
use PhpOffice\PhpSpreadsheet\Spreadsheet;
use PhpOffice\PhpSpreadsheet\Writer\Xlsx;

class DashboardController extends Controller
{
    use ModulePermissionTrait;

    protected ?string $permissionResource = 'dashboard';

    protected array $permissionMapping = [
        'index' => 'read',
        'exportFinancieraPaymentsPdf' => 'financieras.export_pdf',
        'exportFinancieraPaymentsExcel' => 'financieras.export_excel',
        'exportVentasPdf' => 'ventas.export_pdf',
        'exportVentasExcel' => 'ventas.export_excel',
    ];

    public function __construct()
    {
        $this->initializeModulePermission();
    }

    protected function canAccessAllBranches(): bool
    {
        return auth()->check() && (
            auth()->user()->isSuperAdmin() ||
            auth()->user()->can('dashboard.all_branches') ||
            auth()->user()->can('cobros.all_branches') ||
            auth()->user()->can('financieras.all_branches')
        );
    }

    protected function resolveBranchFilter(Request $request): ?int
    {
        if ($this->canAccessAllBranches()) {
            return $request->filled('branch_id') ? (int) $request->input('branch_id') : null;
        }
        return (int) (auth()->user()->branch_id ?? 1);
    }

    protected function resolveFinancieraDateFilter(Request $request): array
    {
        return DateFilterService::resolve($request, [
            'prefix' => 'fin_',
            'default_type' => 'dia',
            'history_permissions' => ['dashboard.filter_all_dates'],
            'fallback_unprefixed' => true,
        ]);
    }

    protected function resolveMovementsDateFilter(Request $request): array
    {
        return DateFilterService::resolve($request, [
            'prefix' => 'mov_',
            'default_type' => 'dia',
            'history_permissions' => ['dashboard.filter_all_dates'],
        ]);
    }

    protected function buildFinancieraPaymentsQuery(Request $request, ?array $dateFilter = null)
    {
        $dateFilter = $dateFilter ?? $this->resolveFinancieraDateFilter($request);
        $financieraId = $request->input('financiera_id');
        $branchId = $this->resolveBranchFilter($request);
        $search = trim((string) $request->input('fin_search', ''));

        $query = FinSalePayment::with(['sale.financiera', 'financiera', 'branch', 'user', 'posTransaction', 'externalCustomer'])
            ->where(function ($q) {
                $q->whereNull('pos_transaction_id')
                    ->orWhereHas('posTransaction', fn($pt) => $pt->where('status', '!=', 'cancelado'));
            });

        DateFilterService::apply($query, 'payment_date', $dateFilter);

        return $query
            ->when($financieraId, fn($q) => $q->where('financiera_id', $financieraId))
            ->when($branchId, fn($q) => $q->where('branch_id', $branchId))
            ->when($search !== '', function ($q) use ($search) {
                $q->where(function ($sub) use ($search) {
                    $sub->where('receipt_code', 'like', "%{$search}%")
                        ->orWhere('tag_contrato', 'like', "%{$search}%")
                        ->orWhere('imei', 'like', "%{$search}%")
                        ->orWhere('customer_name', 'like', "%{$search}%")
                        ->orWhereHas('externalCustomer', function ($ext) use ($search) {
                            $ext->where('tit_name', 'like', "%{$search}%")
                                ->orWhere('tit_phone', 'like', "%{$search}%");
                        });
                });
            })
            ->orderByDesc('payment_date')
            ->orderByDesc('id');
    }

    protected function buildMovementsQuery(Request $request, ?array $dateFilter = null)
    {
        $dateFilter = $dateFilter ?? $this->resolveMovementsDateFilter($request);
        $branchId = $this->resolveBranchFilter($request);
        $status = trim((string) $request->input('mov_status', ''));
        $search = trim((string) $request->input('mov_search', ''));

        $query = PosTransaction::with(['branch', 'user', 'cashFlows', 'externalCustomer']);
        DateFilterService::apply($query, 'created_at', $dateFilter);

        return $query
            ->when($branchId, fn($q) => $q->where('branch_id', $branchId))
            ->when(in_array($status, ['completado', 'cancelado'], true), fn($q) => $q->where('status', $status))
            ->when($search !== '', function ($q) use ($search) {
                $q->where(function ($sub) use ($search) {
                    $sub->where('transaction_code', 'like', "%{$search}%")
                        ->orWhere('customer_name', 'like', "%{$search}%")
                        ->orWhere('customer_phone', 'like', "%{$search}%")
                        ->orWhere('notes', 'like', "%{$search}%")
                        ->orWhereHas('externalCustomer', function ($ext) use ($search) {
                            $ext->where('tit_name', 'like', "%{$search}%")
                                ->orWhere('tit_phone', 'like', "%{$search}%");
                        });
                });
            })
            ->orderByDesc('created_at')
            ->orderByDesc('id');
    }

    public function index(Request $request)
    {
        $finDateFilter = $this->resolveFinancieraDateFilter($request);
        $movDateFilter = $this->resolveMovementsDateFilter($request);
        $branchId = $this->resolveBranchFilter($request);

        $finPaymentsQuery = $this->buildFinancieraPaymentsQuery($request, $finDateFilter);
        $allFinPayments = (clone $finPaymentsQuery)->get();
        $financieraPayments = $finPaymentsQuery->paginate(15, ['*'], 'fin_page')->withQueryString();

        $finTotals = [
            'count' => $allFinPayments->count(),
            'amount' => round((float) $allFinPayments->sum('amount'), 2),
            'commission' => round((float) $allFinPayments->sum('commission_amount'), 2),
            'total_paid' => round((float) $allFinPayments->sum('total_amount'), 2),
            'cash' => round((float) $allFinPayments->sum(fn($p) => $p->cash_amount), 2),
            'card' => round((float) $allFinPayments->sum(fn($p) => $p->card_amount), 2),
            'transfer' => round((float) $allFinPayments->sum(fn($p) => $p->transfer_amount), 2),
        ];

        $posTransactionsQuery = $this->buildMovementsQuery($request, $movDateFilter);
        $allMovementsDay = (clone $posTransactionsQuery)->get();
        $dailyMovements = $posTransactionsQuery->paginate(15, ['*'], 'mov_page')->withQueryString();

        $completedMovements = $allMovementsDay->where('status', 'completado');
        $movTotals = [
            'count' => $completedMovements->count(),
            'total_rows' => $allMovementsDay->count(),
            'total' => round((float) $completedMovements->sum('total_amount'), 2),
            'cash' => round((float) $completedMovements->sum('cash_amount'), 2),
            'card' => round((float) $completedMovements->sum('card_amount'), 2),
            'transfer' => round((float) $completedMovements->sum('transfer_amount'), 2),
        ];

        $financieras = FinFinanciera::where('is_active', true)->orderBy('name')->get(['id', 'name']);
        if ($financieras->isEmpty()) {
            $financieras = FinFinanciera::orderBy('name')->get(['id', 'name']);
        }
        $branches = Branch::orderBy('name')->get(['id', 'name']);
        $todayClosedBranchIds = DailyCut::todayClosedBranchIds();

        return view('dashboard.index', [
            'total_paid' => Order::sum('pay'),
            'total_due' => Order::sum('due'),
            'complete_orders' => Order::where('order_status', 'complete')->get(),
            'products' => Product::orderBy('product_store')->take(5)->get(),
            'new_products' => Product::orderBy('buying_date')->take(2)->get(),
            'financieraPayments' => $financieraPayments,
            'finTotals' => $finTotals,
            'finDateFilter' => $finDateFilter,
            'finFilterType' => $finDateFilter['filter_type'],
            'finDate' => $finDateFilter['date'],
            'finStartDate' => $finDateFilter['start_date'] ?? $finDateFilter['date'],
            'finEndDate' => $finDateFilter['end_date'] ?? $finDateFilter['date'],
            'finPeriodLabel' => DateFilterService::periodLabel($finDateFilter),
            'dailyMovements' => $dailyMovements,
            'movTotals' => $movTotals,
            'movDateFilter' => $movDateFilter,
            'movFilterType' => $movDateFilter['filter_type'],
            'movDate' => $movDateFilter['date'],
            'movStartDate' => $movDateFilter['start_date'] ?? $movDateFilter['date'],
            'movEndDate' => $movDateFilter['end_date'] ?? $movDateFilter['date'],
            'movPeriodLabel' => DateFilterService::periodLabel($movDateFilter),
            'movStatus' => (string) $request->input('mov_status', ''),
            'movSearch' => (string) $request->input('mov_search', ''),
            'canDashboardAllDates' => $finDateFilter['can_all_dates'],
            'minAllowedDate' => $finDateFilter['min_allowed_date'],
            'dateRestrictionTriggered' => $finDateFilter['was_restricted'] || $movDateFilter['was_restricted'],
            'financieras' => $financieras,
            'branches' => $branches,
            'branchId' => $branchId,
            'todayClosedBranchIds' => $todayClosedBranchIds,
        ]);
    }

    /**
     * Exportar Pagos de Financieras por Filtro a PDF.
     */
    public function exportFinancieraPaymentsPdf(Request $request)
    {
        $finDateFilter = $this->resolveFinancieraDateFilter($request);
        $payments = $this->buildFinancieraPaymentsQuery($request, $finDateFilter)
            ->reorder('payment_date')
            ->orderBy('id')
            ->get();

        $finFilterType = $finDateFilter['filter_type'];
        $finStartDate = $finDateFilter['start_date'] ?? $finDateFilter['date'];
        $finEndDate = $finDateFilter['end_date'] ?? $finDateFilter['date'];
        $finPeriodLabel = DateFilterService::periodLabel($finDateFilter);
        $branchId = $this->resolveBranchFilter($request);
        $branchName = $branchId ? (Branch::find($branchId)?->name ?? 'Sucursal') : 'Todas las Sucursales';

        $groupedPayments = $payments
            ->groupBy(fn($p) => $p->financiera?->name ?? ($p->sale?->financiera?->name ?? 'Sin Financiera'))
            ->sortKeys()
            ->map(function ($items, $name) {
                return [
                    'name' => $name,
                    'payments' => $items,
                    'count' => $items->count(),
                    'amount' => round((float) $items->sum('amount'), 2),
                    'commission' => round((float) $items->sum('commission_amount'), 2),
                    'total' => round((float) $items->sum('total_amount'), 2),
                ];
            })
            ->values();

        $totals = [
            'count' => $payments->count(),
            'amount' => round((float) $payments->sum('amount'), 2),
            'commission' => round((float) $payments->sum('commission_amount'), 2),
            'total_paid' => round((float) $payments->sum('total_amount'), 2),
        ];

        $pdf = Pdf::loadView(
            'dashboard.financiera-payments-pdf',
            compact('payments', 'groupedPayments', 'totals', 'finFilterType', 'finStartDate', 'finEndDate', 'finPeriodLabel', 'branchName')
        )->setPaper('letter', 'portrait');

        $fileSuffix = DateFilterService::fileSuffix($finDateFilter);
        return $pdf->download("Abonos_Financieras_{$fileSuffix}.pdf");
    }

    /**
     * Exportar Pagos de Financieras por Filtro a Excel (.xlsx).
     */
    public function exportFinancieraPaymentsExcel(Request $request)
    {
        $finDateFilter = $this->resolveFinancieraDateFilter($request);
        $payments = $this->buildFinancieraPaymentsQuery($request, $finDateFilter)->get();

        $spreadsheet = new Spreadsheet();
        $sheet = $spreadsheet->getActiveSheet();
        $sheet->setTitle('Pagos Financieras');

        $headers = [
            'A1' => 'ID',
            'B1' => 'Fecha / Hora',
            'C1' => 'Financiera',
            'D1' => 'Device ID / TAG',
            'E1' => 'IMEI',
            'F1' => 'Cliente',
            'G1' => 'Sucursal',
            'H1' => 'Método de Pago',
            'I1' => 'Abono ($)',
            'J1' => 'Comisión ($)',
            'K1' => 'Total Cobrado ($)',
            'L1' => 'Efectivo ($)',
            'M1' => 'Tarjeta ($)',
            'N1' => 'Transferencia ($)',
            'O1' => 'Cajero',
        ];

        foreach ($headers as $cell => $label) {
            $sheet->setCellValue($cell, $label);
        }
        $sheet->getStyle('A1:O1')->getFont()->setBold(true);

        $row = 2;
        foreach ($payments as $p) {
            $sheet->setCellValue("A{$row}", $p->id);
            $sheet->setCellValue("B{$row}", optional($p->payment_date)->format('d/m/Y H:i'));
            $sheet->setCellValue("C{$row}", $p->financiera?->name ?? ($p->sale?->financiera?->name ?? 'Financiera'));
            $sheet->setCellValue("D{$row}", $p->tag_contrato);
            $sheet->setCellValueExplicit("E{$row}", (string) ($p->imei ?? ''), DataType::TYPE_STRING);
            $sheet->setCellValue("F{$row}", $p->customer_name ?? ($p->externalCustomer?->tit_name ?? ($p->sale?->customer_name ?? 'Cliente')));
            $sheet->setCellValue("G{$row}", $p->branch->name ?? 'General');
            $sheet->setCellValue("H{$row}", strtoupper((string) $p->payment_method));
            $sheet->setCellValue("I{$row}", (float) $p->amount);
            $sheet->setCellValue("J{$row}", (float) $p->commission_amount);
            $sheet->setCellValue("K{$row}", (float) $p->total_paid);
            $sheet->setCellValue("L{$row}", (float) $p->cash_amount);
            $sheet->setCellValue("M{$row}", (float) $p->card_amount);
            $sheet->setCellValue("N{$row}", (float) $p->transfer_amount);
            $sheet->setCellValue("O{$row}", $p->user->name ?? 'Sistema');
            $row++;
        }

        foreach (range('A', 'O') as $col) {
            $sheet->getColumnDimension($col)->setAutoSize(true);
        }

        $fileSuffix = DateFilterService::fileSuffix($finDateFilter);
        $fileName = "Pagos_Financieras_{$fileSuffix}.xlsx";
        $tempFile = tempnam(sys_get_temp_dir(), 'fin_xlsx_');
        $writer = new Xlsx($spreadsheet);
        $writer->save($tempFile);

        return response()->download($tempFile, $fileName)->deleteFileAfterSend(true);
    }

    /**
     * Exportar Movimientos / Ventas POS del Dashboard a PDF.
     */
    public function exportVentasPdf(Request $request)
    {
        $movDateFilter = $this->resolveMovementsDateFilter($request);
        $movements = $this->buildMovementsQuery($request, $movDateFilter)->get();

        $completed = $movements->where('status', 'completado');
        $totals = [
            'total_rows' => $movements->count(),
            'completed_count' => $completed->count(),
            'cancelled_count' => $movements->where('status', 'cancelado')->count(),
            'cash' => round((float) $completed->sum('cash_amount'), 2),
            'card' => round((float) $completed->sum('card_amount'), 2),
            'transfer' => round((float) $completed->sum('transfer_amount'), 2),
            'commission' => round((float) $completed->sum('commission_total'), 2),
            'total' => round((float) $completed->sum('total_amount'), 2),
        ];

        $periodLabel = DateFilterService::periodLabel($movDateFilter);
        $branchId = $this->resolveBranchFilter($request);
        $branchName = $branchId ? (Branch::find($branchId)?->name ?? 'Sucursal') : 'Todas las Sucursales';

        $pdf = Pdf::loadView(
            'dashboard.ventas-pdf',
            compact('movements', 'totals', 'periodLabel', 'movDateFilter', 'branchName')
        )->setPaper('letter', 'landscape');

        $fileSuffix = DateFilterService::fileSuffix($movDateFilter);
        return $pdf->download("Ventas_Movimientos_POS_{$fileSuffix}.pdf");
    }

    /**
     * Exportar Movimientos / Ventas POS del Dashboard a Excel (.xlsx).
     */
    public function exportVentasExcel(Request $request)
    {
        $movDateFilter = $this->resolveMovementsDateFilter($request);
        $movements = $this->buildMovementsQuery($request, $movDateFilter)->get();

        $spreadsheet = new Spreadsheet();
        $sheet = $spreadsheet->getActiveSheet();
        $sheet->setTitle('Ventas POS');

        $headers = [
            'Folio',
            'Fecha / Hora',
            'Sucursal',
            'Cliente',
            'Conceptos',
            'Método de Pago',
            'Efectivo ($)',
            'Tarjeta ($)',
            'Transferencia ($)',
            'Comisión ($)',
            'Total ($)',
            'Estado',
            'Cajero',
        ];
        $sheet->fromArray($headers, null, 'A1');
        $sheet->getStyle('A1:M1')->getFont()->setBold(true);

        $row = 2;
        foreach ($movements as $tx) {
            $sheet->fromArray([
                $tx->folio,
                optional($tx->transaction_date)->format('d/m/Y H:i'),
                $tx->branch->name ?? 'Principal',
                $tx->customer_name ?? ($tx->externalCustomer?->tit_name ?? 'Público en General'),
                $tx->concepts_summary ?: 'Movimiento unificado',
                strtoupper((string) $tx->payment_method),
                (float) $tx->cash_amount,
                (float) $tx->card_amount,
                (float) $tx->transfer_amount,
                (float) $tx->commission_total,
                (float) $tx->total_amount,
                ucfirst((string) $tx->status),
                $tx->user->name ?? 'Sistema',
            ], null, "A{$row}");
            $row++;
        }

        $completed = $movements->where('status', 'completado');
        $sheet->fromArray([
            'TOTALES COMPLETADOS',
            $completed->count() . ' movs',
            '',
            '',
            '',
            '',
            round((float) $completed->sum('cash_amount'), 2),
            round((float) $completed->sum('card_amount'), 2),
            round((float) $completed->sum('transfer_amount'), 2),
            round((float) $completed->sum('commission_total'), 2),
            round((float) $completed->sum('total_amount'), 2),
            '',
            '',
        ], null, "A{$row}");
        $sheet->getStyle("A{$row}:M{$row}")->getFont()->setBold(true);

        foreach (range('A', 'M') as $col) {
            $sheet->getColumnDimension($col)->setAutoSize(true);
        }

        $fileSuffix = DateFilterService::fileSuffix($movDateFilter);
        $tempFile = tempnam(sys_get_temp_dir(), 'ventas_xlsx_');
        (new Xlsx($spreadsheet))->save($tempFile);

        return response()->download($tempFile, "Ventas_Movimientos_POS_{$fileSuffix}.xlsx")->deleteFileAfterSend(true);
    }

    /**
     * Cancelar / Gestionar un movimiento diario (PosTransaction).
     */
    public function cancelTransaction(Request $request, PosTransaction $transaction)
    {
        if (!auth()->user()->isSuperAdmin() && !auth()->user()->can('dashboard.cancel_transaction') && !auth()->user()->can('cobros.cancel_transaction')) {
            abort(403, 'No tienes permisos para cancelar movimientos de cobro centralizado.');
        }

        $result = DB::transaction(function () use ($request, $transaction) {
            $lockedTx = PosTransaction::where('id', $transaction->id)->lockForUpdate()->firstOrFail();

            if ($lockedTx->status === 'cancelado') {
                return [
                    'already_cancelled' => true,
                    'blocked_by_corte_z' => false,
                    'tx' => $lockedTx,
                    'cash_reversal' => null,
                ];
            }

            $txBranchId = (int) ($lockedTx->branch_id ?: (auth()->user()->branch_id ?? 1));
            if (DailyCut::hasTodayCorteZ($txBranchId, true)) {
                return [
                    'already_cancelled' => false,
                    'blocked_by_corte_z' => true,
                    'tx' => $lockedTx,
                    'cash_reversal' => null,
                ];
            }

            $reason = trim((string) $request->input('cancellation_reason', $request->input('reason', '')));

            $lockedTx->update([
                'status' => 'cancelado',
                'cancelled_at' => now(),
                'cancelled_by' => auth()->id(),
                'cancellation_reason' => $reason !== '' ? $reason : $lockedTx->cancellation_reason,
                'notes' => trim(($lockedTx->notes ? $lockedTx->notes . ' | ' : '') . 'Cancelado por ' . auth()->user()->name . ' el ' . now()->format('d/m/Y H:i')),
            ]);

            if ($lockedTx->order_id) {
                Order::where('id', $lockedTx->order_id)
                    ->where('order_status', '!=', 'cancelado')
                    ->update(['order_status' => 'cancelado']);
            }

            $linkedFinPayments = FinSalePayment::where('pos_transaction_id', $lockedTx->id)
                ->lockForUpdate()
                ->get();

            if ($linkedFinPayments->isNotEmpty()) {
                foreach ($linkedFinPayments->filter(fn ($p) => !empty($p->fin_sale_id))->groupBy('fin_sale_id') as $finSaleId => $salePayments) {
                    $lockedFinSale = FinSale::where('id', $finSaleId)->lockForUpdate()->first();
                    if (!$lockedFinSale) {
                        continue;
                    }

                    $revertedAmount = round((float) $salePayments->sum('amount'), 2);
                    $lockedFinSale->syncAfterAbonoCancellation($salePayments->count(), $revertedAmount);

                    $receiptList = $salePayments->pluck('receipt_code')->filter()->implode(', ');
                    FinSaleNote::create([
                        'sale_id' => $lockedFinSale->id,
                        'user_id' => auth()->id(),
                        'note' => "Abono cancelado ({$receiptList} / Folio {$lockedTx->transaction_code}): -$"
                            . number_format($revertedAmount, 2)
                            . ($reason !== '' ? " | Motivo: {$reason}" : ''),
                    ]);
                }
            }

            $cashReversal = CashFlowService::revertPosTransactionCash($lockedTx, $reason !== '' ? $reason : null);

            return [
                'already_cancelled' => false,
                'blocked_by_corte_z' => false,
                'tx' => $lockedTx,
                'cash_reversal' => $cashReversal,
            ];
        });

        if ($result['blocked_by_corte_z']) {
            $blockedMsg = "No es posible cancelar el movimiento {$result['tx']->folio} porque el Corte Final (Corte Z) del día de hoy ya fue aplicado en esta sucursal.";
            if ($request->expectsJson()) {
                return response()->json(['success' => false, 'message' => $blockedMsg], 422);
            }
            return back()->with('error', $blockedMsg);
        }

        if ($result['already_cancelled']) {
            return back()->with('info', 'Este movimiento ya se encuentra cancelado.');
        }

        $tx = $result['tx'];
        $cashReversal = $result['cash_reversal'];

        $message = $cashReversal
            ? "Movimiento {$tx->folio} cancelado. Se registró devolución en efectivo de $" . number_format((float) $cashReversal->amount, 2) . ' en caja física.'
            : "Movimiento {$tx->folio} marcado como cancelado (sin impacto en caja física porque no incluyó pago en efectivo).";

        return back()->with('success', $message);
    }
}
