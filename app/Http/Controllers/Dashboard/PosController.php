<?php

namespace App\Http\Controllers\Dashboard;

use Carbon\Carbon;
use App\Models\Product;
use App\Models\Customer;
use Illuminate\Http\Request;
use App\Http\Controllers\Controller;
use Illuminate\Support\Facades\Redirect;
use Gloudemans\Shoppingcart\Facades\Cart;

class PosController extends Controller
{
    public function index()
    {
        $todayDate = Carbon::now();
        $row = (int) request('row', 10);

        if ($row < 1 || $row > 100) {
            abort(400, 'The per-page parameter must be an integer between 1 and 100.');
        }

        return view('pos.index', [
            'customers' => Customer::all()->sortBy('name'),
            'productItem' => Cart::content(),
            'products' => Product::where('expire_date', '>', $todayDate)->filter(request(['search']))
                ->sortable()
                ->paginate($row)
                ->appends(request()->query()),
        ]);
    }

    public function addCart(Request $request)
    {
        $rules = [
            'id' => 'required|numeric',
            'name' => 'required|string',
            'price' => 'required|numeric',
        ];

        $validatedData = $request->validate($rules);

        Cart::add([
            'id' => $validatedData['id'],
            'name' => $validatedData['name'],
            'qty' => 1,
            'price' => $validatedData['price'],
            'options' => ['size' => 'large']
        ]);

        return Redirect::back()->with('success', 'El carrito se ha actualizado!');
    }

    public function updateCart(Request $request, $rowId)
    {
        $rules = [
            'qty' => 'required|numeric',
        ];

        $validatedData = $request->validate($rules);

        Cart::update($rowId, $validatedData['qty']);

        return Redirect::back()->with('success', 'El carrito se ha actualizado!');
    }

    public function deleteCart(String $rowId)
    {
        Cart::remove($rowId);

        return Redirect::back()->with('success', 'El carrito se ha actualizado!');
    }

    public function createInvoice(Request $request)
    {
        $rules = [
            'customer_id' => 'required'
        ];

        $validatedData = $request->validate($rules);
        $customer = Customer::where('id', $validatedData['customer_id'])->first();
        $content = Cart::content();

    // Verificación del stock para cada producto
        foreach ($content as $item) {
            $product = Product::find($item->id);
            if ($product && $product->stock_quantity < $item->qty) {
                // Si no hay suficiente stock, redirigir con un mensaje de error
                
                return Redirect::back()->with(['warning' => 'No hay suficiente stock para el producto: ' . $product->product_name]);
            }
        }
        // Si todo está bien con el stock, proceder a mostrar la vista de la factura
        return view('pos.create-invoice', [
            'customer' => $customer,
            'content' => $content
        ]);
    }

    public function printInvoice(Request $request)
    {
        $rules = [
            'customer_id' => 'required'
        ];

        $validatedData = $request->validate($rules);
        $customer = Customer::where('id', $validatedData['customer_id'])->first();
        $content = Cart::content();

        return view('pos.print-invoice', [
            'customer' => $customer,
            'content' => $content
        ]);
    }
    public function scanBarcode(Request $request)
    {
        $request->validate([
            'barcode' => 'required|string',
        ]);

        $barcode = $request->input('barcode');

        // Buscar producto por código de barras (ajustar columna si es necesario)
        $product = Product::where('product_code', $barcode)->first();

        if (!$product) {
            return redirect()->back()->with('warning', 'Producto no encontrado.');
        }

        // Añadir producto al carrito
        Cart::add([
            'id' => $product->id,
            'name' => $product->product_name,
            'qty' => 1,
            'price' => $product->selling_price,
            'options' => ['image' => $product->product_image]
        ]);

        return redirect()->back()->with('success', 'Producto añadido correctamente.');
    }

    public function addDynamicProduct(Request $request)
    {
        $validatedData = $request->validate([
            'product_name' => 'required|string|max:255',
            'selling_price' => 'required|numeric',
            'brand' => 'nullable|string|max:255',
            'model' => 'nullable|string|max:255',
            'imei' => 'nullable|string|max:255',
            'category_status' => 'required|string|max:255',
            'warranty_time' => 'nullable|string|max:255',
            'stock_quantity' => 'required|numeric|min:1',
            'observations' => 'nullable|string',
        ]);

        // Asegurar que hay al menos una categoría y un proveedor para evitar errores Constraint
        $category = \App\Models\Category::first();
        $supplier = \App\Models\Supplier::first();

        $product = Product::create([
            'product_name' => $validatedData['product_name'],
            'selling_price' => $validatedData['selling_price'],
            'buying_price' => 0,
            'stock_quantity' => $validatedData['stock_quantity'],
            'product_code' => 'DYN-' . time(),
            'brand' => $validatedData['brand'],
            'model' => $validatedData['model'],
            'imei' => $validatedData['imei'],
            'category_status' => $validatedData['category_status'],
            'warranty_time' => $validatedData['warranty_time'],
            'observations' => $validatedData['observations'],
            'category_id' => $category ? $category->id : 1,
            'supplier_id' => $supplier ? $supplier->id : 1,
        ]);

        Cart::add([
            'id' => $product->id,
            'name' => $product->product_name,
            'qty' => 1,
            'price' => $product->selling_price,
            'options' => [
                'image' => null,
                'brand' => $product->brand,
                'model' => $product->model,
                'serial' => $product->imei,
                'status' => $product->category_status,
                'warranty' => $product->warranty_time,
                'observations' => $product->observations,
            ]
        ]);

        return redirect()->back()->with('success', 'Producto personalizado añadido al carrito correctamente.');
    }
}
