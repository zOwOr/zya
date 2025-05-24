<?php 

namespace App\Http\Controllers\Dashboard;

use App\Models\Tanda;
use App\Models\Customer;
use App\Models\TandaPeriod;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Carbon\Carbon;
use App\Http\Controllers\Controller;

class TandaController extends Controller
{
    public function index()
    {
        $tandas = Tanda::with('clients')->get();
        return view('tandas.index', compact('tandas'));
    }

    public function create()
    {
        $customers = Customer::all();
        return view('tandas.create', compact('customers'));
    }
public function store(Request $request)
{
    $request->validate([
        'description' => 'string|max:255',
        'total_amount' => 'numeric|min:0',
        'payment_amount' => 'numeric|min:0',
        'payment_period' => 'in:semana,quincena,mes',
        'customers' => 'required|array|min:1',
        'duration' => 'integer|min:1',
    ]);

    // Validar que no se repitan posiciones
    $positions = [];
    foreach ($request->customers as $customerId => $data) {
        if (!empty($data['selected'])) {
            $position = $data['position'] ?? null;

            if (!$position || !is_numeric($position)) {
                return back()->withErrors(['customers' => 'Cada cliente seleccionado debe tener una posición válida.']);
            }

            if (in_array($position, $positions)) {
                return back()->withErrors(['customers' => 'No puede haber posiciones duplicadas.']);
            }

            $positions[] = $position;
        }
    }

    DB::transaction(function () use ($request) {
        $tanda = Tanda::create([
            'description' => $request->description,
            'total_amount' => $request->total_amount,
            'payment_amount' => $request->payment_amount,
            'payment_period' => $request->payment_period,
        ]);

        // Adjuntar los clientes con su posición
        foreach ($request->customers as $customerId => $data) {
            if (!empty($data['selected'])) {
                $tanda->clients()->attach($customerId, [
                    'position' => $data['position'],
                ]);
            }
        }

        // Crear los periodos
        $now = Carbon::now();
        for ($i = 1; $i <= $request->duration; $i++) {
            $due = match ($request->payment_period) {
                'semana' => $now->copy()->addWeeks($i),
                'quincena' => $now->copy()->addDays($i * 15),
                'mes' => $now->copy()->addMonths($i),
            };

            $tanda->periods()->create([
                'period_number' => $i,
                'due_date' => $due->format('Y-m-d'),
            ]);
        }
    });

    return redirect()->route('tandas.index')->with('success', 'Tanda creada correctamente');
}

public function updatePayments(Request $request, Tanda $tanda)
{
    $payments = $request->input('payments', []);

    foreach ($payments as $payment) {
        $clientId = $payment['client_id'];
        $period = $payment['period'];
        $amount = $payment['amount'];

        $client = $tanda->clients()->find($clientId);

        if ($client) {
            // Decodifica los pagos existentes o inicializa uno nuevo
            $existingPayments = json_decode($client->pivot->payments ?? '{}', true);

            // Asigna el nuevo valor al periodo correspondiente
            $existingPayments[$period] = $amount;

            // Guarda nuevamente como JSON
            $client->pivot->payments = json_encode($existingPayments);

            // Guarda los cambios en la tabla pivot
            $client->pivot->save();
        }
    }

    return response()->json(['message' => 'Pagos guardados correctamente.']);
}

    public function show(Tanda $tanda)
    {
        $tanda->load(['clients', 'periods']);
        return view('tandas.show', compact('tanda'));
    }

    public function edit(Tanda $tanda)
    {
        $customers = Customer::all();
        $tanda->load('clients');
        return view('tandas.edit', compact('tanda', 'customers'));
    }
public function update(Request $request, Tanda $tanda)
{
    $request->validate([
        'positions' => 'array',
        'positions.*' => 'integer|min:1',
        'remove_clients' => 'array',
        'remove_clients.*' => 'integer|exists:customers,id',
        'new_clients' => 'array',
        'new_clients.*' => 'integer|exists:customers,id',
    ]);

    DB::transaction(function () use ($request, $tanda) {
        // 1. Actualizar posiciones
        if ($request->has('positions')) {
            foreach ($request->positions as $clientId => $position) {
                $tanda->clients()->updateExistingPivot($clientId, ['position' => $position]);
            }
        }

        // 2. Quitar clientes marcados
        if ($request->has('remove_clients')) {
            $tanda->clients()->detach($request->remove_clients);
        }

        // 3. Agregar nuevos clientes al final de la lista, con posiciones consecutivas
        if ($request->has('new_clients')) {
            $maxPosition = $tanda->clients->max('pivot.position') ?? 0;
            foreach ($request->new_clients as $index => $newClientId) {
                $pos = $maxPosition + $index + 1;
                $tanda->clients()->attach($newClientId, ['position' => $pos]);
            }
        }
    });

    return redirect()->route('tandas.show', $tanda->id)
        ->with('success', 'Tanda actualizada correctamente');
}


    public function destroy(Tanda $tanda)
    {
        DB::transaction(function () use ($tanda) {
            $tanda->clients()->detach();
            $tanda->periods()->delete();
            $tanda->delete();
        });

        return redirect()->route('tandas.index')->with('success', 'Tanda eliminada correctamente');
    }
}
