<?php

namespace App\Http\Controllers\Dashboard;

use App\Http\Controllers\Controller;
use App\Http\Controllers\Traits\ModulePermissionTrait;
use App\Models\FinBrand;
use App\Models\FinFinanciera;
use App\Models\FinWarrantyStage;
use Illuminate\Http\Request;

class FinCatalogController extends Controller
{
    use ModulePermissionTrait;

    protected ?string $permissionResource = 'financieras.catalogos';

    public function __construct()
    {
        $this->initializeModulePermission();
    }

    public function index()
    {
        $financieras = FinFinanciera::withCount('sales')->get();
        $brands = FinBrand::withCount('devices')->get();
        $stages = FinWarrantyStage::withCount('warranties')->orderBy('order')->get();

        return view('financieras.catalogos.index', compact('financieras', 'brands', 'stages'));
    }

    // Financieras
    public function storeFinanciera(Request $request)
    {
        $request->validate([
            'name' => 'required|string|max:150|unique:fin_financieras,name',
            'contact_name' => 'nullable|string|max:150',
            'contact_phone' => 'nullable|string|max:50',
            'contact_email' => 'nullable|email|max:150',
        ]);

        FinFinanciera::create($request->all());

        return back()->with('success', 'Financiera creada correctamente.');
    }

    public function updateFinanciera(Request $request, FinFinanciera $financiera)
    {
        $request->validate([
            'name' => 'required|string|max:150|unique:fin_financieras,name,' . $financiera->id,
            'contact_name' => 'nullable|string|max:150',
            'contact_phone' => 'nullable|string|max:50',
            'contact_email' => 'nullable|email|max:150',
            'is_active' => 'required|boolean',
        ]);

        $financiera->update($request->all());

        return back()->with('success', 'Financiera actualizada.');
    }

    public function destroyFinanciera(FinFinanciera $financiera)
    {
        if ($financiera->sales()->exists()) {
            return back()->with('error', 'No se puede eliminar la financiera porque tiene ventas asociadas.');
        }

        $financiera->delete();
        return back()->with('success', 'Financiera eliminada.');
    }

    // Brands
    public function storeBrand(Request $request)
    {
        $request->validate([
            'name' => 'required|string|max:100|unique:fin_brands,name',
        ]);

        FinBrand::create(['name' => trim($request->input('name'))]);

        return back()->with('success', 'Marca agregada correctamente.');
    }

    public function updateBrand(Request $request, FinBrand $brand)
    {
        $request->validate([
            'name' => 'required|string|max:100|unique:fin_brands,name,' . $brand->id,
            'is_active' => 'required|boolean',
        ]);

        $brand->update($request->all());

        return back()->with('success', 'Marca actualizada.');
    }

    public function destroyBrand(FinBrand $brand)
    {
        if ($brand->devices()->exists()) {
            return back()->with('error', 'No se puede eliminar la marca porque tiene dispositivos registrados.');
        }

        $brand->delete();
        return back()->with('success', 'Marca eliminada.');
    }

    // Warranty Stages
    public function storeWarrantyStage(Request $request)
    {
        $request->validate([
            'name' => 'required|string|max:100',
            'order' => 'required|integer|min:0',
            'color' => 'nullable|string|max:20',
            'is_final' => 'boolean',
        ]);

        FinWarrantyStage::create([
            'name' => trim($request->input('name')),
            'order' => $request->input('order', 0),
            'color' => $request->input('color', '#6c757d'),
            'is_final' => $request->boolean('is_final'),
        ]);

        return back()->with('success', 'Etapa de garantía agregada.');
    }

    public function updateWarrantyStage(Request $request, FinWarrantyStage $stage)
    {
        $request->validate([
            'name' => 'required|string|max:100',
            'order' => 'required|integer|min:0',
            'color' => 'nullable|string|max:20',
            'is_final' => 'boolean',
        ]);

        $stage->update([
            'name' => trim($request->input('name')),
            'order' => $request->input('order', 0),
            'color' => $request->input('color', '#6c757d'),
            'is_final' => $request->boolean('is_final'),
        ]);

        return back()->with('success', 'Etapa de garantía actualizada.');
    }

    public function destroyWarrantyStage(FinWarrantyStage $stage)
    {
        if ($stage->warranties()->exists()) {
            return back()->with('error', 'No se puede eliminar la etapa porque hay garantías asignadas a ella.');
        }

        $stage->delete();
        return back()->with('success', 'Etapa eliminada.');
    }
}
