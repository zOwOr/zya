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
    $balance = $totalIncome - $totalExpense;

    return view('cash.daily-cut', compact('cashFlows', 'totalIncome', 'totalExpense', 'balance'));
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

public function applyCut()
{
    $today = Carbon::today();
    $branchId = auth()->user()->branch_id;

    // Verificar si ya existe un corte para esta sucursal hoy
    if (DailyCut::where('branch_id', $branchId)->where('date', $today)->exists()) {
        return redirect()->route('cash.daily-cut')->with('error', 'El corte del día ya fue aplicado para esta sucursal.');
    }

    $cashFlows = CashFlow::where('branch_id', $branchId)
        ->whereDate('created_at', $today)
        ->get();

    $totalIncome = $cashFlows->where('type', 'income')->sum('amount');
    $totalExpense = $cashFlows->where('type', 'expense')->sum('amount');
    $balance = $totalIncome - $totalExpense;

    DailyCut::create([
        'branch_id' => $branchId,
        'date' => $today,
        'total_income' => $totalIncome,
        'total_expense' => $totalExpense,
        'balance' => $balance,
    ]);

    return redirect()->route('cash.daily-cut')->with('success', 'Corte diario aplicado correctamente.');
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

    $totalIncome = $cashFlows->where('type', 'income')->sum('amount');
    $totalExpense = $cashFlows->where('type', 'expense')->sum('amount');
    $balance = $totalIncome - $totalExpense;

    return view('cash.index', compact('cashFlows', 'date', 'totalIncome', 'totalExpense', 'balance'));
}

}