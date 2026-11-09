<?php

namespace App\Http\Controllers\Dashboard;

use App\Http\Controllers\Controller;
use App\Models\Branch;
use App\Models\FinFinanciera;
use App\Models\FinSalePayment;
use App\Models\Order;
use App\Models\PosTransaction;
use App\Models\Product;
use Barryvdh\DomPDF\Facade\Pdf;
use Carbon\Carbon;
use Illuminate\Http\Request;
use PhpOffice\PhpSpreadsheet\Spreadsheet;
use PhpOffice\PhpSpreadsheet\Writer\Xlsx;

class DashboardController extends Controller
{
    protected function canAccessAllBranches(): bool
    {
        return auth()->check() && (auth()->user()->isSuperAdmin() || auth()->user()->can('cobros.all_branches') || auth()->user()->can('financieras.all_branches'));
    }

    protected function resolveBranchFilter(Request $request): ?int
    {
        if ($this->canAccessAllBranches()) {
            return $request->filled('branch_id') ? (int) $request->input('branch_id') : null;
        }
        return (int) (auth()->user()->branch_id ?? 1);
    }

    protected function buildFinancieraPaymentsQuery(Request $request)
    {
        $startDate = $request->input('fin_start_date', Carbon::today()->toDateString());
        $endDate = $request->input('fin_end_date', Carbon::today()->toDateString());
        $financieraId = $request->input('financiera_id');
        $branchId = $this->resolveBranchFilter($request);
        $search = $request->input('fin_search');

        return FinSalePayment::with(['sale', 'financiera', 'branch', 'user'])
            ->when($startDate, fn($q) => $q->whereDate('payment_date', '>=', $startDate))
            ->when($endDate, fn($q) => $q->whereDate('payment_date', '<=', $endDate))
            ->when($financieraId, fn($q) => $q->where('financiera_id', $financieraId))
            ->when($branchId, fn($q) => $q->where('branch_id', $branchId))
            ->when($search, function ($q) use ($search) {
                $q->where(function ($sub) use ($search) {
                    $sub->where('tag_contrato', 'like', "%{$search}%")
                        ->orWhere('imei', 'like', "%{$search}%")
                        ->orWhere('customer_name', 'like', "%{$search}%");
                });
            })
            ->orderByDesc('payment_date')
            ->orderByDesc('id');
    }

    public function index(Request $request)
    {
        $finStartDate = $request->input('fin_start_date', Carbon::today()->toDateString());
        $finEndDate = $request->input('fin_end_date', Carbon::today()->toDateString());
        $movDate = $request->input('mov_date', Carbon::today()->toDateString());
        $branchId = $this->resolveBranchFilter($request);

        $finPaymentsQuery = $this->buildFinancieraPaymentsQuery($request);
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

        // Movimientos (procesos de venta con varios pagos distintos) por día por fecha seleccionada
        $posTransactionsQuery = PosTransaction::with(['branch', 'user', 'cashFlows'])
            ->whereDate('created_at', $movDate)
            ->when($branchId, fn($q) => $q->where('branch_id', $branchId))
            ->orderByDesc('created_at')
            ->orderByDesc('id');

        $allMovementsDay = (clone $posTransactionsQuery)->get();
        $dailyMovements = $posTransactionsQuery->paginate(15, ['*'], 'mov_page')->withQueryString();

        $movTotals = [
            'count' => $allMovementsDay->where('status', 'completado')->count(),
            'total' => round((float) $allMovementsDay->where('status', 'completado')->sum('total_amount'), 2),
            'cash' => round((float) $allMovementsDay->where('status', 'completado')->sum('cash_amount'), 2),
            'card' => round((float) $allMovementsDay->where('status', 'completado')->sum('card_amount'), 2),
            'transfer' => round((float) $allMovementsDay->where('status', 'completado')->sum('transfer_amount'), 2),
        ];

        $financieras = FinFinanciera::where('is_active', true)->orderBy('name')->get(['id', 'name']);
        if ($financieras->isEmpty()) {
            $financieras = FinFinanciera::orderBy('name')->get(['id', 'name']);
        }
        $branches = Branch::orderBy('name')->get(['id', 'name']);

        return view('dashboard.index', [
            'total_paid' => Order::sum('pay'),
            'total_due' => Order::sum('due'),
            'complete_orders' => Order::where('order_status', 'complete')->get(),
            'products' => Product::orderBy('product_store')->take(5)->get(),
            'new_products' => Product::orderBy('buying_date')->take(2)->get(),
            'financieraPayments' => $financieraPayments,
            'finTotals' => $finTotals,
            'finStartDate' => $finStartDate,
            'finEndDate' => $finEndDate,
            'dailyMovements' => $dailyMovements,
            'movTotals' => $movTotals,
            'movDate' => $movDate,
            'financieras' => $financieras,
            'branches' => $branches,
            'branchId' => $branchId,
        ]);
    }

    /**
     * Exportar Pagos de Financieras por Fecha a PDF.
     */
    public function exportFinancieraPaymentsPdf(Request $request)
    {
        $payments = $this->buildFinancieraPaymentsQuery($request)->get();
        $finStartDate = $request->input('fin_start_date', Carbon::today()->toDateString());
        $finEndDate = $request->input('fin_end_date', Carbon::today()->toDateString());

        $totals = [
            'count' => $payments->count(),
            'amount' => round((float) $payments->sum('amount'), 2),
            'commission' => round((float) $payments->sum('commission_amount'), 2),
            'total_paid' => round((float) $payments->sum('total_amount'), 2),
        ];

        $pdf = Pdf::loadView('dashboard.financiera-payments-pdf', compact('payments', 'totals', 'finStartDate', 'finEndDate'))
            ->setPaper('a4', 'landscape');

        return $pdf->download("Pagos_Financieras_{$finStartDate}_a_{$finEndDate}.pdf");
    }

    /**
     * Exportar Pagos de Financieras por Fecha a Excel (.xlsx).
     */
    public function exportFinancieraPaymentsExcel(Request $request)
    {
        $payments = $this->buildFinancieraPaymentsQuery($request)->get();
        $finStartDate = $request->input('fin_start_date', Carbon::today()->toDateString());
        $finEndDate = $request->input('fin_end_date', Carbon::today()->toDateString());

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

        $row = 2;
        foreach ($payments as $p) {
            $sheet->setCellValue("A{$row}", $p->id);
            $sheet->setCellValue("B{$row}", optional($p->payment_date)->format('d/m/Y H:i'));
            $sheet->setCellValue("C{$row}", $p->financiera->name ?? ($p->sale->financiera ?? 'Financiera'));
            $sheet->setCellValue("D{$row}", $p->tag_contrato);
            $sheet->setCellValue("E{$row}", $p->imei);
            $sheet->setCellValue("F{$row}", $p->customer_name ?? ($p->sale->customer_name ?? 'Cliente'));
            $sheet->setCellValue("G{$row}", $p->branch->name ?? 'General');
            $sheet->setCellValue("H{$row}", strtoupper($p->payment_method));
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

        $fileName = "Pagos_Financieras_{$finStartDate}_a_{$finEndDate}.xlsx";
        $tempFile = tempnam(sys_get_temp_dir(), 'fin_xlsx_');
        $writer = new Xlsx($spreadsheet);
        $writer->save($tempFile);

        return response()->download($tempFile, $fileName)->deleteFileAfterSend(true);
    }

    /**
     * Cancelar / Gestionar un movimiento diario (PosTransaction).
     */
    public function cancelTransaction(Request $request, PosTransaction $transaction)
    {
        if (!auth()->user()->isSuperAdmin() && !auth()->user()->can('cobros.cancel_transaction')) {
            abort(403, 'No tienes permisos para cancelar movimientos de cobro centralizado.');
        }

        if ($transaction->status === 'cancelado') {
            return back()->with('info', 'Este movimiento ya se encuentra cancelado.');
        }

        $transaction->update([
            'status' => 'cancelado',
            'cancelled_at' => now(),
            'cancelled_by' => auth()->id(),
            'notes' => trim(($transaction->notes ? $transaction->notes . ' | ' : '') . 'Cancelado por ' . auth()->user()->name . ' el ' . now()->format('d/m/Y H:i')),
        ]);

        return back()->with('success', "Movimiento {$transaction->folio} marcado como cancelado.");
    }
}
