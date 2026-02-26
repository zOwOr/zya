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
            'tit_photo' => 'image|file',
            'tit_photo_ine_f' => 'image|file',
            'tit_photo_ine_b' => 'image|file',
            'tit_facebook' => 'required|string',    
            'tit_photo_home' => 'image|file',
            'tit_link_location' => 'max:999',
            'tit_photo_proof_address' => 'image|file',
            'tit_work' => 'max:999',
            'tit_city' => 'required|string',

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
            'aval_photo_ine_f' => 'image|file',
            'aval_photo_ine_b' => 'image|file',
            'aval_photo_proof_address' => 'image|file',


        ];

        $validatedData = $request->validate($rules);

        /**
         * Handle upload image with Storage.
         */


        if ($request->hasFile('tit_photo')) {
            $validatedData['tit_photo'] = $this->storeImage($request->file('tit_photo'), 'customers');
        }
    
        if ($request->hasFile('tit_photo_ine_f')) {
            $validatedData['tit_photo_ine_f'] = $this->storeImage($request->file('tit_photo_ine_f'), 'customers');
        }
    
        if ($request->hasFile('tit_photo_ine_b')) {
            $validatedData['tit_photo_ine_b'] = $this->storeImage($request->file('tit_photo_ine_b'), 'customers');
        }
    
        if ($request->hasFile('tit_photo_home')) {
            $validatedData['tit_photo_home'] = $this->storeImage($request->file('tit_photo_home'), 'customers');
        }
    
        if ($request->hasFile('tit_photo_proof_address')) {
            $validatedData['tit_photo_proof_address'] = $this->storeImage($request->file('tit_photo_proof_address'), 'customers');
        }
    
        if ($request->hasFile('aval_photo_ine_f')) {
            $validatedData['aval_photo_ine_f'] = $this->storeImage($request->file('aval_photo_ine_f'), 'customers');
        }
    
        if ($request->hasFile('aval_photo_ine_b')) {
            $validatedData['aval_photo_ine_b'] = $this->storeImage($request->file('aval_photo_ine_b'), 'customers');
        }
    
        if ($request->hasFile('aval_photo_home')) {
            $validatedData['aval_photo_home'] = $this->storeImage($request->file('aval_photo_home'), 'customers');
        }

// 🔥 Si viene confirmación, guardar directamente
if ($request->has('confirm_duplicate')) {
    Customer::create($validatedData);

    return redirect()->route('customers.index')
        ->with('success', 'Cliente registrado correctamente.');
}


// 🔎 Verificar duplicado de aval (cualquiera de los campos)
if (
    !empty($validatedData['aval_name']) ||
    !empty($validatedData['aval_phone']) ||
    !empty($validatedData['aval_address'])
) {

    $existingAvals = Customer::where(function ($query) use ($validatedData) {

        if (!empty($validatedData['aval_name'])) {
            $query->orWhere('aval_name', $validatedData['aval_name']);
        }

        if (!empty($validatedData['aval_phone'])) {
            $query->orWhere('aval_phone', $validatedData['aval_phone']);
        }

        if (!empty($validatedData['aval_address'])) {
            $query->orWhere('aval_address', $validatedData['aval_address']);
        }

    })->get();

    if ($existingAvals->count() > 0) {

        $clientesDuplicados = [];

        foreach ($existingAvals as $aval) {

            $camposDuplicados = [];

            if (
                !empty($validatedData['aval_name']) &&
                $aval->aval_name === $validatedData['aval_name']
            ) {
                $camposDuplicados[] = "Nombre";
            }

            if (
                !empty($validatedData['aval_phone']) &&
                $aval->aval_phone === $validatedData['aval_phone']
            ) {
                $camposDuplicados[] = "Teléfono";
            }

            if (
                !empty($validatedData['aval_address']) &&
                $aval->aval_address === $validatedData['aval_address']
            ) {
                $camposDuplicados[] = "Dirección";
            }

            $clientesDuplicados[] = [
                'cliente' => $aval->tit_name,
                'campos' => $camposDuplicados
            ];
        }

        return redirect()->back()->withInput()->with('duplicate_aval', [
            'clientes' => $clientesDuplicados
        ]);
    }
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
            'tit_email' => 'required|email|max:50,'.$customer->id,
            'tit_phone' => 'required|string|max:15|unique:customers,tit_phone, '.$customer->id,
            'tit_status' => 'required|string|max:15',
            'tit_address' => 'required|string',
            'tit_photo' => 'image|file',
            'tit_photo_ine_f' => 'image|file',
            'tit_photo_ine_b' => 'image|file',
            'tit_facebook' => 'required|string',
            'tit_photo_home' => 'image|file',
            'tit_link_location' => 'max:999',
            'tit_photo_proof_address' => 'image|file',
            'tit_work' => 'max:999',
            'tit_city' => 'required|string',

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
            'aval_photo_ine_f' => 'image|file',
            'aval_photo_ine_b' => 'image|file',
            'aval_photo_proof_address' => 'image|file',



        ];

        $validatedData = $request->validate($rules);

        /**
         * Handle upload image with Storage.
         */


        
        if ($request->hasFile('tit_photo')) {
            $validatedData['tit_photo'] = $this->storeImage($request->file('tit_photo'), 'customers');
        }
    
        if ($request->hasFile('tit_photo_ine_f')) {
            $validatedData['tit_photo_ine_f'] = $this->storeImage($request->file('tit_photo_ine_f'), 'customers');
        }
    
        if ($request->hasFile('tit_photo_ine_b')) {
            $validatedData['tit_photo_ine_b'] = $this->storeImage($request->file('tit_photo_ine_b'), 'customers');
        }
    
        if ($request->hasFile('tit_photo_home')) {
            $validatedData['tit_photo_home'] = $this->storeImage($request->file('tit_photo_home'), 'customers');
        }
    
        if ($request->hasFile('tit_photo_proof_address')) {
            $validatedData['tit_photo_proof_address'] = $this->storeImage($request->file('tit_photo_proof_address'), 'customers');
        }
    
        if ($request->hasFile('aval_photo_ine_f')) {
            $validatedData['aval_photo_ine_f'] = $this->storeImage($request->file('aval_photo_ine_f'), 'customers');
        }
    
        if ($request->hasFile('aval_photo_ine_b')) {
            $validatedData['aval_photo_ine_b'] = $this->storeImage($request->file('aval_photo_ine_b'), 'customers');
        }
    
        if ($request->hasFile('aval_photo_home')) {
            $validatedData['aval_photo_home'] = $this->storeImage($request->file('aval_photo_home'), 'customers');
        }

        // Verificar duplicado de aval (excepto el cliente actual)
if (
    !$request->has('confirm_duplicate') &&
    !empty($validatedData['aval_name']) &&
    !empty($validatedData['aval_phone']) &&
    !empty($validatedData['aval_address'])
) {
    $existingAval = Customer::where('id', '!=', $customer->id)
    ->where(function ($query) use ($validatedData) {

        $query->where('aval_name', $validatedData['aval_name'])
              ->orWhere('aval_phone', $validatedData['aval_phone'])
              ->orWhere('aval_address', $validatedData['aval_address']);

    })->first();

    $duplicateData = [];

if ($existingAval) {

    if ($existingAval->aval_name === $validatedData['aval_name']) {
        $duplicateData['Nombre'] = $validatedData['aval_name'];
    }

    if ($existingAval->aval_phone === $validatedData['aval_phone']) {
        $duplicateData['Teléfono'] = $validatedData['aval_phone'];
    }

    if ($existingAval->aval_address === $validatedData['aval_address']) {
        $duplicateData['Dirección'] = $validatedData['aval_address'];
    }

    return redirect()->back()->withInput()->with('duplicate_aval', [
        'cliente' => $existingAval->tit_name,
        'duplicates' => $duplicateData
    ]);
}
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

}
