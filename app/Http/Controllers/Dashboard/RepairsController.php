<?php

namespace App\Http\Controllers\Dashboard;

use App\Models\Customer;
use App\Models\Employee;
use App\Models\Repairs;
use App\Models\Attendence;
use Illuminate\Http\Request;
use App\Http\Controllers\Controller;
use Illuminate\Support\Facades\Redirect;
use Illuminate\Support\Facades\Storage;

class RepairsController extends Controller
{
    /**
     * Display a listing of the resource.
     */
    public function index()
    {

        $reparaciones = Repairs::latest()->paginate(10);
        $clientes = Customer::all();
        return view('repairs.index' , compact('reparaciones',"clientes"));
    }

    /**
     * Show the form for creating a new resource.
     */
    public function create()
    {
        $clientes = Customer::all();
        return view('repairs.create', compact("clientes"));

    }

public function show(Repairs $repair) // <-- No uses Repairs con "s"
{
    return view('repairs.show', [
        'repair' => $repair,
    ]);
}

    public function store(Request $request)
    {
        $request->validate([
            'cliente' => 'required|string|max:255',
            'telefono' => 'required|string|max:50',
            'marca' => 'required|string|max:100',
            'modelo' => 'required|string|max:100',
            'imei' => 'nullable|string|max:100',
            'problema_reportado' => 'required|string',
            'diagnostico',
            'fecha_ingreso' => 'required|date',
            'fecha_entrega' => 'required|date',
            'foto_recibido_frontal' => 'nullable|image|max:2048',
            'foto_recibido_trasera' => 'nullable|image|max:2048',
            'foto_entregado_frontal' => 'nullable|image|max:2048',
            'foto_entregado_trasera' => 'nullable|image|max:2048',
            'precio' => 'nullable|numeric',
            'estado' => 'required|in:pendiente,en reparación,reparado,entregado',
        ]);

        $data = $request->all();

        // Subir imágenes si existen
        foreach (['foto_recibido_frontal', 'foto_recibido_trasera', 'foto_entregado_frontal', 'foto_entregado_trasera'] as $campo) {
            if ($request->hasFile($campo)) {
                $data[$campo] = $request->file($campo)->store("repairs", "public");
            }
        }

        Repairs::create($data);

        return redirect()->route('repairs.index')->with('success', 'Reparación registrada correctamente.');
    }

    public function edit(Repairs $repair) {
        $clientes = Customer::all();
        return view('repairs.edit', compact('repair','clientes'));
    }

public function update(Request $request, Repairs $repair)
    {
        $request->validate([
            'cliente' => 'required|string|max:255',
            'telefono' => 'required|string|max:50',
            'marca' => 'required|string|max:100',
            'modelo' => 'required|string|max:100',
            'imei' => 'nullable|string|max:100',
            'problema_reportado' => 'required|string',
            'diagnostico',
            'fecha_ingreso' => 'required|date',
            'fecha_entrega' => 'required|date',
            'foto_recibido_frontal' => 'nullable|image|max:2048',
            'foto_recibido_trasera' => 'nullable|image|max:2048',
            'foto_entregado_frontal' => 'nullable|image|max:2048',
            'foto_entregado_trasera' => 'nullable|image|max:2048',
            'precio' => 'nullable|numeric',
            'estado' => 'required|in:pendiente,en reparación,reparado,entregado',
        ]);

        $data = $request->all();

        // Subir nuevas imágenes si se cargan y borrar las anteriores
        foreach (['foto_recibido_frontal', 'foto_recibido_trasera', 'foto_entregado_frontal', 'foto_entregado_trasera'] as $campo) {
            if ($request->hasFile($campo)) {
                // Eliminar anterior
                if ($repair->$campo) {
                    Storage::disk('public')->delete($repair->$campo);
                }
                $data[$campo] = $request->file($campo)->store("repairs", "public");
            }
        }

        $repair->update($data);

        return redirect()->route('repairs.index')->with('success', 'Reparación actualizada correctamente.');
    }

public function destroy(Repairs $repair)
{
    // Esto sí se enlaza correctamente a {repair}
    // Borrar imágenes asociadas
    foreach (['foto_recibido_frontal', 'foto_recibido_trasera', 'foto_entregado_frontal', 'foto_entregado_trasera'] as $campo) {
        if ($repair->$campo) {
            Storage::disk('public')->delete($repair->$campo);
        }
    }

    $repair->delete();

    return redirect()->route('repairs.index')->with('success', 'Reparación eliminada correctamente.');
}

}
