<?php

namespace App\Http\Controllers\Dashboard;

use App\Models\Branch;
use Illuminate\Http\Request;
use App\Http\Controllers\Controller;

class BranchController extends Controller
{
    // Mostrar listado
    public function index()
    {
        $branches = Branch::paginate(15);
        return view('branches.index', compact('branches'));
    }

    // Mostrar formulario de creación
    public function create()
    {
        return view('branches.create');
    }

    // Guardar nueva sucursal
    public function store(Request $request)
    {
        $request->validate([
            'name' => 'required|unique:branches,name|max:100',
            'address' => 'nullable|string|max:255',
            'phone' => 'nullable|string|max:20',
        ]);

        Branch::create($request->only('name', 'address', 'phone'));

        return redirect()->route('branches.index')->with('success', 'Sucursal creada correctamente.');
    }

    // Mostrar formulario de edición
    public function edit(Branch $branch)
    {
        return view('branches.edit', compact('branch'));
    }

    // Actualizar sucursal
    public function update(Request $request, Branch $branch)
    {
        $request->validate([
            'name' => 'required|unique:branches,name,'.$branch->id.'|max:100',
            'address' => 'nullable|string|max:255',
            'phone' => 'nullable|string|max:20',
        ]);

        $branch->update($request->only('name', 'address', 'phone'));

        return redirect()->route('branches.index')->with('success', 'Sucursal actualizada correctamente.');
    }

    // Eliminar sucursal
    public function destroy(Branch $branch)
    {
        $branch->delete();

        return redirect()->route('branches.index')->with('success', 'Sucursal eliminada correctamente.');
    }
}
