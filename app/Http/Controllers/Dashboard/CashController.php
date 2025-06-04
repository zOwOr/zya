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
        // Cuántas filas por página (default 20, editable con ?row=…)
        $perPage = 5;


        // Filtro de fecha opcional
        $date = $request->input('date');

        $cashFlows = CashFlow::when($date,                     // si viene ?date=YYYY-MM-DD
                            fn($q) => $q->whereDate('created_at', $date))
                        ->orderByDesc('created_at')
                        ->paginate($perPage)                  // 👉 ¡paginado!
                        ->withQueryString();                  // conserva filtros en links()

        // Totales (se reutilizan en la vista)
        $totalIncome  = CashFlow::when($date, fn($q)=>$q->whereDate('created_at',$date))
                                ->where('type','income')->sum('amount');
        $totalExpense = CashFlow::when($date, fn($q)=>$q->whereDate('created_at',$date))
                                ->where('type','expense')->sum('amount');
        $balance = $totalIncome - $totalExpense;

        return view('cash.index',
            compact('cashFlows','date','totalIncome','totalExpense','balance','perPage'));
    }


    // Mostrar los movimientos del día (corte diario)
public function dailyCut()
{
    $today = Carbon::today();

    // Paginar los resultados (ej. 10 por página)
    $cashFlows = CashFlow::whereDate('created_at', $today)
        ->orderByDesc('created_at')
        ->paginate(perPage: 5 );

    // Calcular totales con los datos completos del día (sin paginación)
    $allDailyCashFlows = CashFlow::whereDate('created_at', $today)->get();

    $totalIncome = $allDailyCashFlows->where('type', 'income')->sum('amount');
    $totalExpense = $allDailyCashFlows->where('type', 'expense')->sum('amount');
    $balance = $totalIncome - $totalExpense;

    return view('cash.daily-cut', [
        'cashFlows' => $cashFlows,
        'totalIncome' => $totalIncome,
        'totalExpense' => $totalExpense,
        'balance' => $balance,
    ]);
}

    
    // Registrar un nuevo movimiento manual (opcional)
    public function store(Request $request)
    {
        $request->validate([
            'type' => 'required|in:income,expense',
            'amount' => 'required|numeric|min:0.01',
            'description' => 'nullable|string|max:255',
            'reference' => 'nullable|string|max:100',
            'module' => 'nullable|string|max:100',
        ]);

        CashFlow::create($request->all());

        return redirect()->route('cash.index')->with('success', 'Movimiento registrado correctamente.');
    }
    public function applyCut()
{
    $today = Carbon::today();

    // Verifica si ya existe un corte para hoy
    if (DailyCut::where('date', $today)->exists()) {
        return redirect()->route('cash.daily-cut')->with('error', 'El corte del día ya fue aplicado.');
    }

    // Obtener todos los movimientos del día
    $cashFlows = CashFlow::whereDate('created_at', $today)->get();

    $totalIncome = $cashFlows->where('type', 'income')->sum('amount');
    $totalExpense = $cashFlows->where('type', 'expense')->sum('amount');
    $balance = $totalIncome - $totalExpense;

    // Guardar el corte
    DailyCut::create([
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

    $cashFlows = CashFlow::whereDate('created_at', $date)
        ->orderByDesc('created_at')
        ->paginate(5)
        ->withQueryString();

    $totalIncome = $cashFlows->where('type', 'income')->sum('amount');
    $totalExpense = $cashFlows->where('type', 'expense')->sum('amount');
    $balance = $totalIncome - $totalExpense;

    return view('cash.index', compact('cashFlows', 'date', 'totalIncome', 'totalExpense', 'balance'));
}

}