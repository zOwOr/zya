<?php 

namespace App\Http\Controllers\Dashboard;

use App\Models\Tanda;
use App\Models\Customer;
use App\Models\TandaPeriod;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Carbon\Carbon;
use App\Http\Controllers\Controller;
use App\Models\CashFlow;
use App\Models\DailyCut;
use Illuminate\Support\Facades\Log;
use App\Models\PaymentHistory;

class CashController extends Controller
{
    // Mostrar todos los movimientos de caja
    public function index(Request $request)
    {
        $perPage = 5;
        $date = $request->input('date');
        $branchId = auth()->user()->branch_id;

        $cashFlows = CashFlow::where('branch_id', $branchId)
            ->when($date, fn($q) => $q->whereDate('created_at', $date))
            ->orderByDesc('created_at')
            ->paginate($perPage)
            ->withQueryString();

        $totalIncome = CashFlow::where('branch_id', $branchId)
            ->when($date, fn($q) => $q->whereDate('created_at', $date))
            ->where('type', 'income')->sum('amount');

        $totalExpense = CashFlow::where('branch_id', $branchId)
            ->when($date, fn($q) => $q->whereDate('created_at', $date))
            ->where('type', 'expense')->sum('amount');

        $balance = $totalIncome - $totalExpense;

        return view('cash.index', compact('cashFlows', 'date', 'totalIncome', 'totalExpense', 'balance', 'perPage'));
    }

    // Mostrar los movimientos del día (corte diario)
public function dailyCut()
{
    $today = Carbon::today();
    $branchId = auth()->user()->branch_id;

    $cashFlows = CashFlow::where('branch_id', $branchId)
        ->whereDate('created_at', $today)
        ->orderByDesc('created_at')
        ->paginate(5);

    $allDailyCashFlows = CashFlow::where('branch_id', $branchId)
        ->whereDate('created_at', $today)
        ->get();

    $totalIncome = $allDailyCashFlows->where('type', 'income')->sum('amount');
    $totalExpense = $allDailyCashFlows->where('type', 'expense')->sum('amount');

    $lastCut = DailyCut::where('branch_id', $branchId)
        ->where('date', '<', $today)
        ->orderBy('date', 'desc')
        ->first();

    $openingBalance = $lastCut ? $lastCut->balance : 0;
    $balance = $openingBalance + $totalIncome - $totalExpense;

    $dailyCut = DailyCut::where('branch_id', $branchId)
        ->where('date', $today)
        ->first();

    // Crea objeto si aún no existe corte
    if (!$dailyCut) {
        $dailyCut = (object)[
            'opening_balance' => $openingBalance,
            'total_income' => $totalIncome,
            'total_expense' => $totalExpense,
            'balance' => $balance,
        ];
    }

    // ✅ Traer pagos del día que NO entran a caja
    $externalPayments = PaymentHistory::where('branch_id', $branchId)
        ->whereDate('created_at', $today)
        ->whereIn('method', ['Cheque', 'Due'])
        ->get();

    return view('cash.daily-cut', compact('cashFlows', 'dailyCut', 'externalPayments'));
}


    // Registrar un nuevo movimiento manual (opcional)
    public function store(Request $request)
    {
        $validated = $request->validate([
            'type' => 'required|in:income,expense',
            'amount' => 'required|numeric|min:0.01',
            'description' => 'nullable|string|max:255',
            'reference' => 'nullable|string|max:100',
            'module' => 'nullable|string|max:100',
        ]);

        $validated['branch_id'] = auth()->user()->branch_id;

        CashFlow::create($validated);

        return redirect()->route('cash.index')->with('success', 'Movimiento registrado correctamente.');
    }

public function applyCut(Request $request)
{
    $today = Carbon::today();
    $branchId = auth()->user()->branch_id;

    // Validar si ya existe un corte para hoy en esta sucursal
    if (DailyCut::where('branch_id', $branchId)->where('date', $today)->exists()) {
        return back()->with('info', 'El corte ya fue aplicado hoy.');
    }

    // Ingresos y egresos del día actual, solo de esta sucursal
    $totalIncome = CashFlow::where('branch_id', $branchId)
        ->whereDate('created_at', $today)
        ->where('type', 'income')
        ->sum('amount');

    $totalExpense = CashFlow::where('branch_id', $branchId)
        ->whereDate('created_at', $today)
        ->where('type', 'expense')
        ->sum('amount');

    // Corte anterior SOLO de la sucursal actual
    $lastCut = DailyCut::where('branch_id', $branchId)
        ->where('date', '<', $today)
        ->orderBy('date', 'desc')
        ->first();

    $openingBalance = $lastCut ? $lastCut->balance : 0;

    // Leer retiro ingresado
    $withdrawAmount = floatval($request->input('withdraw_amount', 0));
    $withdrawAmount = max($withdrawAmount, 0);

    // Registrar retiro como egreso en la misma sucursal
    if ($withdrawAmount > 0) {
        CashFlow::create([
            'branch_id' => $branchId,
            'type' => 'expense',
            'amount' => $withdrawAmount,
            'description' => 'Retiro al aplicar corte',
            'module' => 'Corte',
            'reference' => 'Retiro Corte Diario',
        ]);

        $totalExpense += $withdrawAmount;
    }

    // Cálculo correcto del saldo final
    $finalBalance = $openingBalance + $totalIncome - $totalExpense;

    // Registrar el corte
    DailyCut::create([
        'branch_id' => $branchId,
        'date' => $today,
        'opening_balance' => $openingBalance,
        'total_income' => $totalIncome,
        'total_expense' => $totalExpense,
        'balance' => $finalBalance,
    ]);

    return redirect()->route('cash.dailyCut')->with('success', 'Corte aplicado correctamente.');
}




    public function filterByDate(Request $request)
    {
        $date = $request->input('date', Carbon::today()->toDateString());
        $branchId = auth()->user()->branch_id;

        $cashFlows = CashFlow::where('branch_id', $branchId)
            ->whereDate('created_at', $date)
            ->orderByDesc('created_at')
            ->paginate(5)
            ->withQueryString();

        // Aquí NO uses los datos paginados para sumar ingresos/egresos, sino consulta toda la fecha
        $totalIncome = CashFlow::where('branch_id', $branchId)
            ->whereDate('created_at', $date)
            ->where('type', 'income')
            ->sum('amount');

        $totalExpense = CashFlow::where('branch_id', $branchId)
            ->whereDate('created_at', $date)
            ->where('type', 'expense')
            ->sum('amount');

        $balance = $totalIncome - $totalExpense;

        return view('cash.index', compact('cashFlows', 'date', 'totalIncome', 'totalExpense', 'balance'));
    }

    public function printCut()
    {
        $today = Carbon::today();
        $branchId = auth()->user()->branch_id;

        $dailyCut = DailyCut::where('branch_id', $branchId)
            ->where('date', $today)
            ->first();

        if (!$dailyCut) {
            return redirect()->route('cash.daily-cut')->with('error', 'No hay corte aplicado para hoy en esta sucursal');
        }

        return view('cash.print', compact('dailyCut'));
    }
}
