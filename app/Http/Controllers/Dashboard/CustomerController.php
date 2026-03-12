<?php

namespace App\Http\Controllers\Dashboard;

use App\Models\Customer;
use Illuminate\Http\Request;
use App\Http\Controllers\Controller;
use Intervention\Image\Facades\Image;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Facades\Redirect;

class CustomerController extends Controller
{
    /**
     * Display a listing of the resource.
     */
    public function index()
    {
        $row = (int) request('row', 10);

        if ($row < 1 || $row > 100) {
            abort(400, 'The per-page parameter must be an integer between 1 and 100.');
        }

        return view('customers.index', [
            'customers' => Customer::filter(request(['search']))->sortable()->paginate($row)->appends(request()->query()),
        ]);
    }

    /**
     * Show the form for creating a new resource.
     */
    public function create()
    {
        return view('customers.create');
    }

    /**
     * Store a newly created resource in storage.
     */
    public function store(Request $request)
    {
        $rules = [
            'tit_name' => 'required|string|max:100',
            'tit_email' => 'required|email|max:100',
            'tit_phone' => 'required|string|max:15|unique:customers,tit_phone',
            'tit_status' => 'required|string|max:15',
            'tit_address' => 'required|string|max:999',
            'tit_photo' => 'file|mimes:jpeg,png,jpg,gif,pdf,webp',
            'tit_photo_ine_f' => 'file|mimes:jpeg,png,jpg,gif,pdf,webp',
            'tit_photo_ine_b' => 'file|mimes:jpeg,png,jpg,gif,pdf,webp',
            'tit_facebook' => 'required|string',    
            'tit_photo_home' => 'file|mimes:jpeg,png,jpg,gif,pdf,webp',
            'tit_link_location' => 'max:999',
            'tit_photo_proof_address' => 'file|mimes:jpeg,png,jpg,gif,pdf,webp',
            'tit_work' => 'max:999',
            'tit_city' => 'required|string',
            'alternate_phone' => 'nullable|string|max:15',
            'position' => 'nullable|string|max:255',
            'monthly_income' => 'nullable|numeric',
            'income_receipt_path' => 'nullable|file|mimes:jpeg,png,jpg,gif,pdf,webp',

            'ref1_name' => 'max:100',
            'ref1_phone' => 'max:15',
            'ref1_address' => 'max:999',

            'ref2_name' => 'max:100',
            'ref2_phone' => 'max:15',
            'ref2_address' => 'max:999',
            
            'ref3_name' => 'max:100',
            'ref3_phone' => 'max:15',
            'ref3_address' => 'max:999',

            'aval_name' => 'max:100',
            'aval_phone' => 'max:15',
            'aval_address' => 'max:999',
            'aval_photo_ine_f' => 'file|mimes:jpeg,png,jpg,gif,pdf,webp',
            'aval_photo_ine_b' => 'file|mimes:jpeg,png,jpg,gif,pdf,webp',
            'aval_photo_proof_address' => 'file|mimes:jpeg,png,jpg,gif,pdf,webp',


        ];

        $validatedData = $request->validate($rules);

        /**
         * Handle upload image with Storage.
         * Los archivos se procesan primero. Si hay duplicado, los nombres
         * ya guardados se persisten en sesión para no perderlos en el re-envío.
         */

        // Campos de archivo que se manejan
        $fileFields = [
            'tit_photo', 'tit_photo_ine_f', 'tit_photo_ine_b',
            'tit_photo_home', 'tit_photo_proof_address',
            'aval_photo_ine_f', 'aval_photo_ine_b', 'aval_photo_home',
            'income_receipt_path',
        ];

        // 🔥 Si viene confirmación, restaurar archivos ya guardados desde sesión
        if ($request->has('confirm_duplicate')) {
            $savedFiles = session('pending_customer_files', []);
            foreach ($fileFields as $field) {
                if (!empty($savedFiles[$field])) {
                    $validatedData[$field] = $savedFiles[$field];
                }
            }
            session()->forget('pending_customer_files');

            Customer::create($validatedData);

            return redirect()->route('customers.index')
                ->with('success', 'Cliente registrado correctamente.');
        }

        // Procesar y guardar los archivos subidos
        foreach ($fileFields as $field) {
            if ($request->hasFile($field)) {
                $validatedData[$field] = $this->storeImage($request->file($field), 'customers');
            }
        }


// 🔎 Verificar duplicado titular y rastreo de aval
$alertas = [];

// 1. Cliente Titular Duplicado
$clienteDuplicado = Customer::where(function ($query) use ($validatedData) {
    if (!empty($validatedData['tit_name'])) {
        $query->orWhere('tit_name', $validatedData['tit_name']);
    }
    if (!empty($validatedData['tit_phone'])) {
        $query->orWhere('tit_phone', $validatedData['tit_phone']);
    }
})->get();

if ($clienteDuplicado->count() > 0) {
    foreach ($clienteDuplicado as $dup) {
        $campos = [];
        if (!empty($validatedData['tit_name']) && $dup->tit_name === $validatedData['tit_name']) $campos[] = "Nombre Titular";
        if (!empty($validatedData['tit_phone']) && $dup->tit_phone === $validatedData['tit_phone']) $campos[] = "Teléfono Titular";
        if (count($campos) > 0) {
            $alertas[] = [
                'cliente' => $dup->tit_name,
                'campos' => $campos,
                'alerta_tipo' => 'Titular duplicado'
            ];
        }
    }
}

// 2. Rastreo de Aval (Prestanombres) - ¿El titular ha sido aval de alguien?
if (!empty($validatedData['tit_name'])) {
    $fueAval = Customer::where('aval_name', $validatedData['tit_name'])->get();
    foreach ($fueAval as $antAval) {
        $alertas[] = [
            'cliente' => $antAval->tit_name,
            'campos' => ["Fue AVAL de este cliente"],
            'alerta_tipo' => 'Posible Prestanombre'
        ];
    }
}

// 3. Duplicado de Aval actual
if (!empty($validatedData['aval_name']) || !empty($validatedData['aval_phone']) || !empty($validatedData['aval_address'])) {
    $existingAvals = Customer::where(function ($query) use ($validatedData) {
        if (!empty($validatedData['aval_name'])) $query->orWhere('aval_name', $validatedData['aval_name']);
        if (!empty($validatedData['aval_phone'])) $query->orWhere('aval_phone', $validatedData['aval_phone']);
        if (!empty($validatedData['aval_address'])) $query->orWhere('aval_address', $validatedData['aval_address']);
    })->get();

    if ($existingAvals->count() > 0) {
        foreach ($existingAvals as $aval) {
            $camposDuplicados = [];
            if (!empty($validatedData['aval_name']) && $aval->aval_name === $validatedData['aval_name']) $camposDuplicados[] = "Nombre Aval";
            if (!empty($validatedData['aval_phone']) && $aval->aval_phone === $validatedData['aval_phone']) $camposDuplicados[] = "Teléfono Aval";
            if (!empty($validatedData['aval_address']) && $aval->aval_address === $validatedData['aval_address']) $camposDuplicados[] = "Dirección Aval";
            if (count($camposDuplicados) > 0) {
                $alertas[] = [
                    'cliente' => $aval->tit_name,
                    'campos' => $camposDuplicados,
                    'alerta_tipo' => 'Aval duplicado'
                ];
            }
        }
    }
}

if (count($alertas) > 0) {
    // Guardar los nombres de archivo ya procesados en sesión
    $pendingFiles = [];
    foreach ($fileFields as $field) {
        if (!empty($validatedData[$field])) {
            $pendingFiles[$field] = $validatedData[$field];
        }
    }
    session(['pending_customer_files' => $pendingFiles]);

    return redirect()->back()->withInput()->with('duplicate_aval', [
        'clientes' => $alertas
    ]);
}

        Customer::create($validatedData);

        return Redirect::route('customers.index')->with('success', 'El cliente se ha creado exitosamente!');
    }

    /**
     * Display the specified resource.
     */
    public function show(Customer $customer)
    {
        return view('customers.show', [
            'customer' => $customer,
        ]);
    }

    /**
     * Show the form for editing the specified resource.
     */
    public function edit(Customer $customer)
    {
        return view('customers.edit', [
            'customer' => $customer
        ]);
    }

    /**
     * Update the specified resource in storage.
     */
    public function update(Request $request, Customer $customer)
    {
        $rules = [

            'tit_name' => 'required|string|max:50',
            'tit_email' => 'required|email|max:50',
            'tit_phone' => 'required|string|max:15|unique:customers,tit_phone,' . $customer->id,
            'tit_status' => 'required|string|max:15',
            'tit_address' => 'required|string',
            'tit_photo' => 'file|mimes:jpeg,png,jpg,gif,pdf,webp',
            'tit_photo_ine_f' => 'file|mimes:jpeg,png,jpg,gif,pdf,webp',
            'tit_photo_ine_b' => 'file|mimes:jpeg,png,jpg,gif,pdf,webp',
            'tit_facebook' => 'required|string',
            'tit_photo_home' => 'file|mimes:jpeg,png,jpg,gif,pdf,webp',
            'tit_link_location' => 'max:999',
            'tit_photo_proof_address' => 'file|mimes:jpeg,png,jpg,gif,pdf,webp',
            'tit_work' => 'max:999',
            'tit_city' => 'required|string',
            'alternate_phone' => 'nullable|string|max:15',
            'position' => 'nullable|string|max:255',
            'monthly_income' => 'nullable|numeric',
            'income_receipt_path' => 'nullable|file|mimes:jpeg,png,jpg,gif,pdf,webp',

            'ref1_name' => 'max:50',
            'ref1_phone' => 'max:15',
            'ref1_address' => 'max:999',

            'ref2_name' => 'max:50',
            'ref2_phone' => 'max:15',
            'ref2_address' => 'max:999',
            
            'ref3_name' => 'max:50',
            'ref3_phone' => 'max:15',
            'ref3_address' => 'max:999',

            'aval_name' => 'max:50',
            'aval_phone' => 'max:15',
            'aval_address' => 'max:999',
            'aval_photo_ine_f' => 'file|mimes:jpeg,png,jpg,gif,pdf,webp',
            'aval_photo_ine_b' => 'file|mimes:jpeg,png,jpg,gif,pdf,webp',
            'aval_photo_proof_address' => 'file|mimes:jpeg,png,jpg,gif,pdf,webp',



        ];

        $validatedData = $request->validate($rules);

        /**
         * Handle upload image with Storage.
         * Los archivos se procesan primero. Si hay duplicado, los nombres
         * ya guardados se persisten en sesión para no perderlos en el re-envío.
         */

        // Campos de archivo que se manejan
        $fileFields = [
            'tit_photo', 'tit_photo_ine_f', 'tit_photo_ine_b',
            'tit_photo_home', 'tit_photo_proof_address',
            'aval_photo_ine_f', 'aval_photo_ine_b', 'aval_photo_home',
            'income_receipt_path',
        ];

        // 🔥 Si viene confirmación, restaurar archivos ya guardados desde sesión
        if ($request->has('confirm_duplicate')) {
            $savedFiles = session('pending_customer_files_' . $customer->id, []);
            foreach ($fileFields as $field) {
                if (!empty($savedFiles[$field])) {
                    $validatedData[$field] = $savedFiles[$field];
                }
            }
            session()->forget('pending_customer_files_' . $customer->id);

            $customer->update($validatedData);

            return redirect()->route('customers.index')
                ->with('success', 'Cliente actualizado correctamente.');
        }

        // Procesar y guardar los archivos subidos
        foreach ($fileFields as $field) {
            if ($request->hasFile($field)) {
                $validatedData[$field] = $this->storeImage($request->file($field), 'customers');
            }
        }

// 🔎 Verificar duplicado titular y rastreo de aval
$alertas = [];

// 1. Cliente Titular Duplicado
$clienteDuplicado = Customer::where('id', '!=', $customer->id)
    ->where(function ($query) use ($validatedData) {
        if (!empty($validatedData['tit_name'])) {
            $query->orWhere('tit_name', $validatedData['tit_name']);
        }
        if (!empty($validatedData['tit_phone'])) {
            $query->orWhere('tit_phone', $validatedData['tit_phone']);
        }
    })->get();

if ($clienteDuplicado->count() > 0) {
    foreach ($clienteDuplicado as $dup) {
        $campos = [];
        if (!empty($validatedData['tit_name']) && $dup->tit_name === $validatedData['tit_name']) $campos[] = "Nombre Titular";
        if (!empty($validatedData['tit_phone']) && $dup->tit_phone === $validatedData['tit_phone']) $campos[] = "Teléfono Titular";
        if (count($campos) > 0) {
            $alertas[] = [
                'cliente' => $dup->tit_name,
                'campos' => $campos,
                'alerta_tipo' => 'Titular duplicado'
            ];
        }
    }
}

// 2. Rastreo de Aval (Prestanombres) - ¿El titular ha sido aval de alguien?
if (!empty($validatedData['tit_name'])) {
    $fueAval = Customer::where('id', '!=', $customer->id)
        ->where('aval_name', $validatedData['tit_name'])->get();
    foreach ($fueAval as $antAval) {
        $alertas[] = [
            'cliente' => $antAval->tit_name,
            'campos' => ["Fue AVAL de este cliente"],
            'alerta_tipo' => 'Posible Prestanombre'
        ];
    }
}

// 3. Duplicado de Aval actual
if (!empty($validatedData['aval_name']) || !empty($validatedData['aval_phone']) || !empty($validatedData['aval_address'])) {
    $existingAvals = Customer::where('id', '!=', $customer->id)
        ->where(function ($query) use ($validatedData) {
            if (!empty($validatedData['aval_name'])) $query->orWhere('aval_name', $validatedData['aval_name']);
            if (!empty($validatedData['aval_phone'])) $query->orWhere('aval_phone', $validatedData['aval_phone']);
            if (!empty($validatedData['aval_address'])) $query->orWhere('aval_address', $validatedData['aval_address']);
        })->get();

    if ($existingAvals->count() > 0) {
        foreach ($existingAvals as $aval) {
            $camposDuplicados = [];
            if (!empty($validatedData['aval_name']) && $aval->aval_name === $validatedData['aval_name']) $camposDuplicados[] = "Nombre Aval";
            if (!empty($validatedData['aval_phone']) && $aval->aval_phone === $validatedData['aval_phone']) $camposDuplicados[] = "Teléfono Aval";
            if (!empty($validatedData['aval_address']) && $aval->aval_address === $validatedData['aval_address']) $camposDuplicados[] = "Dirección Aval";
            if (count($camposDuplicados) > 0) {
                $alertas[] = [
                    'cliente' => $aval->tit_name,
                    'campos' => $camposDuplicados,
                    'alerta_tipo' => 'Aval duplicado'
                ];
            }
        }
    }
}

if (count($alertas) > 0) {
    // Guardar los nombres de archivo ya procesados en sesión
    $pendingFiles = [];
    foreach ($fileFields as $field) {
        if (!empty($validatedData[$field])) {
            $pendingFiles[$field] = $validatedData[$field];
        }
    }
    session(['pending_customer_files_' . $customer->id => $pendingFiles]);

    return redirect()->back()->withInput()->with('duplicate_aval', [
        'clientes' => $alertas
    ]);
}
        Customer::where('id', $customer->id)->update($validatedData);

        return Redirect::route('customers.index')->with('success', 'La información del cliente se ha actualizado exitosamente!');
    }

    /**
     * Remove the specified resource from storage.
     */
    public function destroy(Customer $customer)
    {
        /**
         * Delete photo if exists.
         */
        if($customer->photo){
            Storage::delete('public/customers/' . $customer->photo);
        }

        Customer::destroy($customer->id);

        return Redirect::route('customers.index')->with('success', 'El cliente se ha eliminado exitosamente!');
    }
        private function storeImage($file, $directory)
    {
        $fileName = hexdec(uniqid()) . '.' . $file->getClientOriginalExtension();
        $filePath = 'public/' . $directory . '/';

        // Guarda la imagen en el almacenamiento público
        $file->storeAs($filePath, $fileName);

        return $fileName; // Retorna el nombre del archivo guardado
    }

    public function checkPhone(Request $request)
    {
        $phone = $request->phone;
        $id = $request->id;

        $exists = Customer::where('tit_phone', $phone)
            ->when($id, function($query) use ($id) {
                return $query->where('id', '!=', $id);
            })
            ->exists();

        return response()->json(['exists' => $exists]);
    }

    public function checkName(Request $request)
    {
        $name = $request->name;
        $id = $request->id;

        $exists = Customer::where('tit_name', $name)
            ->when($id, function($query) use ($id) {
                return $query->where('id', '!=', $id);
            })
            ->exists();

        return response()->json(['exists' => $exists]);
    }
}
